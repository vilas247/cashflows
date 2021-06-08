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
 * Class SettleOrder
 *
 * Represents a CASHFLOW Settlements
 */
class SettleOrder extends BaseController
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
				$builder->where('refund_status', "REFUND");
				$query = $builder->get();
				$ref_result = $query->getResultArray();
				$data['ref_result'] = array();
				if(count($ref_result) > 0){
					$data['ref_result'] = $ref_result;
				}
				
				return view('settleOrder',$data);
			}else{
				return redirect()->to('/');
			}
		}else{
			return redirect()->to('/');
		}
	}
	
	/**
	 * SettleOrder - page
	 *
	 */
	public function proceedSettle()
	{
		if($this->request->getMethod() == "post"){
			$invoice_id = $this->request->getVar('invoice_id');
			if(!empty($invoice_id)){
				$db = \Config\Database::connect();
				$builder = $db->table('order_payment_details');
				$builder->select('*');
				$builder->where('order_id', $invoice_id);
				$query = $builder->get();
				$result_refund = $query->getResultArray();
				if(isset($result_refund[0]) && ($result_refund[0]['type'] == "AUTH") && ($result_refund[0]['settlement_status'] != "Completed")) {
					$payment_details = json_decode(str_replace("\\","",$result_refund[0]['initial_api_response']),true);
					//print_r($payment_details);exit;
					if(isset($payment_details['paymentjobref']) && isset($payment_details['paymentref']) && isset($payment_details['ordernumber'])){
						$status = $this->captureFunds($payment_details);
						if($status){
							return redirect()->to('/settleOrder/index/'.base64_encode(json_encode($payment_details['ordernumber'])).'?error=0');
						}else{
							return redirect()->to('/settleOrder/index/'.base64_encode(json_encode($payment_details['ordernumber'])).'?error=1');
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
	
	public function captureFunds($request){
		
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
				$payment_option = $clientDetails['payment_option'];
				
				/* paymet Request */
				$config_id = $clientDetails['config_id'];
				$api_key = $clientDetails['api_key'];
				
				$capture = '{"amountToCapture": "'.$orderDetails['total_amount'].'", "isFinalCapture": "true"}';
				$hashed_password = hash('sha512', $api_key.$capture);
				try{
					
					$client = \Config\Services::curlrequest();
					$url = $paymentURL.'/api/gateway/payment-jobs/'.$request['paymentjobref'].'/payments/'.$request['paymentref'].'/captures';
					$response = $client->setBody($capture)->request('post',$url , [
							'headers' => [
									'configurationId' => $config_id,
									'hash' => $hashed_password,
									'Content-Type' => 'application/json'
							]
					]);
					if (strpos($response->getHeader('content-type'), 'application/json') != false){
						$body = $response->getBody();
						$db = \Config\Database::connect();
						$data = [
							'email_id' => $orderDetails['email_id'],
							'type' => "Cashflow",
							'action'    => "Settlement",
							'api_url'    => addslashes($url),
							'api_request' => addslashes($capture),
							'api_response' => addslashes($body),
							'token_validation_id' => $orderDetails['token_validation_id'],
						];
						$builderinsert = $db->table('api_log'); 
						$builderinsert->insert($data);
						
						$resp = json_decode($body,true);
						if(isset($resp['data']['status']) && ($resp['data']['status'] == "Completed")){
							$status = true;;
							$data = [
								'status' => "CONFIRMED",
								'settlement_status' => $resp['data']['status'],
								'api_response' => addslashes(json_encode($request,true)),
								'capture_api_reponse' => addslashes($body),
								'amount_paid' => $resp['data']['amountToCapture']
							];
						}else{
							$data = [
								'settlement_status' => 'Pending',
								'api_response' => addslashes(json_encode($request,true)),
								'capture_api_reponse' => addslashes($body)
							];
						}
						$builderupdate = $db->table('order_payment_details');
						$builderupdate->where('order_id', $request['ordernumber']); 
						$builderupdate->update($data);
					}
				}catch(\Exception $e){
				}
			}
		}
		return $status;
	}
}
