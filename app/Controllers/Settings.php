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
 * Class Settings
 *
 * Permit to connect CASHFLOW with Bigcommerce and vice-versa.
 * Configure connector and launch configuration.
 */
class Settings extends BaseController
{
	/**
	 * Bcredirect - redirect from Bigcommerce
	 * callback url.
	 *
	 * @return Load setting page based on redirect param
	 */
	public function bcredirect($emailId,$validationId)
	{
		$session = session();
		if (!empty($emailId) && !empty($validationId))
		{
			$session->set('email_id', $emailId);
			
			$session->set('validation_id', $validationId);
		}
		return redirect()->to('/');
	}
	/**
	 * BcAuthcallback - BigCommerce app installation redirect funtion to store
	 * details in DB.
	 *
	 * @return redirect to setting page
	 */
	public function bcAuthcallback()
	{
		helper('settingsviews');
		$client   = \Config\Services::curlrequest();
		$response = $client->request('POST', getenv('bigcommerceapp.oauthloginUrl'), [
			'query'  => [
				'client_id'     => getenv('bigcommerceapp.APP_CLIENT_ID'),
				'client_secret' => getenv('bigcommerceapp.APP_CLIENT_SECRET'),
				'redirect_uri'  => getenv('app.baseURL') . 'settings/bcAuthcallback',
				'grant_type'    => 'authorization_code',
				'code'          => $_GET['code'],
				'scope'         => $_GET['scope'],
				'context'       => $_GET['context'],
			],
			'verify' => false,
		]);
		if (strpos($response->getHeader('content-type'), 'application/json') != false)
		{
			$body     = $response->getBody();
			$response = json_decode($body, true);
			if (isset($response['access_token']))
			{
				$validationResponse = \SettingsViews::storeTokenData($response);
			}
			$data = $_REQUEST;
			if (isset($validationResponse['email']))
			{
				$emailId      = $validationResponse['email'];
				$validationId = $validationResponse['id'];
				$accessToken  = $response['access_token'];
				return redirect()->to('/settings/bcredirect/' . $emailId . '/' . $validationId);
			}
		}
	}
	
	/**
	 * BcLoadcallback - BigCommerce app iframe redirect default funtion.
	 *
	 * @return redirect to setting page
	 */
	public function bcLoadcallback()
	{
		helper('settingsviews');
		$data     = $_REQUEST;
		$jsonData = \SettingsViews::verifySignedRequest($_GET['signed_payload']);
		if ($jsonData != null && $jsonData != '')
		{
			$storeContext = @$jsonData['context'];
			$storeHash    = str_replace('stores/', '', $storeContext);
			$email = @$jsonData['user']['email'];
			$db           = \Config\Database::connect();
			$builder      = $db->table('cashflow_token_validation');
			$builder->select('*');
			$builder->where('store_hash', $storeHash);
			$builder->where('email_id', $email);
			$query        = $builder->get();
			$result       = $query->getResultArray();
			if(count($result) > 0){
				$validationId = $result[0]['validation_id'];
				$emailId      = $result[0]['email_id'];
				$bcToken      = $result[0]['acess_token'];
				return redirect()->to('/settings/bcredirect/' . $emailId . '/' . $validationId);
			}
		}
	}
	
	/**
	 * BcRemovecallback - BigCommerce app remove redirect funtion.
	 * Update the status to 0
	 *
	 * @return success
	 */
	public function bcRemovecallback()
	{
		helper('settingsviews');
		$data     = $_REQUEST;
		$jsonData = \SettingsViews::verifySignedRequest($_GET['signed_payload']);
		if ($jsonData != null && $jsonData != '')
		{
			$storeContext  = @$jsonData['context'];
			$storeHash     = str_replace('stores/', '', $storeContext);
			return 'success';
		}
	}
	
	/**
	 * BcUninstallcallback - BigCommerce app uninstall redirect funtion.
	 * Delete entry from app DB
	 */
	public function bcUninstallcallback()
	{
		$data = $_REQUEST;
		helper('settingsviews');
		if (isset($data['signed_payload']))
		{
			$payload = explode('.', $data['signed_payload']);
			if (isset($payload[0]))
			{
				$signedPayload = $payload[0];
				$userData      = json_decode(base64_decode($signedPayload), true);
				if (isset($userData['context']))
				{
					$storeHash = str_replace('stores/', '', $userData['context']);
					$email = @$userData['user']['email'];
					if ($storeHash != '' && !empty($email))
					{
						$db      = \Config\Database::connect();
						$builder = $db->table('cashflow_token_validation');
						$builder->select('*');
						$builder->where('store_hash', $storeHash);
						$builder->where('email_id', $email);
						$query  = $builder->get();
						$result = $query->getResultArray();
						if (count($result) > 0)
						{
							$result = $result[0];
							\SettingsViews::unInstallCustomPages($result);
						}
					}
				}
			}
		}
	}
	
	/**
	 * BCEnabePayment - enable Payment in bigcommerce redirect funtion to store
	 * details in DB.
	 *
	 * @return redirect to setting page
	 */
	public function bcEnablePayment()
	{
		helper('settingsviews');
		$clientDetails = \SettingsViews::getClientDetails();
		if(!empty($clientDetails)){
			$status = \SettingsViews::validateClientDetails();
			if($status){
				$result = \SettingsViews::enablePayment();
				return redirect()->to('/home/dashboard?enabled=1');
			}else{
				return view('/');
			}
		}else{
			return redirect()->to('/');
		}
	}
	/**
	 * BCEnabePayment - disable Payment in bigcommerce redirect funtion to store
	 * details in DB.
	 *
	 * @return redirect to setting page
	 */
	public function bcDisablePayment()
	{
		helper('settingsviews');
		$clientDetails = \SettingsViews::getClientDetails();
		if(!empty($clientDetails)){
			$status = \SettingsViews::validateClientDetails();
			if($status){
				$result = \SettingsViews::disablePayment();
				return redirect()->to('/home/dashboard?disabled=1');
			}else{
				return redirect()->to('/');
			}
		}else{
			return redirect()->to('/');
		}
	}
	
