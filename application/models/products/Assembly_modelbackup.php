<?php
class Assembly_model extends CI_Model
{
	public $productMapping,$logs = array(),$path,$deleteLogs = array(),$deleteLogPath;
    public function __construct()
    {
        parent::__construct();
    }
    public function getProductStock($productIds)
    {
        $return = array();
        if ($productIds) {
            $productIds   = array_unique($productIds);
            $proListDatas = $this->{$this->globalConfig['fetchProduct']}->getProductStockAssembly($productIds);
            foreach ($proListDatas as $proListData) {
                foreach ($proListData as $proIds => $proList) {
                    $return[$proIds] = $proList;
                }
            }
        }
        return $return;
    }
    public function warehouseList()
    {
        $return        = array();
        $locationLists = $this->{$this->globalConfig['fetchProduct']}->getAllLocation();
        foreach ($locationLists as $locationList) {
            foreach ($locationList as $warehouseId => $location) {
                $return[$warehouseId] = $location;
            }
        }
        return $return;
    }
	public function warehouseListDb()
    {
		$return = array();
        $savedWarehouseDataTemps = $this->db->get_where('warehouse_master')->result_array();
		$warehouseLists = array();
		if($savedWarehouseDataTemps){
			foreach($savedWarehouseDataTemps as $savedWarehouseDataTemp){
				$return[$savedWarehouseDataTemp['warehouseId']] = array(
					'id' => $savedWarehouseDataTemp['warehouseId'],
					'name' => $savedWarehouseDataTemp['warehouseName'],
				);
			}
		}
        return $return;
    }
    public function getAllPriceList()
    {
        $return           = array();
        $getAllPriceLists = $this->{$this->globalConfig['fetchProduct']}->getAllPriceList();
        foreach ($getAllPriceLists as $getAllPriceList) {
            foreach ($getAllPriceList as $listId => $getAllPrice) {
                $return[$listId] = $getAllPrice;
            }
        }
        return $return;
    }
    public function getDefaultWarehouseLocation($locationId)
    {
        $getDefaultWarehouseLocation = $this->{$this->globalConfig['fetchProduct']}->getDefaultWarehouseLocation($locationId);
        $return                      = array_shift($getDefaultWarehouseLocation);
        return $return;
    }
    public function getProductPrice($productId)
    {
        $getAllPriceLists = $this->{$this->globalConfig['fetchProduct']}->getProductPriceList($productId);
        return $getAllPriceLists;
    }
	
