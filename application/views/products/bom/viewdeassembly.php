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
				<span>Disassembly  Details</span>
			</li>
		</ul>
	</div>
	<!-- END PAGE BAR -->
	<!-- BEGIN PAGE TITLE--> 
	<div class="row">
		<div class="col-md-2">
			<h3 class="page-title"> Disassembly Details
			</h3>
		</div>
		<div class="col-md-10">
			<span class="page-title pull-right">
				<input class="btn default print" type="button" value="Print"/>
				<!--input class="btn default" type="button" value="Download PDF" id="makePDF"/-->
			</span>
		</div>
	</div>
	<!-- END PAGE TITLE-->
	<!-- END PAGE HEADER-->
	<div class="row">
		<div class="col-md-12 containerss" id="containerss">
			<form action="<?php echo base_url('products/assembly/saveassembly');?>" method = "post" id="assemblyform" >
			<!-- Begin: life time stats -->
			<div class="portlet ">
				<div class="portlet-title">
					<div class="caption" style="width: 100%;">
						<div class="table-container">
							<table class="table table-striped table-bordered table-checkable">
								<thead>
									<tr>
										<th style="word-break:break-all;width:10%;">Disassembly ID</th>
										<th width="10%">Product ID</th>
										<th width="10%">SKU</th>
										<th width="15%">Name</th>
										<th width="12%">Warehouse</th>
										<th width="12%" >Bin Location</th>
										<th width="5%">Qty</th>
										<th width="6%">Recipe</th>	
										<th width="5%">Created By</th>		
										<th width="5%">Status</th>
										<th width="17%">Created</th>											
									</tr>
								</thead>
								<tbody>
								<?php 
								foreach($allproducts as $allproduct){
									if($allproduct['isDeassembly']){ ?> 
										<tr>
											<td style="word-break:break-all;width:250px;"><?php echo $allproduct['createdId'];?></td>
											<td><?php echo $allproduct['productId'];?></td>
											<td><?php echo $allproduct['sku'];?></td>
											<td><?php echo $allproduct['name'];?></td>
											<td><?php echo $warehouseList[$allproduct['warehouse']]['name'];?></td>
											<td><?php echo $allproduct['locationId'] ? @$getAllWarehouseLocation[$allproduct['warehouse']][$allproduct['locationId']]['name'] : "";?></td>
											<td><?php echo $allproduct['qty'];?></td>
											<td><?php echo '('.$allproduct['receipId'].') '.$recipeData[$allproduct['productId']][$allproduct['receipId']]['recipename'];?></td>
											<td><?php echo $allproduct['username'] ? $allproduct['username'] : 'N/A';?></td>
											<td><?php echo $allproduct['status'] == '1' ? '<span class="badge badge-success">Completed</span>': '<span class="badge badge-info">Work in Progress</span>';?></td>
											<td><?php echo date('M d,Y H:i:s',strtotime($allproduct['created']));?></td> 
										</tr>
									<?php }
								}
								
								?>
									
								</tbody>
							</table>
						</div>					
					</div>					
				</div>				
				<div class="portlet-body">
					<div class="portlet-title">
						<div class="caption" style="width: 100%;">
							<h3 class="page-title"> Component Details</h3>			
						</div>					
					</div>
					<div class="table-container">	
						<table class="table table-striped table-bordered table-hover table-checkable receipecontainer datatable_products" id="datatable_products">	
							<thead>
								<tr>
									<th width="5%">Recipe #</th>
									<th width="20%">Comp. Product ID</th>
									<th width="20%">Comp. SKU</th>
									<th width="20%">Comp. Name</th>
									<th width="20%" >Warehouse</th>
									<th width="10%" >Bin Location</th>	
									<th width="5%" >Qty</th>									
								</tr>
							</thead>
							<tbody>								
								<?php 
								foreach($allproducts as $allproduct){
									if($allproduct['isDeassembly'] == '0'){ ?> 
										<tr>
											<td><?php echo $allproduct['receipId'];?></td>
											<td><?php echo $allproduct['productId'];?></td>
											<td><?php echo $allproduct['sku'];?></td>
											<td><?php echo $allproduct['name'];?></td>
											<td><?php echo $warehouseList[$allproduct['warehouse']]['name'];?></td>
											<td><?php echo @$getAllWarehouseLocation[$allproduct['autoAssemblyWipWarehouse']][$allproduct['locationId']]['name'] ? @$getAllWarehouseLocation[$allproduct['autoAssemblyWipWarehouse']][$allproduct['locationId']]['name'] : @$getAllWarehouseLocation[$allproduct['warehouse']][$allproduct['locationId']]['name'];?></td>
											<td><?php echo $allproduct['qty'];?></td>
										</tr>
									<?php }
								}
								
								?>
							</tbody>							
						</table>
					</div>
				</div>
			</div>
			
		</form>
			<!-- End: life time stats -->
		</div>
	</div>
</div>
<br><br><br><br><br><br>
<!-- END CONTENT BODY -->
</div>
<script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
<script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/divjs.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-beta4/html2canvas.js" type="text/javascript"></script>
<script>
  $('.print').click(function(){
    $('.containerss').printElement({
    });
  })
</script>
