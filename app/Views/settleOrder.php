<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.83.1">
    <title>Settle Client Transaction</title>

    <!-- Bootstrap core CSS -->
    <link href="<?= getenv('app.ASSETSPATH') ?>css/bootstrap.min.css" rel="stylesheet">

    <!-- font-awesome css-->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.1/css/all.css" integrity="sha384-O8whS3fhG2OnA5Kas0Y9l3cfpmYjapjI0E4theH4iuMD+pLhbf6JI0jIMfYcK3yZ" crossorigin="anonymous">

    <link href="<?= getenv('app.ASSETSPATH') ?>css/custom.css" rel="stylesheet">
    <link href="<?= getenv('app.ASSETSPATH') ?>css/style.css" rel="stylesheet">

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
          <div class="col-12 col-md-10 offset-md-1">
            <div class="card">
                  <div class="card-header">
                    <div class="row">
                        <div class="col-md-6 col-sm-6 col-6">
                            <p>Settle Client Transaction</p>
                        </div>
                        <div class="col-md-6 text-end col-sm-6 col-6">
                            <a href="<?= getenv('app.baseURL') ?>home/dashboard"><p><i class="fas fa-arrow-left me-2"></i>Back to dashboard</p></a>
                        </div>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="row card-content p-3">
                        <div class="col-md-3 col-sm-3 px-md-2 col-6">
                            <p>Email Id</p>
                            <p><strong><?= $clientDetails['email_id'] ?></strong></p>
                        </div>
                        <div class="col-md-3 col-sm-3 px-lg-4 col-6">
                            <p>Invoice Id</p>
                            <p><strong><?= $orderDetails['invoice_id'] ?></strong></p>
                        </div>
                        <div class="col-md-3 col-sm-3 px-lg-5 col-6">
                            <p>Amount</p>
                            <p><strong><?= $orderDetails['currency'].' '.$orderDetails['total_amount'] ?></strong></p>
                        </div>
                        <div class="col-md-3 col-sm-3 px-lg-5 col-6">
                            <p>Status</p>
                            <p><span class="badge bg-success"><?= ucfirst(strtolower($orderDetails['status'])) ?></span></p>
                        </div>
                    </div>
                    <div class="row p-3">
                        <div class="col-md-3 col-sm-12 col-12">
                            <p>Amount Paid</p>
                            <p><strong><?= $orderDetails['currency'].' '.$orderDetails['amount_paid'] ?></strong></p>
                        </div>
                         <div class="col-md-3 col-sm-12 col-12">
                            <p>Settlement Status</p>
							<?php if($orderDetails['settlement_status'] == "Completed"){ ?>
							 <p><span class="badge bg-success"><?= ucfirst(strtolower($orderDetails['settlement_status'])) ?></span></p>
							<?php }else{ ?>
                            <p><span class="badge bg-danger"><?= ucfirst(strtolower($orderDetails['settlement_status'])) ?></span></p>
							<?php } ?>
                        </div>
                    </div>
                  </div>
            </div>
			<?php if($orderDetails['settlement_status'] != "Completed"){ ?>
			<form id="settleOrder" action="<?= getenv('app.baseURL') ?>settleOrder/proceedSettle" method="POST" >
            <div class="row mt-3">
                <div class="col-md-12 text-end">
					<input type="hidden" name="invoice_id" value="<?= $orderDetails['invoice_id'] ?>" />
                    <button type="submit" class="btn btn-purple">Update</button>
                </div>
            </div>
			</form>
			<?php } ?>
          </div>        
        </div>
      </div>
    </main>
  </body>
  <script src="<?= getenv('app.ASSETSPATH') ?>js/jquery-min.js"></script>
  <script type="text/javascript" charset="utf8" src="<?= getenv('app.ASSETSPATH') ?>js/toaster/jquery.toaster.js"></script>
	<script type="text/javascript" charset="utf8" src="<?= getenv('app.ASSETSPATH') ?>js/247cashflowloader.js"></script>
	<script>
		var text = "Please wait...";
		var current_effect = "bounce";
		var getUrlParameter = function getUrlParameter(sParam) {
			var sPageURL = window.location.search.substring(1),
				sURLVariables = sPageURL.split('&'),
				sParameterName,
				i;

			for (i = 0; i < sURLVariables.length; i++) {
				sParameterName = sURLVariables[i].split('=');

				if (sParameterName[0] === sParam) {
					return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
				}
			}
			return false;
		};
		$(document).ready(function(){
			var error = getUrlParameter('error');
			if(error){
				if(error == 0){
					$.toaster({ priority : "success", title : "Success", message : "Settlement Processed Successfully" });
				}else if(error == 1){
					$.toaster({ priority : "danger", title : "Error", message : "Settlement Process Failed" });
				}else if(error == 2){
					$.toaster({ priority : "danger", title : "Error", message : "Something Went Wrong" });
				}
			}
		});
		$('body').on('submit','#settleOrder',function(e){
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
		</script>
</html>