	public function saveAssembly($datas, $assemblyId ="")
    {
		//echo json_encode(array('status' => '0','message' => 'Development mode activated'));
		//return false;
        if ($datas['productId']) {
			$logtime = date('c');
			$this->brightpearl->reInitialize();
			$this->logs = array();$createdId = "";
			if(!is_dir(dirname($path))) { @mkdir(dirname($path),0777,true);@chmod(dirname($path), 0777); }			
			$user_session_data = $this->session->userdata('login_user_data');
			//updatePriceList function modified by Hitesh
			//function update the price but returns price for non tracked item only
			$nonTrackedItemPrice = $this->updatePriceList($datas['productId'],$datas['receipeid']);
			if(!$nonTrackedItemPrice){
				$nonTrackedItemPrice = 0.00;
			}
            $isError  = 0;
            $config   = $this->db->get('account_' . $this->globalConfig['fetchProduct'] . '_config')->row_array();
            $priceList = $this->getProductPrice(array($datas['productId']));
			$assemblyId = $datas['assemblyId'];
			$saveAssemblyCompDatas = array();$saveAssemblyBomDatas = array();
			$isAutoassembly = 0;
			$targetBinLocationId = $this->getBinLocationByWarehouse($datas['targetwarehouse'], $datas['targetBinLocation']);
			if($assemblyId){
				$createdId = $assemblyId;
				$saveAssemblyCompDatasTemps = $this->db->get_where('product_assembly',array('createdId' => $createdId))->result_array();
				if($saveAssemblyCompDatasTemps && $saveAssemblyCompDatasTemps['0']['autoAssemblyWipWarehouse']){
					$datas['autoAssemblyWipWarehouse'] = $saveAssemblyCompDatasTemps['0']['autoAssemblyWipWarehouse'];
				}
				foreach($saveAssemblyCompDatasTemps as $saveAssemblyCompDatasTemp){
					if($saveAssemblyCompDatasTemp['isAssembly'] == '0'){
						$saveAssemblyCompDatas[$saveAssemblyCompDatasTemp['productId']] = $saveAssemblyCompDatasTemp;
					}else{
						$saveAssemblyBomDatas[$saveAssemblyCompDatasTemp['productId']] = $saveAssemblyCompDatasTemp;
					}
					
					if($saveAssemblyCompDatasTemp['autoAssembly']){
						$isAutoassembly = 1;
					}
				}
			}
			else{
				$createdId = uniqid('AS'.date('s'));
			}
			$this->path = FCPATH.'logs'.DIRECTORY_SEPARATOR.'assembly'.DIRECTORY_SEPARATOR . $createdId.'_'.$logtime.'.logs';
			$defaultWareHouse = array();
			$defaultWareHouse[$datas['targetwarehouse']] = $this->getDefaultWarehouseLocation($datas['targetwarehouse']);
            $saveReceipeDatasTemps = $this->db->get_where('product_bom', array('productId' => $datas['productId'], 'receipeid' => $datas['receipeid']))->result_array();
            $saveReceipe      = array(); $saveReceipeDatas = array();
			$billcomponents = $datas['billcomponents'];	
            foreach ($saveReceipeDatasTemps as $saveReceipeData) {
				$receipeId = $saveReceipeData['receipeId']; 
				$saveReceipeDatas[$saveReceipeData['componentProductId']] = $saveReceipeData;
				if(isset($billcomponents[$receipeId][$saveReceipeData['componentProductId']])){
					$saveReceipeData['qty'] = $billcomponents[$receipeId][$saveReceipeData['componentProductId']]['qty'];
				}
                $saveReceipe[$saveReceipeData['componentProductId']] = $saveReceipeData;
            } 
			$this->logs['saveReceipe'] = $saveReceipe;
			$postStockTransferArrays = array("decrease" => array(),"increase" => array());
            foreach ($datas[$datas['receipeid']]['productId'] as $key => $productId){
				$sourceWareHouseId = @$datas[$datas['receipeid']]['sourcewarehouse'][$key];
				$sourceBinLocation = @$datas[$datas['receipeid']]['sourceBinLocation'][$key];
				if($isAutoassembly){
					$sourceWareHouseId = $saveAssemblyCompDatas[$productId]['warehouse'];
					$sourceBinLocation = $saveAssemblyCompDatas[$productId]['locationId'];
				}
				if(!$sourceBinLocation){
					$sourceBinLocation = $saveAssemblyCompDatas[$productId]['locationId'];
				}
				$reason = 'Assembly of product. Assembly id: '.$createdId;
				if($datas['isOrderAssembly']){
					$reason = 'SO#'.$datas['orderId'].' #Auto Assembly Id : '.$createdId;
				}
				if($sourceWareHouseId){
					if(@!$defaultWareHouse[$sourceWareHouseId]){
						$defaultWareHouse[$sourceWareHouseId] = $this->getDefaultWarehouseLocation($sourceWareHouseId);
					}
					if($saveReceipe[$productId]['qty'] <= 0){
						continue; //added by hitesh
					}
					$postStockTransferArrays['decrease'][$sourceWareHouseId][] = array(
						'quantity'  => (-1) * ceil(($saveReceipe[$productId]['qty'] * ($datas['qtydiassemble']/$saveReceipe[$productId]['bomQty']))),
						'sku' 		=> $saveReceipe[$productId]['sku'],
						'name' 		=> $saveReceipe[$productId]['name'],
						'productId' => $productId,
						'reason'    => $reason,
						'locationId' => ($sourceBinLocation)?($sourceBinLocation):($defaultWareHouse[$sourceWareHouseId]),
					);
				} 
            }
			if($isAutoassembly){
				$targetBinLocationId = array();
				$targetBinLocationId['id'] = $saveAssemblyBomDatas[$datas['productId']]['locationId'];
			}
			@$postStockTransferArrays['increase'][$datas['targetwarehouse']][] = array(
                'quantity'  => $datas['qtydiassemble'],
                'productId' => $datas['productId'],
                'sku' 		=> $datas['sku'],
                'name' 		=> $datas['name'],
                'locationId' => ($targetBinLocationId['id'])?($targetBinLocationId['id']):($defaultWareHouse[$datas['targetwarehouse']]),
                'reason'    => $reason,
                'cost'      => array(
                    'currency' => $config['currencyCode'],
                    'value'    => ($priceList[$datas['productId']][$datas['costingmethod']])?($priceList[$datas['productId']][$datas['costingmethod']]):0.00,
                ), 
            );
			$this->logs['postStockTransferArrays'] = $postStockTransferArrays;
			$createdInventoryId = '';
			$autoAssemblyCreated = 1;
			$autoAssembly = @$datas['autoAssembly'];
			if($datas['autoAssembly']){
				$autoAssemblyToWarehouse = $datas['autoAssemblyWipWarehouse'];
				$autoAssemblyToWarehouseDefaultBin = $this->getDefaultWarehouseLocation($autoAssemblyToWarehouse);
				$autoAssemblyCreated = 0;
				$autoAssemblyFromWarehouse = $datas['targetwarehouse'];
				$autoAssemblyFromWarehouseDefaultBin = $this->getDefaultWarehouseLocation($autoAssemblyFromWarehouse);
				$dataAutoAssemblySent = array();
				foreach($postStockTransferArrays as $typeTest => $t1){
					foreach($t1 as  $t2){
						if($typeTest == 'decrease'){
							if(is_array($t2))
							$dataAutoAssemblySent = array_merge($dataAutoAssemblySent,$t2);
						}
					}
				}
				if($dataAutoAssemblySent){
					$stockTransferCreateUrl = 'warehouse-service/warehouse/'.$autoAssemblyFromWarehouse.'/stock-transfer';
					$stockTransferCreateUrlRequset = array(
						'targetWarehouseId' => $autoAssemblyToWarehouse,
						'reference' => 'Asmb id: '.$createdId,
					);
					$this->logs['stockTransferCreateUrl'] 		 = $stockTransferCreateUrl;
					$this->logs['stockTransferCreateUrlRequset'] = $stockTransferCreateUrlRequset;
					$stockTransferCreateUrlRequsetRes = @reset($this->brightpearl->getCurl($stockTransferCreateUrl,'POST',json_encode($stockTransferCreateUrlRequset),'json'));
					$this->logs['stockTransferCreateUrlRequsetRes'] = $stockTransferCreateUrlRequsetRes;
					if(!isset($stockTransferCreateUrlRequsetRes['errors'])){
						$autoAssemblyCreated = 1;
						$createdInventoryId = $stockTransferCreateUrlRequsetRes;
					}
					$this->addProductInExternalTransfer($createdId); // adding product in external transfer
				}				
			}
			if(!$autoAssemblyCreated){return false;}
			$errorResults = array();$isSaveInProgress = '0';$isWorkInProgressSaved = 0;
			if($datas['btnsaveworkinprogress']){
				$isSaveInProgress = '1';
				foreach ($postStockTransferArrays as $postStockTransferArrayTemps) {
					foreach($postStockTransferArrayTemps as $warehouseId => $postStockTransferArray){
						foreach ($postStockTransferArray as $key => $postStockTransfers) {
							if($postStockTransfers){
								$isWorkInProgressSaved = 1;
								$saveArray = array(
									'productId'         => @$postStockTransfers['productId'],
									'sku'         		=> @$postStockTransfers['sku'],
									'name'         		=> @$postStockTransfers['name'],
									'receipId'          => @$datas['receipeid'],
									'qty'               => @$postStockTransfers['quantity'],
									'isAssembly'        => @($postStockTransfers['quantity'] > 0)?'1':'0',
									'warehouse'         => @$warehouseId, 
									'locationId'        => @$postStockTransfers['locationId'],
									'currencyCode'      => @$postStockTransfers['cost']['currency'],
									'price'             => @$postStockTransfers['cost']['value'],
									'createdId' 		=> $createdId,
									'username' 			=> @$user_session_data['username'],
									'costingMethod' 	=> @$datas['costingmethod'],
									'finalBIn' 			=> @$datas['finalBIn'],
									'status' 			=> '0',
									'ip' 				=> @$_SERVER['REMOTE_ADDR'],
									'autoAssembly' 		=> (int)$autoAssembly,
									'createdInventoryId' => $createdInventoryId,
									'autoAssemblyWipWarehouse' => $datas['autoAssemblyWipWarehouse'],
									'isOrderAssembly' => $datas['isOrderAssembly'],
								);
								$this->db->replace('product_assembly', $saveArray); 
							}
						}
					} 
				}  
			}
			else{
				$isSaveInProgress = '0';
				$return = false;
				$isAutoassembly = $this->isAutoassembly($createdId);
				if($isAutoassembly){
					$this->addProductInExternalTransfer($createdId); // adding product in external transfer
					$autoAssemblyToWarehouse = $datas['autoAssemblyWipWarehouse'];
					$autoAssemblyToWarehouseDefaultBin = $this->getDefaultWarehouseLocation($autoAssemblyToWarehouse);
					$this->releaseAutoAssembly($createdId);
					$datasTemps = $this->db->get_where('product_assembly',array('autoAssembly' => '1','createdInventoryId > ' => '0','isInventoryTransfer' => '1','isInventoryRelease' => '0','isAssembly' => '0', 'createdId' => $createdId))->result_array();
					if($datasTemps){
						$return = true;
					}
				}
				if($return){
					echo json_encode(array('status' => '0','message' => 'Problem in realease the quantity in Reserved location '));
					return false;
				}
				$correctionIdss = array();
				foreach($postStockTransferArrays as $type => $postStockTransferArrayTemps) {
					$goodsMovedPriceDatas = array();$increasePrice = 0.00;
					foreach($postStockTransferArrayTemps as  $warehouseId =>  $postStockTransferArray){
						if($type == 'increase'){
							$fifoPriceLists = array();
							if($datas['costingmethod'] == 'fifo'){
								if($correctionIdss){
									foreach($correctionIdss as $wid => $correctionIds){
										$correctionIds = array_filter($correctionIds);
										$correctionIds = array_filter($correctionIds);
										sort($correctionIds);
										$fetchCurrectionUrl = '/warehouse-service/warehouse/'.$wid.'/stock-correction/'.implode(",",$correctionIds);	
										$fetchCorrectionDatas = $this->brightpearl->getCurl($fetchCurrectionUrl);
										foreach($fetchCorrectionDatas as $accId => $fetchCorrectionData){
											if($fetchCorrectionData){
												foreach($fetchCorrectionData as $fetchCorrectionDat){
													foreach($fetchCorrectionDat['goodsMoved'] as $goodsMoved){
														if(@$saveReceipeDatas[$goodsMoved['productId']]['qty'] > 0){
															$tmpCal = $goodsMoved['productValue']['value'] * abs($goodsMoved['quantity']);
															if($tmpCal > 0){
																$increasePrice += ($tmpCal / ($datas['qtydiassemble'] * $saveReceipeDatas[$goodsMoved['productId']]['bomQty']));
															}
														}
													}
												}
												$postStockTransferArray['0']['cost']['value'] = sprintf("%.4f",($increasePrice + $nonTrackedItemPrice));
											}
										}
									}
								}
							} 
						}
						else {							
							//continue;
						}
						
						$url = '/warehouse-service/warehouse/' . $warehouseId . '/stock-correction';	
						if($isAutoassembly){
							if($type == 'decrease'){
								$url = '/warehouse-service/warehouse/' . $datas['autoAssemblyWipWarehouse'] . '/stock-correction';	
							}
						}
						$postStockTrans['corrections'] = array();
						$count          = 0;
						foreach($postStockTransferArray as $key => $postStockTransfers) {
							if($isAutoassembly){
								if(($type == 'decrease') && ($autoAssemblyToWarehouseDefaultBin)){
									$postStockTransfers['locationId'] = $autoAssemblyToWarehouseDefaultBin;
								}
							}
							$postStockTrans['corrections'][] = $postStockTransfers;
						}
						$results = $this->{$this->globalConfig['fetchProduct']}->postStockCorrection($url, $postStockTrans);
						$this->logs['final submit'] = array(
							'URL' 			=> $url,
							'postStockTrans Request Data' 	=> $postStockTrans,
							'postStockTrans Response Data' => $results,
						);
						foreach ($results as $accountId => $result) {
							foreach ($result as $key => $rows) {
								if($key === 'errors'){
									$errorResults[] = $results;
									$isError = true;
									break;
								}
								else{
									if($rows){
										if($type == 'decrease'){
											$correctionIdss[$warehouseId][] = $rows;
										}
										$saveArray = array(
											'productId'         => @$postStockTrans['corrections'][$key]['productId'],
											'sku'         		=> @$postStockTrans['corrections'][$key]['sku'],
											'name'         		=> @$postStockTrans['corrections'][$key]['name'],
											'receipId'          => @$datas['receipeid'],
											'qty'               => @$postStockTrans['corrections'][$key]['quantity'],
											'isAssembly'        => @($postStockTrans['corrections'][$key]['quantity'] > 0)?'1':'0',
											'warehouse'         => @$warehouseId, 
											'locationId'        => @$postStockTrans['corrections'][$key]['locationId'],
											'currencyCode'      => @$postStockTrans['corrections'][$key]['cost']['currency'],
											'price'             => @$postStockTrans['corrections'][$key]['cost']['value'],
											'createdId' 		=> $createdId,
											'status' 			=> 1,
											'costingMethod' 	=> @$datas['costingmethod'],
											'finalBIn' 			=> @$datas['finalBIn'],
											'username' 			=> @$user_session_data['username'],
											'ip' 				=> @$_SERVER['REMOTE_ADDR'],
											'createdInventoryId' => @$createdInventoryId,
											'autoAssemblyWipWarehouse' => $datas['autoAssemblyWipWarehouse'],
											'isOrderAssembly' => $datas['isOrderAssembly'],
										);
										if($isAutoassembly){
											$this->db->where(array('createdId' => $createdId, 'productId' => $postStockTrans['corrections'][$key]['productId']))->update('product_assembly', $saveArray);
										}else{
											$this->db->replace('product_assembly', $saveArray); 
										}
									}
								}                        
							}
						}
					}
				}
				if($this->logs){
					file_put_contents($this->path,json_encode($this->logs),FILE_APPEND);
				}
			}
			if($isError == '1'){
                echo json_encode(array('status' => '0','message' => json_encode($errorResults), 'assemblyId' => '', 'isSaveInProgress' => $isSaveInProgress, 'createdSkuName'=> ''));
            }
            else{
                if($assemblyId){
					echo json_encode(array('status' => '1','message' => 'Assembly successfully updated <br> Updated id : <b>'.$assemblyId.'</b>', 'assemblyId' => $assemblyId, 'isSaveInProgress' => $isSaveInProgress, 'createdSkuName' =>  base64_encode($datas['name']. ' - ' . $datas['sku'] ) ));
				}else{
					if(!$autoAssembly){
						if(!$datas['isOrderAssembly']){
							echo json_encode(array('status' => '1','message' => 'Assembly successfully created<br> Created id : <b>'.$createdId.'</b>','assemblyId' => $createdId, 'isSaveInProgress' => $isSaveInProgress,'createdSkuName' =>  base64_encode($datas['name']. ' - ' . $datas['sku'] )));
						}
					}
				}
            }
			if($datas['autoAssembly']){
				$this->addProductInExternalTransfer($createdId); // adding product in external transfer
				$this->releaseAutoAssembly($createdId);
			}
			if($createdId){
				return $createdId;
			}
        }
    }
	
