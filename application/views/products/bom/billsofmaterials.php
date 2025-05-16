<style>
.dropzone {
    min-height: auto;
    padding: 1.5rem 1.75rem;
    text-align: center;
    cursor: pointer;
    border: 1px dashed #009EF7;
    background-color: #F1FAFF;
    border-radius: 0.475rem !important;
}
input[type=file]:focus, input[type=checkbox]:focus, input[type=radio]:focus {
    outline: none;
    outline: none;
    outline-offset: 0px;
}
.btn-primary {
    color: #fff;
    background-color: #009EF7;
}
input.btn.btn-primary.uploadform {
    display: inline-block;
    margin-bottom: 0;
    font-weight: 400;
    text-align: center;
    vertical-align: middle;
    touch-action: manipulation;
    cursor: pointer;
    /* border: 1px solid transparent; */
    white-space: nowrap;
    padding: 10px 22px;
    font-size: 14px;
    line-height: 1.42857;
    border-radius: 4px;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    border-radius: 15px!important;
}
</style>
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
				<span>BOM Details</span>
			</li>
		</ul>
	</div>
	<div class="row">
		<div class="col-md-12">
			<!-- Begin: life time stats -->
			<div class="portlet ">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-shopping-cart"></i>BOM Listing </div>
					<div class="actions">
						<a href="<?php echo base_url('products/products/fetchProducts');?>" class="btn btn-circle btn-success btnactionsubmit"> 
							<i class="fa fa-refresh"></i>
							<span class="hidden-xs"> Fetch BOM </span>
						</a>
						<a href="<?php echo base_url('products/billsofmaterials/importboms');?>" class="btn btn-circle btn-info uplaodpreorder"> 
							<i class="fa fa-upload"></i>
							<span class="hidden-xs"> Import BOM </span>
						</a>

						<a href="<?php echo base_url('products/billsofmaterials/exportboms');?>" class="btn btn-circle green-meadow ">
							<i class="fa fa-download"></i>
							<span class="hidden-xs"> Export BOM </span>
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
									<th width="15%"> Product ID </th>
									<th width="15%"> SKU </th>
									<th width="15%"> Name </th>	
									<th width="10%"> Actions </th>
								</tr>
								<tr role="row" class="filter">
									<td> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="productId"> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="sku"> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="name"> </td>	
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
<div class="modal fade" id="popup" role="dialog">
    <div class="modal-dialog">        
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Upload BOM CSV file</h4>
        </div>
        <div class="modal-body">
           <form method="post"  action ="<?php echo base_url("products/billsofmaterials/importboms");?>" id="uploadform" enctype="multipart/form-data">
	    		<div class="dropzone" style="float: left;text-align: center;width: 100%;">
	       		<input required="true" type="file" name="uploadprefile"  accept=".csv" style="display: inline;" />
	       		<input type="Submit" class="btn btn-primary uploadform" value="Upload" value="Submit" /> 
	       	</div>
	       </form>                       
        </div>
        <div class="modal-footer">
          
        </div>
      </div>                  
    </div>
</div>		
<script src="<?php echo $this->config->item('script_url');?>assets/pages/scripts/ecommerce-productsbillsofmaterial.js" type="text/javascript"></script>
<script type="text/javascript">
	jQuery(".uplaodpreorder").click(function(e){
		e.preventDefault();
		jQuery("#popup").modal('show');
	})
</script>
