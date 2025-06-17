<?php 
$user_session_data = $this->session->userdata('login_user_data');
?>
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
				<span>Assembly Listing</span> 
			</li>
		</ul>
	</div>
	<div class="row">
		<div class="col-md-12">
			<!-- Begin: life time stats -->
			<div class="portlet ">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-shopping-cart"></i>Assembly Listing </div>	
						<?php if($user_session_data['accessLabel'] == '1' || $user_session_data['accessLabel'] =='3'){?>
							<div class="actions">
								<a href="<?php echo base_url('products/assembly/addNewAssembly');?>" class="btn btn-circle btn-info"> 
									<i class="fa fa-plus"></i>
									<span class="hidden-xs"> Create New Assembly </span>
								</a>												
							</div>
						<?php } ?>
				</div>
				<div class="portlet-body">
					<div class="table-container">
					<?php if($user_session_data['accessLabel'] == '1' || $user_session_data['accessLabel'] =='3'){?>
						<div class="table-actions-wrapper">
							<span> </span>
							<select class="table-group-action-input form-control input-inline input-small input-sm">
								<option value="">Select...</option>
								<option value="delete">Delete</option>
							</select>
							<button class="btn btn-sm btn-success table-group-action-submit">
								<i class="fa fa-check"></i> Submit</button>
						</div>
					<?php } ?>
						<table class="table table-striped table-bordered table-hover table-checkable" id="datatable_products">
							<thead>
								<tr role="row" class="heading">
									<th width="1%"><input type="checkbox" class="group-checkable"> </th>
									<th width="10%"> Assembly Type</th>
									<th width="7%"> Created By </th>
									<th width="10%"> Assign To </th>
									<th width="7%"> SO ID </th>
									<th width="10%"> Assembly&nbsp;ID </th>
									<th width="10%"> Warehouse </th>
									<th width="5%">Product ID </th>
									<th width="10%"> SKU </th>	
									<th width="10%"> Name </th>	
									<th width="5%"> QTY </th>	
									<th width="5%"> Recipe </th>	
									<!-- <th width="10%"> Costing Method </th> -->
									<th width="5%"> Status </th>	
									<th width="10%"> Created </th>	
									<th width="10%"> Actions </th>
								</tr>
								<tr role="row" class="filter">
									<td></td> 
									<td><select class="form-control form-filter input-sm"  name="assemblyType">
											<option value=""> Select Assembly Type </option>
											<option value="1">Manual Assembly</option>
											<option value="2">Standard Auto-Assembly</option>
											<option value="3">Reorder Assembly</option>
											<?php if($this->globalConfig['enableImportAssembly']){ ?>
												<option value="4">Import Assembly</option>
											<?php } ?>
										</select>  </td>
									<td><input type="text" class="form-control form-filter input-sm" name="username"> </td>
									<td style="text-align:center"> 
										<?php if($user_session_data['accessLabel'] == '1' || $user_session_data['accessLabel'] =='3'){?>
											<select class="form-control form-filter input-sm" name="assignToUserId">
												<option value="">Select User</option>
												<?php foreach ($assignUserList as $assignUsers){?>
												<option value="<?php echo $assignUsers['user_id'];?>"><?php echo ucfirst($assignUsers['firstname']) . ' '.ucfirst($assignUsers['lastname']); ?></option>
												<?php } ?>
												<option value="notAssined">Not Assigned</option>
											</select>
										<?php } ?>
									</td>
									<td><input type="text" class="form-control form-filter input-sm" name="orderId"> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="createdId"> </td>
									<td>
										<select class="form-control form-filter input-sm"  name="warehouse">
											<option value=""> Select Warehouse </option>
											<?php foreach($warehouseList as $warehouseIds => $warehouse){?>
												<option value="<?php echo $warehouseIds;?>"><?php echo $warehouse['warehouseName'];?> </option>
											<?php }?>
										</select>
									</td>
									<td><input type="text" class="form-control form-filter input-sm" name="productId"> </td>
									<td><input type="text" class="form-control form-filter input-sm" name="sku"> </td>	
									<td><input type="text" class="form-control form-filter input-sm" name="name"> </td>	
									<td><input type="text" class="form-control form-filter input-sm" name="qty"> </td>	
									<td><input type="text" class="form-control form-filter input-sm" name="receipId"> </td>	
									<!-- <td></td> -->
									<td>
										<select class="form-control form-filter input-sm" name="status">
											<option value=""> Select Status </option>
											<option value="0"> Save WIP </option>
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
<script>
var EcommerceProducts = function () {

    var initPickers = function () {
        //init date pickers
        $('.date-picker').datepicker({
            rtl: App.isRTL(),
            autoclose: true
        });
    }

    var handleProducts = function() { 
        var grid = new Datatable();

        grid.init({
            src: $("#datatable_products"),
            onSuccess: function (grid) {
                // execute some code after table records loaded
            },
            onError: function (grid) {
                // execute some code on network or other general error  
            },
            dataTable: { // here you can define a typical datatable settings from http://datatables.net/usage/options 

                // Uncomment below line("dom" parameter) to fix the dropdown overflow issue in the datatable cells. The default datatable layout
                // setup uses scrollable div(table-scrollable) with overflow:auto to enable vertical scroll(see: assets/global/scripts/datatable.js). 
                // So when dropdowns used the scrollable div should be removed. 
                //"dom": "<'row'<'col-md-8 col-sm-12'pli><'col-md-4 col-sm-12'<'table-group-actions pull-right'>>r>t<'row'<'col-md-8 col-sm-12'pli><'col-md-4 col-sm-12'>>",

                "lengthMenu": [
                    [10, 20, 50, 100, 150],
                    [10, 20, 50, 100, 150] // change per page values here 
                ],
                "pageLength": 20, // default record count per page
                "ajax": {
                    "url": base_url+"products/assembly/getProduct", // ajax source
                },
                "order": [
                    [0, "desc"]
                ] // set first column as a default sort by asc
            }
        });

        jQuery(document).on("click",".btnactionsubmit",function(e){
            e.preventDefault();
            var url = jQuery(this).attr('href');
            jQuery.get(url,function(res){
                grid.getDataTable().ajax.reload();
            })
        })
         // handle group actionsubmit button click
        grid.getTableWrapper().on('click', '.table-group-action-submit', function (e) {
            e.preventDefault();
            var action = $(".table-group-action-input", grid.getTableWrapper());
            if (action.val() != "" && grid.getSelectedRowsCount() > 0) {
                grid.setAjaxParam("customActionType", "group_action");
                grid.setAjaxParam("customActionName", action.val());
                grid.setAjaxParam("id", grid.getSelectedRows());
                grid.getDataTable().ajax.reload();
                grid.clearAjaxParams();
            } else if (action.val() == "") {
                App.alert({
                    type: 'danger',
                    icon: 'warning',
                    message: 'Please select an action',
                    container: grid.getTableWrapper(),
                    place: 'prepend'
                });
            } else if (grid.getSelectedRowsCount() === 0) {
                App.alert({
                    type: 'danger',
                    icon: 'warning',
                    message: 'No record selected',
                    container: grid.getTableWrapper(),
                    place: 'prepend'
                });
            }
        });
    }

    return {

        //main function to initiate the module
        init: function () {

            handleProducts();
            initPickers();
            
        }

    };

}();

jQuery(document).ready(function() {    
   EcommerceProducts.init();
});
</script>
