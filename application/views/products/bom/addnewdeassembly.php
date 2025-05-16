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
	<!-- END PAGE BAR -->
	<!-- BEGIN PAGE TITLE-->
	<h3 class="page-title"> Disassembly Details 
	</h3>
	<!-- END PAGE TITLE-->
	<!-- END PAGE HEADER-->
	<div class="row">
		<div class="col-md-12">
			<!-- Begin: life time stats -->
			<div class="portlet ">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-shopping-cart"></i>Search BOM </div>					
				</div>
				<div class="portlet-body">
					<div class="table-container">						
						<div class="form-group col-md-6">
						 <input name="searchBillsofmaterial" value="" class="form-control atutocomplate" type="text" placeholder="Search by product name or sku">
						</div>  
						<div class="form-group col-md-6">
						<a href="<?php echo base_url('products/deassembly')?>" class="btn btn-primary pull-right">Back</a>
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
$availableTags = array_unique($availableTags);
$availableTags = array_filter($availableTags);  
?>
<script>
var loadedValue = [];
var loadValue = function(){
	$( ".qty" ).each(function( index,element  ) {
		var test = $(element).val();
		loadedValue.push(test);
	});
	return loadedValue;		
}
var availableTags = [<?php echo '"'.implode('","',  $availableTags ).'"' ?>]; 
jQuery(".atutocomplate").autocomplete({
	source: availableTags,
	minLength: 4, 
	select: function (event, ui) {
		if(ui.item.value){		
			productName = btoa(ui.item.value); 
			jQuery.get( "<?php echo base_url('products/deassembly/editdeassemblyajax/');?>" + productName, function( data ) {
				jQuery(".mainboday").html(data);
				loadValue();
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
	jQuery.get( "<?php echo base_url('products/deassembly/editdeassemblyajax/');?>" + jQuery(this).val(), function( data ) {
		jQuery(".mainboday").html(data);
		console.log(data);
	});
}) */
</script>	
