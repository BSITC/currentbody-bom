<?php 
$user_session_data = $this->session->userdata('login_user_data');
?>
<!-- BEGIN SIDEBAR -->
<div class="page-sidebar-wrapper">               
	<div class="page-sidebar navbar-collapse collapse sidebarnewcss">                   
		<ul class="page-sidebar-menu  page-header-fixed page-sidebar-menu-closed" data-keep-expanded="true" data-auto-scroll="true" data-slide-speed="200" style="padding-top: 20px">
			<!-- DOC: To remove the sidebar toggler from the sidebar you just need to completely remove the below "sidebar-toggler-wrapper" LI element -->
			<li class="sidebar-toggler-wrapper hide">
				<!-- BEGIN SIDEBAR TOGGLER BUTTON -->
				<div class="sidebar-toggler"> </div>
				<!-- END SIDEBAR TOGGLER BUTTON -->
			</li>
		  	<li class="nav-item dashboard"> 
				<a href="<?php echo base_url();?>dashboard" class="nav-link ">
					<i class="icon-home"></i>
					<span class="title">Dashboard</span>
					<span class="selected"></span>
				</a>
			</li>
			<?php if($user_session_data['accessLabel'] == '1'){?>
			<li class="nav-item account">
				<a href="javascript:;" class="nav-link nav-toggle">
					<i class="fa fa-cogs"></i>
					<span class="title">Account Settings</span>
					<span class="selected"></span>
					<span class="arrow open"></span>
				</a>
				<ul class="sub-menu">		
					<li class="nav-item">
						<a href="javascript:;" class="nav-link nav-toggle">
							<i class="fa fa-sun-o"></i>
							<span class="title"><?php echo $this->globalConfig['account1Name'];?> Settings</span>
							<span class="selected"></span>
							<span class="arrow open"></span>
						</a>
						<ul class="sub-menu">		
							<li class="nav-item  ">
								<a href="<?php echo base_url('account/account1/account');?>" class="nav-link ">
									<i class="glyphicon glyphicon-chevron-right "></i>
									<span class="title">Accounts</span>
								</a>
							</li>
							<li class="nav-item  ">
								<a href="<?php echo base_url('account/account1/config');?>" class="nav-link ">
									<i class="glyphicon glyphicon-chevron-right "></i>
									<span class="title">Default Configuration</span>
								</a>
							</li>

						</ul>
					</li>
					<?php if($user_session_data['accessLabel'] == '1' || $user_session_data['accessLabel'] =='3'){?>
						<li class="nav-item">
							<a href="javascript:;" class="nav-link nav-toggle">
								<i class="icon-user"></i>
								<span class="title">Admin Capabilities</span>
								<span class="selected"></span>
								<span class="arrow open"></span>
							</a>
							<ul class="sub-menu">		
								<li class="nav-item">
									<a href="<?php echo base_url('users/users');?>" class="nav-link">
										<i class="glyphicon glyphicon-chevron-right"></i>
										<span class="title">Manage Users</span>
									</a>
								</li>
							</ul>
						</li>
					<?php } ?>
					<?php if($this->globalConfig['enableAccountSettings2']){ ?>
					<li class="nav-item">
						<a href="javascript:;" class="nav-link nav-toggle">
							<img width="16px" src="<?php echo base_url('assets/images/shopify.png');?>">
							<span class="title"><?php echo $this->globalConfig['account2Name'];?> Settings</span>
							<span class="selected"></span>
							<span class="arrow open"></span>
						</a>
						<ul class="sub-menu">		
							<li class="nav-item  ">
								<a href="<?php echo base_url('account/account2/account');?>" class="nav-link ">
									<i class="glyphicon glyphicon-chevron-right "></i>
									<span class="title">Accounts</span>
								</a>
							</li>
							<li class="nav-item  ">
								<a href="<?php echo base_url('account/account2/config');?>" class="nav-link ">
									<i class="glyphicon glyphicon-chevron-right "></i>
									<span class="title">Default Configuration</span>
								</a>
							</li>

						</ul>
					</li>	
					<?php } ?>				
				</ul>
			</li>
			<?php } ?>
			<li class="nav-item billsofmaterials">
				<a href="<?php echo base_url();?>products/billsofmaterials" class="nav-link ">
					<i class="icon-graph"></i>
					<span class="title">BOM Listing</span>
					<span class="selected"></span>
				</a>
			</li>
			<li class="nav-item assembly">
				<a href="<?php echo base_url();?>products/assembly" class="nav-link ">
					<i class="fa fa-chevron-right"></i>
					<span class="title">Assembly Listing</span>
					<span class="selected"></span>
				</a>
			</li>
			<?php 
			if($this->globalConfig['enableImportAssembly']){ ?>
				<li class="nav-item assemblyimport">
					<a href="<?php echo base_url();?>products/assemblyimport" class="nav-link ">
						<i class="fa fa-download"></i>
						<span class="title">Import Assembly Listing</span>
						<span class="selected"></span>
					</a>
				</li>
			<?php } ?>
			<li class="nav-item deassembly">
				<a href="<?php echo base_url();?>products/deassembly" class="nav-link ">
					<i class="fa fa-chevron-left"></i>
					<span class="title">Disassembly Listing</span>
					<span class="selected"></span>
				</a>
			</li>
			<?php if($this->globalConfig['enableProduct']){ ?>
				<li class="nav-item products hide">				
					<a href="javascript:;" class="nav-link nav-toggle">
						<i class="icon-graph"></i>
						<span class="title">Products</span>
						<span class="selected"></span>
						<span class="arrow open"></span>
					</a>

					<ul class="sub-menu">					
						<li class="nav-item products">
							<a href="<?php echo base_url();?>products/products" class="nav-link ">
								<i class="icon-graph"></i>
								<span class="title">Products</span>
							</a>
						</li>
					</ul>
				</li>   
			<?php } ?>
 
			<?php if($this->globalConfig['enableSalesOrder']){ ?>
			<li class="nav-item sales">
				<a href="<?php echo base_url();?>sales/sales" class="nav-link ">
					<i class="icon-basket"></i>
					<span class="title">Sales Order Listing</span>
					<span class="selected"></span>
				</a>
			</li> 
			<?php } ?> 
	   </ul>
		<!-- END SIDEBAR MENU -->
		<!-- END SIDEBAR MENU -->
	</div>
	<!-- END SIDEBAR -->
</div>
<!-- END SIDEBAR -->
<?php	
$controllerName = @$this->router->class;
$allDirectory = @json_encode(array_filter(explode("/",$this->router->directory)));
$method = @$this->router->method;
?>
<script>	
	var directory =<?php echo $allDirectory;?>;
	var controllerName = '<?php echo $controllerName;?>';
	var method = '<?php echo $method;?>';
	var activeClass = '';
	for (index in directory) {
		value = directory[index];
		if(jQuery(activeClass + " ."+value).length){
			activeClass += " ." + value;
		}
	}
	var activeClass1 = '';	
	if(controllerName !=""){
		if(jQuery(activeClass + " ."+controllerName).length){
			activeClass += " ." + controllerName;
		}		
	}
	if( (method !="") && (method != 'index')){
		if(jQuery(activeClass + " ."+method).length){
			activeClass += " ." + method;
		}		
	}
	if(activeClass != ""){
		jQuery(activeClass).addClass('active open');
	}
</script>