<?php 
$config = $this->db->get('account_brightpearl_config')->row_array();
$displayWarehouses =  array();
if($config['displayWarehouses']){
	$displayWarehouses =  explode(",",$config['displayWarehouses']);
}
$allBinLocations = array();
foreach($getAllWarehouseLocation as $temp2){
	foreach($temp2 as $t2){
		$allBinLocations[$t2['warehouseId']][] = $t2['name'];
	}
}
$saveBinDatass = array();
$saveBinDatasTemps = $this->db->get_where('warehouse_binlocation',array('isDefaultLocation' => '1'))->result_array();
foreach($saveBinDatasTemps as $saveBinDatasTemp){
	$saveBinDatass[$saveBinDatasTemp['warehouseId']] = $saveBinDatasTemp['name'];
}
?>
<div class="page-content-wrapper createassembly">
	<h3 class="page-title"> Disassembly Details
	</h3>
	<!-- END PAGE TITLE-->
	<!-- END PAGE HEADER-->
	<div class="row">
		<div class="col-md-12">
			<form action="<?php echo base_url('products/deassembly/saveDeassembly');?>" method = "post" id="disassembleform" >
			<input type="hidden" name="data[productId]" value="<?php echo $productId;?>" />
			<input type="hidden" name="data[sku]" value="<?php echo $products['sku'];?>" />
			<input type="hidden" name="data[name]" value="<?php echo $products['name'];?>" />

			<!-- Begin: life time stats -->
			<div class="portlet ">
				<div class="portlet-title">
					<div class="caption" style="width: 100%;">
						<div class="table-container table-responsive">
							<table class="table table-striped table-bordered table-hover">
								<thead>
									<tr>
										<th>Product ID</th>
										<th>SKU</th>
										<th>Name</th>
										<?php
										sort($warehouseList);
										$listTemps = array();
										$width = 50 / count($warehouseList);
										$maxDisambleArray = array();
										$defaultDisplay = 0;
										foreach ($warehouseList as $warehouse) {
											if(!empty($displayWarehouses)){
												if(in_array($warehouse['id'],$displayWarehouses)){
													$listTemps[] = $warehouse['id'];
													echo '<td>'.$warehouse['name'].'</td>';
												}
											}else{
												$listTemps[] = $warehouse['id'];
												echo '<td>'.$warehouse['name'].'</td>';
											}
										}
										?>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><?php echo $products['productId'];?></td>
										<td><?php echo $products['sku'];?></td>
										<td><?php echo $products['name'];?></td>
										<?php
											foreach ($listTemps as $listTemp) {
												echo '<td>'.@(int)$productStock[$products['productId']]['warehouses'][$listTemp]['onHand'].'</td>';
												if(@$listTemp){
													$maxDisambleArray[$listTemp] = @(int)$productStock[$products['productId']]['warehouses'][$listTemp]['onHand'];
													if(!$defaultDisplay)
													$defaultDisplay = @(int)$productStock[$products['productId']]['warehouses'][$listTemp]['onHand'];
												}
											}
										?>
													
									</tr>
								</tbody>
							</table>
						</div>					
					</div>					
				</div>
				<div class="portlet-title">
					<div class="caption" style="width: 100%">
						<div class="form-group col-md-6">
                            <label class="control-label col-md-3">Select Recipe</label>
                            <div class="col-md-7">
                                <select class="form-control receipeidselect" name="data[receipeid]" >
									<option value="0" data-bomQty="0">Select Recipe</option>
									<?php foreach ($billcomponents as $receipeid => $billcomponent) { ?>
									<option value="<?php echo $receipeid;?>" data-bomQty="<?php echo $billcomponent['0']['bomQty'];?>"><?php echo '('.$receipeid.') '.$billcomponent['0']['recipename'];?></option>
									<?php } ?>
								</select>
                            </div>
                        </div>
						<div class="form-group col-md-6">
                            <label class="control-label col-md-3">BOM Recipe Qty : <span class="productQty"></span></label>
                        </div>
					</div>					
				</div>
				<div class="portlet-body">
					<div class="table-container table-responsive parentAll">	
						<table class="table table-striped table-bordered table-hover receipecontainer datatable_products hide" id="datatable_products" style="margin-bottom: 23px !important;">							
							<thead>
								<tr>
									<th width="5%">Recipe #</th>
									<th width="20%">Comp. SKU</th>
									<th width="20%">Comp. Name</th>
									<th width="5%" >Qty</th>
									<?php
									sort($warehouseList);
									$listTemps = array();
									$width = 50 / count($warehouseList);
									foreach ($warehouseList as $warehouse) {
										if(!empty($displayWarehouses)){
											if(in_array($warehouse['id'],$displayWarehouses)){
												$listTemps[] = $warehouse['id'];
												echo '<td>'.$warehouse['name'].'</td>';
											}
										}else{
											$listTemps[] = $warehouse['id'];
											echo '<th width="'.$width.'%">'.$warehouse['name'].'</th>';
										}
									}
									?>
								</tr>
							</thead>
								<?php 
								if(!$products['binlocation']){
									$products['binlocation'] = $config['location'];
								}
								$defaultBinlocation = array();
								foreach ($billcomponents as $receipeid => $billcomponent) {
								$defaultBinlocation[$receipeid] = $products['binlocation'];
								?>
									<tbody class="receipid<?php echo $receipeid;?>" style="display: none;" class="hideAll">
										<?php foreach ($billcomponent as $billcompo) { ?>
											<tr data-id="<?php echo $billcompo['id'];?>">
												<?php
												$receipeid = ($receipeid)?($receipeid):'1';  
												?>
												<td width="5%"><span class="receipeid"><?php echo $receipeid;?></span></td>
												<td width="20%"><input value="<?php echo $billcompo['sku'];?>" name="data[billcomponents][<?php echo $receipeid;?>][<?php echo $billcompo['componentProductId'];?>][sku]" readonly="true" placeholder="Enter sku" class="atutocomplate form-control ui-autocomplete-input" autocomplete="off" type="text"></td>
												<td width="20%"><input value="<?php echo $billcompo['name'];?>" name="data[billcomponents][<?php echo $receipeid;?>][<?php echo $billcompo['componentProductId'];?>][name]" readonly="true"  placeholder="Enter name" class="name form-control" type="text"></td>
												<td width="5%"><input value="<?php echo $billcompo['qty'];?>" name="data[billcomponents][<?php echo $receipeid;?>][<?php echo $billcompo['componentProductId'];?>][qty]" placeholder="Qty" class="qty form-control" style="width:80px;" type="text"></td>
												<?php
													foreach ($listTemps as $listTemp) {
														echo '<td width="'.$width.'%">'.@(int)$productStock[$billcompo['componentProductId']]['warehouses'][$listTemp]['onHand'].'</td>';
													}
												?>
											</tr>											
										<?php } ?>
									</tbody>
									<?php }
									$productStocks = $productStock[$products['productId']]['warehouses'];
									$maxDisambleArray = array();
									$t1Max = array();$t2Max = array();$t3Max = array();
									foreach($productStocks as $warehouseId => $productStk){
										foreach($productStk['byLocation'] as $locationId =>  $byLocation){
											$t1Max[$locationId] = $byLocation['onHand'];
										}
										arsort($t1Max);
										foreach($t1Max as $key => $val){break;}
										$t2Max[$warehouseId] = $val;
										$t3Max[$warehouseId] = array('max' => array('location' => $key,'value' => $val));
									}
									arsort($t2Max);
									foreach($t2Max as $key => $val){break;}
									$maxDisambleArray = array(
										'warehouse' => @$key,
										'location' 	=> @$t3Max[$key]['max']['location'],
										'qty' 		=> @$val,
									);
									?>
							</tbody>							
						</table>
					</div>
				</div>

				<div class="portlet-title step3" style="display: none;">
					<div class="caption" style="width: 100%;">
						<div class="table-container">
							<table class="table table-striped table-bordered table-hover table-checkable">
								<thead>
									<tr>
										<th width="25%" class="qtydisth">
											<div class="form-group">
					                            <label class="control-label col-md-5">Disassembly Qty</label>
					                            <div class="col-md-4">
					                                <input type="text" name="data[qtydiassemble]" class="form-control qtydiassemble"> 
					                            </div>
												<span class="qtydisthmax">Max : <span class="qtydisthmaxval"><?php echo $maxDisambleArray['qty'];?></span></span>
					                        </div>
                    					</th>  
										<th width="25%">
											<div class="form-group">
					                            <label class="control-label col-md-5">Source Warehouse</label>
					                            <div class="col-md-6">
					                                <select class="form-control targetwarehouse" name="data[targetwarehouse]" >
														<?php
														foreach ($warehouseList as $warehouse) {
														$selected = '';
														if(($maxDisambleArray['warehouse'] == $warehouse['id'])){
															$selected = 'selected="selected"';
														}
														?>
														<option <?php echo $selected;?> value="<?php echo $warehouse['id'];?>"><?php echo $warehouse['name'];?></option>
														<?php } ?>
													</select>
					                            </div>
					                        </div>
										</th>
										<th width="25%">
											<div class="form-group">
					                            <label class="control-label col-md-5">Costing Method</label>
					                            <div class="col-md-6">
					                                <select class="form-control costingmethod" name="data[costingmethod]" >
														<option value="<?php echo $config['costPriceListbom'];?>">Cost Pricelist</option>

													</select>
					                            </div>
					                        </div>
										</th>
										<th width="25%">
											<div class="form-group">
					                            <label class="control-label col-md-5">Bin Location</label>
					                            <div class="col-md-6">
					                                <select class="form-control targetBinLocation" name="data[targetBinLocation]" >
														<option value="">Select Bin Location</option>
															<?php
															foreach ($listTemps as $listTemp) {
																$binProductChecks = $productStock[$products['productId']]['warehouses'][$listTemp];
																if(@$binProductChecks['byLocation']){
																	foreach($binProductChecks['byLocation'] as $loationId => $byLocation){
																		$selected = '';
																		if(($maxDisambleArray['warehouse'] == $listTemp)&&($maxDisambleArray['location'] == $loationId)){
																			$selected = 'selected="selected"';
																		}
																		echo '<option '.$selected.' class="warehouse'.$listTemp.' location'.$loationId.'" data-qty="'.$byLocation['onHand'].'"  data-warehouse="'.$listTemp.'"  data-location="'.$getAllWarehouses[$loationId]['name'].'"  data-receipeid="'.$receipeid.'" data-proId="'.$products['productId'].'" value="'.$getAllWarehouses[$loationId]['id'].'" >'.$getAllWarehouses[$loationId]['name'].' - '.$byLocation['onHand'].'</option>';
																	} 
																} 
															}
															?>
													</select>
					                            </div>
					                        </div>
										</th>
										
									</tr>
								</thead>								
							</table>
						</div>					
					</div>					
				</div>
				<div class="step4" style="display: none;">
					<div class="portlet-title">
						<div class="caption" style="width: 100%;">
							<h3 class="page-title"> Destination Warehouse</h3>			
						</div>					
					</div>
					<div class="portlet-body">
						<div class="table-container table-responsive parentAll">
							<table class="table table-bordered datatable_products" id="sourecewarehouse" style="margin-bottom: 23px !important;">
								<thead>
									<tr>
										<th width="5%">Recipe #</th>
										<th width="15%">Comp. SKU</th>
										<th width="15%">Comp. Name</th>
										<th width="15%">Cost Price</th>
										<th width="15%" >Destination Warehouse</th>
										<th width="15%" >Bin Location</th>
									</tr>
								</thead>
									<?php foreach ($billcomponents as $receipeid => $billcomponent) { ?>
										<tbody class="receipid<?php echo $receipeid;?>">
											<?php foreach ($billcomponent as $billcompo) {
											if(@$productBySku[strtolower($billcompo['sku'])]['isStockTracked']){
											?>
												<tr data-id="<?php echo $billcompo['id'];?>">
													<td width="5%"><span class="receipeid"><?php echo ($receipeid)?($receipeid):'1';?></span></td>
													<td width="15%"><input value="<?php echo $billcompo['componentProductId'];?>" type="hidden" name="data[<?php echo $receipeid;?>][productId][]"><input value="<?php echo $billcompo['sku'];?>" readonly="true" placeholder="Enter sku" name="data[<?php echo $receipeid;?>][sku][]" class="atutocomplate form-control ui-autocomplete-input" autocomplete="off" type="text"></td>
													<td width="15%"><input value="<?php echo $billcompo['name'];?>" readonly="true"  placeholder="Enter name" name="data[<?php echo $receipeid;?>][name][]" class="name form-control" type="text"></td>
													<td width="15%"><input value="<?php echo $getProductPrice[$billcompo['componentProductId']];?>" placeholder="Enter cost price" name="data[<?php echo $receipeid;?>][deassemblyPrice][]" class="deassemblyPrice form-control" type="text"></td>
													<td width="15%">
														<select class="form-control sourcewarehouse" name="data[<?php echo $receipeid;?>][sourcewarehouse][]" >
														<?php
														$selectedTargeWarehouse = 0;
														foreach ($warehouseList as $warehouse) {
															if(!$selectedTargeWarehouse) $selectedTargeWarehouse = $warehouse['id'];
														?>
															
															<option value="<?php echo $warehouse['id'];?>"><?php echo $warehouse['name'];?></option>
														<?php } ?>
													</select>
													</td>
													<td width="15%">
														<div class="form-group">
															<label class="control-label col-md-5">Bin Location</label>
															<div class="col-md-6">
																	<?php
																	/* <select class="form-control sourceBinLocation  sourceBinLocationSelected<?php echo  $billcompo['updateLocationId']?>" name="data[<?php echo $receipeid;?>][sourceBinLocation][]" > 
																	foreach($getAllWarehouseLocation as $accountId => $getAllWarehouses){
																		foreach($getAllWarehouses as $getAllWarehouse){
																			$selected = ($getAllWarehouse['id'] == $products['binlocation'])?('selected="selected"'):('');
																			echo '<option class="warehouse'.$getAllWarehouse['warehouseId'].' location'.$getAllWarehouse['id'].'" value="'.$getAllWarehouse['id'].'" '.$selected.'>'.$getAllWarehouse['name'].'</option>';
																		}												
																	} 
																</select> */
																	?>
																<input name="data[<?php echo $receipeid;?>][sourceBinLocation][]" value="" class="form-control sourceBinLocation" type="text">
															</div>
														</div>
													</td>	
												</tr>											
											<?php }} ?>
										</tbody>
										<?php } ?>

								</tbody>
								<tfoot class="footaction hide">
									<tr><td colspan="6"></td></tr>
									<tr>
										<td colspan="6" style="text-align:center;">
											<button class="btn btn-circle btn-info btnsavediassembly"> 
												<i class="fa fa-save"></i>
												<span class="hidden-xs"> Submit </span>
											</button>
											<a href="<?php echo base_url('/products/deassembly');?>" class="btn btn-circle red">
												<i class="fa fa-close"></i>
												<span class="hidden-xs"> Cancel </span>
											</a>																			
										</td>
									</tr>
								</tfoot>

							</table>
						</div>
					</div>
				</div>
			</div>
			<div class="deloader">

			</div>
			<div class="message">
				<div class="alert alert-danger" style="display: none;">
				  <strong>Success!</strong> Indicates a successful or positive action.
				</div>
				<div class="alert alert-success" style="display: none;">
				  <strong>Success!</strong> Deassembly successfully created.
				</div>

			</div>
		</form>
			<!-- End: life time stats -->
	</div>
