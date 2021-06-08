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
 * Class SettingsViews
 *
 * Represents a helper class to connect CASHFLOW & BigCommerce 
 * connector and launch configuration.
 */
class SettingsViews
{
	/**
	 * xmltoArray - Static funtion to convert xml to array.
	 *
	 * @param string| $xmlObject
	 *
	 * @return array
	 */
	public static function xmltoArray ( $xmlObject, $out = array () ) {
		helper('settingsviews');
		foreach ( (array) $xmlObject as $index => $node )
			$out[$index] = ( is_object ( $node ) ) ? \SettingsViews::xmltoArray ( $node ) : $node;

		return $out;
	}
	
	/**
	 * storeTokenData - Static funtion to create BigCommerce details.
	 *
	 * @param array| $response
	 *
	 * @return Validation id & Email
	 */
	public static function storeTokenData($response)
	{		
		$email = '';
		$accessToken = '';
		$storeHash = '';
		helper('settingsviews');
		if(isset($response['user']['email'])){
			$email = $response['user']['email'];
		}
		if(isset($response['access_token'])){
			$accessToken = $response['access_token'];
		}
		if(isset($response['context'])){
			$storeHash = str_replace("stores/","",$response['context']);
		}
		if(!empty($email) && !empty($accessToken) && !empty($storeHash)){
			$db = \Config\Database::connect();
			$builder = $db->table('cashflow_token_validation');        
			$builder->select('*');       
			$builder->where('email_id', $email);
			$builder->where('store_hash', $storeHash);
			$query = $builder->get();
			$result = $query->getResultArray();
			if (count($result) > 0) {

				$data = [
						'acess_token' => $accessToken,
						'store_hash' => $storeHash
					];
				$builderupdate = $db->table('cashflow_token_validation'); 
				$builderupdate->where('email_id', $email); 
				$builderupdate->where('store_hash', $storeHash); 
				$builderupdate->update($data);
				
				\SettingsViews::createCustomPages($accessToken,$storeHash,$email,$result[0]['validation_id'],$result[0]['sellerdb']);
				
				$responseRedirect = array();
				$responseRedirect['id'] = $result[0]['validation_id'];
				$responseRedirect['email'] = $email;					
				return $responseRedirect;								
			}else{
				$sellerdb = '247c'.strtotime(date('y-m-d h:m:s'));
				$data = [
					'acess_token' => $accessToken,
					'store_hash' => $storeHash,
					'email_id'    => $email,
					'sellerdb'    => $sellerdb
				];
				$builderinsert = $db->table('cashflow_token_validation'); 
				$builderinsert->insert($data);
				try{
					\SettingsViews::createCustomPages($accessToken,$storeHash,$email,$db->insertID(),$sellerdb);
				}catch(\Exception $e){
					log_message('info', 'exception:'.$e->getMessage());
				}
				
				$responseRedirect = array();
				$responseRedirect['id'] = $db->insertID();
				$responseRedirect['email'] = $email;
				return $responseRedirect;		
			}
		}
	}
	
	/**
	 * create custom Pages and scripts in Bigcommerce store
	 *
	 */
	public static function createCustomPages($acess_token,$store_hash,$email_id,$validation_id,$sellerdb){
		helper('settingsviews');
		$clientDetails = \SettingsViews::getClientDetails();
		if(!empty($acess_token) && !empty($store_hash) && !empty($email_id) && !empty($validation_id) && !empty($sellerdb)){
			
			$url = getenv('bigcommerceapp.STORE_URL').$store_hash.'/v2/pages';
			/*$header = [
						'X-Auth-Token' => $acess_token,
						'Accept' => 'application/json',
						'Content-Type' => 'application/json'
				];*/
			$header = array(
						'X-Auth-Token:'.$acess_token,
						'Accept:application/json',
						'Content-Type:application/json'
				);
			helper('customorderpages');
			\CustomOrderPages::customOrderConfirmation($url,$header,$email_id,$validation_id,$sellerdb);
			helper('customwebhooks');
			$urlw = getenv('bigcommerceapp.STORE_URL').$store_hash.'/v3/hooks';
			\CustomWebhooks::createCustomWebhooks($urlw,$header,$email_id,$validation_id,$sellerdb);
		}
	}
	
