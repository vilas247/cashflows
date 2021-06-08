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
 * Class Webhooks
 *
 * Represents a CASHFLOW webhooks related function to BigCommerce
 * update of order changes.
 */
class Webhooks extends BaseController
{
	/**
	 * Index - default home page for Bigcommerce Webhooks
	 * and valid segments.
	 *
	 */
	public function index($bc_email_id,$key)
	{
		helper('settingsviews');
		$data = file_get_contents('php://input');
		//$data = '{"created_at":1623051110,"store_id":"1000941740","producer":"stores/v6q95r5n91","scope":"store/order/statusUpdated","hash":"8ff00a08f931f2fd9bc8d8b55686775469d63a43","data":{"type":"order","id":423,"status":{"previous_status_id":11,"new_status_id":2}}}';
		log_message('info', 'webhooksData:'.$data);
		if(!empty($data)){
			$check_errors = json_decode($data);
			if(isset($check_errors->errors)){
			}else{
				if(json_last_error() === 0){
					$data = json_decode($data,true);
					$order_data = $data['data'];
					if(isset($order_data['id']) && isset($order_data['status']) && isset($order_data['status']['new_status_id'])){
						$order_id = $order_data['id'];
						$email_id = $bc_email_id;
						$validation_id = json_decode(base64_decode($key),true);
						$db = \Config\Database::connect();
						$builder = $db->table('cashflow_token_validation');  
						$builder->select('*');       
						$builder->where('email_id', $email_id);
						$builder->where('validation_id', $validation_id);
						$query = $builder->get();
						$result = $query->getResultArray();
						if (count($result) > 0) {
							$result = $result[0];
							$acess_token = $result['acess_token'];
							$store_hash = $result['store_hash'];
							
							$builder = $db->table('order_details');  
							$builder->select('*');       
							$builder->where('order_id', $order_id);
							$query = $builder->get();
							$result_order_det = $query->getResultArray();
							if(isset($result_order_det[0])){
								$result_order_det = $result_order_det[0];
								if($order_data['status']['new_status_id'] == "2"){
									$this->proceedSettle($result_order_det['invoice_id']);
								}
							}
						}
					}
				}
			}
			
		}
	}
	
	/**
	 * SettleOrder - page
	 *
	 */
	public function proceedSettle($invoice_id)
	{
		if(!empty($invoice_id)){
			if(!empty($invoice_id)){
				$db = \Config\Database::connect();
				$builder = $db->table('order_payment_details');
				$builder->select('*');
				$builder->where('order_id', $invoice_id);
				$query = $builder->get();
				$result_refund = $query->getResultArray();
				if(isset($result_refund[0]) && ($result_refund[0]['type'] == "AUTH") && ($result_refund[0]['settlement_status'] != "Completed")) {
					$payment_details = json_decode(str_replace("\\","",$result_refund[0]['initial_api_response']),true);
					if(isset($payment_details['paymentjobref']) && isset($payment_details['paymentref']) && isset($payment_details['ordernumber'])){
						$status = $this->captureFunds($payment_details);
					}
				}
			}
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
					log_message('info', 'Settlement Failed:'.$e->getMessage());
				}
			}
		}
		return $status;
	}
}

//status ids
/*"1"=>Pending
"2"=>Shipped
"3"=>Partially Shipped
"4" selected="true"=>Refunded
"5"=>Cancelled
"6"=>Declined
"7"=>Awaiting Payment
"8"=>Awaiting Pickup
"9"=>Awaiting Shipment
"10"=>Completed
"11"=>Awaiting Fulfillment
"12"=>Manual Verification Required
"13"=>Disputed
"14"=>Partially Refunded*/