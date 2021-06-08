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
 * Class CustomPaymentScript
 *
 * Represents a helper class to create Payment Script in BigCommerce 
 */
class CustomPaymentScript
{
	/* creating folder Based on Seller */
	public static function createPaymentScript($sellerdb,$email_id,$validation_id){
		$tokenData = array("email_id"=>$email_id,"key"=>$validation_id);
		if(!empty($sellerdb)){
			
			$enable = 0;
			
			$buttonCode = '<button type="submit" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">CASHFLOWS PAYMENTS</button>';
			
			$db = \Config\Database::connect();
			$builder = $db->table('custom_cashflowpay_button');  
			$builder->select('*');       
			$builder->where('email_id', $email_id);
			$builder->where('token_validation_id', $validation_id);
			$query = $builder->get();
			$result_c = $query->getResultArray();
			if (count($result_c) > 0) {
				$result_c = $result_c[0];
				
				if(isset($result_c['is_enabled']) && $result_c['is_enabled'] == 1){
					$enable = 1;
				}
				
				if($enable == 1){
					if(!empty($result_c['html_code'])){
						$buttonCode = html_entity_decode($result_c['html_code']);
					}
				}
			}
			$enable = 0;
			if(isset($result_c['is_enabled']) && $result_c['is_enabled'] == 1){
				$enable = 1;
			}
			
			$folderPath = getenv('app.SCRIPSPATH').$sellerdb;
			$filecontent = '$("head").append("<script src=\"'.getenv('app.ASSETSPATH').'js/247cashflowloader.js\" ></script>");';
			$filecontent .= '$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"'.getenv('app.ASSETSPATH').'css/247cashflowloader.css\" />");';
			
			$css_prop_default = '#cashflowPaymentForm>button{
	display:block;
	background-color: #00FF00 !important;
	color: #000000 !important;
	border-color: #FF0000 !important;
}';
			
			if($enable == 1){
				$id = $result_c['container_id'];
				$css_prop = $result_c['css_prop'];
				
				if(!empty($id)){
					$filecontent .= '$(document).ready(function() {
				var stIntIdCashflow = setInterval(function() {
					if($(".checkout-step--payment").length > 0) {
						if($("#247cashflowpayment").length == 0){
							$("'.$id.'").after(\'<div id="247cashflowpayment" class="checkout-form" style="padding:1px;display:none;"><div id="247cashflowErr" style="color:red"></div><form id="cashflowPaymentForm" name="cashflowPayment"><input type="hidden" id="247cashflowkey" value="'.base64_encode(json_encode($tokenData)).'" >'.$buttonCode.'</form></div>\');
							loadCashflowStatus();
							clearInterval(stIntIdCashflow);
							/**
								when user is logged in and billing/shipping 
								address set show custom payment button 
							*/
							checkCashflowPayBtnVisibility();
						}
					}
				}, 1000);';
				}else{
					$filecontent .= '$(document).ready(function() {
					var stIntIdCashflow = setInterval(function() {
						if($(".checkout-step--payment").length > 0) {
							if($("#247cashflowpayment").length == 0){
								$(".checkout-step--payment .checkout-view-header").after(\'<div id="247cashflowpayment" class="checkout-form" style="padding:1px;display:none;"><div id="247cashflowErr" style="color:red"></div><form id="cashflowPaymentForm" name="cashflowPayment"><input type="hidden" id="247cashflowkey" value="'.base64_encode(json_encode($tokenData)).'" >'.$buttonCode.'</form></div>\');
								loadCashflowStatus();
								clearInterval(stIntIdCashflow);
								/**
									when user is logged in and billing/shipping 
									address set show custom payment button 
								*/
								checkCashflowPayBtnVisibility();
							}
						}
					}, 1000);';
				}
				
				if(!empty($css_prop)){
					$filecontent .= '$("body").append("<style>'.preg_replace("/[\r\n]*/","",$css_prop).'</style>");';
				}else{
					$filecontent .= '$("body").append("<style>'.preg_replace("/[\r\n]*/","",$css_prop_default).'</style>");';
				}
			}else{
					$filecontent .= '$("body").append("<style>'.preg_replace("/[\r\n]*/","",$css_prop_default).'</style>");';
					$filecontent .= '$(document).ready(function() {
		var stIntIdCashflow = setInterval(function() {
			if($(".checkout-step--payment").length > 0) {
				if($("#247cashflowpayment").length == 0){
					$(".checkout-step--payment .checkout-view-header").after(\'<div id="247cashflowpayment" class="checkout-form" style="padding:1px;display:none;"><div id="247cashflowErr" style="color:red"></div><form id="cashflowPaymentForm" name="cashflowPayment"><input type="hidden" id="247cashflowkey" value="'.base64_encode(json_encode($tokenData)).'" >'.$buttonCode.'</form></div>\');
					loadCashflowStatus();
					clearInterval(stIntIdCashflow);
					/**
						when user is logged in and billing/shipping 
						address set show custom payment button 
					*/
					checkCashflowPayBtnVisibility();
				}
			}
		}, 1000);';
			}
			$filecontent .= '$("body").on("click","button[data-test=\'step-edit-button\'], button[data-test=\'sign-out-link\']",function(e){
					//hide cardstream payment button
					$("#247cashflowpayment").hide();
				});

				$("body").on("click", "button#checkout-customer-continue, button#checkout-shipping-continue, button#checkout-billing-continue", function() {
					checkCashflowPayBtnVisibility();
				});
			});
			function cashflowbillingAddressValdation(billingAddress){
				var errorCount = 0;
				if(typeof(billingAddress.firstName) != "undefined" && billingAddress.firstName !== null && billingAddress.firstName !== "") {
					
				}else{
					errorCount++;
				}
				if(typeof(billingAddress.lastName) != "undefined" && billingAddress.lastName !== null && billingAddress.lastName !== "") {
					
				}else{
					errorCount++;
				}
				if(typeof(billingAddress.address1) != "undefined" && billingAddress.address1 !== null && billingAddress.address1 !== "") {
					
				}else{
					errorCount++;
				}
				if(typeof(billingAddress.email) != "undefined" && billingAddress.email !== null && billingAddress.email !== "") {
					
				}else{
					errorCount++;
				}
				if(typeof(billingAddress.city) != "undefined" && billingAddress.city !== null && billingAddress.city !== "") {
					
				}else{
					errorCount++;
				}
				if(typeof(billingAddress.postalCode) != "undefined" && billingAddress.postalCode !== null && billingAddress.postalCode !== "") {
					
				}else{
					errorCount++;
				}
				if(typeof(billingAddress.country) != "undefined" && billingAddress.country !== null && billingAddress.country !== "") {
					
				}else{
					errorCount++;
				}
				
				return errorCount;
			}

