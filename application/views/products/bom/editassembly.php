<?php 
$config = $this->db->get('account_brightpearl_config')->row_array();
$autoAssembly = 0;
if(isset($assemblyData['0']['autoAssembly'])){
	$autoAssembly = (int)$assemblyData['0']['autoAssembly'];
}
$allBinLocations = array();$allBinLocations = array();
foreach($getAllWarehouseLocation as $temp2){
	foreach($temp2 as $t2){
		$allBinLocations[$t2['warehouseId']][] = $t2['name'];
	}
}
$bomParamsData = json_decode($products['params'], true);
$selectPerBinlocations = array();
if($bomParamsData['warehouses']){
	foreach($bomParamsData['warehouses'] as $warehouseId =>  $Pdatawarehouses){
		if($Pdatawarehouses['defaultLocationId'] > 0)
			$selectPerBinlocations[$warehouseId] = $Pdatawarehouses['defaultLocationId'];
	}
}
$costingmethods = array(
	$config['costPriceListbom'] => 'Cost Pricelist',
	'fifo' => 'FIFO',
);
if($config['costPriceListbom'] == 'fifo'){
	$costingmethods = array(
		$config['costPriceListbomNonTrack'] => 'Cost Pricelist',
		'fifo' => 'FIFO',
	);
}
$user_session_data = $this->session->userdata('login_user_data');
$colMd = $user_session_data['accessLabel'] =='2' ? 'col-md-6' : 'col-md-2';
?>
<style>
	#sourecewarehouse .show{display: contents !important;}