	/**
	 * updatePaymetDetails - store payment details of globalpay and worldpay
	 * details in DB.
	 *
	 * @return redirect to setting page
	 */
	public function updatePaymetDetails()
	{
		if($this->request->getMethod() == "post"){
			$config_id = $this->request->getVar('config_id');
			$api_key = $this->request->getVar('api_key');
		
			helper('settingsviews');
			$clientDetails = \SettingsViews::getClientDetails();
			if(!empty($clientDetails)){
			
				$data = [
					'config_id' => $config_id,
					'api_key' => $api_key
				];
				$db = \Config\Database::connect();
				$builderupdate = $db->table('cashflow_token_validation'); 
				$builderupdate->where('email_id', $clientDetails['email_id']); 
				$builderupdate->where('store_hash', $clientDetails['store_hash']); 
				$builderupdate->update($data);
				helper('custompaymentscript');
				\CustomPaymentScript::createPaymentScript($clientDetails['sellerdb'],$clientDetails['email_id'],$clientDetails['validation_id']);
				return redirect()->to('/home/dashboard');
			}else{
				return redirect()->to('/');
			}
		}else{
			return redirect()->to('/');
		}
	}
	
	/**
	 * updatePaymetDetails - store payment details of globalpay and worldpay
	 * details in DB.
	 *
	 * @return redirect to setting page
	 */
	public function updateSettings()
	{
		if($this->request->getMethod() == "post"){
			$payment_option = $this->request->getVar('payment_option');
		
			helper('settingsviews');
			$clientDetails = \SettingsViews::getClientDetails();
			if(!empty($clientDetails)){
			
				$data = [
					'payment_option' => $payment_option
				];
				$db = \Config\Database::connect();
				$builderupdate = $db->table('cashflow_token_validation'); 
				$builderupdate->where('email_id', $clientDetails['email_id']); 
				$builderupdate->where('store_hash', $clientDetails['store_hash']); 
				$builderupdate->update($data);
				helper('custompaymentscript');
				return redirect()->to('/home/dashboard?updated=1');
			}else{
				return redirect()->to('/');
			}
		}else{
			return redirect()->to('/');
		}
	}
	
	/**
	 * Index - default home page once app installed in PURPLEBRICKS
	 * and valid segments.
	 *
	 * @return Load BigCommerce store page
	 */
	public function customButton()
	{
		helper('settingsviews');
		$data = array();
		$data['buttonDetails'] = array();
		$clientDetails = \SettingsViews::getClientDetails();
		if(!empty($clientDetails)){
			$db      = \Config\Database::connect();
			$builder = $db->table('custom_cashflowpay_button');
			$builder->select('*');
			$builder->where('token_validation_id', $clientDetails['validation_id']);
			$builder->where('email_id', $clientDetails['email_id']);
			$query  = $builder->get();
			$result = $query->getResultArray();
			if(count($result) > 0){
				$data['buttonDetails'] = $result;
			}
			return view('customButton',$data);
		}else{
			return redirect()->to('/');
		}
	}
	
	/**
	 * updateCustomButton - update 
	 * details in DB.
	 *
	 * @return redirect to setting page
	 */
	public function updateCustomButton()
	{
		$db = \Config\Database::connect();
		if($this->request->getMethod() == "post"){
			$container_id = $this->request->getPost('container_id');
			$css_prop = $this->request->getPost('css_prop');
			$html_code = $this->request->getPost('html_code');
			$is_enabled = $this->request->getPost('is_enabled');
			
			$enable = 0;
			if($is_enabled == "on"){
				$enable = 1;
			}
		
			helper('settingsviews');
			$clientDetails = \SettingsViews::getClientDetails();
			if(!empty($clientDetails)){
				$builder = $db->table('custom_cashflowpay_button');  
				$builder->select('*');       
				$builder->where('email_id', $clientDetails['email_id']);
				$builder->where('token_validation_id', $clientDetails['validation_id']);
				$query = $builder->get();
				$result = $query->getResultArray();
				if (count($result) > 0) {
					$data = [
						'container_id' => $container_id,
						'css_prop' => $css_prop,
						'html_code' => htmlentities($html_code),
						'is_enabled' => $enable,
					];
					$builderupdate = $db->table('custom_cashflowpay_button'); 
					$builderupdate->where('email_id', $clientDetails['email_id']); 
					$builderupdate->where('token_validation_id', $clientDetails['validation_id']); 
					$builderupdate->update($data);
				}else{
					$data = [
						'email_id' => $clientDetails['email_id'],
						'token_validation_id' => $clientDetails['validation_id'],
						'container_id' => $container_id,
						'css_prop' => $css_prop,
						'html_code' => htmlentities($html_code),
						'is_enabled' => $enable,
					];
					$builderinsert = $db->table('custom_cashflowpay_button'); 
					$builderinsert->insert($data);
				}
				helper('custompaymentscript');
				\CustomPaymentScript::createPaymentScript($clientDetails['sellerdb'],$clientDetails['email_id'],$clientDetails['validation_id']);
			}
		}
		return redirect()->to('/settings/customButton?updated=1');
	}
}
