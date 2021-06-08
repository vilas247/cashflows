<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.83.1">
    <title>Custom Payment Button</title>

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
          <div class="col-12 col-md-10 offset-md-1">
			<form action="<?= getenv('app.baseURL') ?>settings/updateCustomButton" id="updateCustomButton" method="POST" >
            <div class="card">
                  <div class="card-header">
                    <div class="row">
                        <div class="col-md-6 col-sm-6 col-6">
                            <p>Custom Payment Button</p>
                        </div>
                        <div class="col-md-6 text-end col-sm-6 col-6">
                            <p><a href="<?= getenv('app.baseURL') ?>home/dashboard"><i class="fas fa-arrow-left me-2"></i>Back to dashboard</a></p>
                        </div>
                    </div>
                  </div>
				  <?php
						$container_id = '.checkout-step--payment .checkout-view-header';
						$html_code = '<button type="submit" class="button button--action button--large button--slab optimizedCheckout-buttonPrimary" style="background-color: #424242;border-color: #424242;color: #fff;">CASHFLOWS PAYMENTS</button>';
						$css_prop = '#cashflowPaymentForm>button{
	display:block;
	background-color: #00FF00 !important;
	color: #000000 !important;
	border-color: #FF0000 !important;
}';
						$result_c = $buttonDetails;
						if(count($result_c) > 0){
							$result_c = $result_c[0];
						}else{
							$result_c['container_id'] = $container_id;
							$result_c['css_prop'] = $css_prop;
							$result_c['html_code'] = $html_code;
						}
						//print_r($result_c);exit;
						$enable = '';
						if(isset($result_c['is_enabled']) && $result_c['is_enabled'] == "1"){
							$enable = "checked";
						}
					?>
                  <div class="card-body">
                    <div class="row p-3">
                        <div class="col-md-6 col-sm-6 col-12 mb-3">
                            <p><strong>Container Id</strong></p>
                            <div class="my-3 col-md-12">
                              <textarea class="form-control" id="exampleFormControlTextarea1" name="container_id" rows="6"><?= @$result_c['container_id'] ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-12 mb-3">
                            <div class="d-flex justify-content-between">
                                <p><strong>Css Properties</strong></p>
								<div class="form-check form-switch">
                                  <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked" name="is_enabled" <?= $enable ?> />
                                </div>
                            </div>
                            <div class="my-3 col-md-12">
                              <textarea class="form-control" id="exampleFormControlTextarea1" name="css_prop" rows="6"><?= @$result_c['css_prop'] ?></textarea>
                            </div>
                        </div>
					</div>
					<div class="row p-3">
						<div class="col-md-12 col-sm-12 col-12 mb-3">
                            <div class="d-flex justify-content-between">
                                <p><strong>Html Code</strong></p>
                            </div>
                            <div class="my-3 col-md-12">
                              <textarea class="form-control" id="exampleFormControlTextarea1" name="html_code" rows="6"><?= @$result_c['html_code'] ?></textarea>
                            </div>
                        </div>
                    </div>
                  </div>
            </div>
            <div class="row mt-3">
              <div class="col-md-12 text-end">
				<div class="text-right">
					<button type="button" id="resetCustom" class="btn order-btn">Reset</button>&nbsp;&nbsp;&nbsp;
					<button type="submit" class="btn btn-purple">Update</button>
				</div>
              </div>
            </div>
			</form>
          </div>        
        </div>
      </div>
    </main>
  </body>
</html>
<script src="<?= getenv('app.ASSETSPATH') ?>js/jquery-min.js"></script>
<script src="<?= getenv('app.ASSETSPATH') ?>js/toaster/jquery.toaster.js"></script>
<script src="<?= getenv('app.ASSETSPATH') ?>js/247cashflowloader.js"></script>
<script>
	var text = "Please wait...";
	var current_effect = "bounce";
	var id = '<?= $container_id ?>';
	var css = '<?= base64_encode($css_prop) ?>';
	var html_code = '<?= $html_code ?>';
	$('body').on('click','#resetCustom',function(){
		$('body #container_id').val(id);
		$('body #css_prop').val(window.atob(css));
		$('body #html_code').val(html_code);
	});
	$('body').on('submit','#updateCustomButton',function(e){
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
		var updated = getUrlParameter('updated');
		if(updated){
			$.toaster({ priority : "success", title : "Success", message : "Cashflows Payments Custom button updated for your Store,Please wait for some time and check the changes" });
		}
	});
</script>