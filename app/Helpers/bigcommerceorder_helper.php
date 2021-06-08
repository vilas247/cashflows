<?php
/**
 * This file is part of the 247Commerce BigCommerce CASHFLOW App.
 *
 * ©247 Commerce Limited <info@247commerce.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
 /**
 * Class BigCommerceOrder
 *
 * Represents a helper class to create Order in BigCommerce once paymet is success
 */
class BigCommerceOrder
{		

	public static function productOptions($email_id,$productId,$variantId,$token_validation_id){
		$data = array();
		helper('settingsviews');
		$clientDetails = \BigCommerceOrder::getClientDetails($email_id,$token_validation_id);
		if(!empty($clientDetails)){
			$url = getenv('bigcommerceapp.STORE_URL').$clientDetails['store_hash'].'/v3/catalog/products/'.$productId.'/variants';
			try{
				$client = \Config\Services::curlrequest();
				$response = $client->request('get', $url, [
						'headers' => [
								'X-Auth-Token' => $clientDetails['acess_token'],
								'store_hash' => $clientDetails['store_hash'],
								'Accept' => 'application/json',
								'Content-Type' => 'application/json'
						]
				]);
				if (strpos($response->getHeader('content-type'), 'application/json') != false){
					$res = $response->getBody();
				
					$db = \Config\Database::connect();
					$data = [
						'email_id' => $email_id,
						'type' => "BigCommerce",
						'action'    => "Product Options",
						'api_url'    => addslashes($url),
						'api_request' => "",
						'api_response' => addslashes($res),
						'token_validation_id' => $token_validation_id,
					];
					$builderinsert = $db->table('api_log'); 
					$builderinsert->insert($data);
					if(!empty($res)){
						$res = json_decode($res,true);
						if(isset($res['data'])){
							$res = $res['data'];
							if(count($res) > 0){
								foreach($res as $k=>$v){
									if($v['id'] == $variantId){
										$data = $v;
										break;
									}
								}
							}
						}
					}
				}
			}catch(\Exception $e){
				log_message('info', 'exception:'.$e->getMessage());
			}
		}
		return $data;
	}

	public static function deleteCart($email_id,$cart_id,$token_validation_id){
		$res = "";
		
		helper('settingsviews');
		$clientDetails = \BigCommerceOrder::getClientDetails($email_id,$token_validation_id);
		if(!empty($clientDetails)){
			$url = getenv('bigcommerceapp.STORE_URL').$clientDetails['store_hash'].'/v3/carts/'.$cart_id;
			$request = '';
			try{
				$client = \Config\Services::curlrequest();
				$response = $client->request('delete', $url, [
						'headers' => [
								'X-Auth-Token' => $clientDetails['acess_token'],
								'store_hash' => $clientDetails['store_hash'],
								'Accept' => 'application/json',
								'Content-Type' => 'application/json'
						]
				]);
				if (strpos($response->getHeader('content-type'), 'application/json') != false){
					$res = $response->getBody();
				
					$db = \Config\Database::connect();
					$data = [
						'email_id' => $email_id,
						'type' => "BigCommerce",
						'action'    => "Clear Cart",
						'api_url'    => addslashes($url),
						'api_request' => addslashes($request),
						'api_response' => addslashes($res),
						'token_validation_id' => $token_validation_id,
					];
					$builderinsert = $db->table('api_log'); 
					$builderinsert->insert($data);
				}
			}catch(\Exception $e){
				log_message('info', 'exception:'.$e->getMessage());
			}
		}
		return $res;
		
	}

