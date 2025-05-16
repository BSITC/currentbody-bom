<html>
	<head>
        <meta charset="utf-8" />
         <title><?php echo @($this->session->userdata('global_config')['app_name'])?($this->session->userdata('global_config')['app_name']):'BSITC';?></title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <script>
        var base_url = "<?php echo base_url();?>";
        </script>
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&subset=all" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config->item('script_url');?>assets/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config->item('script_url');?>assets/global/plugins/simple-line-icons/simple-line-icons.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config->item('script_url');?>assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config->item('script_url');?>assets/global/plugins/bootstrap-switch/css/bootstrap-switch.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config->item('script_url');?>assets/global/plugins/bootstrap-fileinput/bootstrap-fileinput.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config->item('script_url');?>assets/global/css/components.min.css" rel="stylesheet" id="style_components" type="text/css" />
        <link href="<?php echo $this->config->item('script_url');?>assets/global/css/plugins.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config->item('script_url');?>assets/layouts/layout/css/layout.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo $this->config->item('script_url');?>assets/layouts/layout/css/themes/blue.min.css" rel="stylesheet" type="text/css" id="style_color" />
        <link href="<?php echo $this->config->item('script_url');?>assets/layouts/layout/css/custom.min.css" rel="stylesheet" type="text/css" />
        <link rel="shortcut icon" href="favicon.ico" />

        <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/jquery.min.js" type="text/javascript"></script>
		<script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/bootstrap-fileinput/bootstrap-fileinput.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/jquery.sparkline.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/global/scripts/app.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
        <script src="<?php echo $this->config->item('script_url');?>assets/layouts/layout/scripts/script.js" type="text/javascript"></script>	
		
		
		<script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js" type="text/javascript"></script>
		<script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
		<script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/moment.min.js" type="text/javascript"></script>
		<script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.js" type="text/javascript"></script>
		<script src="<?php echo $this->config->item('script_url');?>assets/global/scripts/datatable.js" type="text/javascript"></script>
		<script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
		<link href="<?php echo $this->config->item('script_url');?>extracss/2extracss.css" rel="stylesheet" type="text/css" />
		<style>
			.page-content-wrapper.createassembly {
				background-color: #fff;
			}
		</style>
	</head>
	 <script type="text/javascript">
        jsOrder =  [1, "desc"];
        loadUrl ="";
    </script>
	<?php
		if(@$this->user_session_data){
		echo '<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-sidebar-closed">';
	}
	else{
		$method = @$this->router->method;
		if($method == 'allAssemblyReport'){
			echo '<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-sidebar-closed">';
		}
		else{
			echo '<body>';
		}
	}
	?>