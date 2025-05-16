<div class="page-content-wrapper">
<!-- BEGIN CONTENT BODY -->
 
<div class="page-content">
	<!-- BEGIN PAGE HEADER-->
	<!-- BEGIN PAGE BAR -->
	<div class="page-bar">
		<ul class="page-breadcrumb">
			<li>
				<a href="index.html">Home</a>
				<i class="fa fa-circle"></i>
			</li>
			<li>
				<span>Disassembly Log</span>
			</li>
		</ul>
	</div>
	<div class="row">
		<div class="portlet ">		
			<div class="portlet-title">
				<div class="caption">
					Create Disassembly Log <?php $assembyID =  $this->uri->slash_segment(4);$assembyID =  str_replace('/', '', @$assembyID);?> <b> <?php echo $assembyID ? '(' . $assembyID . ')' : "";?> </div>	<a href="<?php echo base_url('products/assembly')?>" class="btn btn-primary pull-right">Back</a>	
			</div>
			<div class="portlet-body">
				<div class="table-container">
					<?php 
						if(!$disassemblyLogData){
							echo '<div class="alert alert-info" role="alert"><b>Log not Found!<b></div>';
						}else{
							echo "<pre>";print_r($disassemblyLogData); echo "</pre>";
						}
					?>
				</div>
			</div>
		</div>
		 
	</div>
</div>
<br><br><br><br><br><br>
<!-- END CONTENT BODY -->
</div>
<script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/divjs.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-beta4/html2canvas.js" type="text/javascript"></script>
<script>
  jQuery('.print').click(function(){
    jQuery('.containerss').printElement({});
  })
  jQuery(document).ready(function(){
	  var isPrintview = '<?php echo $this->uri->segment(5, 0);?>';
	  if((isPrintview) && isPrintview == 'printView'){
		  jQuery('.print').trigger('click');
	  }
  });
</script>