	public function getBinLocationByWarehouse($warehouseId, $binLocation){
		return $this->db->get_where('warehouse_binlocation',array('name' => $binLocation,'warehouseId' => $warehouseId))->row_array();
	}
	public function getAllWarehouseLocationInsert(){
		return $this->{$this->globalConfig['account1Liberary']}->getAllWarehouseLocationInsert();
	}
	public function addProductInExternalTransfer($assemblyId =''){
		$this->brightpearl->reInitialize();
		 $config   = $this->db->get('account_' . $this->globalConfig['fetchProduct'] . '_config')->row_array();
		if($assemblyId){
			$datasTemps = $this->db->get_where('product_assembly',array('autoAssembly' => '1','createdInventoryId > ' => '0','isInventoryTransfer' => '0','isAssembly' => '0', 'createdId' => $assemblyId))->result_array();
		}else{
			$datasTemps = $this->db->get_where('product_assembly',array('autoAssembly' => '1','createdInventoryId > ' => '0','isInventoryTransfer' => '0','isAssembly' => '0'))->result_array();
		}
		$datas = array();
		foreach($datasTemps as $datasTemp){
			if($datasTemp['createdInventoryId']){
				$datas[$datasTemp['createdInventoryId']][] = $datasTemp;
			}
		}
		$this->logs['addProductInExternalTransfer datas'.$assemblyId] = $datas;
		if($datas){
			foreach($datas as $stockTransferId => $data){
				$tranferWarehouseIDs = ''; $createdId = '';$autoAssemblyWipWarehouse = '';
				$tranferWarehouseIDs = reset($data);
				$tranferWarehouseID = $tranferWarehouseIDs['warehouse'];
				$createdId 			= $tranferWarehouseIDs['createdId'];
				$autoAssemblyWipWarehouse 			= $tranferWarehouseIDs['autoAssemblyWipWarehouse'];
				$request = array();
				$transferredProducts = array();
				foreach($data as $row){
					$transferredProducts[] = array(
						'productId' 		=> $row['productId'],
						'quantity' 			=> abs($row['qty']),
						/* 'fromLocationId' 	=> $row['finalBIn'], */
						'fromLocationId' 	=> $row['locationId'],
					);				
				}
				if($transferredProducts){
					$request = array(
						'targetWarehouseId'   => $autoAssemblyWipWarehouse,
						'stockTransferId' 	  => $stockTransferId,
						'transferredProducts' => $transferredProducts,
					);
					$stockTransferCreateUrl = '/warehouse-service/warehouse/'.$tranferWarehouseID.'/external-transfer';
					$stockTransferCreateUrlRequsetRes = @reset($this->brightpearl->getCurl($stockTransferCreateUrl,'POST',json_encode($request),'json'));
					$this->logs['tranferLog request'.$assemblyId] = $request;
					$this->logs['tranferLog stockTransferCreateUrlRequsetRes'.$assemblyId] = $stockTransferCreateUrlRequsetRes;
					if(!isset($stockTransferCreateUrlRequsetRes['errors'])){
						$this->db->where(array('createdInventoryId' => $stockTransferId))->update('product_assembly',array('isInventoryTransfer' => '1'));
						foreach($stockTransferCreateUrlRequsetRes as $stockTransferCreateUrlRequsetRe){
							$eventUrl = '/warehouse-service/goods-note/goods-out/'.$stockTransferCreateUrlRequsetRe.'/event';
							$eventRequst = array(
								'events' => array(
									array(
										'eventCode' 	=> 'SHW',
										'occured' 		=> date('c'),
										'eventOwnerId' 	=> $config['defaultOwnerId'],   // hitesh needs to put this value from config
									),
								),
							);
							$eventRes = @reset($this->brightpearl->getCurl($eventUrl,'POST',json_encode($eventRequst),'json'));
						}
					}
					$this->db->where(array('createdId' => $createdId))->update('product_assembly',array('goodsOutId' => $stockTransferCreateUrlRequsetRes['0']));
				}
			}
	
		}
		//file_put_contents($this->path,json_encode($transferLogs),FILE_APPEND); 
	}
	public function releaseAutoAssembly($createdId = ""){
		 
		$this->brightpearl->reInitialize();
		$config   = $this->db->get('account_' . $this->globalConfig['fetchProduct'] . '_config')->row_array();
		if($createdId){
			$datasTemps = $this->db->get_where('product_assembly',array('autoAssembly' => '1','createdInventoryId > ' => '0','isInventoryTransfer' => '1','isInventoryRelease' => '0','isAssembly' => '0', 'createdId' => $createdId))->result_array();
		}else{
			$datasTemps = $this->db->get_where('product_assembly',array('autoAssembly' => '1','createdInventoryId > ' => '0','isInventoryTransfer' => '1','isInventoryRelease' => '0','isAssembly' => '0'))->result_array();
		}
		$this->logs['datasTemps'. $createdId] = $datasTemps;
		$this->deleteLogs['datasTemps'] = $datasTemps;
		$getDefaultLocations = array();
		foreach($datasTemps as $datasTemp){
			$url = '/warehouse-service/warehouse/'.$datasTemp['autoAssemblyWipWarehouse'].'/quarantine/release';
			$getDefaultLocation = '';
			if(isset($getDefaultLocations[$datasTemp['warehouse']])){
				$getDefaultLocation = $getDefaultLocations[$datasTemp['warehouse']];
			}
			else{						
				$getDefaultLocation = $this->{$this->globalConfig['fetchProduct']}->getDefaultWarehouseLocation($datasTemp['autoAssemblyWipWarehouse']);
				$getDefaultLocation = reset($getDefaultLocation);
				if(!isset($getDefaultLocation['errors'])){
					$getDefaultLocations[$datasTemp['warehouse']] = $getDefaultLocation;
				}
			}					
			$releaseDataRequest = array(
				'productId'    => $datasTemp['productId'],
				'quantity'	   => abs($datasTemp['qty']),
				'toLocationId' 	=> $getDefaultLocation,
			);
			if($releaseDataRequest){
				$releaseResDatas = $this->brightpearl->getCurl($url,'POST',json_encode($releaseDataRequest),'json');
				$releaseResData = reset($releaseResDatas);
				$this->logs['releaseDataRequest'. $createdId] = $releaseDataRequest;
				$this->logs['releaseResDatas'. $createdId] = $releaseResDatas;
				$this->deleteLogs['releaseDataRequest'] = $releaseDataRequest;
				$this->deleteLogs['releaseResDatas'] = $releaseDataRequest;
				if(!isset($releaseResData['errors'])){
					$this->db->where(array('id' => $datasTemp['id']))->update('product_assembly',array('isInventoryRelease' => '1'));
				}
			}
		}
		file_put_contents($this->path,json_encode($this->logs),FILE_APPEND); 
	}
	
