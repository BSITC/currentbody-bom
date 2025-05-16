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
				<span>Product Details</span>
			</li>
		</ul>
	</div>
	<!-- END PAGE BAR -->
	<!-- BEGIN PAGE TITLE-->
	<h3 class="page-title"> Products
		<small>Products</small>
	</h3>
	<!-- END PAGE TITLE-->
	<!-- END PAGE HEADER-->
	<div class="row">
		<div class="col-md-12">
			<!-- Begin: life time stats -->
			<div class="portlet ">				
				<div class="portlet-body">
					<div class="table-container">
						<div class="table-actions-wrapper">
							<span> </span>
							<select class="table-group-action-input form-control input-inline input-small input-sm">
								<option value="">Select...</option>
								<option value="0">Pending</option>
								<option value="1">Sent</option>
								<option value="2">Updated</option>
								<option value="3">Error</option>
								<option value="4">Archive</option>
							</select>
							<button class="btn btn-sm btn-success table-group-action-submit">
								<i class="fa fa-check"></i> Submit</button>
						</div>
						<table class="table table-striped table-bordered table-hover table-checkable" id="datatable_products">
							<thead>
								<tr role="row" class="heading">
									<th width="1%">
										<input type="checkbox" class="group-checkable"> </th>
									<th width="15%"> Product&nbsp;Name </th>
									<th width="10%"> Status </th>
								</tr>
								<tr role="row" class="filter">
									<td> </td>									
									<td><input type="text" class="form-control form-filter input-sm" name="newSku"> </td>
									<td>
										<select name="prebook" class="form-control form-filter input-sm">
											<option value="">Select...</option>
											<option value="0">No</option>
											<option value="1">Yes</option>											
										</select>
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
<script src="<?php echo $this->config->item('script_url');?>assets/pages/scripts/ecommerce-preproducts.js" type="text/javascript"></script>
