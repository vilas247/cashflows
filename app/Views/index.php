<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.83.1">
    <title>Login</title>

    <!-- Bootstrap core CSS -->
    <link href="<?= getenv('app.ASSETSPATH') ?>css/bootstrap.min.css" rel="stylesheet">

    <!-- font-awesome css-->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.1/css/all.css" integrity="sha384-O8whS3fhG2OnA5Kas0Y9l3cfpmYjapjI0E4theH4iuMD+pLhbf6JI0jIMfYcK3yZ" crossorigin="anonymous">

    <link href="<?= getenv('app.ASSETSPATH') ?>css/style.css" rel="stylesheet">
    <link href="<?= getenv('app.ASSETSPATH') ?>css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="<?= getenv('app.ASSETSPATH') ?>css/toaster/toaster.css">
	<link rel="stylesheet" href="<?= getenv('app.ASSETSPATH') ?>css/247cashflowloader.css">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">

  </head>
  <body>
    <main class="main-content">
      <div class="container">
        <div class="row">
          <div class="col-12 col-lg-4 col-md-6 offset-md-3 offset-lg-4">
            <div class="col-sm-6 mx-auto my-3" style="text-align: center;">
                <img src="<?= getenv('app.ASSETSPATH') ?>images/cash-flow.png">
            </div>
            <div class="login-form pg-30">
              <div class="text-center py-3">
                <h3>Getting Started</h3>
                <h5>Login to the dashboard</h5>
              </div>
              <form class="form-horizontal" id="validateForm" action="<?= getenv('app.baseURL') ?>settings/updatePaymetDetails" method="POST" >
                <div class="mb-3 input-group input-height">
                  <input type="text" class="form-control" id="confi-id" name="config_id" placeholder="Configuration ID">
                </div>
                <div class="mb-3 input-height" id="show_hide_password">
                  <input type="password" class="form-control" id="key" name="api_key" placeholder="Current API Key">
                </div>
                <div class="mb-3 input-group login-msg">
                  <small><b>How can I </b><a href="#">get my configuration id, current api key?</a></small>
                </div>
                <button type="submit" class="btn btn-purple d-block w-100 btn-lg">Submit</button>
              </form>
            </div>
          </div>        
        </div>
      </div>
    </main>
    <script src="<?= getenv('app.ASSETSPATH') ?>js/jquery-min.js"></script>
    <script src="<?= getenv('app.ASSETSPATH') ?>js/bootstrap.min.js"></script>
    <script src="<?= getenv('app.ASSETSPATH') ?>js/bootstrap.bundle.min.js"></script>
	<script src="<?= getenv('app.ASSETSPATH') ?>js/247cashflowloader.js"></script>
	<script src="<?= getenv('app.ASSETSPATH') ?>js/toaster/jquery.toaster.js"></script>
    <script type="text/javascript">
      $(document).ready(function() {
		var text = "Please wait...";
			var current_effect = "bounce";
		$("#show_hide_password a").on('click', function(event) {
              event.preventDefault();
              if($('#show_hide_password input').attr("type") == "text"){
                  $('#show_hide_password input').attr('type', 'password');
                  $('#show_hide_password i').addClass( "fa-eye-slash" );
                  $('#show_hide_password i').removeClass( "fa-eye" );
              }else if($('#show_hide_password input').attr("type") == "password"){
                  $('#show_hide_password input').attr('type', 'text');
                  $('#show_hide_password i').removeClass( "fa-eye-slash" );
                  $('#show_hide_password i').addClass( "fa-eye" );
              }
          });
			$('body').on('submit','#validateForm',function(e){
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
      });
    </script>
  </body>
</html>
