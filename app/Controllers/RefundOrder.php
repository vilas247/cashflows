<?php
/**
 * This file is part of the 247Commerce BigCommerce CASHFLOW App.
 *
 * ©247 Commerce Limited <info@247commerce.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Controllers;

/**
 * Class RefundOrder
 *
 * Represents a CASHFLOW Refunds
 */
class RefundOrder extends BaseController
{
	/**
	 * Index - default page
	 *
	 */
	public function index($authKey)
	{
		helper('settingsviews');
		$clientDetails = \SettingsViews::getClientDetails();
		$data = array();
		if(!empty($clientDetails) && !empty($authKey)){
			$data['clientDetails'] = $clientDetails;
			$invoice_id = json_decode(base64_decode($authKey));
			if(!empty($invoice_id)){
				$db = \Config\Database::connect();
				$builder = $db->table('order_payment_details opd');
				$builder->select('*');
				$builder->join('order_details od', 'opd.order_id = od.invoice_id','left');
				$builder->where('opd.order_id', $invoice_id);
				$query = $builder->get();
				$result = $query->getResultArray();
				$data['orderDetails'] = array();
				if(count($result)>0){
					$data['orderDetails'] = $result[0];
				}
				
				$builder = $db->table('order_refund');
				$builder->select('*');
				$builder->where('invoice_id', $invoice_id);
				$query = $builder->get();
				$ref_result = $query->getResultArray();
				$data['ref_result'] = array();
				if(count($ref_result) > 0){
					$data['ref_result'] = $ref_result;
				}
				
				return view('refundOrder',$data);
			}else{
				return redirect()->to('/');
			}
		}else{
			return redirect()->to('/');
		}
	}
	
	/**
	 * RefundOrder - page
	 *
	 */
	public function proceedRefund()
	{
		if($this->request->getMethod() == "post"){
			$invoice_id = $this->request->getVar('invoice_id');
			$refund_amount = $this->request->getVar('refund_amount');
			if(!empty($invoice_id) && ($refund_amount > 0)){
				$db = \Config\Database::connect();
				$builder = $db->table('order_payment_details');
				$builder->select('*');
				$builder->where('order_id', $invoice_id);
				$query = $builder->get();
				$result_refund = $query->getResultArray();
				if(isset($result_refund[0]) && ($result_refund[0]['status'] == "CONFIRMED")) {
					$payment_details = json_decode(str_replace("\\","",$result_refund[0]['initial_api_response']),true);
					//print_r($payment_details);exit;
					if(isset($payment_details['paymentjobref']) && isset($payment_details['paymentref']) && isset($payment_details['ordernumber'])){
						$status = $this->refunds($payment_details,$refund_amount);
						if($status){
							return redirect()->to('/refundOrder/index/'.base64_encode(json_encode($payment_details['ordernumber'])).'?error=0');
						}else{
							return redirect()->to('/refundOrder/index/'.base64_encode(json_encode($payment_details['ordernumber'])).'?error=1');
						}
					}else{
						return redirect()->to('/');
					}
				}else{
					return redirect()->to('/');
				}
			}else{
				return redirect()->to('/');
			}
		}else{
			return redirect()->to('/');
		}
	}
	