	public static function createOrder($email_id,$request,$invoice_id,$token_validation_id){
		$bigComemrceOrderId = "";
		
		helper('settingsviews');
		$clientDetails = \BigCommerceOrder::getClientDetails($email_id,$token_validation_id);
		if(!empty($clientDetails)){
			$url = getenv('bigcommerceapp.STORE_URL').$clientDetails['store_hash'].'/v2/orders';
			$request = json_encode($request);
			try{
				$client = \Config\Services::curlrequest();
				$response = $client->setBody($request)->request('post', $url, [
						'headers' => [
								'X-Auth-Token' => $clientDetails['acess_token'],
								'store_hash' => $clientDetails['store_hash'],
								'Accept' => 'application/json',
								'Content-Type' => 'application/json'
						]
				]);
				
				if (strpos($response->getHeader('content-type'), 'application/json') != false){
					$res = $response->getBody();
				
					$db = \Config\Database::connect();
					$data = [
						'email_id' => $email_id,
						'type' => "BigCommerce",
						'action'    => "Create Order",
						'api_url'    => addslashes($url),
						'api_request' => addslashes($request),
						'api_response' => addslashes($res),
						'token_validation_id' => $token_validation_id,
					];
					$builderinsert = $db->table('api_log'); 
					$builderinsert->insert($data);
					
					if(!empty($res)){
						$res = json_decode($res,true);
						if(isset($res['id'])){
							
							$data = [
								'email_id' => $email_id,
								'invoice_id' => $invoice_id,
								'order_id'    => $res['id'],
								'bg_customer_id'    => $res['customer_id'],
								'reponse_params' => addslashes(json_encode($res)),
								'total_inc_tax' => $res['total_inc_tax'],
								'total_ex_tax' => $res['total_ex_tax'],
								'currecy' => $res['currency_code'],
								'token_validation_id' => $token_validation_id,
							];
							$builderinsert = $db->table('order_details'); 
							$builderinsert->insert($data);

							$bigComemrceOrderId = $res['id'];
						}
					}
				}
			}catch(\Exception $e){
				log_message('info', 'exception:'.$e->getMessage());
			}
		}

		return $bigComemrceOrderId;
	}

