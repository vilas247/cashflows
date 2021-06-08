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
 * Class Home
 *
 * Represents a CASHFLOW setup related function to load BigCommerce
 * connector and launch configuration.
 */
class Home extends BaseController
{
	/**
	 * Index - default home page once app installed in CASHFLOW
	 * and valid segments.
	 *
	 * @return Load BigCommerce store page
	 */
	public function index()
	{
		helper('settingsviews');
		$clientDetails = \SettingsViews::getClientDetails();
		if(!empty($clientDetails)){
			$status = \SettingsViews::validateClientDetails();
			if($status){
				return redirect()->to('/home/dashboard');
			}else{
				return view('index');
			}
		}else{
			echo "Something Went Wrong";
		}
	}
	
	/*
	* Dashboard
	*/
	public function dashboard()
	{
		helper('settingsviews');
		
		$clientDetails = \SettingsViews::getClientDetails();
		$data = array();
		if(!empty($clientDetails)){
			$data['clientDetails'] = $clientDetails;
			
			$db = \Config\Database::connect();
			$builder = $db->table('order_payment_details opd');
			$builder->select('opd.api_response,opd.id,opd.settlement_status,opd.type,opd.amount_paid,opd.email_id as email,opd.order_id as invoice_id,od.order_id,opd.status,opd.currency,opd.total_amount,opd.created_date');
			$builder->join('order_details od', 'opd.order_id = od.invoice_id','left');
			$builder->where('opd.email_id', $clientDetails['email_id']);
			$builder->orderBy('opd.id', 'DESC');
			$builder->limit(15);
			$query = $builder->get();
			$result = $query->getResultArray();
			$data['orderDetails'] = $result;
			return view('dashboard',$data);
		}else{
			return redirect()->to('/');
		}
	}
	
