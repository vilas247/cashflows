<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="Author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.83.1">
    <title>Settings</title>

    <!-- Bootstrap core CSS -->
    <link href="<?= getenv('app.ASSETSPATH') ?>css/bootstrap.min.css" rel="stylesheet">

    <!-- font-awesome css-->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.1/css/all.css" integrity="sha384-O8whS3fhG2OnA5Kas0Y9l3cfpmYjapjI0E4theH4iuMD+pLhbf6JI0jIMfYcK3yZ" crossorigin="anonymous">

    <link href="<?= getenv('app.ASSETSPATH') ?>css/custom.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?= getenv('app.ASSETSPATH') ?>css/toaster/toaster.css">
	<link rel="stylesheet" href="<?= getenv('app.ASSETSPATH') ?>css/247cashflowloader.css">
  </head>
  <body>
	<?php include('template/header.php');?>
    <main class="main-content">
      <div class="container">
        <div class="row">
          <div class="col-12">
			<form id="updateSettings" action="<?= getenv('app.baseURL') ?>settings/updateSettings" method="POST" >
            <div class="card">
                  <div class="card-header">
                    <div class="row">
                        <div class="col-6">
                            <p>Settings</p>
                        </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="row p-3">
						<?php
							$payment_option = 'CFO';
							$enabled = false;
							if($clientDetails['is_enable'] == 1){
								$enabled = true;
							}
							if(isset($clientDetails['payment_option'])){
								$payment_option = $clientDetails['payment_option'];
							}
						?>
                        <div class="col-md-3 col-sm-6 col-12 my-3">
                            <p>Name</p>
                            <p><strong><?= $clientDetails['email_id'] ?></strong></p>
                        </div>
                        <div class="col-md-2 col-sm-6 col-12 my-3">
                            <p>Configuration Id</p>
                            <p><strong><?= $clientDetails['config_id'] ?></strong></p>
                        </div>
                        <div class="col-md-3 col-sm-4 col-12 my-3">
                            <p>Current Api Key</p>
                            <p><strong><?= $clientDetails['api_key'] ?></strong></p>
                        </div>
                        <div class="col-md-3 col-sm-6 col-12 my-3">
                            <p>Payment Options</p>
                            <div class="form-check">
                                  <input class="form-check-input btns-red" type="radio" name="payment_option" <?= ($payment_option == "CFO")?'checked':'' ?> value="CFO" id="flexRadioDefault1" >
                                  <label class="form-check-label" for="flexRadioDefault1">
                                    Capture on order placed
                                  </label>
                                </div>
                                <div class="form-check">
                                  <input class="form-check-input btns-red" type="radio" name="payment_option" <?= ($payment_option == "CFS")?'checked':'' ?> value="CFS" id="flexRadioDefault2">
                                  <label class="form-check-label" for="flexRadioDefault2">
                                    Capture on Shipment
                                  </label>
                                </div>
                            </div>
                        <div class="col-sm-2 col-md-1 col-12 my-3">
                            <p>Action</p>
                            <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" id="actionChange" <?= ($enabled)?'checked':'' ?> value="<?= ($enabled)?'1':'0' ?>" >
                            </div>
                        </div>
                    </div>
                  </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-purple">Update</button>
                </div>
            </div>
			</form>
          </div>        
        </div>
        <div class="row mb-2">
            <div class="col-md-6 col-sm-6 col-5">
               <h5>Order Details &nbsp;<img src="<?= getenv('app.ASSETSPATH') ?>images/refresh.svg" id="refreshButton" style="height:3%;width:3%"></h5>
            </div>
        </div>
        <div class="table-responsive">
            <table id="myTable" class="table">
                  <tr class="header">
                    <th>Payment Number</th>
                    <th>Payment type</th>
                    <th>Payment Status</th>
                    <th>Settlement Status</th>
                    <th>Currency</th>
                    <th>Total</th>
                    <th>Amount Paid</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                  </tr>
				  <?php
					if(count($orderDetails) > 0){
						foreach($orderDetails as $k=>$values) {
				  ?>
                  <tr>
                    <td><?= $values['invoice_id'] ?></td>
                    <td><?= $values['type'] ?></td>
					<td>
						<?php
							$status = '';
							if($values['status'] == "CONFIRMED"){
								$status = '<span class="badge bg-success table-status-clr">Confirmed</span>';
							}else if($values['status'] == "RESERVED"){
								$status = '<span class="badge bg-success table-status-clr">Reserved</span>';
							}else{
								$status = '<span class="badge btn-pink table-status-clr">'.ucfirst(strtolower($values['status'])).'</span>';
							}
						?>
						<?= $status ?>
					</td>
					<td>
						<?php
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
						?>
						<?= $sstatus ?>
					</td>
                    <td><?= $values['currency'] ?></td>
                    <td><?= $values['total_amount'] ?></td>
                    <td><?= $values['amount_paid'] ?></td>
                    <td><?= date("Y-m-d h:i A",strtotime($values['created_date'])) ?></td>
					<td>
						<?php
							$db = \Config\Database::connect();
							$builder = $db->table('order_refund');
							$builder->select('*');
							$builder->where('invoice_id', $values['invoice_id']);
							$builder->where('refund_status', "Completed");
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
						?>
						<?= $actions ?>
					</td>
                  </tr>
				<?php } } ?>
                 
            </table>
        </div>
      </div>
    </main>
	<!-- Modal -->
		<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
			  <div class="modal-content">
				<div class="modal-header">
				  <h5 class="modal-title" id="exampleModalLongTitle"><span><img src="<?= getenv('app.ASSETSPATH') ?>images/icons/trash-purple.svg" style="margin-top: -5px;"></span> <span class="purple">Remove Cashflow Payments from Checkout</span>  </h5>
				  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<img style="height:25px;" src="<?= getenv('app.ASSETSPATH') ?>images/cross.png" />
				  </button>
				</div>
				<div class="modal-body" id="modalContent">
				  Are you sure you want to disable Cashflow Payments? </strong>
				</div>
				<div class="modal-footer">
				  <button type="button" class="btn btn-order" id="cancelConfirm" data-dismiss="modal">Cancel</button>
				  <button type="button" class="btn btn-order" id="deleteConfirm">Disable</button>
				</div>
			  </div>
			</div>
		  </div>
    <script src="<?= getenv('app.ASSETSPATH') ?>js/jquery-min.js"></script>
    <script src="<?= getenv('app.ASSETSPATH') ?>js/bootstrap.min.js"></script>
    <script src="<?= getenv('app.ASSETSPATH') ?>js/bootstrap.bundle.min.js"></script>
	<script src="<?= getenv('app.ASSETSPATH') ?>js/247cashflowloader.js"></script>
	<script src="<?= getenv('app.ASSETSPATH') ?>js/toaster/jquery.toaster.js"></script>
    <script>