	public static function updateOrderStatus($bigComemrceOrderId,$email_id,$token_validation_id) {
		
		helper('settingsviews');
		$clientDetails = \BigCommerceOrder::getClientDetails($email_id,$token_validation_id);
		if(!empty($clientDetails)){
			$url_u = getenv('bigcommerceapp.STORE_URL').$clientDetails['store_hash'].'/v2/orders/'.$bigComemrceOrderId;
			$request_u = array("status_id"=>11);
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
	
	/**
	 * get BigCommerce Order Data from BigCommerce API
	 * @param text| $order_id
	 * @return order Data from BigCommerce api
	 */
	public static function getBigCommerceOrder($order_id){
		
		$final_data = array();
		$final_data['status'] = false;
		$final_data['data'] = array();
		$final_data['msg'] = '';
		
		$db = \Config\Database::connect();
		$data = array();
		if(!empty($order_id)){
			$request = '';
			helper('settingsviews');
			$builder = $db->table('order_details');        
			$builder->select('*');       
			$builder->where('order_id', $order_id);
			$query = $builder->get();
			$result = $query->getResultArray();
			if (count($result) > 0) {
				$result = $result[0];
				$clientDetails = \BigCommerceOrder::getClientDetails($result['email_id'],$result['token_validation_id']);
				if(!empty($clientDetails)){
					$url = getenv('bigcommerceapp.STORE_URL').$clientDetails['store_hash'].'/v2/orders/'.$order_id;
					try{
						$client = \Config\Services::curlrequest();
						$res = $client->request('get', $url, [
								'headers' => [
										'X-Auth-Token' => $clientDetails['acess_token'],
										'store_hash' => $clientDetails['store_hash'],
										'Accept' => 'application/json',
										'Content-Type' => 'application/json'
								]
						]);
						
						if (strpos($res->getHeader('content-type'), 'application/json') != false){
							$result_T = $res->getBody();
							$response = json_decode($result_T,true);
							if(isset($response['id'])){			
								$final_data['status'] = true;
								$final_data['data'] = $response;
								
								$client = \Config\Services::curlrequest();
								$res_store = $client->request('get', getenv('bigcommerceapp.STORE_URL').$clientDetails['store_hash'].'/v2/store', [
										'headers' => [
												'X-Auth-Token' => $clientDetails['acess_token'],
												'Accept' => 'application/json',
												'Content-Type' => 'application/json'
										]
								]);
								if (strpos($res_store->getHeader('content-type'), 'application/json') != false){
									$res_store = $res_store->getBody();
									$res_store = json_decode($res_store,true);
									if(isset($res_store['secure_url'])){
										$final_data['data']['storeData'] = $res_store;
									}
									$images = array();
									
									$client = \Config\Services::curlrequest();
									$result_P = $client->request('get', getenv('bigcommerceapp.STORE_URL').$clientDetails['store_hash'].'/v2/orders/'.$order_id.'/products', [
											'headers' => [
													'X-Auth-Token' => $clientDetails['acess_token'],
													'Accept' => 'application/json',
													'Content-Type' => 'application/json'
											]
									]);
									if (strpos($result_P->getHeader('content-type'), 'application/json') != false){
										$response_P = $result_P->getBody();
										$response_P = json_decode($response_P,true);
										foreach($response_P as $k=>$v){
											if(isset($v['product_id'])){
												$client = \Config\Services::curlrequest();
												$result_I = $client->request('get', getenv('bigcommerceapp.STORE_URL').$clientDetails['store_hash'].'/v3/catalog/products/'.$v['product_id'].'/images', [
														'headers' => [
																'X-Auth-Token' => $clientDetails['acess_token'],
																'Accept' => 'application/json',
																'Content-Type' => 'application/json'
														]
												]);
												if (strpos($result_I->getHeader('content-type'), 'application/json') != false){
													$response_I = $result_I->getBody();
													$response_I = json_decode($response_I,true);
													if(isset($response_I['data'])){
														foreach($response_I['data'] as $k1=>$v1){
															if($v['product_id'] == $v1['product_id']){
																$b64image = base64_encode(file_get_contents($v1['url_thumbnail']));
																$type = pathinfo($v1['url_thumbnail'], PATHINFO_EXTENSION);
																$response_I['data'][$k1]['encodedImage'] = 'data:image/' . $type . ';base64,' . $b64image;
															}
														}
														$response_P[$k]['productImages'] = $response_I['data'];
													}
												}
											}
										}
										$final_data['data']['productsData'] = $response_P;
									}
								}
							}else{
								$final_data['msg'] = 'No data found';
							}
						}
					}catch(\Exception $e){
						log_message('info', 'exception:'.$e->getMessage());
					}
				}
			}
		}
		
		return $final_data;
	}

	public static function get_client_ip()
	{
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		} else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		} else if (isset($_SERVER['HTTP_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		} else {
			$ipaddress = 'UNKNOWN';
		}

		return $ipaddress;
	}

	public static function getGeoData(){
		$PublicIP = get_client_ip();
		$PublicIP = explode(",",$PublicIP);
		$json     = file_get_contents("http://ipinfo.io/$PublicIP[0]/geo");
		$json     = json_decode($json, true);
		return $json;
	}
	
	/**
	 * getAppClientDetails - Static funtion to get BigCommerce client details.
	 *
	 * @param text| $email_id
	 * @param text| $validation_id
	 *
	 * @return BigCommerce Store URL id & Email
	 */
	public static function getClientDetails($email_id,$validation_id)
	{
		$data = array();
		if(!empty($email_id) && !empty($validation_id)){
			
			$db = \Config\Database::connect();
			$builder = $db->table('cashflow_token_validation');        
			$builder->select('*');       
			$builder->where('email_id', $email_id);
			$builder->where('validation_id', $validation_id);
			$query = $builder->get();
			$result = $query->getResultArray();
			if (count($result) > 0) {
				$data = $result[0];
			}
		}
		return $data;	
	}
}