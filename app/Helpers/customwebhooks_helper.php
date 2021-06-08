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
 * Class CustomWebhooks
 *
 * Represents a helper class to get updates from BigCommerce 
 */
class CustomWebhooks
{		
	/**
	 * create custom Pages order confirmation for globalpay (Paynow) in Bigcommerce store
	 *
	 * @param text| $email_id
	 * @param text| $validation_id
	 * @param text| $sellerdb
	 *
	 */
	public static function createCustomWebhooks($url,$header,$email_id,$validation_id,$sellerdb){
		$db = \Config\Database::connect();
		$webhooks = array(
					array(
						"id"=>1,
						"scope"=>"store/order/statusUpdated",
						"destination"=>getenv('app.baseURL')."webhooks/index/".$email_id."/".base64_encode(json_encode($validation_id,true))
					)
				);
		foreach($webhooks as $k=>$v){
			$request = array(
						"scope"=>$v['scope'],
						"destination"=>$v['destination'],
						"is_active"=>true
					);
			$request = json_encode($request,true);
			try{
				/*$client = \Config\Services::curlrequest();
				$response = $client->setBody($request)->request('POST', $url, [
						'headers' => $header
				]);
				if (strpos($response->getHeader('content-type'), 'application/json') != false){
					$res = $response->getBody();
					$response = json_decode($res,true);*/
					
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
					curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					
					$res = curl_exec($ch);
					curl_close($ch);
				
					$data = [
						'email_id' => $email_id,
						'type' => 'BigCommerce',
						'action'    => 'Webhooks',
						'api_url'    => addslashes($url),
						'api_request' => addslashes($request),
						'api_response' => addslashes($res),
						'token_validation_id' => $validation_id,
					];
					$builderinsert = $db->table('api_log'); 
					$builderinsert->insert($data);
					if(!empty($res)){
						$check_errors = json_decode($res);
						if(isset($check_errors->errors)){
						}else{
							if(json_last_error() === 0){
								$res = json_decode($res,true);
								if(isset($res['data']['id'])){
									$data = [
										'email_id' => $email_id,
										'webhook_bc_id' => $res['data']['id'],
										'scope' => $res['data']['scope'],
										'destination' => $res['data']['destination'],
										'api_response' => addslashes(json_encode($res)),
										'token_validation_id' => $validation_id,
									];
									$builderinsert = $db->table('247webhooks'); 
									$builderinsert->insert($data);
								}
							}
						}
					}
				//}
			}catch(\Exception $e){
				log_message('info', 'exception:'.$e->getMessage());
			}
		}
	}
}