	/**
	 * getStoreDetails - Static funtion to get BigCommerce store Url & email.
	 *
	 * @param text| $acessToken
	 * @param text| $storeHash
	 *
	 * @return BigCommerce Store URL id & Email
	 */
	public static function getStoreDetails($storeHash,$acessToken)
	{		
		$client = \Config\Services::curlrequest();
		$response = $client->request('get', getenv('bigcommerceapp.STORE_URL').$storeHash.'/v2/store', [
				'headers' => [
						'X-Auth-Token' => $acessToken,
						'Accept' => 'application/json',
						'Content-Type' => 'application/json'
				]
		]);
		if (strpos($response->getHeader('content-type'), 'application/json') != false){
			$body = $response->getBody();
			$res = json_decode($body,true);
			$responseArray = array();
			$responseArray['url'] = $res['secure_url'];
			$responseArray['email'] = $res['admin_email'];
			return $responseArray;		
		}
			
	}
	
	/**
	 * getAppClientDetails - Static funtion to get BigCommerce client details.
	 *
	 * @param text| $acessToken
	 * @param text| $storeHash
	 *
	 * @return BigCommerce Store URL id & Email
	 */
	public static function getClientDetails()
	{		
		helper('settingsviews');
		$session = session();
		$email_id = $session->get('email_id');
		$validation_id = $session->get('validation_id');
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
	
	/**
	 * getStaticPageDetails - Static funtion to get BigCommerce client details.
	 *
	 * @param text| $acessToken
	 * @param text| $storeHash
	 *
	 * @return BigCommerce Store URL id & Email
	 */
	public static function getStaicPagesDetails()
	{		
		$session = session();
		$email_id = $session->get('email_id');
		$validation_id = $session->get('validation_id');
		$data = array();
		if(!empty($email_id) && !empty($validation_id)){
			
			$db = \Config\Database::connect();
			$builder = $db->table('247custompages');        
			$builder->select('*');       
			$builder->where('email_id', $email_id);
			$builder->where('token_validation_id', $validation_id);
			$query = $builder->get();
			$result = $query->getResultArray();
			if (count($result) > 0) {
				$data = $result;
			}
		}
		return $data;	
	}
	
	/**
	 * getAppClientDetails - Static funtion to get BigCommerce client details.
	 *
	 * @param text| $acessToken
	 * @param text| $storeHash
	 *
	 * @return BigCommerce Store URL id & Email
	 */
	public static function validateClientDetails()
	{		
		helper('settingsviews');
		$session = session();
		$email_id = $session->get('email_id');
		$validation_id = $session->get('validation_id');
		$status = false;
		if(!empty($email_id) && !empty($validation_id)){
			
			$db = \Config\Database::connect();
			$builder = $db->table('cashflow_token_validation');        
			$builder->select('*');       
			$builder->where('email_id', $email_id);
			$builder->where('validation_id', $validation_id);
			$query = $builder->get();
			$result = $query->getResultArray();
			if (count($result) > 0) {
				$result = $result[0];
				if(!empty($result['config_id']) && !empty($result['api_key'])){
					$status = true;
				}
			}
		}
		return $status;	
	}
	
	/**
	 * uninstall custom Pages and scripts in Bigcommerce store
	 */
	public static function unInstallCustomPages($clientDetails){
		helper('settingsviews');
		if(!empty($clientDetails)){
			$email_id = $clientDetails['email_id'];
			$validation_id = $clientDetails['validation_id'];
			$sellerdb = $clientDetails['sellerdb'];
			$store_hash = $clientDetails['store_hash'];
			$acess_token = $clientDetails['acess_token'];
			try{
				\SettingsViews::deleteScripts($sellerdb,$acess_token,$store_hash,$email_id,$validation_id);
			}catch(\Exception $e){
				log_message('info', 'exception:'.$e->getMessage());
			}
			try{
				\SettingsViews::deleteCustomPages($sellerdb,$acess_token,$store_hash,$email_id,$validation_id);
			}catch(\Exception $e){
				log_message('info', 'exception:'.$e->getMessage());
			}
			try{
				\SettingsViews::deleteCustomWebhooks($sellerdb,$acess_token,$store_hash,$email_id,$validation_id);
			}catch(\Exception $e){
				log_message('info', 'exception:'.$e->getMessage());
			}
			$db = \Config\Database::connect();
			$data = [
						'is_enable' => 0,
						'config_id' => '',
						'api_key' => ''
					];
			$builderupdate = $db->table('cashflow_token_validation'); 
			$builderupdate->where('email_id', $email_id);
			$builderupdate->where('validation_id', $validation_id);
			$builderupdate->update($data);
		}
	}
	/**
	 * delete scripts in Bigcommerce store
	 */
	public static function deleteScripts($sellerdb,$acess_token,$store_hash,$email_id,$validation_id){
		$db = \Config\Database::connect();
		$builder = $db->table('cashflow_scripts');        
		$builder->select('*');       
		$builder->where('script_email_id', $email_id);
		$builder->where('token_validation_id', $validation_id);
		$query = $builder->get();
		$result = $query->getResultArray();
		if (count($result) > 0) {
			foreach($result as $k=>$v){
				$header = array(
					"X-Auth-Client: ".$acess_token,
					"X-Auth-Token: ".$acess_token,
					"Accept: application/json",
					"Content-Type: application/json"
				);
				$request = '';
				$url = getenv('bigcommerceapp.STORE_URL').$store_hash.'/v3/content/scripts/'.$v['script_code'];
				try{
					$client = \Config\Services::curlrequest();
					$response = $client->request('delete', $url, [
							'headers' => [
									'X-Auth-Token' => $acess_token,
									'Accept' => 'application/json',
									'Content-Type' => 'application/json'
							]
					]);
					if (strpos($response->getHeader('content-type'), 'text/html') != false){
						$body = $response->getBody();
						$response = json_decode($body,true);
						
						$data = [
							'email_id' => $email_id,
							'type' => 'BigCommerce',
							'action'    => 'script_tag_deletion',
							'api_url'    => addslashes($url),
							'api_request' => addslashes($request),
							'api_response' => addslashes($body),
							'token_validation_id' => $validation_id,
						];
						$builderinsert = $db->table('api_log'); 
						$builderinsert->insert($data);
						
					}
				}catch(\Exception $e){
					log_message('info', 'exception:'.$e->getMessage());
				}
				$builderdelete = $db->table('cashflow_scripts'); 
				$builderdelete->delete(['script_id' => $v['script_id']]);
			}
		}
	}
	
	/**
	 * delete webhooks in Bigcommerce store
	 */
	public static function deleteCustomWebhooks($sellerdb,$acess_token,$store_hash,$email_id,$validation_id){
		$db = \Config\Database::connect();
		$builder = $db->table('247webhooks');        
		$builder->select('*');       
		$builder->where('email_id', $email_id);
		$builder->where('token_validation_id', $validation_id);
		$query = $builder->get();
		$result = $query->getResultArray();
		if (count($result) > 0) {
			foreach($result as $k=>$v){
				$header = array(
					"X-Auth-Client: ".$acess_token,
					"X-Auth-Token: ".$acess_token,
					"Accept: application/json",
					"Content-Type: application/json"
				);
				$request = '';
				$url = getenv('bigcommerceapp.STORE_URL').$store_hash.'/v3/hooks/'.$v['webhook_bc_id'];
				try{
					$client = \Config\Services::curlrequest();
					$response = $client->request('delete', $url, [
							'headers' => [
									'X-Auth-Token' => $acess_token,
									'Accept' => 'application/json',
									'Content-Type' => 'application/json'
							]
					]);
					if (strpos($response->getHeader('content-type'), 'text/html') != false){
						$body = $response->getBody();
						$response = json_decode($body,true);
						
						$data = [
							'email_id' => $email_id,
							'type' => 'BigCommerce',
							'action'    => 'Webhooks',
							'api_url'    => addslashes($url),
							'api_request' => addslashes($request),
							'api_response' => addslashes($body),
							'token_validation_id' => $validation_id,
						];
						$builderinsert = $db->table('api_log'); 
						$builderinsert->insert($data);
						
					}
				}catch(\Exception $e){
					log_message('info', 'exception:'.$e->getMessage());
				}
				$builderdelete = $db->table('247webhooks'); 
				$builderdelete->delete(['id' => $v['id']]);
			}
		}
	}
	
	/**
	 * delete custom pages in Bigcommerce store
	 */
	public static function deleteCustomPages($sellerdb,$acess_token,$store_hash,$email_id,$validation_id){
		
		$db = \Config\Database::connect();
		$builder = $db->table('247custompages');        
		$builder->select('*');       
		$builder->where('email_id', $email_id);
		$builder->where('token_validation_id', $validation_id);
		$query = $builder->get();
		$result = $query->getResultArray();
		
		if (count($result) > 0) {
			foreach($result as $k=>$v){
				$header = array(
					"X-Auth-Token: ".$acess_token,
					"Accept: application/json",
					"Content-Type: application/json"
				);
				$request = '';
				$url = getenv('bigcommerceapp.STORE_URL').$store_hash.'/v2/pages/'.$v['page_bc_id'];
				
				try{
					$client = \Config\Services::curlrequest();
					$response = $client->request('delete', $url, [
							'headers' => [
									'X-Auth-Token' => $acess_token,
									'Accept' => 'application/json',
									'Content-Type' => 'application/json'
							]
					]);
					if (strpos($response->getHeader('content-type'), 'application/json') != false){
						$body = $response->getBody();
						$response = json_decode($body,true);
						$data = [
							'email_id' => $email_id,
							'type' => 'BigCommerce',
							'action'    => 'Custom Page Deletion',
							'api_url'    => addslashes($url),
							'api_request' => addslashes($request),
							'api_response' => addslashes($body),
							'token_validation_id' => $validation_id,
						];
						$builderinsert = $db->table('api_log'); 
						$builderinsert->insert($data);
						
						if(empty($body)){
							
							$builderdelete = $db->table('247custompages'); 
							$builderdelete->delete(['id' => $v['id']]);
						}
					}
				}catch(\Exception $e){
					log_message('info', 'exception:'.$e->getMessage());
				}
			}
		}
	}
	
	/**
	 * enable Payment in Bigcommerce store
	 *
	 */
	public static function enablePayment(){
		$db = \Config\Database::connect();
		helper('settingsviews');
		$clientDetails = \SettingsViews::getClientDetails();
		if(!empty($clientDetails)){
			$email_id = $clientDetails['email_id'];
			$validation_id = $clientDetails['validation_id'];
			$sellerdb = $clientDetails['sellerdb'];
			$store_hash = $clientDetails['store_hash'];
			$acess_token = $clientDetails['acess_token'];
			
			$url = getenv('bigcommerceapp.STORE_URL').$store_hash.'/v2/pages';
			$header = array(
				"X-Auth-Token: ".$acess_token,
				"Accept: application/json",
				"Content-Type: application/json"
			);
			try{
				$res = \SettingsViews::injectPaymentScripts($sellerdb,$acess_token,$store_hash,$email_id,$validation_id);
				if($res == "1"){
					$data = [
							'is_enable' => 1
						];
					$builderupdate = $db->table('cashflow_token_validation'); 
					$builderupdate->where('email_id', $email_id); 
					$builderupdate->update($data);
				}
				helper('custompaymentscript');
				\CustomPaymentScript::createPaymentScript($sellerdb,$email_id,$validation_id);
			}catch(\Exception $e){
				log_message('info', 'exception:'.$e->getMessage());
			}
		}
	}
	public static function injectPaymentScripts($sellerdb,$acess_token,$store_hash,$email_id,$validation_id){
		$db = \Config\Database::connect();
		$url = array();
		$rStatus = 0;
		$url[] = getenv('bigcommerceapp.JS_SDK');
		$url[] = getenv('app.ASSETSPATH').$sellerdb.'/custom_script.js';
		foreach($url as $k=>$v) {
			$header = array(
				"X-Auth-Client: ".$acess_token,
				"X-Auth-Token: ".$acess_token,
				"Accept: application/json",
				"Content-Type: application/json"
			);
			$location = 'head';
			$cstom_url = getenv('app.ASSETSPATH').$sellerdb.'/custom_script.js';
			if($v == $cstom_url){	
				$location = 'footer';
			}
			$request = '{
			  "name": "CashFlowApp",
			  "description": "Cashflow files",
			  "html": "<script src=\"'.$v.'\"></script>",
			  "auto_uninstall": true,
			  "load_method": "default",
			  "location": "'.$location.'",
			  "visibility": "checkout",
			  "kind": "script_tag",
			  "consent_category": "essential"
			}';
			
			$url = getenv('bigcommerceapp.STORE_URL').$store_hash.'/v3/content/scripts';
			try{
				$client = \Config\Services::curlrequest();
				$response = $client->setBody($request)->request('post', $url, [
						'headers' => [
								'X-Auth-Token' => $acess_token,
								'Accept' => 'application/json',
								'Content-Type' => 'application/json'
						]
				]);
				if (strpos($response->getHeader('content-type'), 'application/json') != false){
					$body = $response->getBody();
					$response = json_decode($body,true);
					$data = [
						'email_id' => $email_id,
						'type' => 'BigCommerce',
						'action'    => 'script_tag_injection',
						'api_url'    => addslashes($url),
						'api_request' => addslashes($request),
						'api_response' => addslashes(json_encode($response,true)),
						'token_validation_id' => $validation_id,
					];
					$builderinsert = $db->table('api_log'); 
					$builderinsert->insert($data);
					if(!empty($response)){
						//$response = json_decode($res,true);
						if(isset($response['data']['uuid'])){
							$data = [
								'script_email_id' => $email_id,
								'script_filename' => basename($v),
								'script_code'    => $response['data']['uuid'],
								'status'    => 1,
								'api_response' => addslashes(json_encode($response,true)),
								'token_validation_id' => $validation_id,
							];
							$builderinsert = $db->table('cashflow_scripts'); 
							$builderinsert->insert($data);
							$rStatus++;
						}
					}
				}
			}catch(\Exception $e){
				log_message('info', 'exception:'.$e->getMessage());
			}
			
		}
		if($rStatus >= 2){
			return 1;
		}else{
			return 0;
		}
	}
	/**
	 * enable Payment in Bigcommerce store
	 *
	 */
	public static function disablePayment(){
		$db = \Config\Database::connect();
		helper('settingsviews');
		$clientDetails = \SettingsViews::getClientDetails();
		if(!empty($clientDetails)){
			$email_id = $clientDetails['email_id'];
			$validation_id = $clientDetails['validation_id'];
			$sellerdb = $clientDetails['sellerdb'];
			$store_hash = $clientDetails['store_hash'];
			$acess_token = $clientDetails['acess_token'];
			
			$url = getenv('bigcommerceapp.STORE_URL').$store_hash.'/v2/pages';
			$header = array(
				"X-Auth-Token: ".$acess_token,
				"Accept: application/json",
				"Content-Type: application/json"
			);
			try{
				\SettingsViews::deleteScripts($sellerdb,$acess_token,$store_hash,$email_id,$validation_id);
			}catch(\Exception $e){
				log_message('info', 'exception:'.$e->getMessage());
			}
				$data = [
					'is_enable' => 0
				];
			$builderupdate = $db->table('cashflow_token_validation'); 
			$builderupdate->where('email_id', $email_id); 
			$builderupdate->update($data);
		}
	}
	