	/**
	 * alterClientDetails - update client details
	 * details in DB.
	 *
	 * @return redirect to setting page
	 */
	public function alterClientDetails()
	{
		$return = false;
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
				$builderupdate->where('validation_id', $clientDetails['validation_id']); 
				$builderupdate->update($data);
				$return = true;
			}
		}
		echo $return;exit;
	}
	
	/*
	* Order Details
	*/
	public function orderDetails()
	{
		helper('settingsviews');
		
		$clientDetails = \SettingsViews::getClientDetails();
		$data = array();
		if(!empty($clientDetails)){
			$data['clientDetails'] = $clientDetails;
			return view('orderDetails',$data);
		}else{
			return redirect()->to('/');
		}
	}
	
	/*
	* getOrder
	*/
	public function orderDetailsProcessing()
	{
		helper('settingsviews');
		
		$clientDetails = \SettingsViews::getClientDetails();
		
		$offset = 0;
		$limit = 10;
		$draw = 1;
		$noofrecords = 0;
		$final_array = array();
		$outer_array = array();
		$recordsTotal = 0;
		$recordsFiltered = 0;
			
		$data = array();
		if(!empty($clientDetails)){
			$cols_data = array();
			$cols_data = json_decode($this->request->getPost('cols_data'),true);
			
			$draw = $this->request->getPost('draw');
			
			$limit = $this->request->getPost('length');
			$offset = $this->request->getPost('start');
			
			$order = $this->request->getPost('order');
				//print_r($db_columns);exit;
			if(!empty($order)){
				$column_det = $db_columns[$order[0]['column']];
				$sorting = $order[0]['dir'];
				$sorting_val = $column_det['value'];
			}
			
			$orderby = 'order by opd.id desc';
			$db = \Config\Database::connect();
			$builder = $db->table('order_payment_details opd');
			$builder->select('count(*) as totalCount');
			$builder->join('order_details od', 'opd.order_id = od.invoice_id','left');
			$builder->where('opd.email_id', $clientDetails['email_id']);
			$builder->orderBy('opd.id', 'DESC');
			$builder->limit(15);
			$query = $builder->get();
			$result = $query->getResultArray();
			if (count($result) > 0) {
				$result = $result[0];
				$recordsTotal = $result['totalCount'];
			}
			
			$builder = $db->table('order_payment_details opd');
			$builder->select('count(*) as totalCount');
			$builder->join('order_details od', 'opd.order_id = od.invoice_id','left');
			$builder->where('opd.email_id', $clientDetails['email_id']);
			if(!empty($this->request->getPost('searchVal'))){
				$search_val = $this->request->getPost('searchVal');
				$builder->like('od.order_id', $search_val);
				$builder->orLike('opd.api_response', $search_val);
			}
			$builder->limit(15);
			$query = $builder->get();
			$result_filter = $query->getResultArray();
			if (count($result_filter) > 0) {
				$result_filter = $result_filter[0];
				$recordsFiltered = $result_filter['totalCount'];
			}
			
			
			$builder = $db->table('order_payment_details opd');
			$builder->select('opd.api_response,opd.id,opd.settlement_status,opd.type,opd.amount_paid,opd.email_id as email,opd.order_id as invoice_id,od.order_id,opd.status,opd.currency,opd.total_amount,opd.created_date');
			$builder->join('order_details od', 'opd.order_id = od.invoice_id','left');
			$builder->where('opd.email_id', $clientDetails['email_id']);
			if(!empty($this->request->getPost('searchVal'))){
				$search_val = $this->request->getPost('searchVal');
				$builder->like('od.order_id', $search_val);
				$builder->orLike('opd.api_response', $search_val);
			}
			$builder->orderBy('opd.id', 'DESC');
			$builder->limit($limit,$offset);
			$query = $builder->get();
			$result_final = $query->getResultArray();
			if(count($result_final) > 0){
				foreach($result_final as $k=>$values) {
					$inner_array = array();
					if(!empty($values['invoice_id'])){
						$inner_array[] = '<input type="checkbox" class="form-check-input order_checkbox" value="'.$values['id'].'" name="chkOrgRow" />';
						foreach($cols_data as $dbk=>$dbv){
							if(isset($values[$dbv['val']])){
								if($dbv['val'] == "created_date"){
									$inner_array[] = date("Y-m-d h:i A",strtotime($values[$dbv['val']]));
								}else if($dbv['val'] == "status"){
									$status = '';
									if($values['status'] == "CONFIRMED"){
										$status = '<span class="badge bg-success table-status-clr">Confirmed</span>';
									}else if($values['status'] == "RESERVED"){
										$status = '<span class="badge bg-success table-status-clr">Reserved</span>';
									}else{
										$status = '<span class="badge btn-pink table-status-clr">'.ucfirst(strtolower($values['status'])).'</span>';
									}
									$inner_array[] = $status;
								}else if($dbv['val'] == "settlement_status"){
									$sstatus = '';
									if($values['type'] == "SALE"){
										if($values['status'] == "CONFIRMED"){
											if($values['settlement_status'] == "REFUND"){
												$sstatus = '<span class="badge bg-success table-status-clr">'.ucfirst($values['settlement_status']).'</span>';
											}else{
												$sstatus = '<span class="badge bg-success table-status-clr">Confirmed</span>';
											}
										}else{
											$sstatus = '<span class="badge btn-pink table-status-clr">'.ucfirst(strtolower($values['settlement_status'])).'</span>';
										}
									}else{
										if(($values['settlement_status'] == "Completed") || ($values['settlement_status'] == "REFUND")){
											$sstatus = '<span class="badge bg-success table-status-clr">'.ucfirst(strtolower($values['settlement_status'])).'</span>';
										}else{
											$sstatus = '<span class="badge btn-pink table-status-clr">'.ucfirst(strtolower($values['settlement_status'])).'</span>';
										}
									}
									$inner_array[] = $sstatus;
								}else{
									$inner_array[] = $values[$dbv['val']];
								}
							}else{
								if($dbv['val'] == "action"){
									$builder = $db->table('order_refund');
									$builder->select('*');
									$builder->where('invoice_id', $values['invoice_id']);
									$query = $builder->get();
									$ref_result = $query->getResultArray();
									
									$refunded_amount = 0;
									$total_amount = $values['total_amount'];
									if (count($ref_result) > 0) {
										foreach($ref_result as $k=>$v){
											if($v['refund_status'] == "Completed"){
												$refunded_amount += $v['refund_amount'];
											}
										}
									}
							
									$actions = '';
									if($values['status'] == "RESERVED" && $values['type'] == "AUTH" && ($values['settlement_status'] == "PENDING" || $values['settlement_status'] == "FAILED")){
										$actions .= '<a href="'.getenv('app.baseURL').'settleOrder/index/'.base64_encode(json_encode($values['invoice_id'])).'" ><button type="button" class="btn btn-outline-danger com-btn sm-margin">Settle</button></a>';
									}else if($values['status'] == "CONFIRMED" && $values['type'] == "AUTH" && ($values['settlement_status'] == "Completed" || $values['settlement_status'] == "REFUND")){
										$actions .= '<button type="button" class="btn btn-danger com-btn sm-margin" disabled >Settled</button>';
										
										if (($total_amount-$refunded_amount) > 0) {
											$actions .= '<a href="'.getenv('app.baseURL').'refundOrder/index/'.base64_encode(json_encode($values['invoice_id'])).'" ><button type="button" class="btn btn-outline-success com-btn sm-margin">Refund</button></a>';
										}else{
											$actions .= '<button type="button" class="btn btn-success com-btn sm-margin" disabled >Refunded</button>';
										}
									}else if($values['status'] == "CONFIRMED"){
										if (($total_amount-$refunded_amount) > 0) {
											$actions .= '<a href="'.getenv('app.baseURL').'refundOrder/index/'.base64_encode(json_encode($values['invoice_id'])).'" ><button style="width: 100%;" type="button" class="btn btn-outline-success com-btn sm-margin">Refund</button></a>';
										}else{
											$actions .= '<button type="button" class="btn btn-success com-btn sm-margin" disabled >Refunded</a></button>';
										}
									}
									$inner_array[] = $actions;
								}else{
									$inner_array[] = '&nbsp;';
								}
							}
						}
					}
					if(!empty($inner_array)){
						$outer_array[] = $inner_array;
					}
					
				}
			}
		}
		$final_array['draw'] = $draw;
		$final_array['recordsTotal'] = $recordsTotal;
		$final_array['recordsFiltered'] = $recordsFiltered;
		$final_array['data'] = $outer_array;
		echo json_encode($final_array,true);exit;
	}
	
	/*
	* getOrderDetails
	*/
	public function getOrderDetails($authKey)
	{
		$final_data = array();
		$final_data['status'] = false;
		$final_data['data'] = array();
		$final_data['msg'] = '';
		if(!empty($authKey)){
			$invoiceId = json_decode(base64_decode($authKey),true);
			helper('settingsviews');
				
			$db = \Config\Database::connect();
			
			$builder = $db->table('order_details');        
			$builder->select('*');       
			$builder->where('invoice_id', $invoiceId);
			$query = $builder->get();
			$result_order_payment = $query->getResultArray();
			if (isset($result_order_payment[0])) {
				$result_order_payment = $result_order_payment[0];
				$order_id = $result_order_payment['order_id'];
				helper('bigcommerceorder');
				$orderDetails = \BigCommerceOrder::getBigCommerceOrder($order_id);
				if($orderDetails['status']){
					$final_data['status'] = true;
					$final_data['data'] = $orderDetails['data'];
					$final_data['msg'] = $orderDetails['msg'];
				}
			}
		}
		echo json_encode($final_data,true);exit;
	}
}
