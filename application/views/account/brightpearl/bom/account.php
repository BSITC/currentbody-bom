<style>
a.showbtn.btn.yellow {
    margin-top: -31px;
    margin-left: 101%;
}
a.showbtnpass.btn.yellow { 
    margin-top: -31px;
    margin-left: 101%;
}
</style>
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
                    <span>Brightpearl Account Settings</span>
                </li>

            </ul>
        </div>
        <div class="portlet ">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-shopping-cart"></i>Brightpearl Account Settings </div>
            </div>
            <div class="portlet-body">
                <div class="table-container">
                    <div class="table-responsive">          
                        <table class="table table-hover text-centered actiontable">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                     <?php if($data['type'] == 'account2'){
                                       echo '<th width="10%">'.$this->globalConfig['account1Name'].' Name</th>';
                                    }?>
                                    <th width="10%">Account ID</th>
                                    <th width="10%">Data Center Code</th>
                                    <th width="10%">Email</th>
                                    <th width="10%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                 <tr class="clone hide">
                                     <td ><span class="value" data-value="id"></span></td>
                                     <td ><span class="value" data-value="accountName"></span></td>
                                    <?php if($data['type'] == 'account2'){ ?>
                                    <td><span class="value" data-value="accountName"></span></td>
                                    <?php } ?>
                                    <td ><span class="value" data-value="dcCode"></span></td>
                                    <td><span class="value" data-value="email"></span></td>
                                    <td class="action">
                                        <a class="actioneditbtn btn btn-icon-only blue" href="javascript:;" title="View"><i class="fa fa-edit" title="Edit settings" ></i></a>
                                        <a href="javascript:;" delurl="<?php echo base_url('account/'.$data['type'].'/account/delete/');?>" class="actiondelbtn btn btn-icon-only red" title="View"><i class="fa fa-trash danger" title="Delete settings" ></i></a>
                                    </td>
                                </tr>
                                <?php   foreach ($data['data'] as $key =>  $row) { ?>    
								<script> var data<?php echo $row['id'];?> = <?php echo json_encode($row);?>;</script>
                                <tr class="tr<?php echo $row['id'];?>">
                                    <td ><span class="value" data-value="id"><?php echo $key + 1;?></span></td>
                                    <?php if($data['type'] == 'account2'){ ?>
                                     <td><span class="value" data-value="account1Id"><?php echo @($data['account1Id'][$row['account1Id']])?($data['account1Id'][$row['account1Id']]['name']):($row['account1Id']);?></span></td>
                                    <?php } ?>
                                    <td><span class="value" data-value="accountName"><?php echo $row['accountName'];?></span></td>
                                    <td><span class="value" data-value="dcCode"><?php echo $row['dcCode'];?></span></td>
                                    <td><span class="value" data-value="email"><?php echo $row['email'];?></span></td>
                                    <td class="action">
                                        <a class="actioneditbtn btn btn-icon-only blue" href="javascript:;" onclick=editAction(data<?php echo $row['id'];?>) title="View"><i class="fa fa-edit" title="Edit settings" ></i></a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="actionmodal" role="dialog" data-backdrop="static">
            <div class="modal-dialog">        
              <!-- Modal content-->
              <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                  <h4 class="modal-title">Brightpearl Account Settings</h4>
                </div>
                <div class="modal-body">
                   <form action="<?php echo base_url('account/'.$data['type'].'/account/save');?>" method="post" id="saveActionForm" class="form-horizontal saveActionForm" novalidate="novalidate">
                        <div class="form-body">
                            <div class="alert alert-danger display-hide">
                                <button class="close" data-close="alert"></button> You have some form errors. Please check below. </div>
                            <?php if($data['type'] == 'account2'){ ?>
                                <div class="form-group">
                                    <label class="control-label col-md-3"><?php echo $this->globalConfig['account1Name'];?> Save Id
                                        <span class="required" aria-required="true"> * </span>
                                    </label>
                                    <div class="col-md-7">
                                        <select name="data[account1Id]" data-required="1" class="form-control account1Id">
                                            <?php
                                            foreach ($data['account1Id'] as $account1Id) {
                                                echo '<option value = "'.$account1Id['id'].'">'.ucwords($account1Id['name']).'</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>                              
                            <?php } ?>
                            <div class="form-group">
                                <label class="control-label col-md-3">Account ID
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
                                    <input name="data[accountName]" data-required="1" class="form-control accountName" type="text"> </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3">Data Center Code
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
                                    <input name="data[dcCode]" data-required="1" class="form-control dcCode" type="text"> 
                                    <span class="help-block"> (Hint :- "eu1" -> Timezone - "GMT, CET", "use" => Timezone - "EST, CST", "usw"=>Timezone - "PST, MST")</span>
                                </div>

                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3">Email
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
                                    <input name="data[email]" data-required="1" class="form-control email" type="text">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-3">Password
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
                                    <input name="data[password]"  data-required="1" class="form-control password passwordremove" type="password">  <a href="javascript:" class="showbtnpass btn yellow"><i class="fa fa-eye" title="Show Token" ></i></a></div>
                            </div>
							<div class="form-group">
                                <label class="control-label col-md-3">Reference
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
                                    <input name="data[reference]"  data-required="1" class="form-control reference" type="text"> </div>
                            </div>
							<div class="form-group">
                                <label class="control-label col-md-3">Token
                                    <span class="required" aria-required="true"> * </span>
                                </label>
                                <div class="col-md-7">
                                    <input name="data[token]"  data-required="1" class="form-control showpassword token" type="text"> <a href="javascript:" class="showbtn btn yellow"><i class="fa fa-eye" title="Show Token" ></i></a> </div>
                            </div>
							
                        </div>
                        <input type="hidden" name="data[id]" class="id" />
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
<script>
jQuery(document).ready(function() {
	
	jQuery(document).on("click",".showbtn",function() {
		jQuery('.showpassword').removeAttr("type");
    });
	jQuery(document).on("click",".actioneditbtn",function() {
		jQuery('.showpassword').attr("type", "password");
		jQuery('.showbtn').show();
    });
	
	jQuery(document).on("click",".showbtnpass",function() {
		jQuery('.passwordremove').removeAttr("type");
		jQuery('.showbtnpass').hide();
    });
	jQuery(document).on("click",".actioneditbtn",function() {
		jQuery('.passwordremove').attr("type", "password");
		jQuery('.showbtnpass').show();
    });
});
</script>