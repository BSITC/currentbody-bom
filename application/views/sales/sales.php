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
				<span>Sales Order Listing</span>
			</li>
		</ul>
	</div>
	<div class="row">
		<div class="col-md-12">
			<!-- Begin: life time stats -->
			<div class="portlet ">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-shopping-cart"></i>
							Sales Order Listing 
					</div>
				</div>
				<div class="portlet-body">
					<div class="table-container">
						<div class="table-actions-wrapper">
							<span> </span>
							<select class="table-group-action-input form-control input-inline input-small input-sm">
								<option value="">Select...</option>
								<option value="0">Pending</option>
								<option value="1">Sent</option>
							</select>
							<button class="btn btn-sm btn-success table-group-action-submit">
								<i class="fa fa-check"></i> Submit</button>
						</div>
						<table class="table table-striped table-bordered table-hover table-checkable" id="datatable_products">
							<thead>
								<tr role="row" class="heading">
									<th width="1%"><input type="checkbox" class="group-checkable"> </th>
									<th width="10%"> SO ID </th>
									<th width="10%"> Reference </th>
									<th width="10%"> Warehouse </th>
									<th width="15%"> Created </th>
									<th width="10%"> Status </th>
									<th width="10%"> Actions </th>
								</tr>
								<tr role="row" class="filter">
									<td> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="orderId"> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="reference"> </td>
									<td>
										<select class="form-control form-filter input-sm"  name="warehouse">
											<option value=""> Select Warehouse </option>
											<?php foreach($warehouseList as $warehouseIds => $warehouse){?>
												<option value="<?php echo $warehouseIds;?>"><?php echo $warehouse['warehouseName'];?> </option>
											<?php }?>
										</select>
									</td>
									<td><div class="input-group date date-picker margin-bottom-5" data-date-format="yyyy-mm-dd">	<input type="text" class="form-control form-filter input-sm" readonly name="updated_from" placeholder="From">	<span class="input-group-btn">		<button class="btn btn-sm default" type="button">			<i class="fa fa-calendar"></i>		</button>	</span></div><div class="input-group date date-picker" data-date-format="yyyy-mm-dd">	<input type="text" class="form-control form-filter input-sm" readonly name="updated_to" placeholder="To">	<span class="input-group-btn">		<button class="btn btn-sm default" type="button">			<i class="fa fa-calendar"></i>		</button>	</span></div>
									</td>
									<td><select name="status" class="form-control form-filter input-sm">	<option value="">Select...</option>	<option value="0">Pending</option>	<option value="1">Allocation All Allocated</option><option value="2">Allocation Not Required</option><option value="3">Allocation None Allocated</option><option value="4">Allocation Part Allocated</option>	</select>
									</td>
									 
									<td><div class="margin-bottom-5">	<button class="btn btn-sm btn-success filter-submit margin-bottom">		<i class="fa fa-search"></i> Search</button></div><button class="btn btn-sm btn-default filter-cancel">	<i class="fa fa-times"></i> Reset</button>
									</td>
								</tr>
							</thead>
							<tbody> </tbody>
						</table>
					</div>
				</div>
			
			</div>
			<!-- End: life time stats -->
		</div>
	</div>
</div>
<!-- END CONTENT BODY -->
</div>		
<script type="text/javascript">
 jsOrder =  [0, "desc"];
	loadUrl = '<?php echo base_url('sales/sales/getSales');?>';
</script>
<script src="<?php echo $this->config->item('script_url');?>assets/pages/scripts/ecommerce-global.js" type="text/javascript"></script>