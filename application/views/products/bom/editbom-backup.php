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
				<span>Bills of Material Details</span>
			</li>
		</ul>
	</div>
	<!-- END PAGE BAR -->
	<!-- BEGIN PAGE TITLE-->
	<h3 class="page-title"> Bills of Material
		<small> Details</small>
	</h3>
	<!-- END PAGE TITLE-->
	<!-- END PAGE HEADER-->
	<div class="row">
		<div class="col-md-12">
			<!-- Begin: life time stats -->
			<div class="portlet ">
				<div class="portlet-title">
					<div class="caption" style="width: 100%;">
						<div class="table-container">
							<table class="table table-striped table-bordered table-hover table-checkable">
								<thead>
									<tr>
										<th width="33%">Brightpearl Product ID</th>
										<th width="33%">Brightpearl Product SKU</th>
										<th width="33%">Brightpearl Product Name</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><?php echo $products['productId'];?></td>
										<td><?php echo $products['sku'];?></td>
										<td><?php echo $products['name'];?></td>
									</tr>
								</tbody>
							</table>
						</div>					
					</div>					
				</div>
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-product-hunt"></i>Component Details </div>					
				</div>
				<div class="portlet-body">
					<div class="table-container">	
						<form action ="<?php echo base_url('products/billsofmaterials/saveReceipe/'.$productId);?>" method = "post">
						<table class="table table-striped table-bordered table-hover table-checkable receipecontainer" id="datatable_products">							
							<tbody>
								<tr>
									<td width="10%">Recipe #</td>
									<td width="15%">Recipe Name</td>
									<td width="22%">Component Brightpearl Product SKU</td>
									<td width="22%">Component Brightpearl Product Name</td>
									<td >Qty</td>
									
								</tr>
								<?php if(@$billcomponents) foreach ($billcomponents as $receipeid => $billcomponent) { ?>
									<tr>
										<td colspan="5" class="receipe">
											<table class="table table-striped table-bordered table-hover">
											<?php foreach ($billcomponent as $billcompo) { ?>
												<tr data-id="<?php echo $billcompo['id'];?>">
													<td width="10%"><span class="receipeid"><?php echo ($receipeid)?($receipeid):'1';?></span><input type="hidden" name="data[receipeid][]" class="receipeidval" value="<?php echo $receipeid;?>" /><input type="hidden" name="data[savebomid][]" class="savebomid" value="<?php echo $billcompo['id'];?>" /></td>
													<td width="15%"><input value="<?php echo $billcompo['recipename'];?>" placeholder="Enter recipe name" name="data[recipename][]" class="recipename form-control" type="text"></td> 
													<td width="22%"><input value="<?php echo $billcompo['sku'];?>" placeholder="Enter sku" name="data[sku][]" class="atutocomplate form-control ui-autocomplete-input" autocomplete="off" type="text"></td>
													<td width="22%"><input value="<?php echo $billcompo['name'];?>" placeholder="Enter name" name="data[name][]" class="name form-control" type="text"></td>
													<td ><input value="<?php echo $billcompo['qty'];?>" placeholder="Qty" name="data[qty][]" class="qty form-control" style="width:80px;" type="text"><a href="javascript:;" class="btn btn-circle red remvoerow pull-right"><i class="fa fa-close"></i></a><span class="addcomponentcontainer"><a href="javascript:;" class="btn btn-circle green addcomponent pull-right"><i class="fa fa-plus"></i><span class="hidden-xs"> Add new Component</span></a></span></td>
												</tr>											
											<?php } ?>
											</table>
										</td>
									</tr>
									<?php } ?>
							</tbody>
							<tfoot>
								<tr><td colspan="3"></td></tr>
								<tr>
									<td colspan="3">
										<button class="btn btn-circle btn-info btnactionsubmit"> 
											<i class="fa fa-save"></i>
											<span class="hidden-xs"> Save </span>
										</button>
										<a href="<?php echo base_url('products/products/postProducts');?>" class="btn btn-circle red">
											<i class="fa fa-close"></i>
											<span class="hidden-xs"> Cancel </span>
										</a>
										<a href="javascript:;" class="btn btn-circle yellow addnewrecipe">
											<i class="fa fa-plus"></i>
											<span class="hidden-xs"> Add new recipe</span>
										</a>										
									</td>
								</tr>
							</tfoot>
						</table>
					</form>
					</div>
				</div>
			</div>
			<!-- End: life time stats -->
		</div>
	</div>