	public function refunds($request,$amount){
		
		$paymentURL = getenv('bigcommerceapp.CASHFLOW_TEST_URL');
		if(getenv('CI_ENVIRONMENT') == "production"){
			$paymentURL = getenv('bigcommerceapp.CASHFLOW_PROD_URL');
		}
		
		$db = \Config\Database::connect();
		$status = false;
		$builder = $db->table('order_payment_details');        
		$builder->select('*');       
		$builder->where('order_id', $request['ordernumber']);
		$query = $builder->get();
		$orderDetails = $query->getResultArray();
		if(count($orderDetails) > 0){
			$orderDetails = $orderDetails[0];
			$builder = $db->table('cashflow_token_validation');        
			$builder->select('*');       
			$builder->where('email_id', $orderDetails['email_id']);
			$builder->where('validation_id', $orderDetails['token_validation_id']);
			$query = $builder->get();
			$result = $query->getResultArray();
			if (count($result) > 0) {
				$clientDetails = $result[0];
				
				/* paymet Request */
				$config_id = $clientDetails['config_id'];
				$api_key = $clientDetails['api_key'];
				
				$refund = '{"amountToRefund": "'.$amount.'", "refundNumber": "'.$request['ordernumber'].'"}';
				$hashed_password = hash('sha512', $api_key.$refund);

				try{
					
					$data = [
						'email_id' => $clientDetails['email_id'],
						'invoice_id' => $request['ordernumber'],
						'refund_status'    => "PENDING",
						'refund_amount'    => $amount,
						'api_request'    => addslashes($refund),
						'token_validation_id'    => $clientDetails['validation_id'],
					];
					$builderinsert = $db->table('order_refund'); 
					$builderinsert->insert($data);
					$r_id = $db->insertID();
					
					$client = \Config\Services::curlrequest();
					$response = $client->setBody($refund)->request('post', $paymentURL.'/api/gateway/payment-jobs/'.$request['paymentjobref'].'/payments/'.$request['paymentref'].'/refunds', [
							'headers' => [
									'configurationId' => $config_id,
									'hash' => $hashed_password,
									'Content-Type' => 'application/json'
							]
					]);
					if (strpos($response->getHeader('content-type'), 'application/json') != false){
						$body = $response->getBody();
						$resp = json_decode($body,true);
						if(isset($resp['data']['status']) && ($resp['data']['status'] == "Completed")){
							$status = true;
							$data = [
								'refund_status' => $resp['data']['status'],
								'api_response' => addslashes($body)
							];
							
							$r_data = [
								'settlement_status' => 'REFUND'
							];
							
							$builderupdate = $db->table('order_payment_details');
							$builderupdate->where('order_id', $request['ordernumber']); 
							$builderupdate->update($r_data);
							
						}else{
							$data = [
								'refund_status' => 'Failed',
								'api_response' => addslashes($body),
							];
						}
						$builderupdate = $db->table('order_refund');
						$builderupdate->where('r_id', $r_id); 
						$builderupdate->update($data);
						if(isset($resp['data']['status']) && ($resp['data']['status'] == "Completed")){
							$statusResponse = $this->updateOrderStatus($clientDetails['email_id'],$r_id,$request['ordernumber'],$clientDetails['validation_id']);
						}
					}
				}catch(\Exception $e){
					print_r($e->getMessage());exit;
				}
			}
		}
		return $status;
	}
	public function testing(){
		$this->updateOrderStatus("bigi@247commerce.co.uk","4","CASHFLOW-1623052575","1");
	}
	public function updateOrderStatus($email_id,$rder_refund_id,$invoice_id,$token_validation_id) {
		
		helper('settingsviews');
		helper('bigcommerceorder');
		$clientDetails = \BigCommerceOrder::getClientDetails($email_id,$token_validation_id);
		if(!empty($clientDetails)){
			$db = \Config\Database::connect();
			
			$order_details = array();
			$builder = $db->table('order_details');        
			$builder->select('*');       
			$builder->where('invoice_id', $invoice_id);
			$query = $builder->get();
			$result_od = $query->getResultArray();
			if (count($result_od) > 0) {
				$order_details = $result_od[0];
			}
			
			$order_refund_details = array();
			$builder = $db->table('order_refund');        
			$builder->select('*');       
			$builder->where('r_id', $rder_refund_id);
			$query = $builder->get();
			$result_or = $query->getResultArray();
			if (count($result_or) > 0) {
				$order_refund_details = $result_or[0];
			}
			
			if(isset($order_details['order_id']) && !empty($order_details['order_id']) && isset($order_refund_details['refund_status']) && ($order_refund_details['refund_status'] == "Completed")){
				$url_u = getenv('bigcommerceapp.STORE_URL').$clientDetails['store_hash'].'/v2/orders/'.$order_details['order_id'];
				$staff_comments = "Payment Number : ".$invoice_id.",Status : Refunded,Refunded Date : ".$order_refund_details['created_date'].",Refunded Amount : ".$order_details['currecy']." ".$order_refund_details['refund_amount'];

				$request_u = array("status_id"=>4,"staff_notes"=>$staff_comments);
				$request_u = json_encode($request_u,true);
				try{
					$client = \Config\Services::curlrequest();
					$response = $client->setBody($request_u)->request('put', $url_u, [
							'headers' => [
									'X-Auth-Token' => $clientDetails['acess_token'],
									'store_hash' => $clientDetails['store_hash'],
									'Accept' => 'application/json',
									'Content-Type' => 'application/json'
							]
					]);
					
					if (strpos($response->getHeader('content-type'), 'application/json') != false){
						$res_u = $response->getBody();
					
						$db = \Config\Database::connect();
						$data = [
							'email_id' => $email_id,
							'type' => "BigCommerce",
							'action'    => "Update Order",
							'api_url'    => addslashes($url_u),
							'api_request' => addslashes($request_u),
							'api_response' => addslashes($res_u),
							'token_validation_id' => $token_validation_id,
						];
						$builderinsert = $db->table('api_log'); 
						$builderinsert->insert($data);
					}
				}catch(\Exception $e){
					log_message('info', 'exception:'.$e->getMessage());
				}
			}
		}
	}
}