function myFunction() {
  // Declare variables
  var input, filter, table, tr, td, i, txtValue;
  input = document.getElementById("myInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("myTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[0];
    if (td) {
      txtValue = td.textContent || td.innerText;
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }
}
</script>
<script type="text/javascript">
		var text = "Please wait...";
		var current_effect = "bounce";
		var app_base_url = "<?= getenv('app.baseURL') ?>";
		$(document).ready(function() {
			$('body').on('change','#actionChange',function(){
				$("body").waitMe({
					effect: current_effect,
					text: text,
					bg: "rgba(255,255,255,0.7)",
					color: "#000",
					maxSize: "",
					waitTime: -1,
					source: "images/img.svg",
					textPos: "vertical",
					fontSize: "",
					onClose: function(el) {}
				});
				var val = $(this).val();
				if(val == "0"){
					var url = app_base_url+'settings/bcEnablePayment';
					window.location.href = url;
				}else{
					$('body #exampleModalCenter').modal('show');
					$("body").waitMe("hide");
				}
			});
			$('body').on('click','#deleteConfirm',function(e){
				$("body").waitMe({
					effect: current_effect,
					text: text,
					bg: "rgba(255,255,255,0.7)",
					color: "#000",
					maxSize: "",
					waitTime: -1,
					source: "images/img.svg",
					textPos: "vertical",
					fontSize: "",
					onClose: function(el) {}
				});
				var url = app_base_url+'settings/bcDisablePayment';
				window.location.href = url;
			});
			$('body').on('click','#cancelConfirm,.close',function(e){
				$('body #exampleModalCenter').modal('hide');
				$('#actionChange').trigger('click');
			});
		});
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
		$(document).ready(function(){
			var enabled = getUrlParameter('enabled');
			if(enabled){
				$.toaster({ priority : "success", title : "Success", message : "Cashflow Payments enabled for your Store" });
			}
			var disabled = getUrlParameter('disabled');
			if(disabled){
				$.toaster({ priority : "success", title : "Success", message : "Cashflow Payments disabled for your Store" });
			}
			var updated = getUrlParameter('updated');
			if(updated){
				$.toaster({ priority : "success", title : "Success", message : "Payment Option Updated" });
			}
		});
		$('body').on('submit','#updateSettings',function(e){
			$("body").waitMe({
				effect: current_effect,
				text: text,
				bg: "rgba(255,255,255,0.7)",
				color: "#000",
				maxSize: "",
				waitTime: -1,
				source: "images/img.svg",
				textPos: "vertical",
				fontSize: "",
				onClose: function(el) {}
			});
		});
		$('body').on('click','#refreshButton',function(){
			$("body").waitMe({
				effect: current_effect,
				text: text,
				bg: "rgba(255,255,255,0.7)",
				color: "#000",
				maxSize: "",
				waitTime: -1,
				source: "images/img.svg",
				textPos: "vertical",
				fontSize: "",
				onClose: function(el) {}
			});
			var url = app_base_url+'home/dashboard';
			window.location.href = url;
		});
	</script>
  </body>
</html>
