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
 * Class CustomOrderPages
 *
 * Represents a helper class to create Success Order Custom Pages in BigCommerce 
 */
class CustomOrderPages
{		
	/**
	 * create custom Pages order confirmation for globalpay (Paynow) in Bigcommerce store
	 *
	 * @param text| $email_id
	 * @param text| $store_hash
	 * @param text| $acess_token
	 * @param text| $validation_id
	 * @param text| $sellerdb
	 *
	 */
	public static function customOrderConfirmation($url,$header,$email_id,$validation_id,$sellerdb){
		$db = \Config\Database::connect();
		/**
		*	custom webpage creation: /cashflow-order-confirmation
		*/
		$request = array(
					"body"=> "<head>
							<script type=\"text/javascript\">var app_base_url = '".getenv('app.baseURL')."';</script>
							<link rel=\"stylesheet\" href=\"".getenv('app.ASSETSPATH')."/css/order-confirmation.css\">
							<script src=\"".getenv('app.ASSETSPATH')."js/jquery-min.js\"></script>
							<script src=\"".getenv('app.ASSETSPATH')."js/order-confirmation.js\"></script>
							</head>
							<body>
							<h1>Please Wait</h1>
							</body>",
				    "channel_id"=> 1,
				    "has_mobile_version"=> false,
				    "is_customers_only"=> false,
				    "is_homepage"=> false,
				    "is_visible"=> false,
				    "mobile_body"=> "",
				    "name"=> "Cashflow Order Confirmation",
				    "parent_id"=> 0,
				    "search_keywords"=> "",
				    "sort_order"=> 0,
				    "type"=> "raw",
				    "url"=> "/cashflow-order-confirmation"
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
					'action'    => 'Custom Page Creation',
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
							if(isset($res['id'])){
								$data = [
									'email_id' => $email_id,
									'page_bc_id' => $res['id'],
									'api_response' => addslashes(json_encode($res)),
									'token_validation_id' => $validation_id,
								];
								$builderinsert = $db->table('247custompages'); 
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