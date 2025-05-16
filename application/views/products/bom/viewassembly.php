<?php 
foreach($assignUserList as $assignUserLists){
	$assignedUsers[$assignUserLists['user_id']] =	ucfirst($assignUserLists['firstname']) . ' '.ucfirst($assignUserLists['lastname']);
}   ?>
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
				<span>Assembly Details</span>
			</li>
		</ul>
	</div>
	<!-- END PAGE BAR -->
	<!-- BEGIN PAGE TITLE--> 
	<div class="row">
		<div class="col-md-6">
			<h3 class="page-title"> Assembly
				  Details <?php $assembyID =  $this->uri->slash_segment(4);$assembyID =  str_replace('/', '', @$assembyID);?> <b> <?php echo $assembyID ? '(' . $assembyID . ')' : "";?></b> 
				 <input type='hidden' id='assembyID' class='assembyID' value=<?php echo $assembyID; ?>>
			</h3>
		</div>
		<div class="col-md-3">
			<span class="page-title">
				<div class="row">
					<div class="col-md-7">
						<?php //if($user_session_data['accessLabel'] == '1' || $user_session_data['accessLabel'] =='3'){?>
									<select class="form-control form-filter input-sm assignToUserId" name="assignToUserId">
										<option value="">Select User To Assign</option>
										<?php foreach ($assignUserList as $assignUsers){?>
										<option value="<?php echo $assignUsers['user_id'];?>" <?php if($allproducts['0']['assignToUserId']==$assignUsers['user_id']){echo "selected"; } ?>><?php echo ucfirst($assignUsers['firstname']) . ' '.ucfirst($assignUsers['lastname']); ?></option>
										<?php } ?>
									</select>
								<?php //} ?>
					</div>
					<div class="col-md-3">
						<input class="btn default submitassignTo pull-right" type="button" value="Submit"/> 
					</div>
				</div>
			</span>
		</div>
		<div class="col-md-2">
			<span class="page-title">
				<div class="row">
					<div class="col-md-9">
						<input class="btn default print pull-right" type="button" value="Print"/> 
					</div>
					<div class="col-md-3">
						<a href="<?php echo base_url('products/assembly')?>" class="btn btn-primary">Back</a>
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
										<th style="width:200px;">Assembly ID</th>
										<th>Product ID</th>
										<th>SKU</th>
										<th>Name</th>
										<th>Warehouse</th>
										<th>Bin Location</th>
										<th>Qty</th>
										<th>Recipe</th>
										<th>Assigned To</th>
										<th>Created By</th>
										<th>Status</th>
										<th>Created</th>
									</tr>
								</thead>
								<tbody>
								<?php 
							//	echo "<pre>getAllWarehouseLocation";print_r($getAllWarehouseLocation); echo "</pre>";die(__FILE__.' : Line No :'.__LINE__);
								foreach($allproducts as $allproduct){
									if($allproduct['isAssembly']){ ?>
										<tr>
											<td style="word-break:break-all;width:200px;"><?php echo $allproduct['createdId'];?></td>
											<td><?php echo $allproduct['productId'];?></td>
											<td style="word-break:break-all"><?php echo $allproduct['sku'];?></td>
											<td style="word-break:break-all"><?php echo $allproduct['name'];?></td>
											<td style="word-break:break-all"><?php echo $warehouseList[$allproduct['warehouse']]['name'];?></td>
											<td><?php echo $allproduct['locationId'] ? @$getAllWarehouseLocation[$allproduct['warehouse']][$allproduct['locationId']]['name'] : "";?></td>
											<td><?php echo $allproduct['qty'];?></td>
											<td><?php echo '('.$allproduct['receipId'].') '.@$recipeData[$allproduct['productId']][$allproduct['receipId']]['recipename'];?></td>
											<td><?php echo $allproduct['assignToUserId'] ? $assignedUsers[$allproduct['assignToUserId']] : $allproduct['assignToUserId'];?></td>
											<td><?php echo $allproduct['username'] ? $allproduct['username'] : 'N/A';?></td>
											<td><?php echo $allproduct['status'] == '1' ? '<span class="badge badge-success">Completed</span>': '<span class="badge badge-warning">WIP</span>';?></td>
											<td><?php echo date('M d,Y H:i:s',strtotime($allproduct['created']));?></td> 
										</tr>
									<?php } } ?>
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
									<th width="10%" >Warehouse</th>
									<th width="10%" >Bin Location</th>	
									<th width="5%" >Qty</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach($allproducts as $allproduct){
									if($allproduct['isAssembly'] == '0'){
									?>
										<tr>
											<td><?php echo $allproduct['receipId'];?></td>
											<td><?php echo $allproduct['productId'];?></td>
											<td><?php echo $allproduct['sku'];?></td>
											<td><?php echo $allproduct['name'];?></td>
											<td><?php echo $warehouseList[$allproduct['warehouse']]['name'];?></td>
											<td><?php echo @$getAllWarehouseLocation[$allproduct['autoAssemblyWipWarehouse']][$allproduct['locationId']]['name'] ? @$getAllWarehouseLocation[$allproduct['autoAssemblyWipWarehouse']][$allproduct['locationId']]['name'] : @$getAllWarehouseLocation[$allproduct['warehouse']][$allproduct['locationId']]['name'];?></td>
											<td><?php echo $allproduct['qty'];?></td>
										</tr>
									<?php } } ?>
							</tbody>
						</table>
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
   jQuery('.submitassignTo').click(function(){
     var assignToUserId = jQuery('.assignToUserId').val();
     var assembyID = jQuery('.assembyID').val();
	 if(assignToUserId){
		 $.ajax({
			 type: "POST",
			// data: assignToUserId,
			  url: base_url+"products/assembly/saveAssignUserId/"+assembyID+'~'+assignToUserId,
			  cache: false,
			  success: function(msg){
				  alert('User successfully updated.');
				 window.location.reload();
			  }
			});
	 }
  })
</script>
