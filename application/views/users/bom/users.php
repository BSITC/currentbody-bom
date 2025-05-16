<div class="page-content-wrapper">
    <!-- BEGIN CONTENT BODY -->
    <div class="page-content">
        <!-- BEGIN PAGE HEADER-->>
        <!-- BEGIN PAGE BAR -->
        <div class="page-bar">
            <ul class="page-breadcrumb">
                <li>
                    <a href="index.html">Home</a>
                    <i class="fa fa-circle"></i>
                </li>
                <li>
                    <span>Admin Capabilities</span>
                </li>
            </ul>
        </div>
        <div class="portlet ">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-shopping-cart"></i>Manage Users </div>
                <div class="actions">
                    <a href="javascript:;" class="btn btn-circle btn-info useractionaddbtn">
                        <i class="fa fa-plus"></i>
                        <span class="hidden-xs"> Add User </span>
                    </a>
                </div>
            </div>
            <div class="portlet-body">
				<div class="table-container">
					<div class="table-actions-wrapper">
						<span> </span>
						<select class="table-group-action-input form-control input-inline input-small input-sm">
							<option value="">Select...</option>
							<option value="deleteAction">Delete</option>
						</select>
						<button class="btn btn-sm btn-success table-group-action-submit">
							<i class="fa fa-check"></i> Submit</button>
					</div>
					<table class="table table-striped table-bordered table-hover table-checkable" id="datatable_users">
						<thead>
							<tr role="row" class="heading">
								<th width="1%"><input type="checkbox" class="group-checkable"></th>
								<th width="15%">First Name </th>
								<th width="15%">Last Name </th>
								<th width="15%">Email</th>
								<th width="15%">Username</th>
								<th width="15%">Is User Active?</th>
								<th width="15%">User Type</th>
								<th width="10%"> Actions </th>
							</tr>
							<tr role="row" class="filter">
								<td></td>
								<td><input type="text" class="form-control form-filter input-sm" name="firstname"> </td>
								<td><input type="text" class="form-control form-filter input-sm" name="lastname"> </td>
								<td><input type="text" class="form-control form-filter input-sm" name="email"> </td>	
								<td><input type="text" class="form-control form-filter input-sm" name="username"> </td>	
								<td>
									<select name="is_active"  class="form-control form-filter input-sm">
									  <option value="">Is user active?</option>
									  <option value="1">Yes</option>
									  <option value="0">No</option>
									</select>
								</td>	
								<td></td>
								<td>
									<div class="margin-bottom-5">
										<button class="btn btn-sm btn-success filter-submit margin-bottom userfiltertrigger">
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
        <div class="modal fade usermodel" id="actionmodal" role="dialog" data-backdrop="static">
            <div class="modal-dialog">        
              <!-- Modal content-->
              <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                  <h4 class="modal-title">Add User</h4>
                </div>
                <div class="modal-body">
					<form action="<?php echo base_url('users/users/save');?>" method="post" id="saveActionForm" class="form-horizontal saveActionForm" novalidate="novalidate">
                        <div class="form-body">
                            <div class="alert alert-danger display-hide">
                                <button class="close" data-close="alert"></button> You have some form errors. Please check below. </div>
                            <div class="form-group">
                                <label class="control-label col-md-3">First Name
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
                                    <input name="data[firstname]" data-required="1" class="form-control firstname" type="text" placeholder="First Name">
                                </div>
                            </div>
							<div class="form-group">
                                <label class="control-label col-md-3">Last Name</label>
                                <div class="col-md-7">
                                    <input name="data[lastname]" data-required="0" class="form-control lastname" type="text" placeholder="Last Name">
                                </div>
                            </div>
							<div class="form-group">
                                <label class="control-label col-md-3">Email
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
                                    <input name="data[email]" data-required="1" class="form-control email" type="email" placeholder="Email">
                                </div>
                            </div>
							<div class="form-group">
                                <label class="control-label col-md-3">Username
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
                                    <input name="data[username]" data-required="1" class="form-control username" type="text" placeholder="Username">
                                </div>
                            </div>
                            <div class="form-group rm-password">
                                <label class="control-label col-md-3">Password
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
                                    <input name="data[password]"  data-required="1" class="form-control password" type="password" placeholder="Password"> </div>
                            </div>
							<div class="form-group">
                                <label class="control-label col-md-3">Is User Active?
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
									<select name="data[is_active]"  data-required="1" class="form-control is_active">
									  <option value="">Is user active?</option>
									  <option value="1">Yes</option>
									  <option value="0">No</option>
									</select>
								</div>
							</div>
							<div class="form-group">
                                <label class="control-label col-md-3">User Type
                                </label>
                                <div class="col-md-7">
									<select name="data[accessLabel]" class="form-control accessLabel">
									  <option value="">User Type</option>
									  <option value="1">Admin</option>
									  <option value="3">Manager</option>
									  <option value="2">Employee</option>
									</select>
								</div>
							</div>
						</div>
						<input type="hidden" name="data[user_id]" class="user_id" />
                    </form>                         
                </div>
                <div class="modal-footer">
                  <button type="button" class="pull-left btn btn-primary submitAction">Save</button>
                  <button type="button" class="btn yellow btn-outline sbold" data-dismiss="modal">Close</button>
                </div>
              </div>                  
            </div>
        </div>
	</div>
</div>
<script src="<?php echo base_url();?>assets/js/ecommerce-users.js" type="text/javascript"></script>
<script type="text/javascript">
	jQuery(".uplaodpreorder").click(function(e){
		e.preventDefault();
		jQuery("#popup").modal('show');
	});
	jQuery(document).on("click", ".actioneditbtn",function(){
		$(".rm-password").hide();
	});
	jQuery(document).on("click", ".useractionaddbtn",function(){
		$(".rm-password").show();
	});
</script>