</div>
<!-- END CONTENT BODY -->
</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">		
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 
<?php
$availableTags = array_column($allproducts,'sku');
$availableTags = array_unique($availableTags);
$availableTags = array_filter($availableTags);  
?>
<script type="text/javascript">
	jQuery(document).ready(function(){
		var availableTags = [<?php echo '"'.implode('","',  $availableTags ).'"' ?>];
		productBySku = <?php echo json_encode($productBySku);?>;  
		function removeAddComponent(){
			jQuery(".receipecontainer>tbody>tr").each(function(){
				$this = jQuery(this);
				$this.find(".addcomponent").remove();
				$this.find(".addcomponentcontainer").last().html('<a href="javascript:;" class="btn btn-circle green addcomponent pull-right"><i class="fa fa-plus"></i><span class="hidden-xs"> Add new Component</span></a>');
			})
		}
		removeAddComponent();   
		jQuery(".addnewrecipe").on("click",function(){ 
			appendRow = '<tr><td width="10%"><span class="receipeid">'+ (jQuery(".receipecontainer>tbody>tr").length)+'</span><input type="hidden" name="data[receipeid][]" class="receipeidval" value="'+ (jQuery(".receipecontainer>tbody>tr").length)+'" /></td><td width="15%"><input value="" placeholder="Enter recipe name" name="data[recipename][]" class="recipename form-control" type="text"></td><td width="22%"><input type="text" placeholder="Enter sku" name="data[sku][]" class="atutocomplate form-control"></td><td width="22%"><input type="text" placeholder="Enter name" name="data[name][]" class="name form-control"></td><td ><input type="text" placeholder="Qty" name="data[qty][]" class="qty form-control" style="width:40px;"><a href="javascript:;" class="btn btn-circle red remvoerow pull-right"><i class="fa fa-close"></i></a><span class="addcomponentcontainer"></span></td></td></tr>';
			addreceipthtml = '<tr><td colspan="5" class="receipe"><table class="table table-striped table-bordered table-hover">'+appendRow+'</table></td></tr>';
			jQuery(".receipecontainer>tbody>tr").last().after(addreceipthtml);
			removeAddComponent();
			jQuery(".atutocomplate").autocomplete({
	            source: availableTags 
	        }).autocomplete('enable');
		})
		jQuery(document).on("click",".addcomponent",function(e){
			if(jQuery(this).closest("tr").find('td .recipename').eq(0).val() == ''){
				alert("Please enter recipe name");
				return false;
			}
			e.preventDefault();
			appendRow = '<tr><td width="10%"><span class="receipeid">'+ (jQuery(this).closest("tr").find('td .receipeid').eq(0).html())+'</span><input type="hidden" name="data[receipeid][]" class="receipeidval" value="'+(jQuery(this).closest("tr").find('td .receipeid').eq(0).text())+'" /></td><td width="15%"><input value="'+(jQuery(this).closest("tr").find('td .recipename').eq(0).val())+'" placeholder="Enter recipe name" name="data[recipename][]" class="recipename form-control" type="text"></td><td width="22%"><input type="text" placeholder="Enter sku" class="atutocomplate form-control" name="data[sku][]"></td><td width="22%"><input type="text" placeholder="Enter name" class="name form-control" name="data[name][]"></td><td ><input type="text" placeholder="Qty" class="qty form-control" style="width:40px;" name="data[qty][]"><a href="javascript:;" class="btn btn-circle red remvoerow pull-right"><i class="fa fa-close"></i></a><span class="addcomponentcontainer"></span></td></tr>';
			jQuery(this).closest("tr").after(appendRow);
			removeAddComponent();
			jQuery(".atutocomplate").autocomplete({
	            source: availableTags
	        }).autocomplete('enable');
		})
	    jQuery(".atutocomplate").autocomplete({
	        source: availableTags,
	        response: function(event, ui) {
	            if (ui.content.length === 0) {
	                $("#empty-message").text("No results found");
	            } else {
	                $("#empty-message").empty();
	            }
	        }
	    })
	    jQuery(document).on("change",".atutocomplate",function(){
	    	jQuery(this).closest("tr").find(".name").val(productBySku[jQuery(this).val()]['name']);
	    })
	    jQuery(document).on("click",".remvoerow",function(){
	    	jQuery(this).closest("tr").remove();
	    	var id = parseInt(jQuery(this).closest("tr").attr('data-id'));
	    	if(id){
	    		jQuery.get('<?php echo base_url('products/billsofmaterials/deletebom/');?>'+id, function(data, status){
	    			//
			    });
	    	}
	    	removeAddComponent();
	    	if(jQuery(this).closest(".receipe").find("tr").length < 1){
	    		jQuery(this).closest(".receipe").remove();
	    	}
			if(jQuery(this).closest(".receipe").find("tr").length == 1){
	    		jQuery(this).closest(".receipe").find("tr").find(".recipename").removeAttr('readonly');
	    	}			
	    });
	})	
</script>
<style type="text/css">
	.receipecontainer .qty.form-control {display: inline;}
	.receipe{ margin: 0px;padding: 0px; }
	.receipe table{ margin: 0px;padding: 0px; }
</style>