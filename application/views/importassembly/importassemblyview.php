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
				<span>Import Assembly Details</span>
			</li>
		</ul>
	</div>
	<!-- END PAGE BAR -->
	<!-- BEGIN PAGE TITLE--> 
	<div class="row">
		<div class="col-md-10">
			<h3 class="page-title">Import Assembly Details<?php $assembyID =  $this->uri->slash_segment(4);$assembyID =  str_replace('/', '', @$assembyID);?> <b> <?php echo $assembyID ? '(' . $assembyID . ')' : "";?></b>
			</h3>
		</div>
		<div class="col-md-2">
			<span class="page-title">
				<div class="row">
					<div class="col-md-9">
						<input class="btn default print pull-right" type="button" value="Print"/> 
					</div>
					<div class="col-md-3">
						<a href="<?php echo base_url('products/assemblyimport')?>" class="btn btn-primary">Back</a>
					</div>
				</div>
			</span>
		</div>
	</div>
	<!-- END PAGE TITLE-->
	<!-- END PAGE HEADER-->
	<div class="row">
		<div class="col-md-12 containerss" id="containerss">
			<!-- Begin: life time stats -->
			<div class="portlet ">
				<div class="portlet-title">
					<div class="caption" style="width: 100%;">
						<div class="table-container">
							<table border="1" class="table table-striped table-bordered table-hover">
								<thead>
									<tr>
										<th>S.No</th>
										<th style="width:200px;">Assembly ID</th>
										<th>SKU</th>
										<th>Warehouse Name</th>
										<th>Receipe Name</th>
										<th>Qty</th>
										<th>Reference</th>
										<th>Status</th>
										<th>Created</th>
										<th>Message</th>
									</tr>
								</thead>
								<tbody>
								<?php
									$i = 1;
									foreach($data as $assemblyInfos){?>
										<tr>
											<td><?php echo $i++;?></td>
											<td><?php echo $assemblyInfos['createdAsseblyId'] == "" ? "N/A": $assemblyInfos['createdAsseblyId'];?></td>
											<td><?php echo $assemblyInfos['bomSku'];?></td>
											<td><?php echo $assemblyInfos['warehouseName'];?></td>
											<td><?php echo $assemblyInfos['receipeName'];?></td>
											<td><?php echo $assemblyInfos['qty'];?></td>
											<td><?php echo $assemblyInfos['reference'];?></td>
											<td><?php echo $assemblyInfos['status'];?></td>
											<td><?php echo $assemblyInfos['created'];?></td>
											<td><span style="color:red;"><?php echo $assemblyInfos['message'];?></span> </td>
										</tr>
								<?php } ?>
								</tbody>
							</table>
						</div>					
					</div>					
				</div>				
				 
			</div>
			<!-- End: life time stats -->
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