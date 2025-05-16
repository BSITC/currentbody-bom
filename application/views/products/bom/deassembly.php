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
				<span>Disassembly Listing</span>
			</li>
		</ul>
	</div>
	<div class="row">
		<div class="col-md-12">
			<!-- Begin: life time stats -->
			<div class="portlet ">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-shopping-cart"></i>Disassembly Listing </div>	
					<div class="actions">
						<a href="<?php echo base_url('products/deassembly/addNewDeassembly');?>" class="btn btn-circle btn-info"> 
							<i class="fa fa-plus"></i>
							<span class="hidden-xs"> Create New Disassembly  </span>
						</a>												
					</div>
				</div>
				<div class="portlet-body">
					<div class="table-container">						
						<table class="table table-striped table-bordered table-hover table-checkable" id="datatable_products">
							<thead>
								<tr role="row" class="heading">
									<th width="1%">
										<input type="checkbox" class="group-checkable"> </th>
									<th width="7%"> Created By </th>
									<th width="10%"> Disassembly &nbsp;ID </th>
									<th width="10%"> Product ID </th>
									<th width="15%"> SKU </th>	
									<th width="15%"> Name </th>	
									<th width="15%"> Status </th>	
									
									<th width="15%"> Created  </th>
									<th width="10%"> Actions </th>
								</tr>
								<tr role="row" class="filter">
									<td> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="username"> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="createdId"> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="productId"> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="sku"> </td>	
									<td><input type="text" class="form-control form-filter input-sm" name="name"> </td>	
									<td>
										<select class="form-control form-filter input-sm" name="status">
											<option value=""> Select Status </option>
											<option value="0"> Work in Progress </option>
											<option value="1"> Completed </option>
										</select>
									</td>
									<td>
										<div class="input-group date date-picker margin-bottom-5" data-date-format="yyyy-mm-dd">
											<input type="text" class="form-control form-filter input-sm" readonly name="updated_from" placeholder="From">
											<span class="input-group-btn">
												<button class="btn btn-sm default" type="button">
													<i class="fa fa-calendar"></i>
												</button>
											</span>
										</div>
										<div class="input-group date date-picker" data-date-format="yyyy-mm-dd">
											<input type="text" class="form-control form-filter input-sm" readonly name="updated_to" placeholder="To">
											<span class="input-group-btn">
												<button class="btn btn-sm default" type="button">
													<i class="fa fa-calendar"></i>
												</button>
											</span>
										</div>
									</td>
									<td>
										<div class="margin-bottom-5">
											<button class="btn btn-sm btn-success filter-submit margin-bottom">
												<i class="fa fa-search"></i> Search</button>
										<button class="btn btn-sm btn-default filter-cancel">
											<i class="fa fa-times"></i> Reset</button>
										</div>
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
<script src="<?php echo $this->config->item('script_url');?>assets/pages/scripts/ecommerce-productsdeassembly.js" type="text/javascript"></script>
