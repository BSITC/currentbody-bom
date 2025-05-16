<style type="text/css">
	.receipecontainer .qty.form-control {display: inline;}
	.receipe{ margin: 0px;padding: 0px; }
	.receipe table{ margin: 0px;padding: 0px; }
	.bomitems input {width: 90%; margin: auto;}
	.bomitems .tableHead input {width: 100%; margin: auto;}
	.bomitems thead {background-color:#ddd;}
	.bomitems thead.checkbox, .radio{margin-top:-5px;}
	input.form-control.isPrimary {text-align: center;top: 10px;height: 25px;}
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
	<!-- END PAGE BAR -->
	<!-- BEGIN PAGE TITLE-->
	<h3 class="page-title"> BOM Details
	</h3>
	<!-- END PAGE TITLE-->
	<!-- END PAGE HEADER-->
	<div class="row">
		<div class="col-md-12">
			<!-- Begin: life time stats -->
			<div class="portlet ">
				<form action ="<?php echo base_url('products/billsofmaterials/saveReceipe/'.$productId);?>" method = "post">
				<div class="portlet-title">
					<div class="caption" style="width: 100%;">
						<div class="table-container">
							<table class="table table-striped table-bordered table-hover table-checkable">
								<thead>
									<tr>
										<th width="15%">Product ID</th>
										<th width="15%">SKU</th>
										<th width="20%">Name</th>
										<th width="15%">Auto Assembly </th>
										<th width="15%">Auto Cost Price Update</th>
										<th width="15%">Default Bin Location </th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><?php echo $products['productId'];?></td>
										<td><?php echo $products['sku'];?></td>
										<td><?php echo $products['name'];?></td>
										<td>
											<?php $checked = ($products['autoAssemble'])?('checked="checked"'):(''); ?>
											<input type="checkbox" name="autoAssemble" value="1" <?php echo $checked;?>>
										</td>
										<td>
											<?php $checked = ($products['autoBomPriceUpdate'])?('checked="autoBomPriceUpdate"'):(''); ?>
											<input type="checkbox" name="autoBomPriceUpdate" value="1" <?php echo $checked;?>>
										</td>
										<td>
											<select name="binlocation" value="<?php echo $products['binlocation'];?>" class="form-control">
												<option value="">Select Bin Location</option>
												<?php
												foreach($getAllWarehouseLocation as $accountId => $getAllWarehouses){
													foreach($getAllWarehouses as $getAllWarehouse){
														$selected = ($getAllWarehouse['id'] == $products['binlocation'])?('selected="selected"'):('');
														echo '<option value="'.$getAllWarehouse['id'].'" '.$selected.'>'.$getAllWarehouse['name'].'</option>';
													}												
												}
												?>
											</select>
										</td>
										
										
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
					<div class="table-container" style="overflow-x:auto;">	
						<table class="table table-striped table-bordered table-hover table-checkable receipecontainer" id="datatable_products">							
							<tbody>								
								<?php if(@$billcomponents) foreach ($billcomponents as $receipeid => $billcomponent) { ?>
									<tr>
										<td colspan="5" class="receipe" data-recipe="<?php echo $receipeid;?>">
											<table class="table table-striped table-bordered table-hover bomitems">
												<thead>
													<tr class="tableHead">
														<th width="30%">
															<div class="col-md-12">
																<label class="col-md-4">Recipe Name </label><div class="col-md-8"><input type="text" name="data[<?php echo $receipeid;?>][recipename]" value="<?php echo $billcomponent['recipe']['recipename'];?>" class="form-control recipename"> </div>
															</div>
														</th>
														<th width="30%">
															<div class="col-md-12">
																<label class="col-md-4">Recipe Qty </label><div class="col-md-8"><input type="text" name="data[<?php echo $receipeid;?>][bomQty]" value="<?php echo $billcomponent['recipe']['bomQty'];?>" class="form-control bomQty"> </div>
															</div>
														</th>
														<th>
															<div class="row-fluid col-md-12">
																<?php $checked = ($billcomponent['recipe']['isPrimary'])?('checked="checked"'):(''); ?>
																<label class="col-md-2">Default Recipe </label><div class="col-md-2"><input type="radio" name="isPrimary" value="<?php echo $receipeid;?>" class="form-control isPrimary" <?php echo $checked;?> > </div>
																<div class="col-md-5">
																<label class="col-md-6">Recipe Order </label><div class="col-md-6"><input type="text" name="data[<?php echo $receipeid;?>][recipeOrder]" value="<?php echo $billcomponent['recipe']['recipeOrder'];?>" class="form-control recipeOrder"> </div>
																</div> 
																<div class=""><a href="javascript:;" class="btn btn-circle red removeComponent pull-right"><i class="fa fa-trash"></i></a><span class="addcomponentcontainer"><a href="javascript:;" class="btn btn-circle green addcomponent pull-right"><i class="fa fa-plus"></i><span class="hidden-xs"> Add Comp.</span></a></span></div>
															</div>
														</th>
														
														
													</tr>
												</thead>
											<?php foreach ($billcomponent['items'] as $billcompo) { ?>
												<tr data-id="<?php echo $billcompo['id'];?>">
													<td width="30%"><input value="<?php echo $billcompo['sku'];?>" placeholder="Enter sku" name="data[<?php echo $receipeid;?>][sku][]" class="atutocomplate form-control ui-autocomplete-input" autocomplete="off" type="text"></td>
													<td width="30%"><input value="<?php echo $billcompo['name'];?>" placeholder="Enter name" name="data[<?php echo $receipeid;?>][name][]" class="name form-control" type="text"></td>
													<td ><input value="<?php echo $billcompo['qty'];?>" placeholder="Qty" name="data[<?php echo $receipeid;?>][qty][]" class="qty form-control" style="width:100px;" type="text"><a href="javascript:;" class="btn btn-circle yellow remvoerow pull-right"><i class="fa fa-close"></i></a></td>
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
										<a href="<?php echo base_url('products/billsofmaterials');?>" class="btn btn-circle red">
											<i class="fa fa-close"></i>
											<span class="hidden-xs"> Cancel </span>
										</a>
										<a href="javascript:;" class="btn btn-circle yellow addnewrecipe">
											<i class="fa fa-plus"></i>
											<span class="hidden-xs"> Add New Recipe</span>
										</a>										
									</td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
				</form>
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
				$this.find(".addcomponentcontainer").last().html('<a href="javascript:;" class="btn btn-circle green addcomponent pull-right"><i class="fa fa-plus"></i><span class="hidden-xs"> Add Component</span></a>');
			})
		}
		//removeAddComponent();
		jQuery(".addnewrecipe").on("click",function(){ 
			lastTr = jQuery(".receipecontainer>tbody>tr:last");
			recipeId = lastTr.children("td.receipe").attr("data-recipe");
			if (typeof(recipeId) == "undefined"){
				recipeId = 1;
			}
			else {
				var recipeDatas = [];
				$('.receipe').each(function (i) {
					recipeDatas.push($(this).attr("data-recipe"));
				});
				var maxValue = 0;
				if(recipeDatas){
					maxValue = Math.max.apply(Math,recipeDatas);
				}
				recipeId = parseInt(maxValue)+1;
			}
			appendRow = '<tr><td colspan="5" class="receipe" data-recipe="'+recipeId+'"><table class="table table-striped table-bordered table-hover bomitems"><thead><tr class="tableHead"><th width="30%"><div class="col-md-12"><label class="col-md-4">Recipe Name : </label><div class="col-md-8"><input name="data['+recipeId+'][recipename]" value="" class="form-control recipename" type="text"> </div></div></th><th width="30%"><div class="col-md-12"><label class="col-md-4">Recipe Qty : </label><div class="col-md-8"><input name="data['+recipeId+'][bomQty]" value="1" class="form-control bomQty" type="text"> </div></div></th><th><div class="row-fluid col-md-12"><label class="col-md-2">Default Recipe : </label><div class="col-md-2"><div class="radio"><input name="isPrimary" value="'+recipeId+'" class="form-control isPrimary" type="radio"></div></div> <div class="col-md-5"><label class="col-md-6">Recipe Order :</label><div class="col-md-6"><input type="text" name="data['+recipeId+'][recipeOrder]" value="" class="form-control recipeOrder"> </div></div><div class=""><a href="javascript:;" class="btn btn-circle red removeComponent pull-right"><i class="fa fa-trash"></i></a><span class="addcomponentcontainer"><a href="javascript:;" class="btn btn-circle green addcomponent pull-right"><i class="fa fa-plus"></i><span class="hidden-xs"> Add Comp.</span></a></span></div></div></th></tr></thead><tbody></tbody></table></td></tr>';
			if(jQuery(".receipecontainer>tbody>tr").length){				
				jQuery(".receipecontainer>tbody>tr").last().after(appendRow);
			}
			else{
				jQuery(".receipecontainer>tbody").append(appendRow);
			}
		})
		jQuery(document).on("click",".addcomponent",function(e){			
			e.preventDefault();
			parentTd = jQuery(this).closest(".receipe");
			if(parentTd.find(".tableHead .recipename").val() == ""){
				alert("Please enter recipe name");
				return false;
			}
			if((parentTd.find(".tableHead .bomQty").val() == "") || (parentTd.find(".tableHead .bomQty").val() < 1)){
				alert("Please enter recipe qty");
				return false;
			}			
			recipeId = parentTd.attr("data-recipe");
			appendRow = '<tr><td width="30%"><input type="text" placeholder="Enter sku" class="atutocomplate form-control" name="data['+recipeId+'][sku][]"></td><td width="30%"><input type="text" placeholder="Enter name" class="name form-control" name="data['+recipeId+'][name][]"></td><td ><input type="text" placeholder="Qty" class="qty form-control" style="width:100px;" name="data['+recipeId+'][qty][]"><a href="javascript:;" class="btn btn-circle yellow remvoerow pull-right"><i class="fa fa-close"></i></a><span class="addcomponentcontainer"></span></td></tr>';
			if(jQuery(this).closest("table").find("tbody tr").length){				
				jQuery(this).closest("table").find("tbody tr:last").after(appendRow);
			}
			else{
				jQuery(this).closest("table").find("tbody").append(appendRow);
			}
			jQuery(".atutocomplate").autocomplete({
	            source: availableTags,
				select: function (event, ui) { 
					if(ui.item.value){
						jQuery(this).closest("tr").find(".name").val(productBySku[ui.item.value]['name']);
					}	
				},
				
	        }).autocomplete('enable');
		})
	    jQuery(".atutocomplate").autocomplete({
	        source: availableTags,
			select: function (event, ui) { 
				if(ui.item.value){
					jQuery(this).closest("tr").find(".name").val(productBySku[ui.item.value]['name']);
				}	
			},
	        response: function(event, ui) {
	            if (ui.content.length === 0) {
	                $("#empty-message").text("No results found");
	            } else {
	                $("#empty-message").empty();
	            }
	        }
	    })	    
	    jQuery(document).on("click",".remvoerow",function(){
	    	jQuery(this).closest("tr").remove();
	    });
		jQuery(document).on("click",".removeComponent",function(){
	    	jQuery(this).closest("tr td.receipe").remove();
	    });
		
	})	
</script>