<?php
$allBinLocations = array();
foreach($data['getAllWarehouseLocation'] as $temp2){
	foreach($temp2 as $t2){
		$allBinLocations[$t2['warehouseId']][] = $t2['name'];
	}
}
$config = $this->db->get('global_config')->row_array();
?>
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
                    <span>Brightpearl Configuration</span>
                </li>

            </ul>
        </div>
        <div class="portlet ">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-shopping-cart"></i>Brightpearl Configuration </div>
            </div>
            <div class="portlet-body">
                <div class="table-container">
                    <div class="table-responsive">          
                        <table class="table table-hover text-centered actiontable">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%">Brightpearl ID</th>
                                    <th width="25%">Account ID</th>
                                    <th width="10%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="clone hide">
                                    <td ><span class="value" data-value="id"></span></td>
                                    <td><span class="value" data-value="brightpearlAccountId"></span></td>
                                    <td><span class="value" data-value="compte"></span></td>
                                    <td class="action">
                                        <a class="actioneditbtn btn btn-icon-only blue" href="javascript:;" title="View"><i class="fa fa-edit" title="Edit settings" ></i></a>
                                    </td>
                                </tr>
                                <?php   foreach ($data['data'] as $key =>  $row) { ?>                               
                                <tr class="tr<?php echo $row['id'];?>">
                                    <td ><span class="value" data-value="id"><?php echo $key + 1;?></span></td>
                                    <td><span class="value" data-value="brightpearlAccountId"><?php echo ($data['saveAccount'][$row['brightpearlAccountId']])?($data['saveAccount'][$row['brightpearlAccountId']]['name']):($row['brightpearlAccountId']);?></span></td>
                                    <td><span class="value" data-value="name"><?php echo $row['name'];?></span></td>
                                    <td class="action">
                                        <a class="actioneditbtn btn btn-icon-only blue" href="javascript:;" onclick='editAction(<?php echo json_encode($row);?>)' title="View"><i class="fa fa-edit" title="Edit settings" ></i></a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="actionmodal" role="dialog">
            <div class="modal-dialog modal-xl">        
              <!-- Modal content-->
              <div class="modal-content">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
                  <h4 class="modal-title">Brightpearl Configuration</h4>
                </div>
                <div class="modal-body">
                   <form action="<?php echo base_url('account/'.$data['type'].'/config/save');?>" method="post" id="saveActionForm" class="form-horizontal saveActionForm" novalidate="novalidate">
                        <div class="form-body">
                                                                                  
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


<div class="confighml">
    <?php   
    $data['data'] = ($data['data'])?($data['data']):(array(''));
    foreach ($data['data'] as $key =>  $row) {
		$account = $data['accountinfo'][$row['id']];
		$costingmethods = array(
			array('id' => $row['costPriceListbomNonTrack'],'name' => 'Cost Pricelist'),
			array('id' => 'fifo','name' => 'FIFO'),
		);		
		?>
        <div class="htmlaccount<?php echo @$row['id'];?>" style="display: none;">
            <div class="alert alert-danger display-hide">
				<button class="close" data-close="alert"></button> You have some form errors. Please check below. 
			</div>
			<ul class="nav nav-tabs tabCss">
				<li class="active"><a href="#defaults" data-toggle="tab">Configuration</a></li>
			</ul>
			<div class="tab-pane active" id="defaults">
				<fieldset>
					<div class="row">
						<legend class="legendstyle col-md-3">Default Settings</legend>
					</div>
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Account ID<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
									 <select name="data[brightpearlAccountId]" data-required="1" class="form-control brightpearlAccountId">
										<option value="">Select a save Brightpearl account</option>
										<?php
										foreach ($data['saveAccount'] as $saveAccount) {
											echo '<option value="'.$saveAccount['id'].'">'.ucwords($saveAccount['name']).'</option>';
										}
										?>
									</select>
								</div>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label class="control-label marginStyle">Email Id to Receive Auto-Assembly</label>
								<div class="marginStyle">
									<input type="text" name="data[autoAssemblyEmail]"  class="form-control autoAssemblyEmail" />
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Costing Method<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
								   <select name="data[costPriceListbom]" class="form-control costPriceListbom">
										<?php
										foreach ($costingmethods as $pricelist) {
											echo '<option value="'.$pricelist['id'].'">'.ucwords($pricelist['name']).'</option>';
										}
										?>
									</select> 
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Cost Price List<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
									<select name="data[costPriceListbomNonTrack]" data-required="1" class="form-control costPriceListbomNonTrack">
										<?php
										foreach ($data['pricelist'][$row['brightpearlAccountId']] as $pricelist) {
											echo '<option value="'.$pricelist['id'].'">'.ucwords($pricelist['name']).'</option>';
										}
										?>
									</select>
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Warehouses for Display<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
										<select name="data[displayWarehouses][]" multiple="multiple" data-required="1" class="form-control displayWarehouses">
										<?php
										$saveWarehouses = explode(",",$row['displayWarehouses']);
										foreach ($data['warehouse'][$row['brightpearlAccountId']] as $warehouse) {
											$selected = (in_array($warehouse['id'],$saveWarehouses))?('selected="selected"'):('');
												echo '<option value="'.$warehouse['id'].'" '.$selected.'>'.ucwords($warehouse['name']).'</option>';
										}
										?>									
									</select> 
								</div>
							</div>
						</div>
					</div>
				</fieldset>
				<fieldset>
					<div class="row">
						<legend class="legendstyle col-md-3">Standard Auto-Assembly Settings</legend>
					</div>
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Default Standard Auto-Assembly Warehouse<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
									  <select name="data[warehouse]" data-required="1" class="form-control warehouse">
										<?php
										foreach ($data['warehouse'][$row['brightpearlAccountId']] as $warehouse) {
											echo '<option value="'.$warehouse['id'].'">'.ucwords($warehouse['name']).'</option>';
										}
										?>
									</select> 
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Exclude Sales Order Status from Auto-Assembly<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
									<select name="data[fetchSalesOrderStatusExclude][]"  multiple="multiple"   class="form-control chosen-select">
										<option value="">Select Order Status</option>';
										<?php
										$saveChanels = explode(",",$row['fetchSalesOrderStatusExclude']);
										foreach ($data['orderstatus'][$row['brightpearlAccountId']] as $orderstatus) {
											$selected = (in_array($orderstatus['id'],$saveChanels))?('selected="selected"'):('');
											if($orderstatus['orderTypeCode'] == 'SO')
												echo '<option value="'.$orderstatus['id'].'" '.$selected.'>'.ucwords($orderstatus['name']).'</option>';
										}
										?>									
									</select> 
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">SO Inclusion Lead Time(In Days)</label>
								<div class="marginStyle">
									<input type="text" name="data[leadTime]"  class="form-control leadTime" placeholder="SO Inclusion Lead Time"/>
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Delivery Date Type
								<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
									<select name="data[dateType]" class="form-control dateType">
										<option value="">Select Delivery Date Type</option>
										<option value="standard">Standard Delivery Date</option>
										<option value="customField">Custom Field(Sales)</option>
									</select>
								</div>
							</div>
						</div>
						<div class="col-md-3" id="deliveryDateCustom" style="display:none;">
							<div class="form-group">
								<label class="control-label marginStyle">Custom Field for Delivery Date
								<span class="required" aria-required="true"> * </span>
								</label>
								<div class="marginStyle">
								    <input type="text" name="data[deliveryDateCustomField]"  class="form-control deliveryDateCustomField" placeholder="Custom Field for Delivery Date"/>
								</div>
							</div>
						</div>
					</div>
				</fieldset>
				<fieldset>
					<div class="row">
						<legend class="legendstyle col-md-3">Reorder Point Settings</legend>
					</div>
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">First Reorder Point Warehouse<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
									 <select name="data[defaultAutoAssembyWarehouse]" data-required="1" class="form-control defaultAutoAssembyWarehouse acc2list">
										<?php
										foreach ($data['warehouse'][$row['brightpearlAccountId']] as $warehouse) {
											echo '<option value="'.$warehouse['id'].'">'.ucwords($warehouse['name']).'</option>';
										}
										?>
									</select> 
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">First Reorder Point Bin Location<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
								   <input name="data[defaultAutoAssembyLocation]" value="<?php //echo $valueName;?>" class="form-control atutocomplate defaultAutoAssembyLocation" type="text">
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">First Reorder Point WIP Warehouse<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
								   <select name="data[defaultAutoAssembyTargetWarehouse]" data-required="1" class="form-control defaultAutoAssembyTargetWarehouse">
										<?php
										foreach ($data['warehouse'][$row['brightpearlAccountId']] as $warehouse) {
											echo '<option value="'.$warehouse['id'].'">'.ucwords($warehouse['name']).'</option>';
										}
										?>
									</select> 
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Second Reorder Point Warehouse<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
									<select name="data[defaultAutoAssembyWarehouse2]" data-required="1" class="form-control defaultAutoAssembyWarehouse2 acc2list">
										<?php
										foreach ($data['warehouse'][$row['brightpearlAccountId']] as $warehouse) {
											echo '<option value="'.$warehouse['id'].'">'.ucwords($warehouse['name']).'</option>';
										}
										?>
									</select> 
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Second Reorder Point Bin Location<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
								   <input name="data[defaultAutoAssembyLocation2]" value="<?php //echo $valueName;?>" class="form-control atutocomplate defaultAutoAssembyLocation2" type="text">
								</div>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Second Reorder Point WIP Warehouse<span class="required" aria-required="true"> * </span></label>
								<div class="marginStyle">
								   <select name="data[defaultAutoAssembyTargetWarehouse2]" data-required="1" class="form-control defaultAutoAssembyTargetWarehouse2">
										<?php
										foreach ($data['warehouse'][$row['brightpearlAccountId']] as $warehouse) {
											echo '<option value="'.$warehouse['id'].'">'.ucwords($warehouse['name']).'</option>';
										}
										?>
									</select> 
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Auto Completion of Reorder Assembly</label>
								<div class="marginStyle">
								   <select name="data[autoCompleteReorderAssembly]"  class="form-control autoCompleteReorderAssembly">
										<option value=""> Select an option </option>
										<option value="1"> Yes </option>
										<option value="0"> No </option>
									</select> 
								</div>
							</div>
						</div>
					</div>
				</fieldset>
				<fieldset>
					<div class="row">
						<legend class="legendstyle col-md-3">Custom Field Settings</legend>
					</div>
					<div class="row">
						<div class="col-md-3">
							<div class="form-group">
								<label class="control-label marginStyle">Custom Field for BOM</label>
								<div class="marginStyle">
								    <input type="text" name="data[bomCustomField]"  class="form-control bomCustomField" placeholder="Custom Field for BOM"/>
								</div>
							</div>
						</div>
					</div>
				</fieldset>
				<input type="hidden" name="data[currencyCode]" value="<?php echo $account['configuration']['baseCurrencyCode'];?>" />
			</div>
        </div> 
    <?php } ?>
</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" />	
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
allBinLocations = <?php echo json_encode($allBinLocations);?>;
jQuery(document).on("change",".acc2list",function(){
	acc2listval = jQuery(this).val();
	if(acc2listval.length > 0){		
		jQuery(".acc2listoption option").hide();
		jQuery(".acc2listoption"+acc2listval).show();
	}
})
jQuery(document).on("change",".defaultAutoAssembyWarehouse",function(){
	defaultAutoAssembyWarehouse = jQuery(this).val();
	allBinLocation = allBinLocations[defaultAutoAssembyWarehouse];
	jQuery(".atutocomplate").autocomplete({
		source: allBinLocation,
		minLength: 2, 
		autoFocus:true,
		select: function (event, ui) {
			if(ui.item.value){
				console.log(ui.item.value);
			}	
		},
		response: function(event, ui) {
			if (ui.content.length === 0) {
				jQuery("#empty-message").text("No results found"); 
			} else {
				jQuery("#empty-message").empty();
			} 
		}
			
	})

});
jQuery(document).on("change",".defaultAutoAssembyWarehouse2",function(){
	defaultAutoAssembyWarehouse = jQuery(this).val();
	allBinLocation = allBinLocations[defaultAutoAssembyWarehouse];
	jQuery(".atutocomplate").autocomplete({
		source: allBinLocation,
		minLength: 2, 
		autoFocus:true,
		select: function (event, ui) {
			if(ui.item.value){
				console.log(ui.item.value);
			}	
		},
		response: function(event, ui) {
			if (ui.content.length === 0) {
				jQuery("#empty-message").text("No results found"); 
			} else {
				jQuery("#empty-message").empty();
			} 
		}	
	})
});

jQuery(document).on("change",".dateType",function(){
	dateType = jQuery(this).val();
	if(dateType == "customField"){
		jQuery("#deliveryDateCustom").show();
		jQuery(".deliveryDateCustomField").attr("data-required","1");
	}else{
		jQuery("#deliveryDateCustom").hide();
		jQuery(".deliveryDateCustomField").removeAttr("data-required","1");
	}
});
jQuery('.submitAction').click(function() {
    leadTime = jQuery('.leadTime').val();
	if(leadTime){
		jQuery(".dateType").attr("data-required","1");
		return false;
	}else{
		jQuery('.dateType').val('');
		jQuery(".dateType").removeAttr("data-required","1");
	}
});
jQuery('.actioneditbtn').click(function() {
    var selected = jQuery('.dateType option:selected');
	selectedval = selected.val();
	if(selectedval == "standard"){
		jQuery("#deliveryDateCustom").hide();
		jQuery(".deliveryDateCustomField").removeAttr("data-required","1");
	}else if(selectedval == ""){
		jQuery("#deliveryDateCustom").hide();
		jQuery(".deliveryDateCustomField").removeAttr("data-required","1");
	}else{
		jQuery("#deliveryDateCustom").show();
		jQuery(".deliveryDateCustomField").attr("data-required","1");
	}
});
</script>
<style>
.ui-autocomplete {
     z-index: 999999 !important;
}
</style>