</style>
<div class="page-content-wrapper createassembly">
<!-- BEGIN CONTENT BODY -->

	<div class="row">
		<div class="col-md-12">
			<form action="<?php echo base_url('products/assembly/saveassembly');?>" method = "post" id="assemblyform" >
			<input type="hidden" name="data[productId]" value="<?php echo $productId;?>" />
			<input type="hidden" name="data[sku]" value="<?php echo $products['sku'];?>" />
			<input type="hidden" name="data[name]" value="<?php echo $products['name'];?>" />
			<input type="hidden" name="data[assemblyId]" value="<?php echo $assemblyId;?>" />
			<!-- Begin: life time stats -->
			<h3 class="page-title">
				<div class="row">
					<div class="col-md-6">
						Assembly Details
					</div>
					<?php 
					$getAssignToUserId = '';
					if(isset($assemblyDatas['0']['assignToUserId'])){
						$getAssignToUserId = $assemblyDatas['0']['assignToUserId'];
					};  
					if($user_session_data['accessLabel'] == '1' || $user_session_data['accessLabel'] =='3'){?>
						<div class="col-md-4">
							<div class="row">
								
								<div class="col-md-4">
								<label class="control-label" style="font-size:14px;">Assign to User</label>
								  
								</div>
								<div class="col-md-8">
									<select class="form-control assignUserId" name="data[assignToUserId]">
										<option value="">Select User</option>
										<?php foreach ($assignUserList as $assignUsers){?>
										<option value="<?php echo $assignUsers['user_id'];?>" <?php echo  @$getAssignToUserId == $assignUsers['user_id']? 'selected="selected"':""?>><?php echo ucfirst($assignUsers['firstname']) . ' '.ucfirst($assignUsers['lastname']); ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
						</div>
					<?php } ?>
					<div class="<?php echo $colMd;?>">
						<a href="<?php echo base_url('products/assembly')?>" class="btn btn-primary pull-right">Back</a>
					</div>
				</div>
			</h3>
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
										foreach ($warehouseList as $warehouse) {
											$listTemps[] = $warehouse['id'];
											echo '<td>'.$warehouse['name'].'</td>';
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
											}
										?>
									</tr>
								</tbody>
							</table>
						</div>					
					</div>					
				</div>
				<div class="portlet-title">
					 <div class="row-fluid">
						<div class="form-group col-md-6">
                            <label class="control-label col-md-3" style="font-size:20px;">Select Recipe</label>
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
                            <label class="control-label">BOM Recipe Qty : <span class="productQty"></span></label>
                        </div>
					 </div>				
				</div>
				<div class="portlet-body">
					<div class="table-container table-responsive parentAll">	
						<table class="table table-striped table-bordered table-hover receipecontainer datatable_products hide" id="datatable_products">							
							<thead>
								<tr>
									<th style="width:170px;">Recipe #</th>
									<th style="width:141px;">Comp. SKU</th>
									<th style="width:181px;">Comp. Name</th>
									<th style="width:124px;">Qty</th>
									<?php
									sort($warehouseList);
									$listTemps = array();$defaultDisplay = 0; $maxDisambleArray = array(); $wareHouseListDatas = array();
									$width = 50 / count($warehouseList);
									foreach ($warehouseList as $warehouse) {
										$listTemps[] = $warehouse['id'];
										echo '<th style="width:30px;">'.$warehouse['name'].'</th>';
									}
									?>
								</tr>
							</thead>
							<?php
								foreach ($billcomponents as $receipeid => $billcomponent) {
									$receipeid = ($receipeid)?($receipeid):'1'; ?>
									<tbody class="receipid<?php echo $receipeid;?>" style="display: none;" class="hideAll">
										<?php foreach ($billcomponent as $billcompo) { ?>
											<tr data-id="<?php echo $billcompo['id'];?>">
												<td style="width:50px;"><span class="receipeid"><?php echo $receipeid;?></span></td>
												<td style="width:50px;"><input value="<?php echo $billcompo['sku'];?>" name="data[billcomponents][<?php echo $receipeid;?>][<?php echo $billcompo['componentProductId'];?>][sku]" readonly="true" placeholder="Enter sku" class="atutocomplate form-control ui-autocomplete-input" autocomplete="off" type="text"></td>
												<td style="width:50px;"><input value="<?php echo $billcompo['name'];?>" name="data[billcomponents][<?php echo $receipeid;?>][<?php echo $billcompo['componentProductId'];?>][name]" readonly="true"  placeholder="Enter name" class="name form-control" type="text"></td>
												<td style="width:20px;"><input value="<?php echo $billcompo['qty'];?>" name="data[billcomponents][<?php echo $receipeid;?>][<?php echo $billcompo['componentProductId'];?>][qty]" readonly="true" placeholder="Qty"  class="qty form-control pro<?php echo $billcompo['componentProductId'];?>" type="text"></td>  
												<?php
													foreach ($listTemps as $listTemp) {
														echo '<td style="width:20px;">'.@(int)$productStock[$billcompo['componentProductId']]['warehouses'][$listTemp]['onHand'].'</td>';
														$proQtyDatas = @$productStock[$billcompo['componentProductId']]['warehouses'][$listTemp];	
														if(@$proQtyDatas['byLocation'])
														foreach($proQtyDatas['byLocation'] as $loationId => $byLocation){
															$wareHouseListDatas[$billcompo['componentProductId']][$listTemp][$loationId] = (int)$byLocation['onHand'];
															
														}
														if($autoAssembly){
															$savedAssemblyTempData = $savedAssemblyTempDatas[$receipeid][$billcompo['componentProductId']];
															
															$saveTempQty = abs($savedAssemblyTempData['qty']);
															$saveLocationId = $savedAssemblyTempData['locationId'];
															$saveWarehouseId = $savedAssemblyTempData['warehouse'];
															if($saveWarehouseId == $listTemp){
																$wareHouseListDatas[$billcompo['componentProductId']][$listTemp][$saveLocationId] += $saveTempQty;
															}
														}
													}	
												?>
											</tr>
										<?php } ?>
									</tbody>
								<?php } ?>
						</table>
					</div>
				</div>
				<?php
					$proBinLocationCheck = array();
					if(isset($productStock[$products['productId']]['warehouses']))
						foreach($productStock[$products['productId']]['warehouses'] as $warehouseId => $warehousesTemp){
							if(isset($warehousesTemp['byLocation']))
							foreach($warehousesTemp['byLocation'] as $bLocId => $byLocation){
								$proBinLocationCheck[$warehouseId][$bLocId] = $byLocation['onHand'];
								break;
							}									
						}
					foreach($wareHouseListDatas as $productId => $wareHouseListData1){
						foreach($wareHouseListData1 as $warehouseId => $wareHouseListData){
							arsort($wareHouseListData);
							$sortTemp = $wareHouseListData;
							$wareHouseListDatas[$productId][$warehouseId] = $sortTemp;
							foreach($sortTemp as $key => $val){
								$wareHouseListDatas[$productId][$warehouseId]['maxdata'] = array('location' => $key, 'value' => $val);
								break;
							}
						}
					}
					$maxDisambleArray = array();
					foreach($billcomponents as $recipeId => $billcomponent){
						foreach($billcomponent as $billcomps){
							//echo "<pre>billcomps";print_r($billcomps); echo "</pre>";die(__FILE__.' : Line No :'.__LINE__);
							if(@$productBySku[strtolower($billcomps['sku'])]['isStockTracked']){
								$maxAssemble = 0;$tempWareHouses = array();
								if(@$wareHouseListDatas[$billcomps['componentProductId']]){
									foreach($wareHouseListDatas[$billcomps['componentProductId']] as $warehouseId => $wareHouseListData){
										$mxqty = 0;
										$mxqty = $wareHouseListData['maxdata']['value'];
										if($autoAssembly){
											if($warehouseId == $config['defaultAutoAssembyTargetWarehouse']){
												$mxqty = 0;
											}
										}
										$tempWareHouses[$warehouseId] = $mxqty;
									}
								}
								arsort($tempWareHouses);
								foreach($tempWareHouses as $warehouse => $tempWareHouse){
									break;
								}
								$maxDisambleArray[$recipeId][$billcomps['componentProductId']] = array(
									'sku' 					=> $billcomps['sku'],
									'componentProductId' 	=> $billcomps['componentProductId'],
									'warehouse' 			=> $warehouse,
									'qty' 		=> @(int)$wareHouseListDatas[$billcomps['componentProductId']][$warehouse]['maxdata']['value'],
									'binlocation' => @(int)$wareHouseListDatas[$billcomps['componentProductId']][$warehouse]['maxdata']['location'],
								);
							}
						}
					}
				?>
				<div class="portlet-title step3" style="display: none;">
					<div class="caption" style="width: 100%;">
						<div class="table-container">
							<table class="table table-striped table-bordered table-hover table-checkable">
								<thead>
									<tr>
										<th width="25%">
											<div class="form-group">
					                            <label class="control-label col-md-5">Qty to assemble</label>
					                            <div class="col-md-4"> 
					                                <input type="text" name="data[qtydiassemble]" class="form-control qtydiassemble">
					                            </div>
					                        </div>
											Max :<span class="qtydisthmax"><span class="qtydisthmaxval"><?php 
												$defaultDisplay = 0;
												foreach($maxDisambleArray as $maxDisambleArra){
													foreach($maxDisambleArra as $maxDisamble){
														$defaultDisplay = $maxDisamble;
														break;
													}
												}			 								
											//echo (string)$defaultDisplay;
											?></span></span>
                    					</th>
										<th width="25%">
											<div class="form-group">
					                            <label class="control-label col-md-5">Target Warehouse</label>
					                            <div class="col-md-6">
													<select class="form-control targetwarehouse" name="data[targetwarehouse]" >
														<?php
														$selectedTargeWarehouse = $config['warehouse'];
														foreach ($warehouseList as $warehouse) {
															if(!$selectedTargeWarehouse){
																$selectedTargeWarehouse = $warehouse['id'];
															}
															?>
															<option value="<?php echo $warehouse['id'];?>" <?php echo $warehouse['id'] == $selectedTargeWarehouse ? 'selected="selected"' : "" ;?>><?php echo $warehouse['name'];?></option>
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
														<?php
														foreach($costingmethods as $costingmethodKey => $costingmethod){
															$selected = '';
															if($costingmethodKey == $config['costPriceListbom']){
																$selected = 'Selected="selected"';
															}
															echo '<option value="'.$costingmethodKey.'" '.$selected.'>'.$costingmethod.'</option>';
														}
														?>
													</select>
					                            </div>
					                        </div>
										</th>
										<th width="25%">
											<div class="form-group">
					                            <label class="control-label col-md-5">Bin Location</label>
					                            <div class="col-md-6">
														<?php														
														/* echo '<select class="form-control targetBinLocation" name="data[targetBinLocation]" >';
														if(!$products['binlocation']){
															//$products['binlocation'] = $config['location'];
														}
														if(@!$proBinLocationCheck[$selectedTargeWarehouse]){
															$proBinLocationCheck[$selectedTargeWarehouse][$products['binlocation']] = $products['binlocation'];
														}														
														foreach($getAllWarehouseLocation as $accountId => $getAllWarehouses){
															foreach($getAllWarehouses as $getAllWarehouse){
																$selected = '';
																if(@$proBinLocationCheck[$selectedTargeWarehouse][$getAllWarehouse['id']])
																$selected = 'selected="selected"';
																echo '<option class="tarwarehouse'.$getAllWarehouse['warehouseId'].'" value="'.$getAllWarehouse['id'].'" '.$selected.'>'.$getAllWarehouse['name'].'</option>';
															}												
														} 
														echo '</select>'; */
														
														?>
														<input name="data[targetBinLocation]" value="<?php echo $proBinLocationCheck[$selectedTargeWarehouse][$getAllWarehouse['id']];?>" class="form-control targetBinLocation" type="text">
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
					<span class="printThisSection">
						<div class="portlet-title">
							<div class="caption" style="width: 100%;">
								<h3 class="page-title"> Source Warehouses</h3>
							</div>					
						</div>
						<?php foreach($orgWarehouseLocation as $getAllWarehouses){?>
						
						<?php } ?>
						<div class="portlet-body">
							<div class="table-container table-responsive parentAll">
								<table class="table table-bordered datatable_products" id="sourecewarehouse">
									<thead>
										<tr>
											<th width="10%">Recipe #</th>
											<th width="25%">Comp. SKU</th>
											<th width="25%">Comp. Name</th>
											<th width="20%" >Source Warehouse</th>
											<th width="20%" >Source Bin Location</th>
										</tr>
									</thead>
										<?php foreach ($billcomponents as $receipeid => $billcomponent) { 
										?>
											<tbody class="receipid<?php echo $receipeid;?>">
												<?php foreach ($billcomponent as $billcompo) { 
												if(@$productBySku[strtolower($billcompo['sku'])]['isStockTracked']){
												?>
													<tr data-id="<?php echo $billcompo['id'];?>" class="pro<?php echo $receipeid.$billcompo['componentProductId'];?>">
														<td width="10%"><span class="receipeid"><?php echo ($receipeid)?($receipeid):'1';?></span></td>
														<td width="25%"><input value="<?php echo $billcompo['componentProductId'];?>" type="hidden" name="data[<?php echo $receipeid;?>][productId][]"><input value="<?php echo $billcompo['sku'];?>" readonly="true" placeholder="Enter sku" name="data[<?php echo $receipeid;?>][sku][]" class="atutocomplate form-control ui-autocomplete-input" autocomplete="off" type="text"></td>
														<td width="25%"><input value="<?php echo $billcompo['name'];?>" readonly="true"  placeholder="Enter name" name="data[<?php echo $receipeid;?>][name][]" class="name form-control" type="text"></td>
														<td width="20%">
															<select class="form-control sourcewarehouse  selectedWarehose<?php echo $billcompo['componentProductId'];?>" name="data[<?php echo $receipeid;?>][sourcewarehouse][]" >
															<?php
															foreach ($warehouseList as $warehouse) { 
															if($autoAssembly){
																$saveWarehouseId = $savedAssemblyTempDatas[$receipeid][$billcompo['componentProductId']]['warehouse'];
																if($warehouse['id'] == $saveWarehouseId){ ?>
																	<option value="<?php echo $warehouse['id'];?>" ><?php echo $warehouse['name'];?></option>
																<?php }
															}
															else{
															?>
																<option value="<?php echo $warehouse['id'];?>" ><?php echo $warehouse['name'];?></option>
															<?php } } ?>
														</select>
														</td>
														<td width="20%">
															<div class="form-group">
																<div class="col-md-12">
																	<select class="form-control sourceBinLocation sourceBinLocationSelected<?php echo $billcompo['componentProductId']?>" name="data[<?php echo $receipeid;?>][sourceBinLocation][]" > 
																		<?php
																		if($autoAssembly){
																			$savedAssemblyTempData = $savedAssemblyTempDatas[$receipeid][$billcompo['componentProductId']];
																			$foundBinLocation = 0;
																			$saveLocationId = $savedAssemblyTempData['locationId'];
																			foreach ($listTemps as $listTemp) {
																				$binProductChecks = @$productStock[$billcompo['componentProductId']]['warehouses'][$listTemp];
																				if(@$binProductChecks['byLocation']){
																					foreach($binProductChecks['byLocation'] as $loationId => $byLocation){
																						if($loationId == $saveLocationId){
																							$foundBinLocation = 1;
																							echo '<option class="warehouse'.$listTemp.' location'.$loationId.'" data-qty="'.( $byLocation['onHand'] + abs($savedAssemblyTempData['qty'])).'"  data-warehouse="'.$listTemp.'"  data-location="'.$getAllWarehouses[$loationId]['name'].'"  data-receipeid="'.$receipeid.'" data-compId="'.$billcompo['componentProductId'].'" value="'.$getAllWarehouses[$loationId]['id'].'" >'.$getAllWarehouses[$loationId]['name'].' - '.( (int)$byLocation['onHand'] + abs($savedAssemblyTempData['qty'])).'</option>';
																						}
																					}
																				}  
																			}
																			if(!$foundBinLocation){
																				foreach($getAllWarehouseLocation as $accountId => $getAllWarehouses){
																					foreach($getAllWarehouses as $loationId => $byLocation){
																						if($loationId == $saveLocationId){
																						echo '<option class="warehouse'.$listTemp.' location'.$loationId.'" data-qty="'.( (int)$byLocation['onHand'] + abs($savedAssemblyTempData['qty'])).'"  data-warehouse="'.$listTemp.'"  data-location="'.$getAllWarehouses[$loationId]['name'].'"  data-receipeid="'.$receipeid.'" data-compId="'.$billcompo['componentProductId'].'" value="'.$getAllWarehouses[$loationId]['id'].'" >'.$getAllWarehouses[$loationId]['name'].' - '.( (int)$byLocation['onHand'] + abs($savedAssemblyTempData['qty'])).'</option>';
																						}
																					}
																				}
																			}
																		}
																		else{
																		?>
																		<option value="">Select bin location</option>
																		<?php
																		foreach ($listTemps as $listTemp) {
																			$binProductChecks = $productStock[$billcompo['componentProductId']]['warehouses'][$listTemp];
																			if(@$binProductChecks['byLocation']){
																				foreach($binProductChecks['byLocation'] as $loationId => $byLocation){
																					echo '<option class="warehouse'.$listTemp.' location'.$loationId.'" data-qty="'.$byLocation['onHand'].'"  data-warehouse="'.$listTemp.'"  data-location="'.$getAllWarehouses[$loationId]['name'].'"  data-receipeid="'.$receipeid.'" data-compId="'.$billcompo['componentProductId'].'" value="'.$getAllWarehouses[$loationId]['id'].'" >'.$getAllWarehouses[$loationId]['name'].' - '.$byLocation['onHand'].'</option>';
																				}
																			} 
																		}
																		}
																		?>
																	</select>
																</div>
															</div>
														</td>												
													</tr>											
												<?php }} ?>
											</tbody>
											<?php }   ?>
									 <tfoot class="footaction hide">
										<tr><td colspan="5"></td></tr>
										<tr>
											<td colspan="5" style="text-align:center;">
												<input type="hidden" value="" name="data[btnsaveworkinprogress]" class="btnsaveworkinprogressinput" />
												<button type="button" class="btn btn-circle btn-warning btnsaveworkinprogress"> 
													<i class="fa fa-save"></i>
													<span class="hidden-xs"> Save WIP </span>
												</button>
												<button type="button" class="btn btn-circle btn-info btnsavediassembly"> 
													<i class="fa fa-save"></i>
													<span class="hidden-xs"> Submit </span>
												</button>
												<a href="<?php echo base_url('/products/assembly');?>" class="btn btn-circle red">
													<i class="fa fa-close"></i>
													<span class="hidden-xs"> Cancel </span>
												</a>	
											</td>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</span>
				</div>
			</div>
			<div class="message">
				<div class="alert alert-danger" style="display: none;">
				  <strong>Success!</strong> Indicates a successful or positive action.
				</div>
				<div class="alert alert-success" style="display: none;">
				  <strong>Success!</strong> assembly successfully created.
				</div>

			</div>
		</form>
			<!-- End: life time stats -->
		</div>
	</div><div class="printBody" style="display:none;">
	
</div>
<br><br><br><br><br><br>
<style>
body #datatable_products {
    margin-bottom: 10px !important;
}
</style>
<!-- END CONTENT BODY -->
</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="<?php echo $this->config->item('script_url');?>assets/global/plugins/divjs.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-beta4/html2canvas.js" type="text/javascript"></script>
<script type="text/javascript">
	allBinLocations = <?php echo json_encode($allBinLocations);?>;
	selectPerBinlocations = <?php echo json_encode($selectPerBinlocations);?>;
	var warehouseLocationAllDatasNew = <?php echo json_encode($warehouseLocationAllDatas);?>;
	var wareHouseListDatas = <?php echo json_encode($wareHouseListDatas)?>;
	var maxDisambleArray = <?php echo json_encode($maxDisambleArray)?>;
	console.log('warehouseLocationAllDatasNew', warehouseLocationAllDatasNew);
	console.log('wareHouseListDatas', wareHouseListDatas);
	autoAssembly = <?php echo $autoAssembly;?>;
	bomQty = 0; receipid = 0;
	function calculateMaxAssemble(){
		maxDisambleArrayObjs = maxDisambleArray[receipid];
		qtydisthmaxval = 99999999;
		for(key in maxDisambleArrayObjs){
			maxDisambleArrayObj = maxDisambleArrayObjs[key];
			warehouseclass = ".pro"+receipid+key+" .sourcewarehouse option[value='"+maxDisambleArrayObj['warehouse']+"']";
			if(jQuery(warehouseclass).length){
				jQuery(warehouseclass).attr('selected',true);
				jQuery(".pro"+receipid+key+" .sourcewarehouse").trigger("change");
			}
			bin = ".pro"+receipid+key+" .sourceBinLocation .warehouse"+maxDisambleArrayObj['warehouse']+".location"+maxDisambleArrayObj['binlocation'];
			if(jQuery(bin).length){
				jQuery(bin).attr('selected',true);
			}
			onHandQty 		= parseInt(maxDisambleArrayObj['qty']);
			compQty 		= parseInt(jQuery(".receipid"+receipid+" .qty.pro"+key).val());
			if(onHandQty > 0){
				t1 = (onHandQty / compQty) * bomqty;
				if(t1 < qtydisthmaxval){
					qtydisthmaxval = t1;
				}
			}
			else{
				qtydisthmaxval = 0;
			}
			
		}
		if(qtydisthmaxval == 99999999){
			qtydisthmaxval = 0;
		}
		else{
			if(qtydisthmaxval < bomqty){					
				qtydisthmaxval = 0;
			}
			else{
				qtydisthmaxval = qtydisthmaxval - (qtydisthmaxval % bomqty);
			}
		}
		jQuery(".qtydisthmaxval").html(qtydisthmaxval); 
	}
	jQuery(".receipeidselect").on("change",function(){
		jQuery('.parentAll table > tbody').hide();
		receipid = jQuery(this).val();
		jQuery(".productQty").html('');
		if(receipid != 0){
			bomqty = parseInt(jQuery(this).find("option[value='"+receipid+"']").attr('data-bomqty')); 
			jQuery(".datatable_products").removeClass("hide");
			jQuery(".datatable_products").show();
			jQuery(".step3").show();
			//jQuery(".datatable_products > .receipid"+receipid).hide();
			jQuery(".datatable_products > .receipid"+receipid).show();
			//jQuery(".datatable_products .receipid"+receipid).show();
			targetwarehouse = jQuery(".targetwarehouse").val();
			jQuery(".productQty").html(bomqty);
			jQuery(".sourcewarehouse").trigger("change");
			jQuery(".targetwarehouse").trigger("change");
			calculateMaxAssemble();
		}
	})
	jQuery(document).on("change",".sourcewarehouse",function(){
		jQuery(this).closest("td").next("td").find(".sourceBinLocation option").hide();
		jQuery(this).closest("td").next("td").find(".sourceBinLocation option").prop('selected', false);
		jQuery(this).closest("td").next("td").find(".sourceBinLocation option.warehouse" + jQuery(this).val()).show();
		jQuery(".qtydisthmaxval").html('0');
	});
	jQuery(document).on("change",".targetwarehouse",function(){
		defaultAutoAssembyWarehouse = '', allBinLocation = '';defaultAutoAssembyWarehous = '';
		jQuery(".targetBinLocation option").hide();
		jQuery(".targetBinLocation option").removeAttr("selected");
		jQuery(".targetBinLocation option").prop('selected', false);
		jQuery(".targetBinLocation option.tarwarehouse" + jQuery(this).val()).show();
		//jQuery(".targetBinLocation option.tarwarehouse" + jQuery(this).val()).eq(0).prop('selected', true);
		//jQuery(".targetBinLocation option.tarwarehouse" + jQuery(this).val()).eq(0).attr('selected', "selected");
		defaultAutoAssembyWarehouse = jQuery(this).val();
		if(selectPerBinlocations){
			defaultAutoAssembyWarehous = selectPerBinlocations[defaultAutoAssembyWarehouse]; 
			console.log('defaultAutoAssembyWarehous', defaultAutoAssembyWarehous);
			console.log('warehouseLocationAllDatasNew', warehouseLocationAllDatasNew[defaultAutoAssembyWarehous]);
			var getOBJwarehouse = warehouseLocationAllDatasNew[defaultAutoAssembyWarehous];
			console.log('getOBJwarehouse', getOBJwarehouse);
			if (typeof getOBJwarehouse === 'undefined') {
				allBinLocation = allBinLocations[defaultAutoAssembyWarehouse];
				jQuery('.targetBinLocation').val(allBinLocation['0']);
			}else{
				jQuery('.targetBinLocation').val(getOBJwarehouse.name);
			}
		}else{
			allBinLocation = allBinLocations[defaultAutoAssembyWarehouse];
			jQuery('.targetBinLocation').val(allBinLocation['0']);
		}
		jQuery(".targetBinLocation").autocomplete({
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
	
	jQuery(document).on("change",".sourceBinLocation",function(){		
		receipid = jQuery(this).find("option:selected").attr('data-receipeid');
		targetBinLocationClass = '.receipid'+receipid;
		qtydisthmaxval = 99999999;
		jQuery(targetBinLocationClass + ' .sourceBinLocation').each(function(){
			selectedoption = jQuery(this).find("option:selected");
			key = selectedoption.attr('data-compId');	
			onHandQty 		= selectedoption.attr('data-qty');	
			console.log('onHandQty', onHandQty);
			compQty 		= parseInt(jQuery(".receipid"+receipid+" .qty.pro"+key).val());
			console.log('compQty', compQty);
			if(onHandQty > 0){
				t1 = (onHandQty / compQty) * bomqty;
				console.log('bomqty', bomqty);
				console.log('t1', t1);
				if(t1 < qtydisthmaxval){
					qtydisthmaxval = t1;
				}
			}
			else{
				qtydisthmaxval = 0;
			} 
		})
		if(qtydisthmaxval == 99999999){
			qtydisthmaxval = 0;
		}
		else{
			if(qtydisthmaxval < bomqty){					
				qtydisthmaxval = 0;
			}
			else{
				qtydisthmaxval = qtydisthmaxval - (qtydisthmaxval % bomqty);
			}
		}
		console.log('qtydisthmaxval', qtydisthmaxval);
		jQuery(".qtydisthmaxval").html(qtydisthmaxval);
	});	
	jQuery(".qtydiassemble").on("change",function(){
		jQuery(".step4").show();
		jQuery(".footaction").removeClass("hide");
	})	
	jQuery(".btnsavediassembly").on("click",function(e){
		e.preventDefault();
		jQuery(".btnsaveworkinprogressinput").val('0');
		qtydiassemble = parseInt(jQuery(".qtydiassemble").val());
		qtydisthmax = parseInt(jQuery(".qtydisthmaxval").html());
		productQty = parseInt(jQuery(".productQty").html());
		if(!(productQty > 0)){
			alert("Please select recipe");
			return false;
		}
		if(!(qtydiassemble > 0)){
			alert("Please enter qty to assemble ");
			return false;
		}		
		if(autoAssembly == false){
			if(qtydiassemble > qtydisthmax){
				alert("You can not assemble more than : "+qtydisthmax);
				return false;
			}
		}
		if(autoAssembly == false){
			if((qtydiassemble % productQty) != 0){
				alert("Qty to assemble must be multiple of BOM Recipe Qty ( "+productQty+" )");
				return false;
			}			
		}
		if(productQty > 0){
			jQuery(".message .alert").hide();
			jQuery.ajax({ method:'post',url:jQuery("#assemblyform").attr('action') ,data:jQuery("#assemblyform").serialize(),success:function (obj) {
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
	})
	function saveInProgress(responsedata) {
		jQuery(".btnsaveworkinprogressinput").val('1');
		
		jQuery.ajax({ method:'post',url:jQuery("#assemblyform").attr('action') ,data:jQuery("#assemblyform").serialize(),success:function (obj) {
				res = JSON.parse(obj);
				responsedata(res);
			}
		})
	}
	jQuery(".btnsaveworkinprogress").on("click",function(e){
		e.preventDefault();
		qtydiassembles = parseInt(jQuery(".qtydiassemble").val());
		qtydisthmaxs = parseInt(jQuery(".qtydisthmaxval").html());
		
		if(autoAssembly == false){
			if(qtydiassembles > qtydisthmaxs){
				alert("You can not assemble more than : "+qtydisthmaxs);
				return false;
			}
		}
		if(confirm("Do you want to print the assembly?")){
			saveInProgress(function(res){
				if(res.status == '1'){
					jQuery(".portlet").hide();
					jQuery(".message .alert-success").html(res.message);
					jQuery(".message .alert-success").show();
					if(res.isSaveInProgress == '1'){
						jQuery.get( "<?php echo base_url('products/assembly/viewassembly/');?>" + res.assemblyId+'/printView', function( data ) {
							if(data){
								jQuery(".printBody").html(data);
								jQuery('.containerss').printElement({});
							}else{
								alert("Something went wrong while printing. Please try again.");
							}
						});
					}
				}
				else{
					jQuery(".message .alert-danger").show();
					jQuery(".message .alert-danger").html(res.message);
				}
			});
		}else{
			saveInProgress(function(res){
				if(res.status == '1'){
					jQuery(".portlet").hide();
					jQuery(".message .alert-success").html(res.message);
					jQuery(".message .alert-success").show();
				}
				else{
					jQuery(".message .alert-danger").show();
					jQuery(".message .alert-danger").html(res.message);
				}
			});
		}
	});
</script>