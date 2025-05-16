<?php
$user_session_data = @$this->session->userdata('login_user_data');	
?>
<div class="page-header navbar navbar-fixed-top">
<!-- BEGIN HEADER INNER -->
<div class="page-header-inner ">
	<!-- BEGIN LOGO -->
	<div class="page-logo">
		<a href="<?php echo base_url('dashboard');?>">
			<img width="140px" style="margin:8px 0 0" src="<?php echo $this->config->item('script_url') . 'assets/layouts/layout/img/logo-small-Brightpearl.png';?>" alt="logo" class="logo-default" /> </a>
		<div class="menu-toggler sidebar-toggler"> </div>
	</div>
	<!-- END LOGO -->
	<!-- BEGIN RESPONSIVE MENU TOGGLER -->
	<a href="javascript:;" class="menu-toggler responsive-toggler" data-toggle="collapse" data-target=".navbar-collapse"> </a>
	<!-- END RESPONSIVE MENU TOGGLER -->
	<!-- BEGIN TOP NAVIGATION MENU -->
	<div class="top-menu">
		<ul class="nav navbar-nav pull-right">
			<!-- BEGIN NOTIFICATION DROPDOWN -->			
			<!-- BEGIN INBOX DROPDOWN -->
			<!-- BEGIN USER LOGIN DROPDOWN -->
			<!-- DOC: Apply "dropdown-dark" class after below "dropdown-extended" to change the dropdown styte -->
			<li class="dropdown dropdown-user">
				<a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
					<img alt="" class="img-circle" src="<?php echo $user_session_data['profileimage'];?>" />
					<span class="username username-hide-on-mobile"> <?php echo $user_session_data['firstname'];?> </span>
					<i class="fa fa-angle-down"></i>
				</a>
				<ul class="dropdown-menu dropdown-menu-default">
					<li>
						<a href="<?php echo base_url();?>users/profile">
							<i class="icon-user"></i> My Profile </a>
					</li>					
					<li>
						<a href="<?php echo base_url();?>users/login/logout">
							<i class="icon-key"></i> Log Out </a>
					</li>
				</ul>
			</li>
			<!-- END USER LOGIN DROPDOWN -->
		</ul>
	</div>
	<!-- END TOP NAVIGATION MENU -->
</div>
<!-- END HEADER INNER -->
</div>
<!-- END HEADER -->
<!-- BEGIN HEADER & CONTENT DIVIDER -->
<div class="clearfix"> </div>
<!-- END HEADER & CONTENT DIVIDER -->
<!-- BEGIN CONTAINER -->