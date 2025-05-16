<?php 
$saveReciepeId = '';$assembleQty = '';$savedWarehouseID = '';$savedLocationID = "";$costingMethodID = '';
$savedCostingMethodID = '';
$autoAssembly = 0;
if(isset($assemblyData['0']['autoAssembly'])){
	$autoAssembly = (int)$assemblyData['0']['autoAssembly'];
}
$defaultWarehouseBinlocation = array();
if($assemblyData){
	$saveReciepeId = $assemblyData['0']['receipId'];
	foreach($assemblyData as $assemblyDat){
		if($assemblyDat['isAssembly']){
			$savedLocationID = $assemblyDat['locationId'];
			$savedWarehouseID = $assemblyDat['warehouse'];
			$savedCostingMethodID = $assemblyDat['costingMethod'];
			$assembleQty = $assemblyDat['qty'];
		}
		
		if(!$assemblyDat['isAssembly']){
			$defaultWarehouseBinlocation[] = array(
				'componentProductId' => $assemblyDat['productId'],
				'defaultWarehouse' => $assemblyDat['warehouse'],
				'defaultLocation' => $assemblyDat['locationId'],
			);
		}
	}
}
$user_session_data = $this->session->userdata('login_user_data');
$colMd = $user_session_data['accessLabel'] =='2' ? 'col-md-6' : 'col-md-2';
?>

<div class="page-content-wrapper">
<!-- BEGIN CONTENT BODY -->
<div class="page-content">
	<!-- BEGIN PAGE HEADER-->
	<!-- BEGIN PAGE BAR -->
	<div class="page-bar">
		<ul class="page-breadcrumb">
			<li>
				<a href="javascript:void();">Home</a>
				<i class="fa fa-circle"></i>
			</li>
			<li>
				<span>Assembly Listing</span>
			</li>
		</ul>
	</div>
	<!-- END PAGE BAR -->
	<!-- BEGIN PAGE TITLE-->
	<h3 class="page-title"> Assembly Details <?php $assembyID =  $this->uri->slash_segment(5);$assembyID =  str_replace('/', '', @$assembyID);  echo $assembyID ? " ": "";?> <b> <?php echo $assembyID ? '(' . $assembyID . ')' : "";?></b>
		 
	</h3>
	<!-- END PAGE TITLE-->
	<!-- END PAGE HEADER-->
	<div class="row">
		<div class="col-md-12">
			<!-- Begin: life time stats -->
			<div class="portlet">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-shopping-cart"></i>Search BOM </div>					
				</div>
				<div class="portlet-body">
					<div class="table-container">						
						<div class="form-group col-md-6">
						 <?php
							$valueName = '';
							if($name){
								$sku = base64_decode($name);
								$valueName = rawurldecode($sku);
							}						 
						 ?>
						 <input name="searchBillsofmaterial" value="<?php echo $valueName;?>" class="form-control atutocomplate" type="text" placeholder="Search by product name or sku" <?php echo $assembyID != "" ? 'readonly="true"': '';?>>
						</div>
					</div>
				</div>				
			</div>
			<div class="mainboday">

			</div>
			<!-- End: life time stats -->
		</div> 
	</div>
</div>
<!-- END CONTENT BODY -->
</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" />	
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<?php
$availableTags =  array_unique(array_column($allproducts,'customName'));
$availableTags = array_filter($availableTags); 
?>
<script>
var autoAssembly = <?php echo $autoAssembly;?>;
var editAssemblyName = '<?php echo $name;?>';
var assemblyId = '<?php echo $assemblyId;?>';
var saveReciepeId = '<?php echo $saveReciepeId;?>';
var assembleQty = '<?php echo $assembleQty;?>';
var savedLocationID = '<?php echo $savedLocationID;?>';
var binlocationName = '<?php echo isset($warehouseLocationAllDatas[$savedLocationID]['name']) ? $warehouseLocationAllDatas[$savedLocationID]['name'] : '';?>';
var savedWarehouseID = '<?php echo $savedWarehouseID;?>';
var savedCostingMethodID = '<?php echo $savedCostingMethodID;?>';
var defaultWarehouseBinlocation = <?php echo json_encode($defaultWarehouseBinlocation); ?>; 
if(editAssemblyName.length > 5){
	jQuery('.portlet').hide();
	jQuery('.mainboday').html('<div class="blockUI blockOverlay" style="z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; background-color: rgb(0, 0, 0); opacity: 0.6; cursor: wait; position: fixed;"></div><div class="blockUI blockMsg blockPage" style="z-index: 1011; position: fixed; padding: 0px; margin: 0px; width: 30%; top: 40%; left: 35%; text-align: center; color: rgb(0, 0, 0); border: 3px solid rgb(170, 170, 170); background-color: rgb(255, 255, 255); cursor: wait;"><h1>Please wait...</h1></div>');
	jQuery.get( "<?php echo base_url('products/assembly/editassemblyajax/');?>" + editAssemblyName + '/' + assemblyId, function( data ) {
		jQuery('.portlet').show();
		jQuery(".mainboday").html(data);
		if(saveReciepeId.length > 0){
			jQuery('.receipeidselect option:selected').removeAttr('selected');
			jQuery('.receipeidselect').find('option[value="'+saveReciepeId+'"]').attr("selected",true);
			jQuery('.receipeidselect').trigger("change");
			jQuery('.qtydiassemble').val(assembleQty);
			jQuery('.qtydiassemble').trigger("change");
			jQuery('.targetwarehouse').val(savedWarehouseID);
			jQuery('.targetwarehouse').trigger("change");
			jQuery('.targetBinLocation').val(savedLocationID);
			jQuery('.costingmethod').val(savedCostingMethodID);
			jQuery('.targetwarehouse').trigger("change");
			jQuery('.targetBinLocation').trigger("change");
			jQuery('.targetBinLocation').val(binlocationName);
			if(autoAssembly == true){
				jQuery(".qtydiassemble").attr('readonly','readonly');
				jQuery(".targetwarehouse").attr('readonly','readonly');
				jQuery(".targetBinLocation").attr('readonly','readonly');
				jQuery(".sourcewarehouse").attr('readonly','readonly');
				jQuery(".sourcewarehouse").attr('readonly','readonly');
				jQuery(".sourceBinLocation").attr('readonly','readonly');
			}
			
		}
	});
}
function base64EncodeUnicode(str) {
    // First we escape the string using encodeURIComponent to get the UTF-8 encoding of the characters, 
    // then we convert the percent encodings into raw bytes, and finally feed it to btoa() function.
    utf8Bytes = encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function(match, p1) {
            return String.fromCharCode('0x' + p1);
    });

    return btoa(utf8Bytes);
}

var availableTags = [<?php echo '"'.implode('","',  $availableTags ).'"' ?>];
 jQuery(".atutocomplate").autocomplete({
	source: availableTags,
	minLength: 4, 
	select: function (event, ui) {
		if(ui.item.value){
			productName = base64EncodeUnicode(ui.item.value);
			productName = encodeURI(productName);
			jQuery.get( "<?php echo base_url('products/assembly/editassemblyajax/');?>" + productName, function( data ) {
				jQuery(".mainboday").html(data);
			});
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
/* jQuery(document).on("change",".atutocomplate",function(){
	jQuery.get( "<?php echo base_url('products/assembly/editassemblyajax/');?>" + jQuery(this).val(), function( data ) {
		jQuery(".mainboday").html(data);
		console.log(data);
	});
}) */
</script>	