</div>
<br><br><br><br><br><br>
<!-- END CONTENT BODY -->
</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">	
<script type="text/javascript">
	saveBinDatass = <?php echo json_encode($saveBinDatass);?>;
	allBinLocations = <?php echo json_encode($allBinLocations);?>;
	var maxDisambleArray = <?php echo json_encode($maxDisambleArray)?>; 
	var defaultBinlocation = <?php echo json_encode($defaultBinlocation); ?>; 
	console.log('defaultBinlocation', defaultBinlocation);
		jQuery(".receipeidselect").on("change",function(){
		jQuery('.parentAll table > tbody').hide();
		receipid = jQuery(this).val();
		jQuery(".productQty").html('');
		if(receipid != 0){
			jQuery(".datatable_products").removeClass("hide");
			jQuery(".datatable_products").show();
			jQuery(".step3").show();
			//jQuery(".datatable_products > tbody > tr").hide();
			//jQuery(".datatable_products > tbody > tr").eq('0').show();
			jQuery(".datatable_products > .receipid"+receipid).show();
			//jQuery(".datatable_products .receipid"+receipid).show();
			bomqty = jQuery(this).find("option[value='"+receipid+"']").attr('data-bomqty');
			jQuery(".productQty").html(bomqty);
			/* jQuery(".sourceBinLocation").find("option[value='"+defaultBinlocation[receipid]+"']").prop('selected', true); */
			jQuery(".sourcewarehouse").trigger("change");
			jQuery(".targetwarehouse").trigger("change");
		}
	})
	jQuery(".qtydiassemble").on("change",function(){
		jQuery(".step4").show();
		jQuery(".footaction").removeClass("hide");
	})
	
	jQuery(document).on("change",".targetwarehouse",function(){
		jQuery(this).closest("tr").find(".targetBinLocation option").hide();
		jQuery(this).closest("tr").find(".targetBinLocation option").prop('selected', false);
		jQuery(this).closest("tr").find(".targetBinLocation option.warehouse" + jQuery(this).val()).show();		
	})
	
	jQuery(document).on("change",".targetBinLocation",function(){
		jQuery(".qtydisthmaxval").html(jQuery(this).find("option:selected").attr('data-qty'));
	})
	
	function btnsaveDeassembly(){
		qtydiassemble = parseInt(jQuery(".qtydiassemble").val());
		productQty = parseInt(jQuery(".productQty").html());
		qtydisthmaxval = parseInt(jQuery(".qtydisthmaxval").html());
		if(!(productQty > 0)){
			alert("Please select recipe");
			return false;
		}
		if(!(qtydiassemble > 0)){
			alert("Please enter qty to disassemble ");
			return false;
		}	
		if(qtydiassemble > qtydisthmaxval){
			alert("You can not disassemble more than : "+qtydisthmaxval);
			return false;
		}
		if((qtydiassemble % productQty) != 0){
			alert("Qty to disassemble must be multiple of BOM Recipe Qty ( "+productQty+" )");
			return false;
		}
		if(productQty > 0){
			jQuery(".message .alert").hide();
			jQuery.ajax({ method:'post',url:jQuery("#disassembleform").attr('action') ,data:jQuery("#disassembleform").serialize(),success:function (obj) {
					res = JSON.parse(obj);	
					if(res.status == '1'){
						jQuery(".portlet").hide();
						jQuery(".message .alert-success").html(res.message);
						jQuery(".message .alert-success").show();
					}
					else{
						jQuery(".message .alert-danger").show();
						jQuery(".message .alert-danger").html(res.message);
					}
				}
			})
		}
	}
	function myFunction(obj){
		jQuery(obj).closest("td").next("td").find(".sourceBinLocation option").hide();
		jQuery(obj).closest("td").next("td").find(".sourceBinLocation option").prop('selected', false);
		jQuery(obj).closest("td").next("td").find(".sourceBinLocation option.warehouse" + jQuery(this).val()).show();
		jQuery(".qtydisthmaxval").html('0');
	}
	
	$(document).on("change", ".targetwarehouse", function(){
		var sourceWarehouceId  = $(this).val();
		$(".sourcewarehouse").val(sourceWarehouceId);
		//$(".sourcewarehouse").change(myFunction).change();
		jQuery(".sourcewarehouse").closest("td").next("td").find(".sourceBinLocation option").hide();
		jQuery(".sourcewarehouse").closest("td").next("td").find(".sourceBinLocation option").prop('selected', false);
		jQuery(".sourcewarehouse").closest("td").next("td").find(".sourceBinLocation option.warehouse" + jQuery(this).val()).show();
		jQuery(".qtydisthmaxval").html('0');
		
	})
	jQuery(document).on("change",".sourcewarehouse",function(){
		jQuery(this).closest("td").next("td").find(".sourceBinLocation option").hide();
		jQuery(this).closest("td").next("td").find(".sourceBinLocation option").prop('selected', false);
		jQuery(this).closest("td").next("td").find(".sourceBinLocation option.warehouse" + jQuery(this).val()).show();
		//jQuery(".qtydisthmaxval").html('0');
		defaultAutoAssembyWarehouse = jQuery(this).val();
		allBinLocation = allBinLocations[defaultAutoAssembyWarehouse];
		defaultBinLocations = saveBinDatass[defaultAutoAssembyWarehouse];
		jQuery('.sourceBinLocation').val(defaultBinLocations);
		jQuery(".sourceBinLocation").autocomplete({
			source: allBinLocation,
			minLength: 2, 
			autoFocus:true,
			select: function (event, ui) {
				if(ui.item.value){
					//console.log(ui.item.value);
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
	jQuery(".btnsavediassembly").on("click",function(e){
		e.preventDefault();		
		var curloadedValue = [];
		$( ".qty" ).each(function(index,element){
			var curtest = $(element).val();
			curloadedValue.push(curtest);
		});
		console.log(loadedValue, curloadedValue);
		if (JSON.stringify(loadedValue) === JSON.stringify(curloadedValue)) {
			btnsaveDeassembly();
		}else{
			if(confirm("Please check the quantities")){
				btnsaveDeassembly();
			}
		}
	})

</script>	
