<!doctype html>
<?php
$global_config = $this->session->userdata('global_config');
$login_user_data = $this->session->userdata('login_user_data');
?>
<html>
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <meta name="description" content="Explore Peoplevox Integration by b.solutions, designed to streamline your e-commerce operations and enhance your online store's functionality.">

        <!-- Open Graph Meta Tags -->
        <meta property="og:title" content="Brightpearl | Peoplevox Integration by b.solutions" />
        <meta property="og:description" content="Your b.solutions Brightpearl | Peoplevox integration is here." />

        <meta property="og:type" content="website" />
        <meta property="og:url" content="<?php echo base_url(); ?>/users/login" />
        <meta property="og:image" content="<?php echo $this->config->item('script_url');?>assets/interface/images/favicon.png" />

        <!-- Add additional tags as necessary -->
        <link rel="shortcut icon" href="<?php echo $this->config->item('script_url');?>assets/interface/images/favicon.png" />
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="<?php echo $this->config->item('script_url');?>assets/interface/css/bootstrap.min.css" type="text/css">
        <link rel="stylesheet" href="<?php echo $this->config->item('script_url');?>assets/interface/css/font-4-6-awesome.min.css" type="text/css">
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"> 
        <link rel="stylesheet" href="<?php echo $this->config->item('script_url');?>assets/interface/css/styles.css" type="text/css">
        <link rel="stylesheet" href="<?php echo $this->config->item('script_url');?>assets/interface/css/sideBar.css" type="text/css">
        <link rel="stylesheet" href="<?php echo $this->config->item('script_url');?>assets/interface/css/rightBar.css" type="text/css">
        <link rel="stylesheet" href="<?php echo $this->config->item('script_url');?>assets/interface/css/login.css" type="text/css">
        <script src="<?php echo $this->config->item('script_url');?>assets/interface/ag-grid/ag-grid-community.min.js"></script>
         <title><?php echo @($global_config['app_name'])?($global_config['app_name']):'b.solutions';?></title>
        <style>
            .ag-column-drop-wrapper {
                display: none;
            }
        </style>
    </head>
  <body>
    <style type="text/css">
        .btn-primary{
            text-transform:none !important;
        }
    </style>
    <section class="loginPage" style="background:#212854;">
       
        <div class="row no-gutters"  style="
/*        background: url('<?php echo $this->config->item('script_url');?>assets/interface/images/bg2.png');*/
        background-repeat: no-repeat;
        background-size: cover;
    background-attachment: fixed;
    background-position: center;
        ">
            <div class="col-md-12 col-lg-7 col-xs-12 logn">
                 <div class="logo-default">
                    <a href="https://b-solutions.io/" target="_blank"><img src="<?php echo $this->config->item('script_url');?>assets/interface/images/bsitc-logo-left.jpg" style="width: 15em!important;margin-top: 20px;"></a>
                </div>
                <div class="loginLBox" style="background-color: transparent!important;"> 
                    <!-- <div class="mb-3"> <img src="<?php //echo $this->config->item('script_url');?>/assets/interface/images/logofull.png" alt="logo"></div> -->
                    <form  action="javascript:;" class="login-form loginForm" method="post" style="min-width:510px;min-height:23em;<?php if(!empty($login_user_data['user_id'])) { echo "display:none;"; } ?>">
                        <h2><?php echo @$global_config['app_name'];?></h2>
                        <!-- <h2>Brightpearl | Peoplevox Integration</h2> -->
                        <div class="alert alert-danger display-hide invalid-credentials">
                            <button class="close" data-close="alert"></button>
                            <span>Enter any username and password. </span>
                        </div>
                        <div class="alert alert-danger display-hide csrf-failed">
                            <button class="close" data-close="alert"></button>
                            <span>Invalid Request!</span>
                        </div>
                        <div class="form-group">
                          <label for="username">Username </label>
                          <input type="text" name="username" class="form-control" id="inputEmail" id="username" required placeholder="Enter username"> 
                        </div>
                        <div class="form-group">
                          <label for="password">Password</label>
                          <input type="password" name="password" class="form-control" placeholder="Enter password"  id="inputPassword" >
                        </div>
                        <button type="submit" id="btnSubmit" class="btn btn-primary" style="text-transform:none!important;">Sign In</button>
                    </form>
                </div>
               
            </div>
            
            <div class="col-md-12 col-lg-5 col-xs-12 logn" style="background: #fff;">
                <div class="loginLBox" style="background: url('<?php echo $this->config->item('script_url');?>assets/interface/images/BP_BOM.gif');
                    background-repeat: no-repeat;
                    background-size: 70%;
                background-attachment: local;
                background-position: center;
                    "></div>
            </div>
            
            <div class="neW_cls">
                <div class="loginCopyRight">
                    Copyright &copy; b.solutions <?= date('Y'); ?>
                </div>
                 <div class="loginCopyRight new" >
                    <a href="https://b-solutions.io/"  target="_blank" style="color: #fc664a;z-index: revert;">b-solutions.io</a>
                </div>
            </div>

        </div>
    </section>
 
    <script src="<?php echo $this->config->item('script_url');?>assets/interface/js/jquery.slim.min.js"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/interface/js/bootstrap.min.js"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/jquery-validation/js/jquery.validate.min.js" type="text/javascript"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/jquery-validation/js/additional-methods.min.js" type="text/javascript"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/backstretch/jquery.backstretch.min.js" type="text/javascript"></script>
    <script src="<?php echo $this->config->item('script_url');?>assets/global/scripts/app.min.js" type="text/javascript"></script>

    <script>
        var baseurl = "<?php echo base_url();?>";
        jQuery("body").addClass("login_body");

        jQuery(".login-form").on("submit",function(e){
            e.preventDefault();
            jQuery(".invalid-credentials").hide(); 
            jQuery(".csrf-failed").hide();
            var username = jQuery("#inputEmail").val();
            var password = jQuery("#inputPassword").val();
            jQuery("#btnSubmit").attr("disabled","disabled").html("Please wait..");
            $.ajax({
                url: '<?php echo base_url('users/login/checkLogin'); ?>',
                type: 'POST',
                data: jQuery( ".login-form" ).serialize(),
                success: function(response) {
                    // response = JSON.parse(response); 
                    // console.log(response);
                    if(response == "1"){
                       window.location= baseurl + "/products/assembly";
                    }
                    else{
                        jQuery("#btnSubmit").removeAttr("disabled").html("Sign In");
                        jQuery(".invalid-credentials").show();
                    }
                },
                error: function(xhr, status, error) {
                    // Handle error xhr.responseText
                    setTimeout(function () {
                        window.location= baseurl
                    }, 2000);
                }
            });
            //return false;
        });

        $(document).ready(function()
        {
            $('.login-bg').backstretch([
                    "<?php echo base_url();?>/assets/pages/img/login/bg1.jpg",
                "<?php echo base_url();?>/assets/pages/img/login/bg2.jpg",
                "<?php echo base_url();?>/assets/pages/img/login/bg3.jpg"
                ], {
                    fade: 1000,
                    duration: 8000
                }
            );
        
            $('#clickmewow').click(function() {
                $('#radio1003').attr('checked', 'checked');
            });
            
            $('.login-form').each(function() {
                $(this).find('input').keypress(function(e) {
                    // Enter pressed?
                    if(e.which == 10 || e.which == 13) {
                        $(this).submit();
                    }
                });
            });
        });
    </script>
  </body>
</html>