	public function isAutoassembly($assemblyId){
		if($assemblyId){
			 $releaseAutoAssemblyDataTemps = $this->db->get_where('product_assembly', array('autoAssembly' => '1','createdId' => $assemblyId))->row_array();
			 if($releaseAutoAssemblyDataTemps){
				 return true;
			 }else{
				 return false;
			 }
		}else{
			return false;
		}
	}
	public function updatePriceList($productId = '',$receipeid = ''){
		if(!$productId){return false;}		
		$this->{$this->globalConfig['fetchProduct']}->reInitialize();
		if($productId){
			$this->db->where(array('products.productId' => $productId));
		}
		if(!$receipeid){
			$this->db->where(array('product_bom.isPrimary' => '1'));
		}
		if($receipeid){
			$this->db->where(array('product_bom.receipeId' => $receipeid));
		}
		$datas = $this->db->join('product_bom', 'product_bom.productId = products.productId')->get_where('products', array('products.isBOM' => '1','products.autoBomPriceUpdate' => '1'))->result_array();
		$productMappings = array();
		$productMappingTemps = $this->db->select('productId,sku,params')->get_where('products')->result_array();
		if($productMappingTemps){
			foreach($productMappingTemps as $productMappingTemp){
				$productMappings[$productMappingTemp['productId']] = $productMappingTemp;
			}
		}
		$bomProDatas = array();$proDatas = array();$componentProductIds = array();
		foreach($datas as $data){
			$proDatas[$data['productId']][$data['componentProductId']] = array(
				'bomQty' 	=> $data['bomQty'],
				'qty' 		=> $data['qty'],
			);
			$componentProductIds[$data['componentProductId']] = $data['componentProductId'];
		}
		
		$bomProIds = array_keys($proDatas);
		$componentProductIds = array_keys($componentProductIds);
		sort($bomProIds);
		sort($componentProductIds);
		$bomPriceDetails = $this->{$this->globalConfig['fetchProduct']}->getProductPriceList($bomProIds);
		$bomPriceDetailsSku = $this->{$this->globalConfig['fetchProduct']}->getSkuByPricelistID($bomProIds);
		$componenetPriceDetails = $this->{$this->globalConfig['fetchProduct']}->getProductPriceList($componentProductIds);
		$updatePriceDetails = array();
		$this->config = $this->db->get('account_brightpearl_config')->row_array();
		$priceListId = $this->config['costPriceListbom'];
		if($priceListId == 'fifo'){
			$priceListId = $this->config['costPriceListbomNonTrack'];
		}
		foreach($proDatas as $bomProId => $proData){
			$updatePriceList = array();$totalCostBomPrice = 0;$totalCompCostPrice = 0;$totalRetailBomPrice = 0;$totalCompRetailPrice = 0;
			foreach($proData as $compProId => $row){
				$bomQty = $row['bomQty'];
				$compQty = $row['qty'];
				if(!$totalCostBomPrice)
					$totalCostBomPrice = $bomQty * $bomPriceDetails[$bomProId][$priceListId];
				$totalCompCostPrice += ($compQty * $componenetPriceDetails[$compProId][$priceListId]);
				/* if(!$totalRetailBomPrice)
					$totalRetailBomPrice = $bomQty * $bomPriceDetails[$bomProId][$this->config['retailPriceListbom']]; */
				$totalCompRetailPrice += ($compQty * $componenetPriceDetails[$compProId][$this->config['retailPriceListbom']]);				
			}
			$totalCostBomPrice = sprintf("%.4f",(round($totalCostBomPrice,3)));
			$totalCompCostPrice = sprintf("%.4f",(round($totalCompCostPrice,3)));
			$totalRetailBomPrice = sprintf("%.4f",(round($totalRetailBomPrice,3)));
			$totalCompRetailPrice = sprintf("%.4f",(round($totalCompRetailPrice,3)));
			if(($totalCostBomPrice != $totalCompCostPrice)){
				$updatePriceDetails[$bomProId][$priceListId] = $totalCompCostPrice / $bomQty;
				/* $updatePriceDetails[$bomProId][$this->config['retailPriceListbom']] = $totalCompRetailPrice / $bomQty; */ 
			}
		}
		if($receipeid){
			if($productId){
				$this->db->where(array('products.productId' => $productId));
			}
			$datas = $this->db->join('product_bom', 'product_bom.productId = products.productId')->get_where('products', array('products.isBOM' => '1','products.autoBomPriceUpdate' => '1','product_bom.receipeId' => $receipeid))->result_array();
			$proDatas = array();
			foreach($datas as $data){
				$proDatas[$data['productId']][$data['componentProductId']] = array(
					'bomQty' 	=> $data['bomQty'],
					'qty' 		=> $data['qty'],
				);
			}
		}
		
		$nonTrackItemsPrice = 0.00; $nonTrackItemsPrices = 0.00;
		foreach($proDatas as $bomProId => $proData){
			foreach($proData as $compProId => $row){
				$bomQty = $row['bomQty'];
				$compQty = $row['qty'];
				if($productMappings[$compProId]){
					$params = json_decode($productMappings[$compProId]['params'], true);
					if($params['stock']['stockTracked']){
						continue;
					}
					$productName = "";
					$salesChannels = reset($params['salesChannels']);
					$productName   = $salesChannels['productName'];
					if((strpos(strtolower($productName), 'hour')) || (strpos(strtolower($productName), 'hours'))){
						$nonTrackItemsPrice += ($compQty * $componenetPriceDetails[$compProId][$priceListId]) * 60; 
					}else{
						$nonTrackItemsPrice += ($compQty * $componenetPriceDetails[$compProId][$priceListId]); 	
					}
				}
			}
			$nonTrackItemsPrices = $nonTrackItemsPrice / $bomQty;
		}
		if($updatePriceDetails){
			foreach($updatePriceDetails as $productId => $updatePriceDetail){
				$postPrice = array('priceLists' => array());$bomPriceDetailsSkuss = '';
				$bomPriceDetailsSkuss = $bomPriceDetailsSku[$productId];
				foreach($updatePriceDetail as $priceListId => $precedata){
					$bomPriceDetailsSkus = "";
					$bomPriceDetailsSkus = $bomPriceDetailsSkuss[$priceListId];
					$postPrice['priceLists'][] = array(
						'priceListId' => $priceListId,
						'quantityPrice' => array('1' => sprintf("%.4f",(round($precedata,5)))),
						'sku' => $bomPriceDetailsSkus
					);
				}
				$postRes = $this->{$this->globalConfig['fetchProduct']}->getCurl('/product-service/product-price/'.$productId.'/price-list','PUT',json_encode($postPrice) ); 
				$path = FCPATH.'logs'.DIRECTORY_SEPARATOR.'assembly'.DIRECTORY_SEPARATOR . $productId. DIRECTORY_SEPARATOR .'price'. DIRECTORY_SEPARATOR . date("Ymd-His-").strtoupper(uniqid()).'.logs';
				if(!is_dir(dirname($path))) { mkdir(dirname($path),0777,true);chmod(dirname($path), 0777); }
				$logs = array(
					'oldPrice' 			=> $bomPriceDetails[$productId],
					'NewPrice' 			=> $postPrice,
					'Response data' 	=> $postRes,
				);
				file_put_contents($path,json_encode($logs),FILE_APPEND);
			}
		}
		return $nonTrackItemsPrices;
	}
	public function removeWipAssemblies($wipAssembliesIds){
		if(!$wipAssembliesIds){
			return false;
		} 
		$logtime = date('c');
		$this->deleteLogs['wipAssembliesIds'.$logtime] = $wipAssembliesIds;
		$deletedWipAssembliesIds = array(); 
		$config   = $this->db->get('account_' . $this->globalConfig['fetchProduct'] . '_config')->row_array();
		$wipAssemblies = array_keys($wipAssembliesIds);
		$this->db->where_in('createdId', $wipAssemblies);
		$datasWipTemps = $this->db->get_where('product_assembly',array('autoAssembly' => '1'))->result_array();
		$stockCorrectionDatasTemps = array();
		foreach($datasWipTemps as $datasWipTemp){
			$stockCorrectionDatasTemps[$datasWipTemp['createdId']][] = $datasWipTemp;
		}
		$returnAssemblyIds = array();$notDeleteAssemblyIds = array();
		foreach($wipAssembliesIds as $createdId => $wipAssembliesId){
			$errorResults = array();
			
			$this->deleteLogPath = FCPATH.'logs'.DIRECTORY_SEPARATOR.'removeassembly'.DIRECTORY_SEPARATOR . $createdId.'_'.$logtime.'.logs';
			if(!$stockCorrectionDatasTemps[$createdId]){
				continue;
			}
			$this->releaseAutoAssembly($createdId);
			$assemblyDatas = $stockCorrectionDatasTemps[$createdId];
			$assemblyData  = reset($assemblyDatas);
			$transferId    = $assemblyData['createdInventoryId'];
			$goodsOutId    = $assemblyData['goodsOutId'];
			$costingMethod = $assemblyData['costingMethod'];
			$return = false;
			$afterReleaseCheckData = $this->db->get_where('product_assembly',array('autoAssembly' => '1','createdInventoryId > ' => '0','isInventoryTransfer' => '1','isInventoryRelease' => '0','isAssembly' => '0', 'createdId' => $createdId))->result_array();
			$this->deleteLogs['afterReleaseCheckData'.$logtime] = $afterReleaseCheckData;
			if($afterReleaseCheckData){
				$return = true;
			}
			if(!$return){
				$correctionDatas = array();
				if($goodsOutId){
					$goodsDatas    = $this->{$this->globalConfig['fetchProduct']}->getResultByIdNew(array($goodsOutId),'/warehouse-service/order/*/goods-note/goods-out/','',500,1);
					if($goodsDatas){
						foreach($goodsDatas as $goodId => $goodsData){
							$getDefaultLocation = '';
							$getDefaultLocation = $this->{$this->globalConfig['fetchProduct']}->getDefaultWarehouseLocation($goodsData['targetWarehouseId']);
							$getDefaultLocation = reset($getDefaultLocation);
							foreach($goodsData['transferRows'] as $transferRows){
								$priceList 			 = $this->getProductPrice(array($transferRows['productId']));
								$correctionDatas['decrease'][$goodsData['targetWarehouseId']][] = array(
									'quantity'   => '-'.$transferRows['quantity'],
									'productId'  => $transferRows['productId'],
									'reason'     => 'Assembly of product. Assembly id: '.$createdId,
									'locationId' => $getDefaultLocation,
								);
								//increase
								$correctionDatas['increase'][$goodsData['warehouseId']][] 	    = array(
									'quantity'   => $transferRows['quantity'],
									'productId'  => $transferRows['productId'],
									'reason'     => 'Assembly of product. Assembly id: '.$createdId,
									'locationId' => $transferRows['locationId'],
									'cost'       => array(
										'currency' => $config['currencyCode'],
										'value'    => ($priceList[$transferRows['productId']][$costingMethod])?($priceList[$transferRows['productId']][$costingMethod]):0.00,
									), 
								);
							}
						}
						$this->deleteLogs['correctionDatas'.$logtime] = $correctionDatas;
						if($correctionDatas){
							$isError  = 0;
							foreach($correctionDatas as $type => $correctionData){
								if($isError){break;}
								foreach($correctionData as $warehouseId => $corrections){
									$postStockTrans['corrections'] = array();$correctionIds = array();
									$correctionUrl  = '/warehouse-service/warehouse/' . $warehouseId . '/stock-correction';
									$this->deleteLogs['correctionUrl'.$logtime] = $correctionUrl;
									if($type == "decrease"){
										$postStockTrans['corrections'] = $corrections;
										$results 		= $this->{$this->globalConfig['fetchProduct']}->postStockCorrection($correctionUrl, $postStockTrans);
										$this->deleteLogs['correction results'.$logtime] = $results;
										foreach ($results as $accountId => $result) {
											foreach ($result as $key => $rows) {
												if($key === 'errors'){
													$errorResults[$createdId] = $results;
													$isError = true;
													break;
												}
												else{
													if($rows){
														$correctionIds[] = $rows;
													}
												}                        
											}
										}
										if($isError){
											break;
										}
									}else{
										if(!$isError){
											$postStockTrans['corrections'] = $corrections;
											$results 		= $this->{$this->globalConfig['fetchProduct']}->postStockCorrection($correctionUrl, $postStockTrans);
											$this->deleteLogs['correction results'.$logtime] = $results;
											foreach ($results as $accountId => $result) {
												foreach ($result as $key => $rows) {
													if($key === 'errors'){
														$errorResults[$createdId] = $results;
														$isError = true;
														break;
													}
													else{
														if($rows){
															$correctionIds[] = $rows;
														}
													}                        
												}
											}
										}
									}
								}
							}
							if($isError){
								$notDeleteAssemblyIds[] = $createdId;
							}else{
								if($correctionIds){
									$returnAssemblyIds[] = $createdId;
								}else{
									$notDeleteAssemblyIds[] = $createdId;
								}
							}
						}else{
							$notDeleteAssemblyIds[] = $createdId;
						}
					}else{
						$returnAssemblyIds[] = $createdId;
					}
				}
				else{
					$returnAssemblyIds[] = $createdId;
				}
			}else{
				$returnAssemblyIds[] = $createdId;
			}
			if($errorResults){
				$asemID = reset(array_keys($errorResults));
				$emailList = array('abraam@uscutter.com,hitesh@businesssolutionsinthecloud.com','jaina@businesssolutionsinthecloud.com', 'dean@businesssolutionsinthecloud.com','aherve@businesssolutionsinthecloud.com');
				$subject = 'Assembly('.$asemID.') Deletion Error Message';
				$body = 'Hi ASA,<br><br>
				<p>We are getting this error message below when trying to delete this assembly : </p>
				<p>'.json_encode($errorResults).'</p>
				<br><br>
				';
				$body .= '			
				<br><br>
				Thanks & Regards<br>
				BSITC Team'; 
				$to = "hitesh@businesssolutionsinthecloud.com";
				// Always set content-type when sending HTML email
				$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
				// More headers
				$headers .= 'From: <alert@businesssolutionsinthecloud.com>' . "\r\n";
				foreach($emailList as $emailLis){
					mail($emailLis,$subject,$body,$headers);
				}
			}
			file_put_contents($this->deleteLogPath,json_encode($this->deleteLogs),FILE_APPEND); 
		}
		return array('deleteAssembly' => $returnAssemblyIds, 'notDeleteAssemblyIds' => $notDeleteAssemblyIds);
	}
	public function storeWarehouse(){
		$this->db->truncate('warehouse_master');
		$warehouseLists = $this->warehouseList(); $warehousesLists = array();
		if($warehouseLists){
			$savedWarehouseDatas = array();
			$savedWarehouseDataTemps = $this->db->get_where('warehouse_master')->row_array();
			foreach($savedWarehouseDataTemps as $savedWarehouseDataTemp){
				$savedWarehouseDatas[$savedWarehouseDataTemp['warehouseId']] = $savedWarehouseDataTemp;
			}
			$saveWarehouseMasterDatas = array();
			foreach($warehouseLists as $warehouseList){
				$warehousesLists[$warehouseList['id']] = $warehouseList['id'];
				if($savedWarehouseDatas[$warehouseList['id']]){
					continue;
				}
				$saveWarehouseMasterDatas = array(
					'warehouseName' => $warehouseList['name'],
					'warehouseId' 	=> $warehouseList['id'],
				);
				$this->db->insert('warehouse_master', $saveWarehouseMasterDatas);
			}
		}
		$saveBinDatas = array();
		$saveBinDatasTemps = $this->db->get_where('warehouse_binlocation')->result_array();
		foreach($saveBinDatasTemps as $saveBinDatasTemp){
			$saveBinDatas[$saveBinDatasTemp['warehouseId']][$saveBinDatasTemp['id']] = $saveBinDatasTemp;
		}
		if($warehousesLists){
			foreach($warehousesLists as $warehouseId => $warehousesList){
				$getDefaultWarehouseLocation = $this->getDefaultWarehouseLocation($warehouseId);
				if($saveBinDatas[$warehouseId][$getDefaultWarehouseLocation]){
					$this->db->where(array('warehouseId' => $warehouseId, 'id' => $getDefaultWarehouseLocation))->update('warehouse_binlocation', array('isDefaultLocation' => '1'));
				}
			}
		}
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
      public function getProduct()
    {
		$warehouseList = $this->getWarehouseMaster();
        $groupAction     = $this->input->post('customActionType');
        $records         = array();
        $records["data"] = array();
        if ($groupAction == 'group_action') {
            $ids = $this->input->post('id');
            if ($ids) {
                $status = $this->input->post('customActionName'); 
                if ($status != '' && $status =='delete') {
					$this->db->where_in('createdId', $ids);
					$deleteOnlyWipAssemblies = array();$deleteReorderWipAssemblies = array();
					$savedAssemblyDatas = $this->db->get_where('product_assembly',array('status' => '0', 'isAssembly' => '1'))->result_array();
					foreach($savedAssemblyDatas as $savedAssemblyData){
						if($savedAssemblyData['autoAssembly']){
							$deleteReorderWipAssemblies[$savedAssemblyData['createdId']] = $savedAssemblyData['createdId'];
						}else{
							$deleteOnlyWipAssemblies[$savedAssemblyData['createdId']] = $savedAssemblyData['createdId'];
						}
					}
					if($deleteReorderWipAssemblies){
						$removeWipAssemblies = $this->removeWipAssemblies($deleteReorderWipAssemblies);
						$deletedAutoAssemblyIds = $removeWipAssemblies['deleteAssembly'];
						$notDeleteAssemblyIds   = $removeWipAssemblies['notDeleteAssemblyIds'];
					}
					$notDeleteAssemblyId		= array_unique($notDeleteAssemblyIds);
					$deletedAutoAssemblyId 	    = array_unique($deletedAutoAssemblyIds);$finalDeletedAutoAssemblyId = array();
					if($deleteOnlyWipAssemblies && $deletedAutoAssemblyId){
						$finalDeletedAutoAssemblyId = array_merge($deleteOnlyWipAssemblies, $deletedAutoAssemblyId);
					}elseif(!empty($deletedAutoAssemblyId) && empty($deleteOnlyWipAssemblies)){
						$finalDeletedAutoAssemblyId = $deletedAutoAssemblyId;
					}elseif(empty($deletedAutoAssemblyId) && !empty($deleteOnlyWipAssemblies)){
						$finalDeletedAutoAssemblyId = $deleteOnlyWipAssemblies;
					}
					if($finalDeletedAutoAssemblyId){
						$isDeleted = 0;
						foreach($finalDeletedAutoAssemblyId as $finalDeleted){
							$res =  $this->db->where('createdId', $finalDeleted)->update('product_assembly', array('isAssemblyDeleted' => '1'));
							$isDeleted = 1;
						}
						if($isDeleted){
							$deletedAssemblies = implode(', ', $ids);
							$records["customActionStatus"]  = "OK"; // pass custom message(useful for getting status of group actions)
							$records["customActionMessage"] = "WIP Assemblies (".$deletedAssemblies.") have been removed.";// pass custom message(useful for getting status of group actions)
						}
					}else{
						$notDeleteAssemblyId = implode(', ', $notDeleteAssemblyId);
						$records["customActionStatus"]  = "danger"; // pass custom message(useful for getting status of group actions)
						$records["customActionMessage"] = "An unexpected error occured while deleting the Assembly. could not able to delete WIP Assemblies " . $notDeleteAssemblyId; // pass custom message(useful for getting status of group actions)
					}
					
                }
            }
        }


        $where = array('isAssembly' => '1');
        $query = $this->db;
        if ($this->input->post('action') == 'filter') {
            if (trim($this->input->post('productId'))) {
                $where['productId'] = trim($this->input->post('productId'));
            }
            if (trim($this->input->post('createdId'))) {
                $where['createdId'] = trim($this->input->post('createdId'));
            }            
            if (trim($this->input->post('sku'))) {
                $where['sku'] = trim($this->input->post('sku'));
            }           
            if (trim($this->input->post('status')) >= '0') {
                $where['status'] = trim($this->input->post('status'));
            }
			if (trim($this->input->post('username'))) {
                $where['username'] = trim($this->input->post('username'));
            } 
			if (trim($this->input->post('name'))) {
                $where['name'] = trim($this->input->post('name'));
            } 
			if (trim($this->input->post('warehouse'))) {
                $where['warehouse'] = trim($this->input->post('warehouse'));
            }
			if (trim($this->input->post('assemblyType'))) {
				if($this->input->post('assemblyType') == '2'){
					$where['isOrderAssembly'] = '1';
				}elseif($this->input->post('assemblyType') == '3'){
					$where['autoAssembly'] = '1';
				}elseif($this->input->post('assemblyType') == '1'){
					 $where['autoAssembly'] = '0';
					 $where['isOrderAssembly'] = '0';
				}else{
					$where['autoAssembly'] = '';
					$where['isOrderAssembly'] = '';
				}
               
            }
        }
        if (trim($this->input->post('updated_from'))) {
            $query->where('date(created) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim($this->input->post('updated_to'))) {
            $query->where('date(created) < ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }
		$query->where('isAssemblyDeleted', '0');
        $totalRecord = @$query->get('product_assembly')->num_rows();
        $limit       = intval($this->input->post('length'));
        $limit       = $limit < 0 ? $totalRecord : $limit;
        $start       = intval($this->input->post('start'));

        $query = $this->db;
        if (trim($this->input->post('updated_from'))) {
            $query->where('date(created) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim($this->input->post('updated_to'))) {
            $query->where('date(created) < ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }

        $status              = array('0' => 'WIP', '1' => 'Completed', '2' => 'Updated', '3' => 'Error', '4' => 'Archive');
        $statusColor         = array('0' => 'warning', '1' => 'success', '2' => 'info', '3' => 'warning', '4' => 'danger');
        $displayProRowHeader = array('id', 'autoAssembly', 'username','createdId','warehouse', 'productId', 'sku', 'name', 'status', 'created');
        if ($this->input->post('order')) {
            foreach ($this->input->post('order') as $ordering) {
                if (@$displayProRowHeader[$ordering['column']]) {
                    $query->order_by($displayProRowHeader[$ordering['column']], $ordering['dir']);
                }
            }
        }
		else{
			//$query->order_by('id','desc');
		}
		$query->where('isAssemblyDeleted', '0');
        $datas = $query->select('id,productId,createdId,receipId,sku,created,name,status, autoAssembly,username,warehouse,isOrderAssembly')->limit($limit, $start)->get('product_assembly')->result_array();
		
        foreach ($datas as $data) {
			$enableEdit = '';$enablePrint = '';
			if($data['status'] == '0'){
				$enableEdit = '<a class="actioneditbtn btn btn-icon-only green" href="' . base_url('products/assembly/addNewAssembly/' . base64_encode($data['name']. ' - ' . $data['sku'] ) . '/'.$data['createdId'] ) . '" title="View Assembly"><i class="fa fa-edit" title="Edit Assembly" ></i></a>';
			}
			//if($data['status']){
				$enablePrint = ' <a class="btn btn-icon-only green" target="_blank" href="' . base_url('products/assembly/generateBarcode/' . $data['createdId']) . '" title="Generate Barcode"><i class="fa fa-print" title="Generate Barcode" ></i></a>';
			//}
			$workInProgress = '';
			if($data['status'] == 0){
				$workInProgress = '<input type="checkbox" name="id[]" value="'. $data['createdId'] . '" class="deleteAssembly">';
			}
			$assemblyType = '<span class="label label-sm label-warning bade" style="background-color:#26c281">Manual Assembly</span>';
			if($data['autoAssembly']){
				$assemblyType = '<span class="label label-sm label-success bade" style="background-color:#7c01c9">Re-Order Assembly</span>' ;;
			}elseif($data['isOrderAssembly']){
				$assemblyType = '<span class="label label-sm label-info bade" style="background-color:#b90e6ca8">Standard Auto-Assembly</span>';
			}
            $records["data"][] = array(
                $workInProgress,
                $assemblyType,
                ucwords($data['username']),
                $data['createdId'],
                $warehouseList[$data['warehouse']]['warehouseName'],
                $data['productId'],
                $data['sku'],
                $data['name'],
               '<span class="label label-sm label-' . $statusColor[$data['status']] . '">' . $status[$data['status']] . '</span>',
                date('M d,Y h:i:s a',strtotime($data['created'])),
                '<a class="actioneditbtn btn btn-icon-only blue" href="' . base_url('products/assembly/viewassembly/' . $data['createdId']) . '" title="View Assembly"><i class="fa fa-eye" title="View Assembly" ></i></a>'.$enableEdit.$enablePrint.'
                ',
            );
        }
        $draw                       = intval($this->input->post('draw'));
        $records["draw"]            = $draw;
        $records["recordsTotal"]    = $totalRecord;
        $records["recordsFiltered"] = $totalRecord;
        return $records;
    }
    public function saveReceipe($productId, $datas)
    {
        if (is_array($datas)) {
            foreach ($datas['sku'] as $i => $sku) {
                $qty       = @$datas['qty'][$i];
                $name      = @$datas['name'][$i];
                $receipeid = @$datas['receipeid'][$i];
                $id        = @$datas['savebomid'][$i];
                if (($sku) && ($qty)) {
                    $saveArray = array(
                        'productId' => $productId,
                        'sku'       => $sku,
                        'name'      => $name,
                        'qty'       => $qty,
                        'receipeid' => ($receipeid) ? ($receipeid) : '1',
                    );
                    if ($id) {
                        $saveArray['id'] = $id;
                        $this->db->where(array('id' => $saveArray['id']))->update('product_bom', $saveArray);
                    } else {
                        $this->db->insert('product_bom', $saveArray);
                    }
                }
            }
        }
    }
}