	/**
	 * verifySignedRequest - Static funtion to check the valid signature.
	 *
	 * @param text| $signedRequest
	 *
	 * @return BigCommerce Store URL id & Email
	 */	
	public static function verifySignedRequest($signedRequest)
	{
		$clientSecret = getenv('bigcommerceapp.APP_CLIENT_SECRET');
		list($encodedData, $encodedSignature) = explode('.', $signedRequest, 2);
		$signature = base64_decode($encodedSignature);
		$jsonStr = base64_decode($encodedData);
		$data = json_decode($jsonStr, true);
		$expectedSignature = hash_hmac('sha256', $jsonStr, $clientSecret, $raw = false);
		if (!hash_equals($expectedSignature, $signature)) {
			error_log('Bad signed request from BigCommerce!');
			return null;
		}
		return $data;
	}
	
	/**
	 * createSignature - Static funtion to generate cardstream signature.
	 *
	 * @param text| $data
	 * @param text| $key
	 *
	 * @return BigCommerce Store URL id & Email
	 */	
	public static function createSignature(array $data, $key) {
		// Sort by field name
		ksort($data);
		
		// Create the URL encoded signature string
		$ret = http_build_query($data, '', '&');
		
		// Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
		$ret = str_replace(array('%0D%0A', '%0A%0D', '%0D'), '%0A', $ret);
		
		// Hash the signature string and the key together
		return hash('SHA512', $ret . $key);
	}
}