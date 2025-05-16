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
				<span>Import Assembly</span>
			</li>
		</ul>
	</div>
	<div class="row">
		<div class="col-md-12">
			<!-- Begin: life time stats -->
			<div class="portlet ">
				<?php if($this->session->flashdata('errormessage')){?>
					<div class="alert alert-danger fade in alert-dismissible" style="margin-top:18px;">
						<a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
						<strong>Error!</strong> <?php echo $this->session->flashdata('errormessage'); ?>
					</div>
				<?php } ?>
				<?php if($this->session->flashdata('successmessage')){?>
					<div class="alert alert-success fade in alert-dismissible" style="margin-top:18px;">
						<a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
						<strong>Success!</strong> <?php echo $this->session->flashdata('successmessage'); ?>
					</div>
				<?php } ?>
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-download"></i>Import Assembly Listing </div>
					<div class="actions">
						<a href="<?php echo base_url('products/assemblyimport/saveImportDatas');?>" class="btn btn-circle btn-info uplaodpreorder"> 
							<i class="fa fa-download"></i>
							<span class="hidden-xs"> Import Assembly </span>
						</a>
						<a href="<?php echo base_url('products/assemblyimport/processImportedAssemblies');?>" class="btn btn-circle btn-info"> 
							<i class="fa fa-download"></i>
							<span class="hidden-xs"> Process Assembly </span>
						</a>
						<a href="<?php echo base_url('products/assemblyimport/downloadSample');?>" class="btn btn-circle btn-info"> 
							<i class="fa fa-upload"></i>
							<span class="hidden-xs"> Download Sample </span>
						</a>
					</div>
				</div>
				<div class="portlet-body">
					<div class="table-container">	
						<div class="table-actions-wrapper">
							<span> </span>
							<select class="table-group-action-input form-control input-inline input-small input-sm">
								<option value="">Select...</option>
								<option value="3">Reviewed</option>
							</select>
							<button class="btn btn-sm btn-success table-group-action-submit">
								<i class="fa fa-check"></i> Submit</button>
						</div>					
						<table class="table table-striped table-bordered table-hover table-checkable" id="datatable_products">
							<thead>
								<tr role="row" class="heading">
									<th width="1%">
										<input type="checkbox" class="group-checkable"> </th>
									<th width="15%"> Imported File Name </th>
									<th width="15%"> No. of Bom </th>
									<th width="15%"> Is Processed? </th>	
									<th width="15%"> Uploaded Time </th>	
									<th width="10%"> Actions </th>
								</tr>
								<tr role="row" class="filter">
									<td> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="toShowFilename"></td>
									<td><input type="text" class="form-control form-filter input-sm" name="totalBom"></td>
									<td>
										<select name="isProcessed" class="form-control form-filter input-sm">	<option value="">Select...</option>	<option value="0">Pending</option>	<option value="1">Processed</option><option value="processedwitherrors">Processed with errors</option><option value="3">Reviewed</option></select>
									</td>
									<td>
										<div class="input-group date date-picker" data-date-format="yyyy-mm-dd">	<input type="text" class="form-control form-filter input-sm" readonly name="updated_from" placeholder="From"><span class="input-group-btn">		<button class="btn btn-sm default" type="button"><i class="fa fa-calendar"></i></button></span></div>
										<div class="input-group date date-picker" data-date-format="yyyy-mm-dd"><input type="text" class="form-control form-filter input-sm" readonly name="updated_to" placeholder="To">	<span class="input-group-btn"><button class="btn btn-sm default" type="button"><i class="fa fa-calendar"></i></button></span></div>
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
<div class="modal fade" id="popup" role="dialog">
    <div class="modal-dialog">        
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Import Assembly File</h4>
        </div>
        <div class="modal-body">
           <form method="post"  action ="<?php echo base_url("products/Assemblyimport/importAssembly");?>" id="uploadform" enctype="multipart/form-data">
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
<script src="<?php echo $this->config->item('script_url');?>assets/pages/scripts/ecommerce-assemblyimport.js" type="text/javascript"></script>
<script type="text/javascript">
	jQuery(".uplaodpreorder").click(function(e){
		e.preventDefault();
		jQuery("#popup").modal('show');
	})
</script>