			function cashflowshippingAddressValdation(shippingAddress){
				var errorCount = 0;
				if(shippingAddress.length > 0){
					if(typeof(shippingAddress[0].shippingAddress) != "undefined" && shippingAddress[0].shippingAddress !== null && shippingAddress[0].shippingAddress !== "") {
						shippingAddress = shippingAddress[0].shippingAddress;
						if(typeof(shippingAddress.firstName) != "undefined" && shippingAddress.firstName !== null && shippingAddress.firstName !== "") {
							
						}else{
							errorCount++;
						}
						if(typeof(shippingAddress.lastName) != "undefined" && shippingAddress.lastName !== null && shippingAddress.lastName !== "") {
							
						}else{
							errorCount++;
						}
						if(typeof(shippingAddress.address1) != "undefined" && shippingAddress.address1 !== null && shippingAddress.address1 !== "") {
							
						}else{
							errorCount++;
						}
						if(typeof(shippingAddress.city) != "undefined" && shippingAddress.city !== null && shippingAddress.city !== "") {
							
						}else{
							errorCount++;
						}
						if(typeof(shippingAddress.postalCode) != "undefined" && shippingAddress.postalCode !== null && shippingAddress.postalCode !== "") {
							
						}else{
							errorCount++;
						}
						if(typeof(shippingAddress.country) != "undefined" && shippingAddress.country !== null && shippingAddress.country !== "") {
							
						}else{
							errorCount++;
						}
					}
				}else{
					errorCount++;
				}
				return errorCount;
			}
			function checkOnlyDownloadableProducts(cartData){
				var status = false;
				if(cartData != ""){
					if(cartData.physicalItems.length > 0 || cartData.customItems.length > 0){
						status = true;
					}
					else{
						if(cartData.digitalItems.length > 0){
							status = false;
						}
					}
				}
				return status;
			}
			var getUrlParameter = function getUrlParameter(sParam) {
				var sPageURL = window.location.search.substring(1),
					sURLVariables = sPageURL.split("&"),
					sParameterName,
					i;

				for (i = 0; i < sURLVariables.length; i++) {
					sParameterName = sURLVariables[i].split("=");

					if (sParameterName[0] === sParam) {
						return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
					}
				}
				return false;
			};
			function loadCashflowStatus(){
				var key = getUrlParameter("cashflowinv");
				if(key != "undefined" && key != ""){
					$.ajax({
						type: "POST",
						dataType: "json",
						crossDomain: true,
						url: "'.getenv('app.baseURL').'cashflowpay/getPaymentStatus",
						dataType: "json",
						data:{"authKey":key},
						success: function (res) {
							if(res.status){
								$("body #247cashflowErr").text(res.msg);
							}
						}
					});
				}
			}
			';
			$filecontent .= 'function checkCashflowPayBtnVisibility() {
				var checkDownlProd = false;
				var key = $("body #247cashflowkey").val();
				$.ajax({
					type: "GET",
					dataType: "json",
					url: "/api/storefront/cart",
					success: function (res) {
						if(res.length > 0){
							if(res[0]["id"] != undefined){
								var cartId = res[0]["id"];
								var cartCheck = res[0]["lineItems"];
								checkDownlProd = checkOnlyDownloadableProducts(cartCheck);
								if(cartId != ""){
									$.ajax({
										type: "GET",
										dataType: "json",
										url: "/api/storefront/checkouts/"+cartId,
										success: function (cartres) {
											var cartData = window.btoa(unescape(encodeURIComponent(JSON.stringify(cartres))));
											var billingAddress = "";
											var consignments = "";
											var bstatus = 0;
											var sstatus = 0;
											if(typeof(cartres.billingAddress) != "undefined" && cartres.billingAddress !== null) {
												billingAddress = cartres.billingAddress;
												bstatus = cashflowbillingAddressValdation(billingAddress);
											}
											if(checkDownlProd){
												if(typeof(cartres.consignments) != "undefined" && cartres.consignments !== null) {
													consignments = cartres.consignments;
													sstatus = cashflowshippingAddressValdation(consignments);
												}
											}

											if(bstatus ==0 && sstatus == 0) {

												//hide cardstream payment button
												$("#247cashflowpayment").show();
											}


										}
									});
								}
							}
						}
					}

				});
			}
			$("body").on("click","#cashflowPaymentForm",function(e){
				e.preventDefault();
				var text = "Please wait...";
				var current_effect = "bounce";
				var key = $("body #247cashflowkey").val();
				$("#247cashflowpayment").waitMe({
					effect: current_effect,
					text: text,
					bg: "rgba(255,255,255,0.7)",
					color: "#000",
					maxSize: "",
					waitTime: -1,
					source: "'.getenv('app.ASSETSPATH').'images/img.svg",
					textPos: "vertical",
					fontSize: "",
					onClose: function(el) {}
				});
				var checkDownlProd = false;
				$.ajax({
					type: "GET",
					dataType: "json",
					url: "/api/storefront/cart",
					success: function (res) {
						if(res.length > 0){
							if(res[0]["id"] != undefined){
								var cartId = res[0]["id"];
								var cartCheck = res[0]["lineItems"];
								checkDownlProd = checkOnlyDownloadableProducts(cartCheck);
								if(cartId != ""){
									$.ajax({
										type: "GET",
										dataType: "json",
										url: "/api/storefront/checkouts/"+cartId,
										success: function (cartres) {
											var billingAddress = "";
											var consignments = "";
											var bstatus = 0;
											var sstatus = 0;
											if(typeof(cartres.billingAddress) != "undefined" && cartres.billingAddress !== null) {
												billingAddress = cartres.billingAddress;
												bstatus = cashflowbillingAddressValdation(billingAddress);
											}
											if(checkDownlProd){
												if(typeof(cartres.consignments) != "undefined" && cartres.consignments !== null) {
													consignments = cartres.consignments;
													sstatus = cashflowshippingAddressValdation(consignments);
												}
											}
											if(bstatus ==0 && sstatus == 0 && parseFloat(cartres.grandTotal)>0){
												$.ajax({
													type: "POST",
													dataType: "json",
													crossDomain: true,
													url: "'.getenv('app.baseURL').'cashflowpay/authentication",
													dataType: "json",
													data:{"authKey":key,"cartId":cartId},
													success: function (res) {
														//$("#247cashflowpayment").waitMe("hide");
														if(res.status){
															window.location.href=res.url;
														}
													},error: function(){
														$("#247cashflowpayment").waitMe("hide");
													}
												});
											}else{
												alert("Please Select Billing Address and Shipping Address");
												$("#247cashflowpayment").waitMe("hide");
											}
										},error: function(){
											$("#247cashflowpayment").waitMe("hide");
										}
									});
								}
							}
						}
					},error: function(){
						$("#cashflowPaymentForm").waitMe("hide");
					}
				});
				
			});';
			$filename = 'custom_script.js';
			helper('filestream');
			$res = \FileStream::saveFile($filename,$filecontent,$folderPath);
		}
	}
}