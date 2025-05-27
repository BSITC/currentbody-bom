<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$startTime = strtotime("now");
$object = file_get_contents("php://input"); 
if($object){
	$url = $_SERVER['REQUEST_URI'];
	$data = json_decode($object, true);	
	if($data['number']){
		$filepath = dirname(dirname(dirname(__FILE__))).'/webhooks/'.date('Y').'/'.date('m').'/'.date('d').'/'.basename($url);
		if(!is_dir(($filepath))){
			mkdir(($filepath),0777,true);
			chmod(($filepath), 0777);
		}
		$startFileName = date('Y-m-d H-i-s ').uniqid();
		$myfile = fopen($filepath.'/'.$startFileName.'.txt', "w");
		$fullFilePatch = $filepath.'/'.$startFileName.'.txt';
		fwrite($myfile, "objectId:".$data['number']."\n\rStart time : ".$startTime.'\r\n'.'Request url :'.$url.'\r\n'.$object); 
	}
}
class Webhooks extends CI_Controller {
	public $file_path,$productMapping,$productMappingBundle;
	public function __construct(){
		parent::__construct();
	}	
	public function processReorderAssembly(){
		$this->load->model('products/products_model','',TRUE);
		$this->products_model->fetchProducts();
		$this->autoAssemblyReorder(); 
	}
	public function fetchProductsOnly(){
		$this->load->model('products/products_model','',TRUE);
		$this->products_model->fetchProducts();
	}
	public function updatePriceList(){
		$this->load->model('products/assembly_model','',TRUE);
		$this->assembly_model->updatePriceList();
	}	
	public function getNotTrackItemPrice($productId = ''){
		if(!$productId){return false;}		
		$this->{$this->globalConfig['fetchProduct']}->reInitialize();
		if($productId){
			$this->db->where(array('products.productId' => $productId));
		}
		
		$datas = $this->db->join('product_bom', 'product_bom.productId = products.productId')->get_where('products', array('products.isBOM' => '1','products.autoBomPriceUpdate' => '1','product_bom.isPrimary' => '1'))->result_array();
		$bomProDatas = array();$proDatas = array();$componentProductIds = array();
		foreach($datas as $data){
			$proDatas[$data['productId']][$data['componentProductId']] = array(
				'bomQty' 	=> $data['bomQty'],
				'qty' 		=> $data['qty'],
			);
			$componentProductIds[$data['componentProductId']] = $data['componentProductId'];
		}
		$productMappings = array();
		$productMappingTemps = $this->db->select('productId,sku,params')->get_where('products')->result_array();
		if($productMappingTemps){
			foreach($productMappingTemps as $productMappingTemp){
				$productMappings[$productMappingTemp['productId']] = $productMappingTemp;
			}
		}
		$bomProIds = array_keys($proDatas);
		$componentProductIds = array_keys($componentProductIds);
		sort($bomProIds);
		sort($componentProductIds);
		$bomPriceDetails = $this->{$this->globalConfig['fetchProduct']}->getProductPriceList($bomProIds);
		$componenetPriceDetails = $this->{$this->globalConfig['fetchProduct']}->getProductPriceList($componentProductIds);
		$updatePriceDetails = array();
		$this->config = $this->db->get('account_brightpearl_config')->row_array();
		$priceListId = $this->config['costPriceListbom'];
		if($priceListId == 'fifo'){
			$priceListId = $this->config['costPriceListbomNonTrack'];
		}
		foreach($proDatas as $bomProId => $proData){
			$nonTrackItemsPrices = 0.00;$nonTrackItemsPrice = 0.00; 
			foreach($proData as $compProId => $row){
				$bomQty = $row['bomQty'];
				$compQty = $row['qty'];
				if($productMappings[$compProId]){
					$params = json_decode($productMappings[$compProId]['params'], true);
					if($params['stock']['stockTracked']){
						continue;
					}
					$nonTrackItemsPrice += ($compQty * $componenetPriceDetails[$compProId][$priceListId]); 
				}
			}
			$nonTrackItemsPrices = $nonTrackItemsPrice / $bomQty;
		}
		return $nonTrackItemsPrices;
	}
	public function processOrderAutoAssemblies(){
		$this->load->model('products/products_model','',TRUE);
		$this->products_model->fetchProducts();
		$this->autoAssembly(); 
	}
	public function alignedSoData(){
		$this->brightpearl->reInitialize();
		$productMapping = array();
		$proTemps = $this->db->select('productId,sku,name')->get_where('products')->result_array();
		foreach($proTemps as $proTemp){
			$productMapping[$proTemp['productId']] = $proTemp;
		}
		$this->config = $this->db->get('account_brightpearl_config')->row_array();
		$datas    = $this->db->order_by('id', 'desc')->get_where('cron_management', array('type' => 'autoAssemblyaligment'))->row_array();
        $cronTime = ($datas['saveTime']) ? ($datas['saveTime']) : (date('Y-m-d\TH:i:s',strtotime('-10 days')));
        $saveTime = date('Y-m-d\TH:i:s',strtotime('-240 min'));
		foreach($this->brightpearl->accountDetails as $accountId => $accountDetails){
			$url = '/order-service/order-search?warehouseId='.$this->config['warehouse'].'&orderTypeId=1&sort=orderId.DESC&updatedOn='.$cronTime.'/';
			$response      = $this->brightpearl->getCurl($url,'get','','json',$accountId)[$accountId];
			if ($response['results']) {
				foreach ($response['results'] as $results) {
					$orderIdArrays[] = $results['0'];
				}
				if ($response['metaData']) {
					for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
						$url1      = $url . '&firstResult=' . $i;
						$response1 = $this->brightpearl->getCurl($url1,'get','','json',$accountId)[$accountId];
						if ($response1['results']) {
							foreach ($response1['results'] as $result) {
								$orderIdArrays[] = $result['0']; 
							}
						}

					}
				}
			}
		}
		$return = array();$saveOrderInfo = array();
		if($orderIdArrays){
			$orderIdArrays = array_unique($orderIdArrays);
			sort($orderIdArrays);
			$orderIdArrays = array_chunk($orderIdArrays,200);
			$checkReservationOrderArray = array();$fullyShipped = array();		
			foreach($orderIdArrays as $orderIdArray){
				if($orderIdArray){
					$resposneDatas = $this->brightpearl->getCurl('/order-service/order/'.implode(",",$orderIdArray));
					$autoAssemblyLogs['salesOrder'][] = $resposneDatas;
					$saveOrderTemps = $this->db->where_in('orderId',$orderIdArray)->get('sales_order')->result_array();
					$saveOrderItemsTemps = $this->db->where_in('orderId',$orderIdArray)->get('sales_item')->result_array();
					foreach($saveOrderTemps as $saveOrderTemp){
						$saveOrderInfo[$saveOrderTemp['orderId']]['orders'] = $saveOrderTemp;
					}
					foreach($saveOrderItemsTemps as $saveOrderItemsTemp){
						$saveOrderInfo[$saveOrderItemsTemp['orderId']]['items'][$saveOrderItemsTemp['rowId']] = $saveOrderItemsTemp;
					}
					foreach($resposneDatas as $resposneData){
						foreach($resposneData as $OrderInfoList){
							if($OrderInfoList['stockStatusCode'] == 'SOA'){
								$fullyShipped[] = $OrderInfoList['id'];
							}
							else if($OrderInfoList['allocationStatusCode'] == 'AAA'){
								$fullyShipped[] = $OrderInfoList['id'];
							}
							
							$totalDiscount = 0;
							$orderId 	= $OrderInfoList['id'];
							$return[$orderId]['orders'] = array(
								'orderId'       => $orderId,
								'delAddressName'=> $OrderInfoList['parties']['customer']['addressFullName'],
								'delPhone' 		=> $OrderInfoList['parties']['customer']['telephone'],
								'customerEmail' => $OrderInfoList['parties']['customer']['email'],
								'customerId'    => $OrderInfoList['parties']['customer']['contactId'],
								'reference'    	=> $OrderInfoList['reference'], 
								'totalAmount'   => $OrderInfoList['totalValue']['total'],
								'totalTax'      => $OrderInfoList['totalValue']['taxAmount'],
								'shippingMethod'=> @$OrderInfoList['delivery']['shippingMethodId'],
								'created'       => gmdate('Y-m-d H:i:s', strtotime($OrderInfoList['createdOn'])),
								'rowData'       => json_encode($OrderInfoList),
							);					
							foreach ($OrderInfoList['orderRows'] as $rowId => $items) {						
								$return[$orderId]['items'][$rowId] = array(
									'orderId'    => $orderId, 
									'rowId'    	 => $rowId, 
									'warehouse'  => $this->config['warehouse'], 
									'sku'  		 => @$items['productSku'],
									'name'  	 => $items['productName'],
									'productId'  => $items['productId'],
									'qty'        => $items['quantity']['magnitude'],
									'price'      => @($items['rowValue']['rowNet']['value'] / $items['quantity']['magnitude']),
									'discountedPrice' => '',
									'tax'   	 => @($items['rowValue']['rowTax']['value'] / $items['quantity']['magnitude'] ),
									'rowData'    => json_encode($items),
								);	
							}
						}
					}
				}
			}
			
			if($return){
				foreach($return as $orderId => $orders){
					if(!isset($saveOrderInfo[$orderId])){
						continue;
					}
					foreach($orders['items'] as $rowId => $item){
						$productId = $item['productId'];						
						if(@$saveOrderInfo[$orderId]['items'][$rowId]){
							$item['id'] = $saveOrderInfo[$orderId]['items'][$rowId]['id'];
							$batchItemUpdate[] = $item;
						}
						unset($saveOrderInfo[$orderId]['items'][$rowId]);
						
					}
				}
			}
			if($saveOrderInfo){
				$deleteIds = array();
				foreach($saveOrderInfo as $orderId => $saveOrderInf){
					if($saveOrderInf['items']){
						foreach($saveOrderInf['items'] as $items){
							$deleteIds[] = $items['id'];
						}
					}
				}
				if($deleteIds){
					$this->db->where_in('id',$deleteIds)->delete('sales_item');
				}
			}	
			if($fullyShipped){
				$fullyShipped = array_unique($fullyShipped);
				$fullyShipped = array_filter($fullyShipped);
				sort($fullyShipped);
				if($fullyShipped['0']){
					$this->db->where_in('orderId',$fullyShipped)->update('sales_item',array('status' => '1'));
				}
			}
			$this->db->insert('cron_management', array('type' => 'autoAssemblyaligment', 'runTime' => $cronTime, 'saveTime' => $saveTime));
		}
	}
	public function checkDeletedOrderIds(){
		$this->brightpearl->reInitialize();
		// added function to check if order is deleted in BP
		foreach($this->brightpearl->accountDetails as $accountId => $accountDetails){
			$allSavedOrderDatas = $this->db->select('orderId')->get_where('sales_item',array('status' => '0'))->result_array();
			$allSavedOrderIds = array_column($allSavedOrderDatas,'orderId');
			$allSavedOrderIds = array_unique($allSavedOrderIds);
			sort($allSavedOrderIds);
			$allSavedOrderIds = array_filter($allSavedOrderIds);
			if($allSavedOrderIds){
				$orderIds = array_chunk($allSavedOrderIds,200);
				$allSavedOrderIds = array_combine($allSavedOrderIds,$allSavedOrderIds);
				$fullyShipped = array();
				foreach($orderIds as $orderId){
					sort($orderId);
					$resposneDatas = $this->brightpearl->getCurl('/order-service/order/'.implode(",",$orderId),'get','','json',$accountId)[$accountId];
					foreach($resposneDatas as $resposneData){
						if($resposneData['stockStatusCode'] == 'SOA'){
							$fullyShipped[] = $resposneData['id'];
						}
						else if($resposneData['allocationStatusCode'] == 'AAA'){
							$fullyShipped[] = $resposneData['id'];
						}
						unset($allSavedOrderIds[$resposneData['id']]);
					}
				}				
			}
			foreach($allSavedOrderIds as $allSavedOrderId){
				$fullyShipped[] = $allSavedOrderId;
			}
			if($fullyShipped){
				$fullyShipped = array_unique($fullyShipped);
				$fullyShipped = array_filter($fullyShipped);
				sort($fullyShipped);
				if($fullyShipped['0']){
					$this->db->where_in('orderId',$fullyShipped)->update('sales_item',array('status' => '1'));
				}
			}
		}
	}
	
	public function autoAssemblyReorder(){
		$this->brightpearl->reInitialize();
		$this->config 	    		 	= $this->db->get('account_brightpearl_config')->row_array();
		$autoCompleteReorderAssembly 	= $this->config['autoCompleteReorderAssembly'];
		$bomMinStockLevel   		 	= $this->config['bomMinStockLevel'];
		$bomAssemblyQty     			= $this->config['bomAssemblyQty'];
		$defaultAutoAssembyWarehouse   	= $this->config['defaultAutoAssembyWarehouse'];
		$defaultAutoAssembyLocation  	= $this->config['defaultAutoAssembyLocation'];
		$defaultAutoAssembyTargetWarehouse  = $this->config['defaultAutoAssembyTargetWarehouse'];
		$defaultAutoAssembyWarehouse2   = $this->config['defaultAutoAssembyWarehouse2'];
		$defaultAutoAssembyLocation2 	= $this->config['defaultAutoAssembyLocation2'];
		$defaultAutoAssembyTargetWarehouse2 = $this->config['defaultAutoAssembyTargetWarehouse2'];
		$defaultCostMethod   		    = $this->config['costPriceListbom'];
		$autoAssemblyLogs 			    = array();	 
		$warehouseList 					= $this->warehouseList()['1'];
		$this->db->order_by('product_bom.isPrimary desc', 'product_bom.recipeOrder asc');
		$datas = $this->db->join('product_bom', '(product_bom.productId = products.productId)')->get_where('products', array('products.isBOM' => '1','products.autoAssemble' => '1'))->result_array();
		$bomProDatas = array();$proDatas = array();$componentProductIds = array();
		$defaultWarehouseListDatas = array(
			$defaultAutoAssembyWarehouse 	=> array(
					'defaultAutoAssembyLocation' 		=> $defaultAutoAssembyLocation,
					'defaultAutoAssembyTargetWarehouse' => $defaultAutoAssembyTargetWarehouse
				),
			$defaultAutoAssembyWarehouse2 	=> array(
					'defaultAutoAssembyLocation' 		=> $defaultAutoAssembyLocation2,
					'defaultAutoAssembyTargetWarehouse' => $defaultAutoAssembyTargetWarehouse2
				),
		);
		$productMappings = array();
		$productMappingsTemps = $this->db->select('productId,isBundle,params')->get_where('products')->result_array();
		foreach($productMappingsTemps as $productMappingsTemp){
			$productMappings[$productMappingsTemp['productId']] = $productMappingsTemp;
		} 
		foreach($datas as $data){
			$params = json_decode($data['params'],true);
			foreach($defaultWarehouseListDatas as $defaultwarehouseID => $defaultWarehouseLocations){
				if(@$params['warehouses'][$defaultwarehouseID]['defaultLocationId']){
					$defaultBinLocation = $params['warehouses'][$defaultwarehouseID]['defaultLocationId'];
				}else{
					$getBinLocationByWarehouse  = $this->getBinLocationByWarehouse($defaultwarehouseID, $defaultWarehouseLocations['defaultAutoAssembyLocation']);
					$defaultBinLocation 		= $getBinLocationByWarehouse['id'];					
				}
				$compProductMappings = $productMappings[$data['componentProductId']];
				$compProductMapping = json_decode($compProductMappings['params'], true);
				if($compProductMapping){
					if($compProductMapping['stock']['stockTracked']){
						$componentProductIds[$data['componentProductId']] = $data['componentProductId'];
						$proDatas[$defaultwarehouseID][$data['productId']][$data['receipeId']][$data['componentProductId']] = array(
							'qty' 				=> $data['qty'],
							'sku' 				=> $data['sku'],
							'name' 				=> $data['name'],
							'sourcewarehouse' 	=> $defaultwarehouseID,
							'sourceBinLocation' => $defaultBinLocation,
						);
					}
				}
				$productMappin = $productMappings[$data['productId']];
				$productMappin = json_decode($productMappin['params'], true);
				if($productMappin){
					if($productMappin['stock']['stockTracked']){
						$componentProductIds[$data['productId']] = $data['productId'];
					}
				}
			}
		}
		$bomDatas = array();
		$bomDataTemps = $this->db->get_where('products', array('isBOM' => '1', 'autoAssemble' => '1'))->result_array();
		foreach($bomDataTemps as $bomDataTemp){
			$bomDatas[$bomDataTemp['productId']] = $bomDataTemp;
		}
		$bomProIds = array_keys($bomDatas);
		$componentItemDatas = array();$componentItemBomQty = array();
		$componentItemDatasTemps = $this->db->where_in('productId',$bomProIds)->get_where('product_bom')->result_array();
		foreach($componentItemDatasTemps as $componentItemDatasTemp){
			$compSavedProductDetails = $productMappings[$componentItemDatasTemp['componentProductId']];
			$compSavedProductDetail = json_decode($compSavedProductDetails['params'], true);
			if(!$compSavedProductDetail['stock']['stockTracked']){
				continue;
			}
			$componentItemDatas[$componentItemDatasTemp['productId']][$componentItemDatasTemp['receipeId']][$componentItemDatasTemp['componentProductId']] = $componentItemDatasTemp;
			$componentItemBomQty[$componentItemDatasTemp['productId']][$componentItemDatasTemp['receipeId']] = $componentItemDatasTemp['bomQty'];
		}
		$productStocks = array();
		if($bomDatas){
			$componentProductIds = array_unique($componentProductIds);
			sort($componentProductIds);
			if(count($componentProductIds) == 1){
				$componentProductIds['1'] = $componentProductIds['0'] + 1;
			}
			$componentProductIds = array_chunk($componentProductIds,10000);	
			$alreadySavedWorkInProgressss = array();
			$alreadySavedWorkInProgressTemps = $this->db->get_where('product_assembly',array('status' => '0','autoAssembly' => '1','isAssembly' => '1','isAssemblyDeleted' => '0'))->result_array();
			foreach($alreadySavedWorkInProgressTemps as $alreadySavedWorkInProgressTemp){
				if($alreadySavedWorkInProgressTemp){
					$alreadySavedWorkInProgressss[$alreadySavedWorkInProgressTemp['warehouse']][$alreadySavedWorkInProgressTemp['productId']][] = $alreadySavedWorkInProgressTemp;
				}
			}
			foreach($componentProductIds as $componentProductId){
				if($componentProductId){
					$productStocksTemps = $this->{$this->globalConfig['fetchProduct']}->getProductStockAssembly(min($componentProductId).'-'.max($componentProductId)); 
					if(isset($productStocksTemps['errors'])){return false;}
					$productStocksTemp = reset($productStocksTemps);
					foreach($productStocksTemp as $key => $productStocksTem){
						if($productStocksTem){
							$productStocks[$key] = $productStocksTem;
						}
					}
				}
			}
			$mailData = array();$createdAssemblyIds = array();
			foreach($defaultWarehouseListDatas as $defaultwarehouseID => $defaultWarehouseLocations){
				$alreadySavedWorkInProgresss = $alreadySavedWorkInProgressss[$defaultwarehouseID];
				if(!$alreadySavedWorkInProgresss){
					$alreadySavedWorkInProgresss = array();
				}
				$saveWorkinProgressDatas = array();
				$quarantineLocation = "";
				$quarantineLocation = $this->{$this->globalConfig['fetchProduct']}->getQuarantineLocation($defaultwarehouseID);
				$quarantineLocation = reset($quarantineLocation);
				foreach($bomDatas as $bomProductId => $bomData){
					try {
					if(@$proDatas[$defaultwarehouseID][$bomProductId]){
						if($productMappings[$bomProductId]['isBundle']){continue;}
						$targetBinLocation 			= '';$targetdefaultLocationId = ''; $autoAssemblyWipWarehouse = '';
						$targetBinLocation 			= $defaultWarehouseLocations['defaultAutoAssembyLocation'];
						$autoAssemblyWipWarehouse   = $defaultWarehouseLocations['defaultAutoAssembyTargetWarehouse'];
						$bomParamsData 	   		    = json_decode($bomData['params'], true);
						$prodcutLevelWarehouseData  = @$bomParamsData['warehouses'][$defaultwarehouseID];
						if(@$prodcutLevelWarehouseData['defaultLocationId']){
							$targetdefaultLocationId    = $prodcutLevelWarehouseData['defaultLocationId'];
							$getBinLocationByWarehouse  = $this->getBinLocationByWarehouseId($defaultwarehouseID, $targetdefaultLocationId);
							if($getBinLocationByWarehouse['name'])
							$targetBinLocation 			= str_replace('..', '',$getBinLocationByWarehouse['name']);
						}
						$assembleCalQty 		    = (int)@$prodcutLevelWarehouseData['reorderQuantity'];
						$bomMinStockLevel 	 	    = (int)@$prodcutLevelWarehouseData['reorderLevel'];
						$bomCurrentStocks 			= (int)@$productStocks[$bomProductId]['warehouses'][$defaultwarehouseID]['onHand'];
						//$alreadySavedWorkInProgress = $this->db->get_where('product_assembly',array('status' => '0','autoAssembly' => '1','isAssembly' => '1', 'warehouse' => $defaultwarehouseID, 'productId' => $bomProductId,'isAssemblyDeleted' => '0'))->result_array();
						$alreadySavedWorkInProgress = @$alreadySavedWorkInProgresss[$bomProductId];
						if(!$alreadySavedWorkInProgress){
							$alreadySavedWorkInProgress = array();
						}
						$alreadySavedWorkInProgressQty = (int)array_sum(array_column($alreadySavedWorkInProgress,'qty'));
						$bomCurrentStocks = $bomCurrentStocks + (int)$alreadySavedWorkInProgressQty;
						//recipe loop starts from here
						foreach($proDatas[$defaultwarehouseID][$bomProductId] as $receipeId => $receipeBasedComponent){
							$tempcomponentItemDatas = array();$isAvailabe = 1;$triggerMail = '0';$componentsMailDatas = array();
							foreach($componentItemDatas[$bomProductId][$receipeId] as $compProId => $componentItemData){
								$proStocks = @$productStocks[$compProId]['warehouses'][$defaultwarehouseID];
								if(isset($proStocks['onHand']) AND ($proStocks['onHand'] > 0)){
									unset($proStocks['byLocation'][$quarantineLocation]);
									foreach($proStocks['byLocation'] as $binLoationId => $proStock){
										if($proStock['onHand']){
											$tempcomponentItemDatas[$compProId][$binLoationId] = @( $proStock['onHand'] * $componentItemData['bomQty'] ) / $componentItemData['qty'];
										}
										else{
											$isAvailabe = 0;
										}
									}
								}
							}
							$maCalTempQty = array();
							foreach($proDatas[$defaultwarehouseID][$bomProductId][$receipeId] as $compId => $proDataTemps){
									$tempcomponentItemData = @$tempcomponentItemDatas[$compId];
									if(!$tempcomponentItemData){
										$maCalTempQty = array();
										break;
									}
									else{
										foreach($tempcomponentItemData as $bin => $binQty){
											$maxBinQty = $binQty;
											foreach($tempcomponentItemDatas as $tt1){
												$maxBinQtyTemp =  @(int)$tt1[$bin];
												if($maxBinQtyTemp < $maxBinQty){
													$maxBinQty = $maxBinQtyTemp;
												}
											}
											$maCalTempQty[$bin] = $maxBinQty;
										}
									}
								
							}
							arsort($maCalTempQty);
							$finalBIn = '';
							$finalMaxCalQty = 0;$checkAssemblePossibleQty = 0;					
							foreach($maCalTempQty as $finalBIn => $finalMaxCalQty){
								break;
							}
							$continue = 1;
							
							$assemblyCalReminderQty = $assembleCalQty % $componentItemBomQty[$bomProductId][$receipeId];
							if($assemblyCalReminderQty > 0){
								break;
							}
							
							
							if($finalMaxCalQty >= $assembleCalQty){
								$checkAssemblePossibleQty = $assembleCalQty;
								foreach($proDatas[$defaultwarehouseID][$bomProductId][$receipeId] as $tKey => $proDat){	
									$proDatas[$defaultwarehouseID][$bomProductId][$receipeId][$tKey]['sourceBinLocation'] = $finalBIn; 
								}
							}
							else{
								$tempCalDatas = array();$tempMax = 99999;
								foreach($proDatas[$defaultwarehouseID][$bomProductId][$receipeId] as $tKey => $proDat){	
									$tempcomponentItemData = @(array)$tempcomponentItemDatas[$tKey];
									if(!$tempcomponentItemData){
										$tempMax = 0;
									}
									arsort($tempcomponentItemData);
									foreach($tempcomponentItemData as $tempBinT => $tempFinalMaxCalQty){
										if($tempFinalMaxCalQty < $tempMax){
											$tempMax = $tempFinalMaxCalQty;
										}
										break;
									}
									
								}
								if($finalMaxCalQty >= $tempMax){
									$checkAssemblePossibleQty = $finalMaxCalQty;
									foreach($proDatas[$defaultwarehouseID][$bomProductId][$receipeId] as $tKey => $proDat){	
											$proDatas[$defaultwarehouseID][$bomProductId][$receipeId][$tKey]['sourceBinLocation'] = $finalBIn; 
									}
								}
								else{
									if($tempMax > 0){
										$checkAssemblePossibleQty = $tempMax;
										foreach($proDatas[$defaultwarehouseID][$bomProductId][$receipeId] as $tKey => $proDat){	
											$tempcomponentItemData = $tempcomponentItemDatas[$tKey];
											arsort($tempcomponentItemData);
											foreach($tempcomponentItemData as $tempBinT => $tempFinalMaxCalQty){
												$proDatas[$defaultwarehouseID][$bomProductId][$receipeId][$tKey]['sourceBinLocation'] = $tempBinT; 
												break;
											}
										}
									}
									else{
										$checkAssemblePossibleQty = 0;
									}
								}
							}
							if($bomMinStockLevel == 0){
								break;
							}
							if($bomCurrentStocks >= $bomMinStockLevel){
								break;
							}
							if($assembleCalQty <= 0 && $bomMinStockLevel <= 0){
								break;
							}
							$qtydiassemble = (int)$checkAssemblePossibleQty;
							$assemblyCalReminderQty = $qtydiassemble % $componentItemBomQty[$bomProductId][$receipeId];
							$qtydiassemble = $qtydiassemble - $assemblyCalReminderQty;
							
							//added by Hitesh 08-04-2021
							//added by Hitesh 27-04-2021
							if($qtydiassemble < $componentItemBomQty[$bomProductId][$receipeId]){
								continue;
							}
							// added by dean on 24 march 2021 => if calculate qty > reOrder qty then post reOrder qty
							if($qtydiassemble > $assembleCalQty){
								$qtydiassemble = $assembleCalQty;
							}
							// end of adding 
							$componentsMailDatas[] = array(
								'bomSku' 				=> $bomData['sku'],
								'bomCurrentStocks' 		=> $bomCurrentStocks,
								'bomMinStockLevel' 		=> $bomMinStockLevel,
								'qtydiassemble' 		=> $qtydiassemble,
								'defaultwarehouseID' 	=> $warehouseList[$defaultwarehouseID]['name'],
								'targetBinLocation' 	=> $targetBinLocation,
							);
							if($qtydiassemble <= 0){
								$mailData[] = $componentsMailDatas;
								continue;
							}
							$additionalArray = array();
							$additionalArray['productId'] = array_keys($proDatas[$defaultwarehouseID][$bomProductId][$receipeId]);
							foreach($proDatas[$defaultwarehouseID][$bomProductId][$receipeId] as $proData){
								$additionalArray['sku'][]     		    =  $proData['sku'];
								$additionalArray['name'][]    		    =  $proData['name'];
								$additionalArray['sourcewarehouse'][]   =  $proData['sourcewarehouse'];
								$additionalArray['sourceBinLocation'][] =  $proData['sourceBinLocation'];
							}
							//updated by hitesh - issue//Two assemblies attempting to allocate one parent unit for each assembly
							foreach($proDatas[$defaultwarehouseID][$bomProductId][$receipeId] as $compProId => $prodcutQtyData){
								$proStocks = @$productStocks[$compProId]['warehouses'][$defaultwarehouseID];
								if($proStocks['onHand'] > 0){
									foreach($proStocks['byLocation'] as $binLoationId => $proStock){
										$productStocks[$compProId]['warehouses'][$defaultwarehouseID]['byLocation'][$binLoationId]['onHand'] = ($proStock['onHand'] - $prodcutQtyData['qty']);
										 $productStocks[$compProId]['warehouses'][$defaultwarehouseID]['onHand'] = ($proStock['onHand'] - $prodcutQtyData['qty']);
									}
								}
							}
							$isBundle = false;
							foreach($proDatas[$defaultwarehouseID][$bomProductId][$receipeId] as $compProId => $compData){
								if($productMappings[$compProId]['isBundle']){
									$isBundle = true;
									break;
								}
							}
							if($isBundle){
								continue;
							}
							$saveWorkinProgressDatas[] = array(
								'productId' 	  	=> $bomProductId,
								'sku' 			  	=> $bomData['sku'],
								'name' 			  	=> $bomData['name'],
								'assemblyId' 	  	=> '',
								'receipeid' 	  	=> $receipeId,
								'billcomponents'  	=> $proDatas[$defaultwarehouseID][$bomProductId],
								'qtydiassemble'   	=> $qtydiassemble,
								'targetwarehouse' 	=> $defaultwarehouseID,
								'costingmethod'   	=> $defaultCostMethod,
								'targetBinLocation' => $targetBinLocation,
								'autoAssemblyWipWarehouse'=> $autoAssemblyWipWarehouse,
								/* 'autoAssemblyBin'	=> $targetLocation, */
								'finalBIn'			=> $finalBIn,
								$receipeId 			=> $additionalArray,
								'btnsaveworkinprogress' => ($autoCompleteReorderAssembly) ? '0' : '1',
								'autoAssembly' 			=> '1',
								'orderId' 				=> '',
								'isOrderAssembly' 		=> '0',
							);
							break;
						}
					}
					}
					catch(Exception $e) {
					  echo 'Message Error: ' .$e->getMessage();
					}
				}
				if($saveWorkinProgressDatas){
					foreach($saveWorkinProgressDatas as $saveWorkinProgressData){
						$this->load->model('products/assembly_model','',TRUE);
						$assemblies = $this->assembly_model->saveAssembly($saveWorkinProgressData);
						$createdAssemblyIds[] = $assemblies;
					}
				}
			}
			if($createdAssemblyIds){
				echo "<b>". count($createdAssemblyIds).' auto-assemblies were created:'.implode(', ', $createdAssemblyIds);
			}
			if($mailData){
				$this->sendReNotSendReorderMail($mailData);
			}
		}
		$this->sendReOrderAutoAssemblyEmail();
	}

	public function autoAssembly(){
		$this->alignedSoData();
		$this->brightpearl->reInitialize();
		$this->load->model('products/assembly_model','',TRUE);
		$this->config = $this->db->get('account_brightpearl_config')->row_array();
		$datas    	  = $this->db->order_by('id', 'desc')->get_where('cron_management', array('type' => 'autoAssembly'))->row_array();
        $cronTime = isset($datas['saveTime']) ? ($datas['saveTime']) : (date('Y-m-d\TH:i:s',strtotime('-10 days')));
        $saveTime = date('Y-m-d\TH:i:s',strtotime('-240 min'));
		$logtime  = date('c');$autoAssemblyLogs = array();
		$path = FCPATH.'logs'.DIRECTORY_SEPARATOR.'Order Logs'.DIRECTORY_SEPARATOR . date('Y-m-d').DIRECTORY_SEPARATOR .$logtime.'.json';
		if(!is_dir(dirname($path))) { @mkdir(dirname($path),0777,true);@chmod(dirname($path), 0777); }
		//stockAllocationId
		$autoAssemblyLogs['LOG TIME'] = $logtime;
		$urls = array('/order-service/order-search?warehouseId='.$this->config['warehouse'].'&orderTypeId=1&stockAllocationId=2&sort=orderId.DESC&createdOn='.$cronTime.'/','/order-service/order-search?warehouseId='.$this->config['warehouse'].'&orderTypeId=1&stockAllocationId=3&sort=orderId.DESC&updatedOn='.$cronTime.'/');
		$orderIdArrays = array();
		$fetchSalesOrderStatusExclude = explode(",", $this->config['fetchSalesOrderStatusExclude']);
		foreach($this->brightpearl->accountDetails as $accountId => $accountDetails){
			foreach($urls as $url){
				$response      = $this->brightpearl->getCurl($url,'get','','json',$accountId)[$accountId];
				$autoAssemblyLogs['ORDER DATA']['URL'][]		= $url;
				$autoAssemblyLogs['ORDER DATA']['RESPONSE'][]	= $response;
				if ($response['results']) {
					foreach ($response['results'] as $results) {
						if($fetchSalesOrderStatusExclude){
							if(in_array($results['3'], $fetchSalesOrderStatusExclude)){
								continue;
							}
						}
						if($results['3'] != '5'){
							$orderIdArrays[] = $results['0'];
						}
					}
					if ($response['metaData']) {
						for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
							$url1      = $url . '&firstResult=' . $i;
							$response1 = $this->brightpearl->getCurl($url1,'get','','json',$accountId)[$accountId];
							if ($response1['results']) {
								foreach ($response1['results'] as $result) {
									if($fetchSalesOrderStatusExclude){
										if(in_array($result['3'], $fetchSalesOrderStatusExclude)){
											continue;
										}
									}
									if($results['3'] != '5'){
										$orderIdArrays[] = $result['0'];
									}
								}
							}
						}
					}
				}
			}
		}
		$saveProductDatas = array();
		$saveProductDatasTemps = $this->db->select("productId, sku, name")->get_where('products',array('products.isBOM' => '1','products.autoAssemble' => '1'))->result_array();
		foreach($saveProductDatasTemps as $saveProductDatasTemp){
			$saveProductDatas[$saveProductDatasTemp['productId']] = $saveProductDatasTemp;
		}
		$proDatasTemps = array();$proDatasTempswithReceipeDatas = array();
		$datas = $this->db->get_where('product_bom')->result_array();
		foreach($datas as $data){
			if(isset($saveProductDatas[$data['productId']])){
				$proDatasTemps[$data['productId']][$data['isPrimary']][] = $data;
				$proDatasTempswithReceipeDatas[$data['productId']][$data['receipeId']][$data['componentProductId']] = $data;
			}
		}
		$autoAssemblyLogs['BOM DATA']['RECIPES'] = $proDatasTemps;
		$componentItemDatas = array(); $bomReceipeDatas = array();$componentItemBomQty = array();
		if($proDatasTemps){
			foreach($proDatasTemps as $productId => $proDatasTemp){
				$isPrimarySet = false;
				foreach($proDatasTemp as $isPrimary => $proDatasTem){
					 if($isPrimary > 0){
						 $isPrimarySet = true;
						 break;
					 }
				}
				if($isPrimarySet){
					foreach($proDatasTemp as $isPrimary =>  $proDatasTem){
						if($isPrimary <= 0){
							continue;
						}
						foreach($proDatasTem as $proDatasTe){
							if($proDatasTe['bomQty'] > 0){
								$componentItemDatas[$proDatasTe['productId']][$proDatasTe['componentProductId']] = $proDatasTe;
								$componentItemBomQty[$proDatasTe['productId']] = $proDatasTe['bomQty'];
							}
							$bomReceipeDatas[$proDatasTe['productId']] = $proDatasTe['receipeId'];
						}
					}
				}else{
					$proDatasTempswithReceipeData = $proDatasTempswithReceipeDatas[$productId];
					if($proDatasTempswithReceipeData){
						ksort($proDatasTempswithReceipeData);
						foreach($proDatasTempswithReceipeData as $proDatasTempswithReceipeDat){
							foreach($proDatasTempswithReceipeDat as $proDatasTempswithReceipeDa){
								if($proDatasTempswithReceipeDa['bomQty'] > 0){
									$componentItemDatas[$proDatasTempswithReceipeDa['productId']][$proDatasTempswithReceipeDa['componentProductId']] = $proDatasTempswithReceipeDa;
									$componentItemBomQty[$proDatasTempswithReceipeDa['productId']] = $proDatasTempswithReceipeDa['bomQty'];
								}
								$bomReceipeDatas[$proDatasTempswithReceipeDa['productId']] = $proDatasTempswithReceipeDa['receipeId'];
							}
							break;
						}
					}
				}
			}
		}
		$autoAssemblyLogs['ORDER DATA']['FOUND ORDERIDs'] = $orderIdArrays;
		$return = array();$saveOrderInfo = array();
		if($orderIdArrays){
			$orderIdArrays = array_unique($orderIdArrays);
			sort($orderIdArrays);
			$orderIdArrays = array_chunk($orderIdArrays,200);
			$checkReservationOrderArray = array();			
			foreach($orderIdArrays as $orderIdArray){
				if($orderIdArray){
					$resposneDatas = $this->brightpearl->getCurl('/order-service/order/'.implode(",",$orderIdArray).'?includeOptional=customFields,nullCustomFields');
					$autoAssemblyLogs['ORDER DATA']['BY ORDERIDs'] = $resposneDatas;
					$saveOrderTemps = $this->db->where_in('orderId',$orderIdArray)->get('sales_order')->result_array();
					$saveOrderItemsTemps = $this->db->where_in('orderId',$orderIdArray)->get('sales_item')->result_array();
					foreach($saveOrderTemps as $saveOrderTemp){
						$saveOrderInfo[$saveOrderTemp['orderId']]['orders'] = $saveOrderTemp;
					}
					foreach($saveOrderItemsTemps as $saveOrderItemsTemp){
						$saveOrderInfo[$saveOrderItemsTemp['orderId']]['items'][$saveOrderItemsTemp['rowId']] = $saveOrderItemsTemp;
					}
					foreach($resposneDatas as $resposneData){
						foreach($resposneData as $OrderInfoList){
							if($OrderInfoList['orderStatus']['orderStatusId'] == '5'){
								continue;
							}
							if($OrderInfoList['shippingStatusCode'] == 'ASS'){
								continue;
							}
							if($OrderInfoList['allocationStatusCode'] == 'AAA'){
								continue;
							}
							if($OrderInfoList['stockStatusCode'] == 'SOA'){
								continue;
							}
							$orderId       = $OrderInfoList['id'];
							$deliveryDate = "";
							if(($this->config['leadTime'] >= 0) && ($this->config['dateType'])){
								if($this->config['dateType'] == "standard"){
									$deliveryDate = date('Y-m-d',strtotime($OrderInfoList['delivery']['deliveryDate']));
								}elseif($this->config['dateType'] == "customField"){
									$deliveryDate = date('Y-m-d',strtotime($OrderInfoList['customFields'][$this->config['deliveryDateCustomField']])); 
								}
								$futureDate = "";
								if($deliveryDate){
									if($this->config['leadTime'] > 0){
										$futureDate = date('Y-m-d',strtotime('+'.$this->config['leadTime'].' days'));
									}elseif($this->config['leadTime'] == 0){
										$futureDate = date('Y-m-d',strtotime('+'.$this->config['leadTime'].' days'));
									}
								}
								if($futureDate){
									if (($deliveryDate >= date('Y-m-d')) && ($deliveryDate <= $futureDate)){
										$return[$orderId]['orders'] = array(
											'orderId'       => $orderId,
											'delAddressName'=> $OrderInfoList['parties']['customer']['addressFullName'],
											'delPhone' 		=> $OrderInfoList['parties']['customer']['telephone'],
											'customerEmail' => $OrderInfoList['parties']['customer']['email'],
											'customerId'    => $OrderInfoList['parties']['customer']['contactId'],
											'warehouse'   	=> $OrderInfoList['warehouseId'],
											'reference'    	=> $OrderInfoList['reference'], 
											'totalAmount'   => $OrderInfoList['totalValue']['total'],
											'totalTax'      => $OrderInfoList['totalValue']['taxAmount'],
											'shippingMethod'=> @$OrderInfoList['delivery']['shippingMethodId'],
											'created'       => gmdate('Y-m-d H:i:s', strtotime($OrderInfoList['createdOn'])),
											'rowData'       => json_encode($OrderInfoList),
										);					
										foreach ($OrderInfoList['orderRows'] as $rowId => $items) {
											$return[$orderId]['items'][$rowId] = array(
												'orderId'    => $orderId, 
												'rowId'    	 => $rowId, 
												'warehouse'  => $this->config['warehouse'], 
												'sku'  		 => @$items['productSku'],
												'name'  	 => $items['productName'],
												'productId'  => $items['productId'],
												'qty'        => $items['quantity']['magnitude'],
												'price'      => @($items['rowValue']['rowNet']['value'] / $items['quantity']['magnitude']),
												'discountedPrice' => '',
												'tax'   	 => @($items['rowValue']['rowTax']['value'] / $items['quantity']['magnitude'] ),
												'rowData'    => json_encode($items),
											);	
										}
									}else{
										continue;
									}
								}else{
									continue;
								}
							}else{
								$return[$orderId]['orders'] = array(
									'orderId'       => $orderId,
									'delAddressName'=> $OrderInfoList['parties']['customer']['addressFullName'],
									'delPhone' 		=> $OrderInfoList['parties']['customer']['telephone'],
									'customerEmail' => $OrderInfoList['parties']['customer']['email'],
									'customerId'    => $OrderInfoList['parties']['customer']['contactId'],
									'warehouse'   	=> $OrderInfoList['warehouseId'],
									'reference'    	=> $OrderInfoList['reference'], 
									'totalAmount'   => $OrderInfoList['totalValue']['total'],
									'totalTax'      => $OrderInfoList['totalValue']['taxAmount'],
									'shippingMethod'=> @$OrderInfoList['delivery']['shippingMethodId'],
									'created'       => gmdate('Y-m-d H:i:s', strtotime($OrderInfoList['createdOn'])),
									'rowData'       => json_encode($OrderInfoList),
								);					
								foreach ($OrderInfoList['orderRows'] as $rowId => $items) {
									$return[$orderId]['items'][$rowId] = array(
										'orderId'    => $orderId, 
										'rowId'    	 => $rowId, 
										'warehouse'  => $this->config['warehouse'], 
										'sku'  		 => @$items['productSku'],
										'name'  	 => $items['productName'],
										'productId'  => $items['productId'],
										'qty'        => $items['quantity']['magnitude'],
										'price'      => @($items['rowValue']['rowNet']['value'] / $items['quantity']['magnitude']),
										'discountedPrice' => '',
										'tax'   	 => @($items['rowValue']['rowTax']['value'] / $items['quantity']['magnitude']),
										'rowData'    => json_encode($items),
									);	
								}
							}
						}
					}
				}
			}
		}
		if($return){
			$batchOrderInsert = array();$batchItemInsert = array();$batchItemUpdate = array();
			foreach($return as $orderId => $orders){
				if(@!$saveOrderInfo[$orderId]){
					$batchOrderInsert[] = $orders['orders'];
				}
				foreach($orders['items'] as $rowId => $item){
					$productId = $item['productId'];
					$item['autoAssembleQtyMessage'] = '0';
					if(@$componentItemDatas[$productId]){
						$item['autoAssembleQtyMessage'] = $item['qty'];
					}
					if(@$saveOrderInfo[$orderId]['items'][$rowId]){
						$item['id'] = $saveOrderInfo[$orderId]['items'][$rowId]['id'];
						$batchItemUpdate[] = $item;
					}
					else{
						$batchItemInsert[] = $item;
					}
				}
			}
			if($batchOrderInsert){
				$this->db->insert_batch('sales_order', $batchOrderInsert); 
			}
			if($batchItemInsert){ 
				$this->db->insert_batch('sales_item', $batchItemInsert);   
			}
			if($batchItemUpdate){
				$this->db->update_batch('sales_item', $batchItemUpdate,'id');
			}
		}
		
		$salesItems = $this->db->get_where('sales_item',array('autoAssembleQtyMessage > ' => '0', 'status' => '0'))->result_array();
		$autoAssemblyLogs['ORDER DATA']['DB SALES ITEM DATA'] = $salesItems;
		$assembleProductInfos = array();$salesOrderInfo = array();$postStockTransferArrays = array();
		foreach($salesItems as $salesItem){
			$qty = @($salesItem['autoAssembleQtyMessage'] - $salesItem['autoAssembledQty']);
			if($qty > 0){
				if(@$assembleProductInfos[$salesItem['orderId']][$salesItem['productId']]['productId']){
					$assembleProductInfos[$salesItem['orderId']][$salesItem['productId']]['qty'] += ($salesItem['autoAssembleQtyMessage'] - $salesItem['autoAssembledQty']);
				}
				else{
					$assembleProductInfos[$salesItem['orderId']][$salesItem['productId']]['qty'] = ($salesItem['autoAssembleQtyMessage'] - $salesItem['autoAssembledQty']);
					$assembleProductInfos[$salesItem['orderId']][$salesItem['productId']]['orderQty'] = $salesItem['qty'];
					$assembleProductInfos[$salesItem['orderId']][$salesItem['productId']]['productId'] = $salesItem['productId'];
					$assembleProductInfos[$salesItem['orderId']][$salesItem['productId']]['rowId'] = $salesItem['rowId'];
					$assembleProductInfos[$salesItem['orderId']][$salesItem['productId']]['warehouse'] = $salesItem['warehouse'];
					$assembleProductInfos[$salesItem['orderId']][$salesItem['productId']]['orderId'] = $salesItem['orderId'];
				}
				if(@$salesOrderInfo[$salesItem['orderId']][$salesItem['rowId']]){
					@$salesOrderInfo[$salesItem['orderId']][$salesItem['rowId']]['qty'] += $salesItem['qty'];	
				}
				else{				
					@$salesOrderInfo[$salesItem['orderId']][$salesItem['rowId']] = array('qty' => $salesItem['qty'],'productId' => $salesItem['productId']);			
				}
			}
			@$assembleProductInfos[$salesItem['orderId']][$salesItem['productId']]['orderTotalQty'] += $salesItem['qty'];

		}
		$bomListIds = array();
		foreach($assembleProductInfos as $assembleProduct){
			foreach($assembleProduct as $assIds){
				if($assIds['productId']){
					$bomListIds[] = $assIds['productId'];
				}
			}
		}
		$bomListIds = array_filter($bomListIds);
		$bomListIds = array_unique($bomListIds);
		$bomProIds = $bomListIds;
		if($bomProIds){
			if(!$this->productMapping){
				$productDatas = $this->db->select('productId,status,isStockTracked,sku,isBundle,binlocation')->get_where('products',array('isStockTracked' => '1'))->result_array();
				foreach($productDatas as $productData){
					if($productData['isStockTracked']){
						$this->productMapping[$productData['productId']] = $productData;
					}
				}				
			}
			if(!$this->productMappingBundle){
				$productDatasbundle = $this->db->select('productId')->get_where('products',array('isBundle' => '1'))->result_array();
				foreach($productDatasbundle as $productDatasbundl){
					$this->productMappingBundle[$productDatasbundl['productId']] = $productDatasbundl;	
				}				
			}
			$bomPriceDetails  		= $this->{$this->globalConfig['fetchProduct']}->getProductPriceList($bomProIds);
			$tempProDatas 	  		= $this->db->select('min(productId) as minpro,max(productId) as maxpro')->get('products')->row_array();
			$productStocks 	  		= $this->{$this->globalConfig['fetchProduct']}->getProductStockAssembly($tempProDatas['minpro'].'-'.$tempProDatas['maxpro']); 
			$productStock 	  		= reset($productStocks);
			$temp 			  		= $this->getReservationInfo($salesOrderInfo);
			$autoAssemblyLogs['ORDER DATA']['RESERVATION INFO'] = $temp;
			$reservationDatas 		= $temp['reservationDatas'];$reservationProductDatas = $temp['reservationProductDatas'];
			$orderCostPriceListbom  = $this->config['costPriceListbom'];
			$warehouseDefaultBinLocations = array();
			foreach($assembleProductInfos as $orderId => $assembleProductInfo){
				//if($orderId != '100634'){continue;}
				$autoAssemblyOrderDatas = array();
				foreach($assembleProductInfo as $productId => $assembleProducts){
					//if($productId != '2307'){continue;}
					if(!$assembleProducts['productId']){continue;}
					if(@$this->productMappingBundle[$productId]){continue;}
					$bomSaveQty = $componentItemBomQty[$productId];
					if($bomSaveQty <= 0){
						continue;
					}
					$isBundle = false;
					foreach($componentItemDatas[$assembleProducts['productId']] as $compProId => $componentItemData){
						if(@$this->productMappingBundle[$compProId]){
							$isBundle = true;
							break;
						}
					}
					if($isBundle){
						continue;
					}
					$autoAssemblyLogs['ORDER DATA'][$orderId]["COMPONENTS STOCKS"][$productId] = $productStock[$productId];
					$assembleTempQty = @$productStock[$productId]['warehouses'][$assembleProducts['warehouse']];
					$benLocationIds = array();
					if(is_array($assembleTempQty['byLocation']))
					$benLocationIds = @array_keys($assembleTempQty['byLocation']);					
					$needAllocateQty = $assembleProducts['orderTotalQty'] - ((int) @$productStock[$productId]['warehouses'][$assembleProducts['warehouse']]['onHand'] + (int) @$reservationProductDatas[$orderId][$productId]['quantity']);
					if($needAllocateQty <= 0){continue;}
					
					/* $tempQty = $assembleProducts['qty'] - (int) @$productStock[$productId]['warehouses'][$assembleProducts['warehouse']]['onHand'];
					$temp1Qty = $assembleProducts['qty'] - (int) @$reservationProductDatas[$orderId][$productId]['quantity'];					
					if($temp1Qty <= 0){continue;}
					if($tempQty <= 0){continue;}
					if($temp1Qty > $tempQty){
						$tempQty = $tempQty;
					} */
					
					$tempQty = $needAllocateQty;
					
					if($tempQty < $bomSaveQty){
						$assembleCalQty = $bomSaveQty;
					}
					else{
						if(($tempQty % $bomSaveQty) != 0){
							$assembleCalQty = @$tempQty + ($bomSaveQty - ($tempQty % $bomSaveQty));	
						}
						else{							
							$assembleCalQty = $tempQty;
						}
					}
					$tempcomponentItemDatas = array();$isAvailabe = 1;
					foreach($componentItemDatas[$assembleProducts['productId']] as $compProId => $componentItemData){
						if(!$this->productMapping[$compProId]){
							continue;
						}
						$proStocks = @$productStock[$compProId]['warehouses'][$assembleProducts['warehouse']];
						if($proStocks['byLocation']){
							foreach($proStocks['byLocation'] as $binLoationId => $proStock){
								if($proStock['onHand']){
									/* if($componentItemData['qty'] >= 0){ */
									if($componentItemData['qty'] <= 0){
										$tempcomponentItemDatas[$compProId][$binLoationId] = 0;
										continue;
									}else{
										$tempcomponentItemDatas[$compProId][$binLoationId] = @( $proStock['onHand'] * $componentItemData['bomQty'] ) / $componentItemData['qty'];
									}
								}
								else{
									$isAvailabe = 0;
								}
							}						
						}else{
							$isAvailabe = 0;
						}
					}
					if(!$isAvailabe){ continue; }
					$isUsedComp = 0; $selectedlocationId = '';
					$checkAssemblePossibleQty = $this->checkAssemblePossibleQty($assembleCalQty, $tempcomponentItemDatas);	
					if(@$checkAssemblePossibleQty['assembleCalQty'] <= 0){ continue; }
					if($checkAssemblePossibleQty['assembleCalQty'] < $bomSaveQty){ continue; }
					if($bomSaveQty <= 0 ){ continue; }
					$assembleCalQty = $checkAssemblePossibleQty['assembleCalQty'] - ($checkAssemblePossibleQty['assembleCalQty'] % $bomSaveQty);
					$productMappingTemps = $this->productMapping[$productId];
					$increaseBinLocation = @$this->getBinLocationByWarehouse($this->config['warehouse'], $this->config['defaultAutoAssembyLocation'])['id'];
					if($productMappingTemps['binlocation']){
						$increaseBinLocation = $productMappingTemps['binlocation'];
					}
					$getBinLocationByWarehouseId = $this->getBinLocationByWarehouseId($this->config['warehouse'], $increaseBinLocation);
					if(!$increaseBinLocation){
						if(!$warehouseDefaultBinLocations[$this->config['warehouse']]){
							$warehouseDefaultBinLocations[$this->config['warehouse']] = $this->getDefaultWarehouseLocation($this->config['warehouse']);
						}
						$increaseBinLocation = $warehouseDefaultBinLocations[$this->config['warehouse']];
					}
					$decreaseBinLocation = array();
					if($assembleCalQty >= $bomSaveQty){
						foreach($componentItemDatas[$assembleProducts['productId']] as $compProId => $componentItemData){
							if(@$this->productMapping[$componentItemData['componentProductId']]){
								if($componentItemData['bomQty'] <= 0){
									continue;
								}
								
								// added by hitesh to send comp location id on dated 04 Aug 2022
								$tempcomponentItemData = $tempcomponentItemDatas[$componentItemData['componentProductId']];
								arsort($tempcomponentItemData);
								$comLocId = array_keys($tempcomponentItemData)['0'];
								// end of adding
								$decreaseBinLocation[$compProId] = ($comLocId)?($comLocId):$checkAssemblePossibleQty['locationId'];
							}
						}
						//assemblymodel will call
						$recipeId = $bomReceipeDatas[$productId]; $additionalArray = array();
						$additionalArray['productId'] = array_keys($componentItemDatas[$productId]);
						foreach($componentItemDatas[$productId] as $componentItemData){
							$additionalArray['sku'][]     		    =  $componentItemData['sku'];
							$additionalArray['name'][]    		    =  $componentItemData['name'];
							$additionalArray['sourcewarehouse'][]   =  $assembleProducts['warehouse'];
							$additionalArray['sourceBinLocation'][] =  $increaseBinLocation;
						}
						$autoAssemblyOrderDatas[] = array(
							'productId' 	  				=> $productId,
							'sku' 			  				=> $saveProductDatas[$productId]['sku'],
							'name' 			  				=> $saveProductDatas[$productId]['name'],
							'assemblyId' 	  				=> '',
							'receipeid' 	  				=> $recipeId,
							'billcomponents'  				=> $componentItemDatas[$productId],
							'qtydiassemble'					=> $assembleCalQty,
							'targetwarehouse' 				=> $assembleProducts['warehouse'],
							'costingmethod'   				=> $orderCostPriceListbom,
							'targetBinLocation' 			=> $getBinLocationByWarehouseId['name'],
							'autoAssemblyWipWarehouse'		=> '',
							'finalBIn'						=> @$finalBIn,
							$recipeId 			 			=> $additionalArray,
							'btnsaveworkinprogress' 		=> '0',
							'autoCompleteReorderAssembly' 	=> '0',
							'autoAssembly' 					=> '0', 
							'orderId' 						=> $orderId, 
							'isOrderAssembly'       		=> '1', 
							'decreaseBinLocation'       	=> $decreaseBinLocation, 
						);
					}
				}
				if($autoAssemblyOrderDatas){
					foreach($autoAssemblyOrderDatas as $autoAssemblyOrderData){
						if(!isset($this->assembly_model)){
							$this->load->model('products/assembly_model','',TRUE);
						}
						$this->assembly_model->saveAssembly($autoAssemblyOrderData);
					}
				}
			}
			$temp = $this->getReservationInfo($salesOrderInfo);//NEED TO ASK DEAN WHY ITS USING TWICE IN THE CODE
			$reservationDatas = $temp['reservationDatas'];
			$tempProDatas = $this->db->select('min(productId) as minpro,max(productId) as maxpro')->get('products')->row_array();
			$productStocks = $this->{$this->globalConfig['fetchProduct']}->getProductStockAssembly($tempProDatas['minpro'].'-'.$tempProDatas['maxpro']); 
			$productStock = reset($productStocks);
			foreach($salesOrderInfo as $orderId => $salesOrders){
				$postReserveProducts = array(); $postMethod = 'POST';$reserverUrl = '/warehouse-service/order/'.$orderId.'/reservation/warehouse/'.$this->config['warehouse'];
				$saveOrderInfosTemps = $this->db->select("qty,productId,rowId")->get_where("sales_item",array("orderId" => $orderId))->result_array();
				$noteAssemblyIdsTemps = $this->db->select("createdId,orderId")->get_where("product_assembly",array("orderId" => $orderId, 'isNoteSent' => '0', 'isAssembly' => '1'))->row_array();
				// $saveRowInfos = json_decode($saveOrderInfosTemps['rowData'],true);
				foreach($saveOrderInfosTemps as $rowId => $salesOrder){
					$rowId = $salesOrder['rowId'];
					$productId = $salesOrder['productId'];	
					if($productId < 1001){continue;}
					if(@$reservationDatas[$orderId]){
						$postMethod = 'PUT';
						$reserverUrl = '/warehouse-service/order/'.$orderId.'/reservation';
					}
					$availableQty = (int) @$productStock[$productId]['warehouses'][$this->config['warehouse']]['onHand'] + @$reservationDatas[$orderId][$rowId]['quantity'];
					$orderQty = (int)$salesOrder['qty'];
					$reserveQty = $orderQty;
					if($availableQty <= $orderQty){
						$reserveQty = $availableQty;
					}
					if($reserveQty > 0){
						$postReserveProducts[] = array(
							'productId' 		=> $productId,
							'salesOrderRowId' 	=> $rowId,
							'quantity' 			=> $reserveQty,
						);
						@$productStock[$productId]['warehouses'][$this->config['warehouse']]['onHand'] = ($productStock[$productId]['warehouses'][$this->config['warehouse']]['onHand']) - ($reserveQty - $reservationDatas[$orderId][$rowId]['quantity']);
					}				
				}
				$autoAssemblyLogs['ORDER DATA']['RESERVATION'][$orderId]['URL'] 	= $reserverUrl;
				$autoAssemblyLogs['ORDER DATA']['RESERVATION'][$orderId]['METHOD'] 	= $postMethod;
				if($postReserveProducts){
					$request = array(
						'products' => $postReserveProducts,
					);					
					$autoAssemblyLogs['ORDER DATA']['RESERVATION'][$orderId]['REQUEST'] = $request;
					foreach($this->brightpearl->accountDetails as $accountId => $accountDetails){
						$res  = $this->brightpearl->getCurl($reserverUrl,$postMethod,json_encode($request),'json',$accountId)[$accountId];
						$autoAssemblyLogs['ORDER DATA']['RESERVATION'][$orderId]['RESPONSE'] = $res;
						if(@!$res['errors']){
							foreach($postReserveProducts as $postReserveProduct){
								$this->db->where(array('orderId' => $orderId, 'rowId' => $postReserveProduct['salesOrderRowId']))->update('sales_item',array('autoAssembledQty' => $postReserveProduct['quantity']));
							}
							$notesUrl = '/order-service/order/'.$orderId.'/note';
							$noteAssemblyId = $noteAssemblyIdsTemps['createdId'];
							$notemessage = "Allocation performed by assembly app";
							if($noteAssemblyId){
								$notemessage = 'Allocation Assembly Id#'.$noteAssemblyId;
							}
							$noteRequest = array(
								'text' => $notemessage,
							);
							$notesRes  = $this->brightpearl->getCurl($notesUrl,'POST',json_encode($noteRequest),'json',$accountId)[$accountId];
							if(!isset($notesRes['errors'])){
								if($noteAssemblyId)
									$this->db->where(array('orderId' => $orderId, 'createdId' => $noteAssemblyId))->update('product_assembly',array('isNoteSent' => '1'));
							}
						}
					}
				}
			}
			$salesOrderIds = array_keys($salesOrderInfo);
			$salesOrderIds = array_chunk($salesOrderIds,200);
			foreach($salesOrderIds as $salesOrderId){
				sort($salesOrderId);
				$response  = $this->brightpearl->getCurl('/order-service/order/'.implode(",",$salesOrderId));
				$autoAssemblyLogs['ORDER DATA']['ALLOCATION STATUS']['URL'] = '/order-service/order/'.implode(",",$salesOrderId);
				$autoAssemblyLogs['ORDER DATA']['ALLOCATION STATUS']['RESPONSE'] = $response;
				foreach($response as $results){
					foreach($results as $result){
						$status = 0;
						if($result['allocationStatusCode'] == 'AAA'){
							$status = 1;
							$this->db->set('autoAssembledQty', 'autoAssembleQtyMessage',false)->where(array('orderId' => $result['id']))->update('sales_item',array('status' => '1'));
						}else if($result['allocationStatusCode'] == 'ANR'){
							$status = 2;
						}else if($result['allocationStatusCode'] == 'ANA'){
							$status = 3;
						}else if($result['allocationStatusCode'] == 'APA'){
							$status = 4;
						}
						$this->db->where(array('orderId' => $result['id']))->update('sales_order',array('rowData' => json_encode($result), 'status' => $status));
					}
				}
			}
		}
		file_put_contents($path,json_encode($autoAssemblyLogs),FILE_APPEND);
		$this->sendAutoAssemblyEmail();
	}
	
	public function storeBinLocation(){
		$this->load->model('products/assembly_model','',TRUE);
		$this->assembly_model->getAllWarehouseLocationInsert();
		$this->assembly_model->storeWarehouse();
	}
	public function sendReNotSendReorderMail($mailData){
		$config = $this->db->get_where('account_brightpearl_config')->row_array();
		$html = '<div> Dear '.ucfirst($config['name']).',<br><p></p>Please note that we are unable to assemble the BOM due to low inventory. Please see details below:</div><br><br>
		<table style="border:1px solid #e7ecf1;padding:5px;">
			<thead style="border-bottom:1px solid #e7ecf1;">
				<tr>
					<th>Bom Sku</th>
					<th>Bom Current Stock</th>
					<th>Bom Minimum stock level</th>
					<th>Assembled Quantity</th>
					<th>Warehouse</th>
					<th>Target Bin Location</th>
				</tr>
			</thead>
			<tbody>';
			foreach($mailData as $mailD){
				foreach($mailD as $mail){
					$html .= '<tr>
						<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$mail['bomSku'].'</td>
						<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$mail['bomCurrentStocks'].'</td>
						<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$mail['bomMinStockLevel'].'</td>
						<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$mail['qtydiassemble'].'</td>
						<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$mail['defaultwarehouseID'].'</td>
						<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$mail['targetBinLocation'].'</td>
					</tr>';
				}
			}
			$html .='</tbody></table>';
			$this->load->library('mailer');
			$subject   = 'Bill of Materials Alert - Unable to assemble BOM-'.date('Y-m-d');
			$from 	   = array('alert@businesssolutionsinthecloud.com' => 'Info');
			$this->mailer->send($config['autoAssemblyEmail'],$subject,$html,$from);
	}
	public function getBinLocationByWarehouse($warehouseId, $binLocation){
		return $this->db->get_where('warehouse_binlocation',array('name' => $binLocation,'warehouseId' => $warehouseId))->row_array();
	}
	public function getBinLocationByWarehouseId($warehouseId, $locationId){
		return $this->db->get_where('warehouse_binlocation',array('id' => $locationId,'warehouseId' => $warehouseId))->row_array();
	}
	public function releaseAutoAssembly(){
		$this->load->model('products/assembly_model','',TRUE);
		$this->assembly_model->addProductInExternalTransfer();
		//$this->assembly_model->releaseAutoAssembly();
	}
	 
	function checkAssemblePossibleQty($assembleCalQty, $tempcomponentItemDatas){
		$return = array('assembleCalQty' => $assembleCalQty,'locationId' => '');
		if($assembleCalQty > 0){
			foreach($tempcomponentItemDatas as $compId => $tempcomponentItemData){
				if($tempcomponentItemData){
					arsort($tempcomponentItemData);
					foreach($tempcomponentItemData as $binLocation => $qty){
						if($qty <= 0){
							$return = array('assembleCalQty' => $assembleCalQty,'locationId' => '');
							return $return;
						}
						else if($qty >= $assembleCalQty){
							$return = array('assembleCalQty' => $assembleCalQty,'locationId' => $binLocation);
						}
						else{
							$this->checkAssemblePossibleQty($qty,$tempcomponentItemDatas);
						}
					}					
				}
				else{
					$return = array('assembleCalQty' => $assembleCalQty,'locationId' => '');
					return $return;
				}
			}
		}
		return $return;
	}
	public function getReservationInfo($salesOrderInfo){  
		$return = array('reservationDatas' => array(), 'reservationProductDatas' => array());
		if($salesOrderInfo){
			$reservationDatas = array();$reservationProductDatas = array();		
			foreach($this->brightpearl->accountDetails as $accountId => $accountDetails){
				foreach($salesOrderInfo as $orderId => $salesOrders){
					$response  = $this->brightpearl->getCurl('/warehouse-service/order/'.$orderId.'/reservation','get','','json',$accountId)[$accountId];
					if(@!$response['errors']){
						foreach($response as $res){
							foreach($res['orderRows'] as $rowId => $orderRows){
								@$reservationDatas[$orderId][$rowId]['quantity'] += $orderRows['quantity'];
								@$reservationProductDatas[$orderId][$orderRows['productId']]['quantity'] +=$orderRows['quantity'];
							}
						}
					}
				}
			}
		}
		return array('reservationDatas' => $reservationDatas, 'reservationProductDatas' => $reservationProductDatas);
	}
	
	public function getDefaultWarehouseLocation($locationId)
    {
        $getDefaultWarehouseLocation = $this->{$this->globalConfig['fetchProduct']}->getDefaultWarehouseLocation($locationId);
        $return                      = array_shift($getDefaultWarehouseLocation); 
        return $return;
    }
	public function createWebHook($subscribeTo = 'product.destroyed', $url = ''){
		$this->brightpearl->reInitialize();
        $params = array(
            'subscribeTo'   => $subscribeTo, 
            'httpMethod'    => 'POST',
            'uriTemplate'   => base_url('webhooks/deleteProduct'),
            'bodyTemplate'  => '{"accountCode": "${account-code}", "resourceType": "${resource-type}", "id": "${resource-id}", "lifecycle-event": "${lifecycle-event}"}',
            'contentType'   => 'application/json',
            'idSetAccepted' => false,
        );
		// echo "<pre>params"; print_r($params); echo "</pre>"; die(__FILE__.__LINE__);
        $url      = '/integration-service/webhook/';
        $response = $this->brightpearl->getCurl($url, "POST", json_encode($params));
        echo "<pre>"; print_r($response); echo "</pre>"; die(__FILE__.__LINE__);
    }
    public function deleteWebhook($id)
    {
		$this->brightpearl->reInitialize();
        $url      = '/integration-service/webhook/' . $id;
        $response = $this->brightpearl->getCurl($url, "DELETE");
        return $response;
    }
    public function getWebhook()
    {
		$this->brightpearl->reInitialize();
        $url      = '/integration-service/webhook/';
        $response = $this->brightpearl->getCurl($url); 
		echo "<pre>"; print_r($response); echo "</pre>"; die(__FILE__.__LINE__);
        return $response;
    }
	
	public function sendAutoAssemblyEmail(){
		$config = $this->db->get_where('account_brightpearl_config')->row_array();
		$datas = $this->db->get_where('product_assembly',array('assemblyEmailSent' => '0','isOrderAssembly' => '1','orderId <>' => ''))->result_array();
		$autoAssemblyDatas = array();
		foreach($datas as $data){
			$key = ($data['isAssembly'])?('bom'):('component');
			$autoAssemblyDatas[$data['createdId']][$key][] = $data;
		}
		if($autoAssemblyDatas){
			$getWarehouseMaster = $this->getWarehouseMaster();
			$this->load->library('mailer');
			$body = 'Hi there,
			<p>Please see details of the sales order auto-assembly(ies) created by the BOM app :</p>
			<table style="border:1px solid #e7ecf1;padding:5px;">
			<thead style="border:1px solid #e7ecf1;padding:5px;">
				<tr>
					<th style="border:1px solid #e7ecf1;text-align: left;">Assembly Id</th>
					<th style="border:1px solid #e7ecf1;text-align: left;">Sales Order Id</th>
					<th style="border:1px solid #e7ecf1;text-align: left;">BOM SKU</th>
					<th style="border:1px solid #e7ecf1;text-align: left;">Assembled Qty</th>
					<th style="border:1px solid #e7ecf1;text-align: left;">Warehouse</th>
					';
				$body .= '</tr>
			</thead>';
			 
			foreach($autoAssemblyDatas as $i => $autoAssemblyData){
				$orderIds = array_unique(array_column($autoAssemblyData['component'],'orderId'));
				$body .= '<tr><td style="border:1px solid #e7ecf1;text-align: left;">'.$autoAssemblyData['bom']['0']['createdId'].'</td><td  style="border:1px solid #e7ecf1;text-align: left;">'.implode(",",$orderIds).'</td><td  style="border:1px solid #e7ecf1;text-align: left;">'.$autoAssemblyData['bom']['0']['sku'].'</td><td  style="border:1px solid #e7ecf1;text-align: left;">'.$autoAssemblyData['bom']['0']['qty'].'</td><td  style="border:1px solid #e7ecf1;text-align: left;">'.$getWarehouseMaster[$autoAssemblyData['bom']['0']['warehouse']]['warehouseName'].'</td>';
				$body .= '</tr>';
			}			
			$body .= '</table>
			<br>
			Thanks & regards<br>
			'; 
			$from = array('alert@businesssolutionsinthecloud.com' => 'Alert');
			$subject = 'Sales order auto assembly report';
			$res = $this->mailer->send($config['autoAssemblyEmail'],$subject,$body,$from); 
			if($res){
				$this->db->where(array('assemblyEmailSent' => '0','isOrderAssembly' => '1'))->update('product_assembly',array('assemblyEmailSent' => '1'));
			} 
		} 
	}
	public function sendReOrderAutoAssemblyEmail(){
		$config = $this->db->get_where('account_brightpearl_config')->row_array();
		$datas = $this->db->get_where('product_assembly',array('assemblyEmailSent' => '0','autoAssembly' => '1','isAssemblyDeleted' => '0','isImportAssembly' => '0'))->result_array();
		$autoAssemblyDatas = array();
		foreach($datas as $data){
			$key = ($data['isAssembly'])?('bom'):('component');
			$autoAssemblyDatas[$data['createdId']][$key][] = $data;
		}
		if($autoAssemblyDatas){
			$getWarehouseMaster = $this->getWarehouseMaster();
			$this->load->library('mailer');
			$body = 'Hi '.ucwords($config['name']).',<br><br>
			<p>This email to let you know that an assembly has just been automatically created. Please see details below:</p><br><br>
			<table style="border:1px solid #e7ecf1;padding:5px;">
			<thead style="border:1px solid #e7ecf1;padding:5px;">
				<tr>
					<th style="border:1px solid #e7ecf1;text-align: left;">Assembly Id</th>
					<th style="border:1px solid #e7ecf1;text-align: left;">BOM SKU</th>
					<th style="border:1px solid #e7ecf1;text-align: left;">Assembled Qty</th>
					<th style="border:1px solid #e7ecf1;text-align: left;">Warehouse</th>
					';
				$body .= '</tr>
			</thead>';
			$autoCount = 1;
			foreach($autoAssemblyDatas as $i => $autoAssemblyData){
				$body .= '<tr>
				<td style="border:1px solid #e7ecf1;text-align: left;">'.$autoAssemblyData['bom']['0']['createdId'].'</td>
				<td style="border:1px solid #e7ecf1;text-align: left;">'.$autoAssemblyData['bom']['0']['sku'].'</td>
				<td style="border:1px solid #e7ecf1;text-align: left;" >'.$autoAssemblyData['bom']['0']['qty'].'</td>
				<td style="border:1px solid #e7ecf1;text-align: left;">'.$getWarehouseMaster[$autoAssemblyData['bom']['0']['warehouse']]['warehouseName'].'</td>';
				$body .= '</tr>';
				$autoCount++;
			}	
			$body .= '</table>
			<br><br><br>			
			Thanks & regards<br>
			'; 
			$from = array('alert@businesssolutionsinthecloud.com' => 'Alert'); 
			$subject = 'Auto-assembly Report';
			$res = $this->mailer->send($config['autoAssemblyEmail'],$subject,$body,$from); 
			if($res){
				$this->db->where(array('assemblyEmailSent' => '0','autoAssembly' => '1'))->update('product_assembly',array('assemblyEmailSent' => '1'));
			} 
		} 
	}
	public function deleteProduct(){
		global $fullFilePatch;
		$object = file_get_contents("php://input");
		if($object){
			$data = json_decode($object, true);	
			if($data['id']){
				$this->db->insert('deleteproduct_webhooks_tracker',array('fullFilePatch' => $fullFilePatch, 'resourceType' => 'deleteProduct','resourceId' => $data['id'],'params' => json_encode($data),'created' => date('Y-m-d H:i:s')));					
			}
		}
	}
	public function processDeleteProduct(){
		$datas = $this->db->select('id,resourceId,params')->get_where('deleteproduct_webhooks_tracker',array('status' => '0','resourceType' => 'deleteProduct'))->result_array();
		if($datas){
			$deleteDatas = array();
			foreach($datas as $data){
				$deleteDatas[$data['resourceId']] = $data;
			}
			if($deleteDatas){
				foreach($deleteDatas as $resourceId => $deleteDatas){
					$this->db->where(array('resourceId' => $resourceId))->update('deleteproduct_webhooks_tracker',array('status' => '2'));
					$this->deleteProductById($resourceId);
				}
			}
		}
	}
	
	public function deleteProductById($productId){
		$config = $this->db->get_where('account_brightpearl_config')->row_array();
		if($productId){
			$productDatas = $this->db->get_where('products',array('productId' => $productId))->row_array();
			if($productDatas){
				$isBom = 0;$deleteResponse = false;
				if($productDatas['isBOM']){
					$productMapping = array();
					$productMappingTemps = $this->db->get_where('products')->result_array();
					if($productMappingTemps){
						foreach($productMappingTemps as $productMappingTemp){
							$productMapping[$productMappingTemp['productId']] = $productMappingTemp;
						}
					}
					$isBom = 1;
					$subject = 'Deleted Product Alert '.date("Y-m-d");
					$productBomDatas = $this->db->get_where('product_bom',array('productId' => $productId))->result_array();
					$html = '';
					if($productBomDatas){
						$html = '<div> Dear '.ucfirst($config['name']).',<br><p></p>The following product record(s) which were linked to the below Bills of Material records in the BOM app have been deleted in Brightpearl. Kindly check to prevent any issues.</div><br><br>
						<table style="border:1px solid #e7ecf1;padding:5px;">
									<thead style="border-bottom:1px solid #e7ecf1;">
										<tr>
											<th>Component SKU</th>
											<th>Recipe Name</th>
											<th>Product SKU</th>
											<th>Product Name</th>	
											<th>Qty</th>
											<th>Bom Qty</th>
											<th>Created By</th>
										</tr>
									</thead>
									<tbody>';
						 
						foreach($productBomDatas as $productBomData){
							$html .= '<tr>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.@$productMapping[$productBomData['componentProductId']]['sku'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['recipename'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['sku'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['name'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['qty'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['bomQty'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.ucwords($productBomData['updatedBy']).'</td>
									</tr>';
						}
						$html .='</tbody></table>';
					}
					$deleteResponse = $this->db->where(array('productId' => $productId))->delete('products');
					if($deleteResponse){
						$this->db->where(array('productId' => $productId))->delete('product_bom');
					}
				}else{
					$bomMappingDatas = array();
					$productDatasBom = $this->db->get_where('products')->result_array();
					if($productDatasBom){
						foreach($productDatasBom as $products){
							$bomMappingDatas[$products['productId']] = $products;
						}
					}
					$subject = 'Deleted Product Alert '.date("Y-m-d");
					$productBomDatas = $this->db->get_where('product_bom',array('componentProductId' => $productId))->result_array();
					if($productBomDatas){
					$html = '<table style="border:1px solid #e7ecf1;padding:5px;">
						<div> Dear '.ucfirst($config['name']).',<br><p></p>The following product record(s) which were linked to the below Bills of Material records in the BOM app have been deleted in Brightpearl. Kindly check to prevent any issues.<div><br><br>
						<thead style="border-bottom:1px solid #e7ecf1;">
							<tr>
								<th>Bom SKU</th>
								<th>Bom Name</th>
								<th>Bom Component Id</th>
								<th>Recipe Name</th>
								<th>Component Product SKU</th>
								<th>Component Product Name</th>	
								<th>Qty</th>
								<th>Bom Qty</th>
								<th>Created By</th>
							</tr>
						</thead>
					<tbody style="font-size:13px;padding:10px;background-color:#e8e8e89e;">';
						foreach($productBomDatas as $productBomData){
							$html .= '<tr>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$bomMappingDatas[$productBomData['productId']]['sku'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$bomMappingDatas[$productBomData['productId']]['name'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['componentProductId'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['recipename'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['sku'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['name'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['qty'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$productBomData['bomQty'].'</td>
										<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.ucwords($productBomData['updatedBy']).'</td>
									</tr>';
						}
					}
					$html .='</tbody></table>';
					$deleteResponse = $this->db->where(array('productId' => $productId))->delete('products');
					if($deleteResponse){
						$this->db->where(array('componentProductId' => $productId))->delete('product_bom');
					}
				}
			}
			if($deleteResponse){
				$this->load->library('mailer');
				$from = array('alert@businesssolutionsinthecloud.com' => 'Alert');
				if($html){
					$this->mailer->send($config['autoAssemblyEmail'],$subject,$html,$from); 					
				}
			}
		}
	}
	public function runTask($taskName = 'processReorderAssembly'){
		if($taskName){
			$isRunning = $this->getRunningTask($taskName);

			$taskLogDir = FCPATH.'jobsoutput/'.date('Y').'/'.date('m').'/'.date('d').'/';
			$taskStatus = ($isRunning) ? "true" : "false";
			if(!is_dir(($taskLogDir))){
				mkdir(($taskLogDir),0777,true);
				chmod(($taskLogDir), 0777);
			}
			file_put_contents($taskLogDir.'scheduler.logs',"\n".date('Y-m-d H:i:s')." - $taskName - Is Running : $taskStatus",FILE_APPEND);

			if(!$isRunning){
				// $isSellRUn = shell_exec('/opt/plesk/php/8.2/bin/php '.FCPATH.'index.php webhooks '.$taskName);

				$fileDir = FCPATH.'jobsoutput/'.date('Y').'/'.date('m').'/'.date('d').'/'.$taskName.'/';
				if(!is_dir(($fileDir))){
					mkdir(($fileDir),0777,true);
					chmod(($fileDir), 0777);
				}
				$filename = gmdate('H-i-s').'.logs';
				$isSellRUn = shell_exec( '/opt/plesk/php/8.2/bin/php -d memory_limit=9928M '.FCPATH.'index.php Webhooks '.$taskName .' > '.$fileDir.$filename );    
				file_put_contents($fileDir.$filename,"End time : ".gmdate('c'),FILE_APPEND);
				$filecontent = file_get_contents($fileDir.$filename);
				if((str_contains($filecontent,"Backtrace:")) OR
				(str_contains($filecontent,"[Error")) OR
				(str_contains($filecontent,"Error]")) OR
				(str_contains($filecontent,".php:")) OR
				(str_contains($filecontent,"Database Error Occurred")) OR
				(str_contains($filecontent,"Type:        Error")) OR
				(str_contains($filecontent,"Type:        TypeError")) OR
				(str_contains($filecontent,"Type:        ValueError"))){
					$this->load->library('mailer');
					// $from = array('info@bsitc-apps.com' => 'Info');
					$to = "seniordev@businesssolutionsinthecloud.com,devops@businesssolutionsinthecloud.com,rohan@businesssolutionsinthecloud.com,max@businesssolutionsinthecloud.com";
					$appName = $this->globalConfig['app_name'];
					$subject= 'Alert '.$appName . ' - ' . $taskName;
					$body= 'Hi,<br>Please see cron output file in attached.<br><br>Thanks & Regards<br>BSITC Team';
					$res = $this->mailer->send($to, $subject, $body,'',$fileDir.$filename);
				}
			}
		}
		die();
	}
	public function getRunningTask($checkTask = 'processReorderAssembly'){
		/* die(); */ 
		$return = false;
		$checkTask = trim(strtolower($checkTask));
		if($checkTask){
			exec('ps aux | grep php', $outputs);
			$fcpath = strtolower(FCPATH. 'index.php webhooks '.$checkTask);	
			foreach($outputs as $output){
				$output = strtolower($output);
				if(substr_count($output,$fcpath)){
					if(substr_count($output,$checkTask)){
						if(!substr_count($output,'runtask')){ 
							$return = true;
						}
					}
				}
			}
		}
		return $return;
	}
	
	public function closeRunner(){
		date_default_timezone_set('GMT');
		$cpid = posix_getpid(); 
		exec('ps aux | grep php', $outputs);
		$currentTime = gmdate('YmdHis',strtotime('-300 min')); 
		foreach($outputs as $output){
			$ps = preg_split('/ +/', $output);
			$pid = $ps[1];				
			$cronStartTimeTimeStamp = strtotime($ps['8']);
			if(substr_count($output,'runTask/')){
				shell_exec("kill $pid");	
			}
			else if(substr_count($output,'/index.php')){
				if(gmdate('Y',$cronStartTimeTimeStamp) == gmdate('Y')){
					$cronStartTime = date('YmdHis',$cronStartTimeTimeStamp);					
					if($cronStartTime < $currentTime){ 
						shell_exec("kill $pid");
					}
				}
			}
		}
	}
	
	public function accountInfo()
    {
        $response = $this->brightpearl->getAccountInfo(); 
		echo "<pre>"; print_r($response); echo "</pre>"; die(__FILE__.__LINE__);
        return $response;
    }
	
	public function getCustomerInfo()
    {
        $this->brightpearl->reInitialize();
		foreach($this->brightpearl->accountDetails as $accountId => $accountDetail){
			//transfer owner id 
			$url 	 = '/contact-service/contact-search?allEmail='.$accountDetail['email'];
			$res 	 = reset($this->brightpearl->getCurl($url));
			$results = reset($res['results']);
			$this->db->where(array('brightpearlAccountId' => $accountId))->update('account_brightpearl_config',array('defaultOwnerId' => $results['0']));
			//Default currency code
			$currencyUrl 	  = '/accounting-service/currency-search';
			$currencyResponses = $this->brightpearl->getCurl($currencyUrl,'GET','','json');
			if($currencyResponses){
				$currencyDatas = array();$defaultCurrencyCode = "";
				foreach($currencyResponses as $account1Id => $currencyResponse){
					if(!isset($currencyResponse['errors'])){
						foreach($currencyResponse['results'] as $results){
							if($results['5']){
								$defaultCurrencyCode = $results['2'];
								break;
							}
						}
					}
				}
			}
			if($defaultCurrencyCode){
				$this->db->where(array('brightpearlAccountId' => $accountId))->update('account_brightpearl_config',array('currencyCode' => $defaultCurrencyCode));
			}
		}
    }
	
	public function warehouseList(){
		$this->brightpearl->reInitialize();
		return $this->brightpearl->getAllLocation();
	}
	public function getWarehouseMaster(){
		$savedWarehouseDataTemps = $this->db->get_where('warehouse_master')->result_array();
		$warehouseLists = array();
		if($savedWarehouseDataTemps){
			foreach($savedWarehouseDataTemps as $savedWarehouseDataTemp){
				$warehouseLists[$savedWarehouseDataTemp['warehouseId']] = $savedWarehouseDataTemp;
			}
		}
		return $warehouseLists;
	}
	
	public function multipleAssemblyAlerts(){ 
		$this->brightpearl->reInitialize();
		foreach($this->brightpearl->accountDetails as $accountId => $accountDetails){
			$getMovementUrl = '/warehouse-service/goods-movement-search?goodsNoteTypeCode=SC&updatedOn='.date('Y-m-d',strtotime('-1 days')).'/';
			$getMovementDatas      = $this->brightpearl->getCurl($getMovementUrl,'get','','json',$accountId)[$accountId];
			$goodsMovmentsSearchDatas = array();
			if($getMovementDatas['results']){
				foreach($getMovementDatas['results'] as $getMovementData){
					if($getMovementData['2'] || $getMovementData['3']){
						//continue;
					}
					//warehouseId = 6, goodsOutId = 7
					if($getMovementData['7']){
						$goodsMovmentsSearchDatas[$getMovementData['6']][] = $getMovementData['7'];
					}
				}	
				if ($getMovementDatas['metaData']['resultsAvailable'] > 500) {
					for ($i = 500; $i <= $getMovementDatas['metaData']['resultsAvailable']; $i = ($i + 500)) {
						$url1      = $getMovementUrl . '&firstResult=' . $i;
						$response1 = $this->brightpearl->getCurl($url1, "GET", '', 'json', $accountId)[$accountId];
						if ($response1['results']) {
							foreach($response1['results'] as $getMovementData){
								if($getMovementData['2'] || $getMovementData['3']){
									//continue;
								}
								//warehouseId = 6, goodsOutId = 7
								if($getMovementData['7']){
									$goodsMovmentsSearchDatas[$getMovementData['6']][] = $getMovementData['7'];
								}
							}
						}
					}
				}
					
			}
			$finalArray = array();
			if($goodsMovmentsSearchDatas){
				foreach($goodsMovmentsSearchDatas as $warehouseId => $goodsMovmentsSearchData){					
					$response = $this->brightpearl->getResultById($goodsMovmentsSearchData,'/warehouse-service/warehouse/'.$warehouseId.'/stock-correction/',$accountId);
					foreach($response as $respons){
						$reason = $respons['reason'];
						if(substr_count($reason,'Assembly of product. Assembly id: AS')){
							$goodsNoteId = $respons['goodsNoteId'];
							$goodsMoveds = $respons['goodsMoved'];
							if($goodsMoveds){
								if(!$goodsMoveds['0']){
									$goodsMoveds = array($goodsMoveds);
								}
								foreach($goodsMoveds as $goodsMoved){
									$finalArray[$reason][$goodsMoved['productId']][] = $goodsMoved;
								}
							}
						}
					}
				}
			}
			$issueList = array();
			foreach($finalArray as $aseemblyIdTemps => $finalArra){
				$aseemblyId = explode(": ",$aseemblyIdTemps)['1'];
				$findPositive = 0;$findNegative = 0;
				foreach($finalArra as $proId => $finalAr){
					if(count($finalAr) > 1){
						$qty = array_sum(array_column($finalAr,'quantity'));
						$tempPData = $finalAr['0'];
						$tempPData['quantity'] = $qty;
						if($qty == 0){
							$finalArra[$proId] = array($tempPData);
						}
						else{
							$issueList[$aseemblyId][$proId] = $finalArra;
							break;
						}
					}
					foreach($finalAr as $finalA){
						if($finalA['quantity'] > 0){
							$findPositive = 1;
						}
						if($finalA['quantity'] < 0){
							$findNegative = 1;
						}
						
					}
				}
				if((!$findPositive) || (!$findNegative)){
					$issueList[$aseemblyId] = $finalArra;
				}
			}
			if($issueList){
				$this->load->library('mailer');
				$appName		= $this->globalConfig['app_name'];
				$filepath = FCPATH.'logs/emailalert/'.date('Y').'/'.date('m').'/'.date('d').'/'.basename($suburl);			
				if(!is_dir(($filepath))){
					mkdir(($filepath),0777,true);
					chmod(($filepath), 0777);
				}	
				$filename = date('Y-m-d H-i-s ').uniqid().'.logs';
				file_put_contents($filepath.$filename,json_encode($issueList, JSON_PRETTY_PRINT));
				
				$subject = 'Alert '.$appName.' -  BOM not correctly created';
				$from = array('info@bsitc-apps.com' => 'Info');
				$body = 'Hi,<br><br>
				<p>No of effected assembly : '.count($issueList).'.</p>
				<p>AssemblyIds : '.implode(", ", array_keys($issueList)).'.</p>
				
				<br><br>

				';
				$body .= '			
				<br><br><br>			
				Thanks & Regards<br>
				BSITC Team'; 
				// $to = "dean@businesssolutionsinthecloud.com,hitesh@businesssolutionsinthecloud.com,aherve@businesssolutionsinthecloud.com,jaina@businesssolutionsinthecloud.com";
				$to = "rohan@businesssolutionsinthecloud.com,max@businesssolutionsinthecloud.com";
				
				$res = $this->mailer->send($to,$subject,$body,$from,$filepath.$filename);
				unlink($filepath.$filename);
			}			
		}
	}
	public function createTestJson(){
		$data = $this->db->query("SELECT * FROM `product_bom` GROUP BY productId ORDER BY `product_bom`.`id` ASC ")->result_array();
		$products = $this->db->query("SELECT productId,name FROM products")->result_array();
		foreach($products as $product){
			$productss[$product['productId']] = $product['name'];
		}
		foreach($data as $datas){
			$productArr[] = array(
								'sku' =>$productss[$datas['productId']],
								'warehouse' =>'Main warehouse',
								'recipe_name' =>$datas['recipename'],
								'qty' => '1',
								'reference' => 'Test assembly'
								);
		}
		echo "<pre>";print_r(json_encode($productArr)); echo "</pre>";die(__FILE__.' : Line No :'.__LINE__);
	}

	# DB Maintenance - Delete old logs - Weakly
	public function deleteLogs()
	{
		if ($this->db->tableExists('cron_management')) {
			$checkTypes = array('orders', 'products', 'shopifyProducts', 'goodsout', 'goodsin', 'stock_correction', 'sales1', 'purchase1', 'stocktransfer', 'bpproduct1', 'sales_order', 'customers', 'purchase_order', 'salescreditpayment1', 'salesCredit1', 'dispatch1', 'sales1', 'dispatch', 'sales', 'refund', 'product', 'sales_credit', 'bpproduct', 'autoAssemblyaligment');
			foreach ($checkTypes as $checkType) {
				$this->db->query("DELETE FROM cron_management  WHERE id < ( select max(id) as maxid from cron_management WHERE type='" . $checkType . "') AND type = '" . $checkType . "';");
			}
		}
		if ($this->db->tableExists('api_call')) {
			$this->db->table('api_call')->truncate();
		}
		if ($this->db->tableExists('ci_sessions')) {
			$this->db->table('ci_sessions')->truncate();
		}
		$diffinsecond = 60 * 24 * 90; # mhd

		/* if($this->db->tableExists('stock_sync_log')){
            $sql = 'DELETE FROM stock_sync_log WHERE "created" < NOW() - INTERVAL \''.$diffinsecond.' minute\' ';
            $this->db->query($sql);
        } */
		if ($this->db->tableExists('sales_reservation_history')) {
			$sql = 'DELETE FROM sales_reservation_history WHERE "current_timestamp" < NOW() - INTERVAL \'' . $diffinsecond . ' minute\' ';
			$this->db->query($sql);
		}
		if ($this->db->tableExists('orders_item_history')) {
			$sql = 'DELETE FROM orders_item_history WHERE "current_timestamp" < NOW() - INTERVAL \'' . $diffinsecond . ' minute\' ';
			$this->db->query($sql);
		}
		if ($this->db->tableExists('global_log')) {
			$sql = 'DELETE FROM global_log WHERE "ApiStartRequst" < NOW() - INTERVAL \'' . $diffinsecond . ' minute\' ';
			$this->db->query($sql);
		}
		if ($this->db->tableExists('webhooks_tracker')) {
			$sql = 'DELETE FROM webhooks_tracker WHERE "created"::TIMESTAMP < NOW() - INTERVAL \'' . $diffinsecond . ' minute\' ';
			$this->db->query($sql);
		}

		// extended to delete file logs as well
		$fileDetails = array(
			'30' => array(
				'jobsoutput',
				'writable/session',
				'public/jobsoutput',
				'webhooksapi',
				'webhooks',
			),
			'90' => array(
				'processedfile',
				'files',
				'oldRowData',
				'oldCreatedRowData',
			),
		);
		foreach ($fileDetails as $days => $givenFilePaths) {
			foreach ($givenFilePaths as $givenFilePath) {
				$filePath = FCPATH . $givenFilePath;
				if (is_dir($filePath)) {
					$command = "find \"" . $filePath . "\" -type f -mtime +" . $days . " -exec rm {} \;";
					shell_exec($command);
				}
			}
		}
		echo "End of Function";
	}

	# Transaction data and logs the files - Weakly
	public function updateOrderIdToOld($orderId = '')
	{
		$diffinsecond = 60 * 24 * 60; # mhd
		$sql = 'SELECT id,"orderId",created FROM sales_order where "created"::TIMESTAMP < NOW() - INTERVAL \'' . $diffinsecond . ' minutes\' and status > 2';
		$orderInfos = $this->db->query($sql)->getResultArray();

		$orderId = array_column($orderInfos, 'orderId');
		$orderId = array_unique($orderId);
		sort($orderId);

		$orderIds = array_chunk($orderId, 500);
		foreach ($orderIds as $orderId) {
			$fileArchives = array();
			$goodsInfos = array();
			$itemInfos = array();
			$dispatchInfos = array();
			$addressInfos = array();
			if ($orderId) {
				$orderId = array_unique($orderId);
				$orderId = array_filter($orderId);
				$itemInfos = $this->db->table("sales_item")->whereIn('orderId', $orderId)->get()->getResultArray();
				// $goodsInfos = $this->db->table("sales_goodsout")->whereIn('orderId', $orderId)->where(array('status > ' => 1))->get()->getResultArray();
				// $dispatchInfos = $this->db->table("sales_dispatch")->whereIn('orderId', $orderId)->where(array('status > ' => 0))->get()->getResultArray();
				// $addressInfos = $this->db->table("sales_address")->whereIn('orderId', $orderId)->get()->getResultArray();
				$salesorderInfos = $this->db->table("sales_order")->whereIn('orderId', $orderId)->get()->getResultArray();
			}
			$dipatchInsert = array();
			if ($dispatchInfos) {
				if ($this->db->tableExists('sales_dispatch_old')) {
					$deleteDispatchIds = array();
					$batchInserts = array();
					foreach ($dispatchInfos as $dispatchInfo) {
						$deleteDispatchIds[] = $dispatchInfo['id'];
						unset($dispatchInfo['id']);
						$batchInserts[] = $dispatchInfo;
					}
					if ($batchInserts) {
						$batchInserts = array_chunk($batchInserts, 500, true);
						foreach ($batchInserts as $batchInsert) {
							$this->db->table("sales_dispatch_old")->insertBatch($batchInsert);
						}
						$this->db->table("sales_dispatch")->whereIn('id', $deleteDispatchIds)->delete();
					}
				}
			}
			if ($addressInfos) {
				if ($this->db->tableExists('sales_address_old')) {
					$dispatchInfos = $addressInfos;
					$deleteDispatchIds = array();
					$batchInserts = array();
					foreach ($dispatchInfos as $dispatchInfo) {
						$filepath = '/sales_address/' . (int)$dispatchInfo['account1Id'] . (int)$dispatchInfo['account2Id'] . $dispatchInfo['orderId'] . '.json';
						$fileArchives['address_' . $dispatchInfo['orderId']] = array('source' => FCPATH . 'rowData' . $filepath, 'destination' => FCPATH . 'oldRowData/' . $filepath);

						$deleteDispatchIds[] = $dispatchInfo['id'];
						unset($dispatchInfo['id']);
						$batchInserts[] = $dispatchInfo;
					}
					if ($batchInserts) {
						$batchInserts = array_chunk($batchInserts, 500, true);
						foreach ($batchInserts as $batchInsert) {
							$this->db->table("sales_address_old")->insertBatch($batchInsert);
						}
						$this->db->table("sales_address")->whereIn('id', $deleteDispatchIds)->delete();
					}
				}
			}


			if ($itemInfos) {
				if ($this->db->tableExists('sales_item_old')) {
					$dispatchInfos = $itemInfos;
					$deleteDispatchIds = array();
					$batchInserts = array();
					foreach ($dispatchInfos as $dispatchInfo) {
						$filepath = '/sales_item/' . (int)$dispatchInfo['account1Id'] . (int)$dispatchInfo['account2Id'] . $dispatchInfo['orderId'] . '.json';
						$fileArchives['item_' . $dispatchInfo['orderId']] = array('source' => FCPATH . 'rowData' . $filepath, 'destination' => FCPATH . 'oldRowData' . $filepath);

						$deleteDispatchIds[] = $dispatchInfo['id'];
						unset($dispatchInfo['id']);
						$batchInserts[] = $dispatchInfo;
					}
					if ($batchInserts) {
						$batchInserts = array_chunk($batchInserts, 500, true);
						foreach ($batchInserts as $batchInsert) {
							$this->db->table("sales_item_old")->insertBatch($batchInsert);
						}
					}
					if ($deleteDispatchIds) {
						$deleteDispatchIds = array_chunk($deleteDispatchIds, 500, true);
						foreach ($deleteDispatchIds as $deleteDispatchId) {
							$this->db->table("sales_item")->whereIn('id', $deleteDispatchId)->delete();
						}
					}
				}
			}

			if ($salesorderInfos) {
				if ($this->db->tableExists('sales_order_old')) {
					$dispatchInfos = $salesorderInfos;
					$deleteDispatchIds = array();
					$batchInserts = array();
					foreach ($dispatchInfos as $dispatchInfo) {

						$filepath = '/sales_order/' . (int)$dispatchInfo['account1Id'] . (int)$dispatchInfo['account2Id'] . $dispatchInfo['orderId'] . '.json';
						$fileArchives['sales_' . $dispatchInfo['orderId']] = array('source' => FCPATH . 'rowData' . $filepath, 'destination' => FCPATH . 'oldRowData' . $filepath);
						$fileArchives['created_sales_' . $dispatchInfo['orderId']] = array('source' => FCPATH . 'createdRawData' . $filepath, 'destination' => FCPATH . 'oldCreatedRowData' . $filepath);

						$deleteDispatchIds[] = $dispatchInfo['id'];
						unset($dispatchInfo['id']);
						$batchInserts[] = $dispatchInfo;
					}

					if ($batchInserts) {
						$batchInserts = array_chunk($batchInserts, 500, true);
						foreach ($batchInserts as $batchInsert) {
							$this->db->table("sales_order_old")->insertBatch($batchInsert);
						}
					}
					if ($deleteDispatchIds) {
						$deleteDispatchIds = array_chunk($deleteDispatchIds, 500, true);
						foreach ($deleteDispatchIds as $deleteDispatchId) {
							$this->db->table("sales_order")->whereIn('id', $deleteDispatchId)->delete();
						}
					}
				}
			}

			if ($goodsInfos) {
				if ($this->db->tableExists('sales_goodsout_old')) {
					$dispatchInfos = $goodsInfos;
					$deleteDispatchIds = array();
					$batchInserts = array();
					foreach ($dispatchInfos as $dispatchInfo) {
						$filepath = FCPATH . 'rowData/sales_goodsout/' . (int)$dispatchInfo['account1Id'] . (int)$dispatchInfo['account2Id'] . $dispatchInfo['goodsOutId'] . '.json';
						$fileArchives['goods_' . $dispatchInfo['goodsOutId']] = array('source' => FCPATH . 'rowData' . $filepath, 'destination' => FCPATH . 'oldRowData' . $filepath);

						$deleteDispatchIds[] = $dispatchInfo['id'];
						unset($dispatchInfo['id']);
						$batchInserts[] = $dispatchInfo;
					}
					if ($batchInserts) {
						$batchInserts = array_chunk($batchInserts, 500, true);
						foreach ($batchInserts as $batchInsert) {
							$this->db->table("sales_goodsout_old")->insertBatch($batchInsert);
						}
					}
					if ($deleteDispatchIds) {
						$deleteDispatchIds = array_chunk($deleteDispatchIds, 500, true);
						foreach ($deleteDispatchIds as $deleteDispatchId) {
							$this->db->table("sales_goodsout")->whereIn('id', $deleteDispatchId)->delete();
						}
					}
				}
			}
			if ($fileArchives) {
				foreach ($fileArchives as $fileArchive) {
					if (file_exists($fileArchive['source'])) {
						if (!is_dir(dirname($fileArchive['destination']))) {
							$baseFolder = dirname($fileArchive['destination']);
							mkdir($baseFolder, 0777, true);
							shell_exec("chmod -R 777 " . $baseFolder);
						}
						@rename($fileArchive['source'], $fileArchive['destination']);
					}
				}
			}
		}
	}
} 