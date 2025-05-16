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
	public function saveAssignUserId($assignedUserId, $assemblyId =""){
		$assemblyDetails = $this->db->where(array('createdId'=>$assemblyId,'isAssembly' =>1))->get('product_assembly')->row_array();
		//echo "<pre>";print_r($assemblyDetails); echo "</pre>";die(__FILE__.' : Line No :'.__LINE__);
		if($assemblyDetails['assignToUserId']!=$assignedUserId){
			/* $this->brightpearl->reInitialize();
			$config   		   = $this->db->get('account_' . $this->globalConfig['fetchProduct'] . '_config')->row_array();
			$orderId 		   = $assemblyDetails['orderId'];
			$orderStatusRequest = array(
								'orderStatusId' => $config['soStatusUpdatedTo'],
			);
			$orderStatusUpdate  = $this->brightpearl->getCurl('/order-service/order/'.$orderId.'/status','PUT',json_encode($orderStatusRequest),'json'); */
			/*$userMappingListTemps = $this->db->select('*')->get_where('admin_user', array('is_active' => '1'))->result_array();
			foreach($userMappingListTemps as $userMappingListTemp){
				$userMappingList[$userMappingListTemp['user_id']] = $userMappingListTemp;
			}
			$userData = $userMappingList[$assignedUserId];
			$assignedUserName = ucfirst($userData['firstname']). ' ' . ucfirst($userData['lastname']);
			echo $csUpdateUrl = '/order-service/order/'.$orderId.'/custom-field';
			$csRequest  = '[
								{
									"op": "add",
									"path": "/PCF_ASSIGNTO",
									"value": "'.$assignedUserName.'"
								}
							]';
							echo "<pre>";print_r($csRequest); echo "</pre>";
			//$res = $this->brightpearl->getCurl($csUpdateUrl,'GET','','');
			$res = $this->brightpearl->getCurl($csUpdateUrl,'PATCH',$csRequest,'');*/
			$this->db->where('createdId',$assemblyId)->update('product_assembly', array('assignToUserId'=>$assignedUserId));
			$this->assemblyDataById($assemblyId, $assignedUserId);
		}
	}
	public function saveAssembly($datas, $assemblyId =""){
		if (!$datas['productId']){
			return false;
		}
		$this->brightpearl->reInitialize();
		$config   		   = $this->db->get('account_' . $this->globalConfig['fetchProduct'] . '_config')->row_array();
		$validateDefaultCurrency = $this->validateDefaultCurrency($config);
		if(!$validateDefaultCurrency['isValid']){
			echo json_encode(array('status' => '0','message' => $validateDefaultCurrency['message']));
			return false;
		}
		$logtime		   = date('c'); $this->logs = array(); $createdId = ""; $isError  = 0;$saveAssemblyCompDatas = array();$saveAssemblyBomDatas = array();
		$productMappings = array(); $bundleProductsIds = array();
		$productMappingTemps = $this->db->select('productId,sku,isBundle,params')->get_where('products')->result_array();
		if($productMappingTemps){
			foreach($productMappingTemps as $productMappingTemp){
				$productMappings[$productMappingTemp['productId']] = $productMappingTemp;
				if($productMappingTemp['isBundle']){
					$bundleProductsIds[] = $productMappingTemp['productId'];
				}
			}
		}
		$bomId 		= array($datas['productId']);
		$compAllProductIds = array();
		if(isset($datas['billcomponents'][$datas['receipeid']])){
			$compAllProductIds  = array_keys($datas['billcomponents'][$datas['receipeid']]);
			$allPIds = array_merge($bomId,$compAllProductIds);
		}
		$showIsBomSkus = array();
		foreach($allPIds as $allPId){
			if (in_array($allPId, $bundleProductsIds)){
				$showIsBomSkus[] = $allPId;
			}
		}
		if($showIsBomSkus){
			echo json_encode(array('status' => '0','message' => "Product Id(s) ".implode(',', $showIsBomSkus)." are bundle type."));
			return false;
		}
		$user_session_data = $this->session->userdata('login_user_data');		
		$assemblyId 	   = $datas['assemblyId'];
		$isAutoassembly    = 0;
		if(!$datas['isOrderAssembly']){$datas['isOrderAssembly'] = 0;}
		if(!@$datas['isImportAssembly']){$datas['isImportAssembly'] = 0;}
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
		$this->path = FCPATH.'logs'.DIRECTORY_SEPARATOR.'assembly'.DIRECTORY_SEPARATOR . $createdId.'.json';
		if(!is_dir(dirname($this->path))) { @mkdir(dirname($this->path),0777,true);@chmod(dirname($this->path), 0777); }
		$this->logs['Log time'] 	= $logtime;
		$this->logs['Post Request'] = $datas;
		//function update the price but returns price for non tracked item only
		$nonTrackedItemPrice = $this->updatePriceList($datas['productId'],$datas['receipeid'], $productMappings, $datas);
		$priceList 		   = $this->getProductPrice(array($datas['productId']));
		if(!$nonTrackedItemPrice){
			$nonTrackedItemPrice = 0.00;
		}
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
		$this->logs['Saved Receipe'] = $saveReceipe;
		$postStockTransferArrays = array("decrease" => array(),"increase" => array());
		foreach ($datas[$datas['receipeid']]['productId'] as $key => $productId){
			$productSavedData = array();
			$productSavedData = $productMappings[$productId];
			if(!$productSavedData){
				continue;
			}
			$productRawData = json_decode($productSavedData['params'], true);
			if(!$productRawData['stock']['stockTracked']){
				continue;
			}
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
			if($datas['isImportAssembly']){
				$reason = 'Assembly Import Ref# '.$datas['reference'];
				if(!$datas['reference']){
					$datas['reference'] = $createdId;
					$reason = 'Assembly Import Ref# '.$createdId;
				}
			}
			if($sourceWareHouseId){
				if(@!$defaultWareHouse[$sourceWareHouseId]){
					$defaultWareHouse[$sourceWareHouseId] = $this->getDefaultWarehouseLocation($sourceWareHouseId);
				}
				if($saveReceipe[$productId]['qty'] <= 0){
					continue; //added by hitesh
				}
				$decreaseBinL = ($sourceBinLocation)?($sourceBinLocation):($defaultWareHouse[$sourceWareHouseId]);
				if($datas['isOrderAssembly']){
					$decreaseBinL = $datas['decreaseBinLocation'][$productId];
				}
				$postStockTransferArrays['decrease'][$sourceWareHouseId][] = array(
					'quantity'  => (-1) * ceil(($saveReceipe[$productId]['qty'] * ($datas['qtydiassemble']/$saveReceipe[$productId]['bomQty']))),
					'sku' 		=> $saveReceipe[$productId]['sku'],
					'name' 		=> $saveReceipe[$productId]['name'],
					'productId' => $productId,
					'reason'    => $reason,
					'locationId' => $decreaseBinL,
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
				$this->logs['Stock Transfer']['Url']     = $stockTransferCreateUrl;
				$this->logs['Stock Transfer']['Requset'] = $stockTransferCreateUrlRequset;
				$stockTransferCreateUrlRequsetRes = @reset($this->brightpearl->getCurl($stockTransferCreateUrl,'POST',json_encode($stockTransferCreateUrlRequset),'json'));
				$this->logs['Stock Transfer']['Response'] = $stockTransferCreateUrlRequsetRes;
				if(!isset($stockTransferCreateUrlRequsetRes['errors'])){
					$autoAssemblyCreated = 1;
					$createdInventoryId = $stockTransferCreateUrlRequsetRes;
				}
				$this->addProductInExternalTransfer($createdId); // adding product in external transfer
			}				
		}
		if(!$autoAssemblyCreated){return false;}
		$errorResults = array();$isSaveInProgress = '0';$isWorkInProgressSaved = 0;$isSubmitted = 0;
		$this->logs['Stock Correction']['Pre Request Data'] = $postStockTransferArrays;
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
								'assignToUserId' 	=> @$datas['assignToUserId'],
								'assignByUserId' 	=> @$user_session_data['user_id'],
								'costingMethod' 	=> @$datas['costingmethod'],
								'finalBIn' 			=> @$datas['finalBIn'],
								'status' 			=> '0',
								'ip' 				=> @$_SERVER['REMOTE_ADDR'],
								'autoAssembly' 		=> (int)$autoAssembly,
								'createdInventoryId' => $createdInventoryId,
								'autoAssemblyWipWarehouse' => $datas['autoAssemblyWipWarehouse'],
								'isOrderAssembly' => $datas['isOrderAssembly'],
								'isImportAssembly' => $datas['isImportAssembly'],
								'reference' 	   => @$datas['reference'],
								'uniqueFileId' 	   => @$datas['uniqueFileId'],
							);
							$responseData = $this->db->replace('product_assembly', $saveArray); 
							if($responseData){
								$isSubmitted = 1;
							}
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
				if(!$isError){
					$goodsMovedPriceDatas = array();$increasePrice = 0.00;
					foreach($postStockTransferArrayTemps as  $warehouseId =>  $postStockTransferArray){
						if($type == 'increase'){
							$fifoPriceLists = array();
							$this->logs['Stock Correction']['Costing Method'] = $datas['costingmethod'];
							if($datas['costingmethod'] == 'fifo'){
								if($correctionIdss){
									$fetchCorrectionDatas = array();
									foreach($correctionIdss as $wid => $correctionIds){
										$correctionIds = array_filter($correctionIds);
										$correctionIds = array_filter($correctionIds);
										sort($correctionIds);
										$fetchCurrectionUrl = '/warehouse-service/warehouse/'.$wid.'/stock-correction/'.implode(",",$correctionIds);	
										if($isAutoassembly){
											$fetchCurrectionUrl = '/warehouse-service/warehouse/' . $datas['autoAssemblyWipWarehouse'] . '/stock-correction/'.implode(",",$correctionIds);
										}
										$fetchCorrectionDatasTemps = $this->brightpearl->getCurl($fetchCurrectionUrl);
										$this->logs['Stock Correction']['Fifo Assembly Fetch Correction Url']   = $fetchCurrectionUrl;
										$this->logs['Stock Correction']['Fifo Assembly Fetch Correction Response'] = $fetchCorrectionDatasTemps;
										foreach($fetchCorrectionDatasTemps as $accId => $fetchCorrectionData){
											if($fetchCorrectionData){							
												foreach($fetchCorrectionData as $fetchCorrectionDat){
													foreach($fetchCorrectionDat['goodsMoved'] as $goodsMoved){
														@$fetchCorrectionDatas[$goodsMoved['productId']]['productValue'] += ($goodsMoved['productValue']['value'] * abs($goodsMoved['quantity']));
														@$fetchCorrectionDatas[$goodsMoved['productId']]['quantity'] += abs($goodsMoved['quantity']);
													}
												}
											}
										}
									}
									$this->logs['Stock Correction']['Calculation Fetched Data'] = $fetchCorrectionDatas;
									$finalAmount = 0;
									if($fetchCorrectionDatas){
										foreach($fetchCorrectionDatas as $correctionProductId => $correctionAmount){
											$correctionAmountCal = $correctionAmount['productValue'];
											$finalAmount += $correctionAmountCal;
										}
									}
									if($finalAmount > 0){ 
										$finalAmount =  $finalAmount / $datas['qtydiassemble'];
										// commented by hitesh after discussion with Tushare to check 
										//$finalAmount =  $finalAmount / $saveReceipeDatas[$correctionProductId]['bomQty'];
										$postStockTransferArray['0']['cost']['value'] = sprintf("%.4f",($finalAmount + $nonTrackedItemPrice));
									}else{
										if($nonTrackedItemPrice > 0){
											$postStockTransferArray['0']['cost']['value'] = $nonTrackedItemPrice;
										}
									}
									$this->logs['Stock Correction']['Final Calculated Price'] = $postStockTransferArray['0']['cost']['value'];
								}
							}
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
						$this->logs['Stock Correction']['Url Type: '. $type] 	     = $url;
						$this->logs['Stock Correction']['Request Type: '.$type]    = $postStockTrans;
						$this->logs['Stock Correction']['Response Type: '.$type]   = $results;
						foreach ($results as $accountId => $result) {
							foreach ($result as $key => $rows) {
								if($key === 'errors'){
									$errorResults[] = $results;
									$isError = true;
									break ;
								}
								else{
									if($rows){
										if($type == 'decrease'){
											$correctionIdss[$warehouseId][] = $rows;
											$this->logs['Stock Correction']['Correction Ids'] = $correctionIdss;
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
											'orderId' 			=> $datas['orderId'],
											'status' 			=> 1,
											'costingMethod' 	=> @$datas['costingmethod'],
											'finalBIn' 			=> @$datas['finalBIn'],
											'username' 			=> @$user_session_data['username'],
											'assignToUserId' 	=> @$datas['assignToUserId'],
											'assignByUserId' 	=> @$user_session_data['user_id'],
											'ip' 				=> @$_SERVER['REMOTE_ADDR'],
											'createdInventoryId' => @$createdInventoryId,
											'autoAssemblyWipWarehouse' => $datas['autoAssemblyWipWarehouse'],
											'isOrderAssembly' => $datas['isOrderAssembly'],
											'isImportAssembly' => $datas['isImportAssembly'],
											'reference' 	   => $datas['reference'],
										);
										if($isAutoassembly){
											$responseData = $this->db->where(array('createdId' => $createdId, 'productId' => $postStockTrans['corrections'][$key]['productId']))->update('product_assembly', $saveArray);
											if($responseData){
												$isSubmitted = 1;
											}
										}else{
											$responseData = $this->db->replace('product_assembly', $saveArray); 
											if($responseData){
												$isSubmitted = 1;
											}
										}
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
		$assignToUser = @$datas['assignToUserId'];
		if($isSaveInProgress == '1'){
			if($assignToUser && $isSubmitted){
				$this->assemblyDataById($createdId, $assignToUser);
			}
		}else{
			if($isSubmitted){
				// $this->assemblyDataById($createdId, $assignToUser, "admin");
				$this->assemblyDataById($createdId, $assignToUser);
			}
		}
		if($isError == '1'){
			if($datas['isImportAssembly']){
				$finalErrorMessages = "";
				$finalErrorMessage = array();
				foreach($errorResults as $saveRrrorMessage){
					foreach($saveRrrorMessage as $saveRrrorMessag){
						foreach($saveRrrorMessag['errors'] as $errors){
							$finalErrorMessage[] = $errors['message'];
						}
					}
				}
				$finalErrorMessages = implode("<br>", $finalErrorMessage);
				$this->db->where(array('uniqueFileId' => $datas['uniqueFileId'], 'bomProductId' => $datas['productId']))->update('import_assembly_bom',array('message' => $finalErrorMessages));
				$this->db->where(array('uniqueFileId' => $datas['uniqueFileId']))->update('import_assembly',array('isProcessedWithError' => '1'));
			}else{
				echo json_encode(array('status' => '0','message' => json_encode($errorResults), 'assemblyId' => '', 'isSaveInProgress' => $isSaveInProgress, 'createdSkuName'=> ''));
			}
		}
		else{
			if($assemblyId){
				echo json_encode(array('status' => '1','message' => 'Assembly successfully updated <br> Updated id : <b>'.$assemblyId.'</b>', 'assemblyId' => $assemblyId, 'isSaveInProgress' => $isSaveInProgress, 'createdSkuName' =>  base64_encode($datas['name']. ' - ' . $datas['sku'] ) ));
			}else{
				if(!$autoAssembly){
					if(!$datas['isOrderAssembly'] || !$datas['isImportAssembly']){
						echo json_encode(array('status' => '1','message' => 'Assembly successfully created<br> Created id : <b>'.$createdId.'</b>','assemblyId' => $createdId, 'isSaveInProgress' => $isSaveInProgress,'createdSkuName' =>  base64_encode($datas['name']. ' - ' . $datas['sku'] )));
					}
				}
			}
			if($datas['isImportAssembly']){
				$this->db->where(array('uniqueFileId' => $datas['uniqueFileId'], 'bomProductId' => $datas['productId'], 'id' => $datas['bomInsertedId']))->update('import_assembly_bom',array('status' => '1', 'createdAsseblyId' => $createdId, 'message' => ''));
			}
		}
		if(!$datas['isOrderAssembly'] || !$datas['isImportAssembly']){
			$this->addProductInExternalTransfer($createdId); // adding product in external transfer
		}
		if($datas['autoAssembly']){
			$this->releaseAutoAssembly($createdId);
		}
		if($createdId && !$isError){
			return $createdId;
		}
    }
	public function validateDefaultCurrency($config = array()){
		$this->brightpearl->reInitialize();
		$currencyUrl 	  = '/accounting-service/currency-search';
		$isValid 		  = true;
		$fetchBody 		  = '';
		$currencyResponses = $this->brightpearl->getCurl($currencyUrl,'GET','','json');
		$fetchBody = $currencyResponses;
		if($currencyResponses){
			$currencyDatas = array();
			foreach($currencyResponses as $account1Id => $currencyResponse){
				if(!isset($currencyResponse['errors'])){
					foreach($currencyResponse['results'] as $results){
						$currencyDatas[$results['0']] = $results['2'];
					}
				}else{
					$isValid = false;
				}
			}
			if($currencyDatas){
				$defaultCurrencyId = array();
				$getAccountInfo = $this->brightpearl->getAccountInfo();
				
				$defaultCurrencyId = reset($getAccountInfo)['configuration']['defaultCurrencyId'];
				$defaultBPCurrency = trim(strtolower($currencyDatas[$defaultCurrencyId]));
				if(!trim(strtolower($config['currencyCode']))){
					$isValid = false;
				}elseif(trim(strtolower($config['currencyCode'])) != $defaultBPCurrency){
					$isValid = false;
				}
			}
		}
		return array('isValid' => $isValid, 'message' => $fetchBody);
	}
	public function getBinLocationByWarehouse($warehouseId, $binLocation){
		return $this->db->get_where('warehouse_binlocation',array('name' => $binLocation,'warehouseId' => $warehouseId))->row_array();
	}
	public function getAllWarehouseLocationInsert(){
		return $this->{$this->globalConfig['account1Liberary']}->getAllWarehouseLocationInsert();
	}
	public function addProductInExternalTransfer($assemblyId = ''){
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
		if($datas){
			foreach($datas as $stockTransferId => $data){
				$tranferWarehouseIDs = ''; $createdId = '';$autoAssemblyWipWarehouse = '';
				$tranferWarehouseIDs = reset($data);
				$tranferWarehouseID = $tranferWarehouseIDs['warehouse'];
				$createdId 			= $tranferWarehouseIDs['createdId'];
				$autoAssemblyWipWarehouse = $tranferWarehouseIDs['autoAssemblyWipWarehouse'];
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
					$this->logs['Transferred Products']['URL']  	 = $stockTransferCreateUrl;
					$this->logs['Transferred Products']['Request']  = $request;
					$this->logs['Transferred Products']['Response'] = $stockTransferCreateUrlRequsetRes;
					if(!isset($stockTransferCreateUrlRequsetRes['errors'])){
						$this->db->where(array('createdInventoryId' => $stockTransferId))->update('product_assembly',array('isInventoryTransfer' => '1'));
						foreach($stockTransferCreateUrlRequsetRes as $stockTransferCreateUrlRequsetRe){
							$eventUrl = '/warehouse-service/goods-note/goods-out/'.$stockTransferCreateUrlRequsetRe.'/event';
							$eventRequst = array(
								'events' => array(
									array(
										'eventCode' 	=> 'SHW',
										'occured' 		=> date('c'),
										'eventOwnerId' 	=> $config['defaultOwnerId'],
									),
								),
							);
							$eventRes = @reset($this->brightpearl->getCurl($eventUrl,'POST',json_encode($eventRequst),'json'));
							$this->logs['Ship Event']['URL'] 		= $eventUrl;
							$this->logs['Ship Event']['Request'] 	= $eventRequst;
							$this->logs['Ship Event']['Response'] 	= $eventRes;
						}
					}
					$this->db->where(array('createdId' => $createdId))->update('product_assembly',array('goodsOutId' => $stockTransferCreateUrlRequsetRes['0']));
				}
			}
		}
	}
	public function assemblyDataById($createdId, $assignToUser, $toAdmin=''){
        $data = array(); $printHtml = ""; $warehouseList = array();$getAllWarehouseLocation=array();$recipeData=array();$allproducts=array();$userMappingList = array();
		if($createdId){
			$userMappingListTemps = $this->db->select('*')->get_where('admin_user', array('is_active' => '1'))->result_array();
			foreach($userMappingListTemps as $userMappingListTemp){
				$userMappingList[$userMappingListTemp['user_id']] = $userMappingListTemp;
			}
			$data['warehouseList'] 			   = $this->warehouseList();
			$data['getAllWarehouseLocation']   = $this->{$this->globalConfig['account1Liberary']}->getAllWarehouseLocation();
			$warehouseList = $data['warehouseList'];
			$getAllWarehouseLocation = $data['getAllWarehouseLocation'];
			$recipeDatas = $this->db->get('product_bom')->result_array(); 
			foreach($recipeDatas as $recipeData){
				$data['recipeData'][$recipeData['receipeId']] = $recipeData;
			}
			$data['allproducts'] = $this->db->get_where('product_assembly',array('createdId' => $createdId))->result_array();
			$recipeData  = $data['recipeData'];
			$allproducts = $data['allproducts'];
			if($toAdmin){
				$assignByUserId = $allproducts['0']['assignByUserId'];
				$assignToUser   = $allproducts['0']['assignToUserId'];
			}
			if($allproducts){
				$userData = $userMappingList[$assignToUser];
				$sendToEmail = $userData['email'];
				$printHtml = '<div> Dear '.ucfirst($userData['firstname']). ' ' . ucfirst($userData['lastname']).',<br><p></p>Please note that you have been assigned an assembly with details below.<br><br>';
				if($toAdmin){
					$assignToUserData = $userMappingList[$assignByUserId];
					$sendToEmail = $assignToUserData['email'];
					$printHtml = '<div> Dear '.ucfirst($assignToUserData['firstname']). ' ' . ucfirst($assignToUserData['lastname']).',<br><p></p>Please note that the WIP assembly you assigned to '.ucfirst($userData['firstname']). ' ' . ucfirst($userData['lastname']).' has been completed.<br>';
				}
				$printHtml .= '<div><h3>Assembly Details</h3></div><p></p><table style="border:1px solid #e7ecf1;padding:5px;"><thead style="border-bottom:1px solid #e7ecf1;"><tr><th style="width:200px;">Assembly  Id</th><th>Product ID</th><th>Product SKU</th><th>Product Name</th><th>Warehouse</th><th>Bin Location</th><th>Qty</th><th>Recipe</th><th>Created By</th><th>Status</th><th>Created</th></tr></thead><tbody style="font-size:13px;padding:10px;background-color:#e8e8e89e;">';
				foreach($allproducts as $allproduct){
					if($allproduct['isAssembly'] == '1'){
						$status = "Work in Progress";
						if($allproduct['status'] == '1'){
							$status = "Completed";
						}
						$printHtml .= '<tr>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$allproduct['createdId'].'</td>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$allproduct['productId'].'</td>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$allproduct['sku'].'</td>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$allproduct['name'].'</td>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$warehouseList[$allproduct['warehouse']]['name'].'</td>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.@$getAllWarehouseLocation[$allproduct['receipId']][$allproduct['locationId']]['name'].'</td>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$allproduct['qty'].'</td>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.@$recipeData[$allproduct['receipId']]['recipename'].'</td>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.ucfirst($userMappingList[$allproduct['assignByUserId']]['firstname']).' '.ucfirst($userMappingList[$allproduct['assignByUserId']]['lastname']).'</td>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.$status.'</td>
							<td style="font-size:13px;padding:10px;background-color:#e8e8e89e;">'.date('M d,Y H:i:s',strtotime($allproduct['created'])).'</td> 
						</tr>';
					}
				}
				$printHtml .= '</tbody></table></div>';
				if(($allproducts) && (!$toAdmin)){
					$printHtml .= '<p></p><div><h3>Component Details</h3></div><p></p>
					<table style="border:1px solid #e7ecf1;padding:5px;"><thead style="border-bottom:1px solid #e7ecf1;"><tr><th >Recipe #</th><th>Component Brightpearl Product Id</th><th>Component Brightpearl Product SKU</th><th >Component Brightpearl Product Name</th><th>Warehouse</th><th>Bin Location</th><th>Qty</th></tr></thead><tbody style="font-size:13px;padding:10px;background-color:#e8e8e89e;">';
					foreach($allproducts as $allproduct){
						if($allproduct['isAssembly'] == '0'){
							$printHtml .='<tr>
								<td>'.@$recipeData[$allproduct['receipId']]['recipename'].'</td>
								<td>'.$allproduct['productId'].'</td>
								<td>'.$allproduct['sku'].'</td>
								<td>'.$allproduct['name'].'</td>
								<td>'.@$warehouseList[$allproduct['warehouse']]['name'].'</td>
								<td>'.@$getAllWarehouseLocation[$allproduct['receipId']][$allproduct['locationId']]['name'].'</td>
								<td>'.$allproduct['qty'].'</td>
							</tr>';
						}
					}
					$printHtml .='</tbody></table></div>';
				}
				if($sendToEmail && $printHtml){
					$subject = "Assembly Assigned Notification ".date("Y-m-d");
					if($toAdmin){
						$subject = "Assembly Completed Notification ".date("Y-m-d");
					}
					// Always set content-type when sending HTML email
					$headers = "MIME-Version: 1.0" . "\r\n";
					$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
					// More headers
					$headers .= 'From: <alerts@businesssolutionsinthecloud.com>' . "\r\n";
					mail($sendToEmail,$subject,$printHtml,$headers);			
				}
			}
		}
    }
	
	public function releaseAutoAssembly($createdId = ""){
		 
		$this->brightpearl->reInitialize();
		$config   = $this->db->get('account_' . $this->globalConfig['fetchProduct'] . '_config')->row_array();
		if($createdId){
			$datasTemps = $this->db->get_where('product_assembly',array('autoAssembly' => '1','createdInventoryId > ' => '0','isInventoryTransfer' => '1','isInventoryRelease' => '0','isAssembly' => '0', 'createdId' => $createdId))->result_array();
		}else{
			$datasTemps = $this->db->get_where('product_assembly',array('autoAssembly' => '1','createdInventoryId > ' => '0','isInventoryTransfer' => '1','isInventoryRelease' => '0','isAssembly' => '0'))->result_array();
		}
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
			$this->logs['Release']['Request']   = $releaseDataRequest;
			if($releaseDataRequest){
				$releaseResDatas = $this->brightpearl->getCurl($url,'POST',json_encode($releaseDataRequest),'json');
				$releaseResData = reset($releaseResDatas);
				$this->logs['Release']['Response'] = $releaseResDatas;
				if(!isset($releaseResData['errors'])){
					$this->db->where(array('id' => $datasTemp['id']))->update('product_assembly',array('isInventoryRelease' => '1'));
				}
			}
		}
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
	public function updatePriceList($productId = '',$receipeid = '', $productMappings = array(), $datasRequest = array()){
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
		
		if(strtolower($datasRequest['costingmethod']) == 'fifo'){
			$datas = $this->db->join('product_bom', 'product_bom.productId = products.productId')->get_where('products', array('products.isBOM' => '1'))->result_array();
		}
		else{
			$datas = $this->db->join('product_bom', 'product_bom.productId = products.productId')->get_where('products', array('products.isBOM' => '1','products.autoBomPriceUpdate' => '1'))->result_array();
		}
		$autoBomPriceUpdate = 0;
		$bomProDatas = array();$proDatas = array();$componentProductIds = array();
		foreach($datas as $data){
			$proDatas[$data['productId']][$data['componentProductId']] = array(
				'bomQty' 	=> $data['bomQty'],
				'qty' 		=> $data['qty'],
			);
			if($data['autoBomPriceUpdate']){
				$autoBomPriceUpdate = 1;
			}
			$componentProductIds[$data['componentProductId']] = $data['componentProductId'];
		}
		$bomProIds = array_keys($proDatas);
		$componentProductIds = array_keys($componentProductIds);
		sort($bomProIds);
		sort($componentProductIds);
		$bomPriceDetails 		= $this->{$this->globalConfig['fetchProduct']}->getProductPriceList($bomProIds);
		$bomPriceDetailsSku 	= $this->{$this->globalConfig['fetchProduct']}->getSkuByPricelistID($bomProIds);
		$componenetPriceDetails = $this->{$this->globalConfig['fetchProduct']}->getProductPriceList($componentProductIds);
		$this->logs['Price Update']['Bom Current price'] 		= $bomPriceDetails;
		$this->logs['Price Update']['Components Current price']  = $componenetPriceDetails;
		$updatePriceDetails = array();
		$this->config = $this->db->get('account_brightpearl_config')->row_array();
		$priceListId  = $this->config['costPriceListbom'];
		$this->logs['Price Update']['Price list']  = $priceListId;
		if($priceListId == 'fifo'){
			$priceListId = $this->config['costPriceListbomNonTrack'];
		}
		foreach($proDatas as $bomProId => $proData){
			$updatePriceList = array();$totalCostBomPrice = 0;$totalCompCostPrice = 0;$totalRetailBomPrice = 0;
			foreach($proData as $compProId => $row){
				$bomQty = $row['bomQty'];
				$compQty = $row['qty'];
				if(!$totalCostBomPrice)
					$totalCostBomPrice = $bomQty * $bomPriceDetails[$bomProId][$priceListId];
				$totalCompCostPrice += ($compQty * $componenetPriceDetails[$compProId][$priceListId]);			
			}
			$totalCostBomPrice = sprintf("%.4f",(round($totalCostBomPrice,3)));
			$totalCompCostPrice = sprintf("%.4f",(round($totalCompCostPrice,3)));
			$totalRetailBomPrice = sprintf("%.4f",(round($totalRetailBomPrice,3)));
			if(($totalCostBomPrice != $totalCompCostPrice)){
				$updatePriceDetails[$bomProId][$priceListId] = $totalCompCostPrice / $bomQty;
			}
		}
		$this->logs['Price Update']['Request']  = $updatePriceDetails;
		if($updatePriceDetails){
			if($autoBomPriceUpdate){
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
					$this->logs['Price Update'][] = array(
						'Old Price' 			=> $bomPriceDetails[$productId],
						'New Price' 			=> $postPrice,
						'Response' 	=> $postRes,
					);
				}
			}
		}
		if($receipeid){
			if($productId){
				$this->db->where(array('products.productId' => $productId));
			}
			if(strtolower($datasRequest['costingmethod']) == 'fifo'){
				$datas = $this->db->join('product_bom', 'product_bom.productId = products.productId')->get_where('products', array('products.isBOM' => '1','product_bom.receipeId' => $receipeid))->result_array();
			}else{
				$datas = $this->db->join('product_bom', 'product_bom.productId = products.productId')->get_where('products', array('products.isBOM' => '1','products.autoBomPriceUpdate' => '1','product_bom.receipeId' => $receipeid))->result_array();
			}
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
					$nonTrackItemsPrice += ($compQty * $componenetPriceDetails[$compProId][$priceListId]); 
				}
			}
			$nonTrackItemsPrices = $nonTrackItemsPrice / $bomQty;
		}
		$this->logs['Price Update']['Non-Track Price'] = $nonTrackItemsPrices;
		return $nonTrackItemsPrices;
	}
	
	public function removeWipAssemblies($wipAssembliesIds){
		if(!$wipAssembliesIds){
			return false;
		} 
		$this->brightpearl->reInitialize();
		$logtime = date('c');
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
			$errorResults = array();$updateStatusDatas = array();$this->deleteLogs = array();
			$this->deleteLogPath = FCPATH.'logs'.DIRECTORY_SEPARATOR.'removeassembly'.DIRECTORY_SEPARATOR . $createdId.'.logs';
			if(!is_dir(dirname($this->deleteLogPath))) { @mkdir(dirname($this->deleteLogPath),0777,true);@chmod(dirname($this->deleteLogPath), 0777); }
			$this->deleteLogs['Reverse Assembly']['LogTime'] = $logtime;
			$this->deleteLogs['Reverse Assembly']['Assembly Id'] = $createdId;
			if(!$stockCorrectionDatasTemps[$createdId]){
				continue;
			}
			$saveAsemblyIdDatas = array();
			$saveAsemblyIdDatasTemps = $this->db->get_where('product_assembly',array('createdId' => $createdId))->result_array();
			foreach($saveAsemblyIdDatasTemps as $saveAsemblyIdDatasTemp){
				$saveAsemblyIdDatas[$saveAsemblyIdDatasTemp['productId']] = $saveAsemblyIdDatasTemp;
			}
			
			$assemblyDatas	= $stockCorrectionDatasTemps[$createdId];
			$productLocationIds = array();
			foreach($assemblyDatas as $assemblyData){
				$productLocationIds[$assemblyData['productId']] = $assemblyData;
			}
			$assemblyData	= reset($assemblyDatas);
			$transferId		= $assemblyData['createdInventoryId'];
			$goodsOutId		= $assemblyData['goodsOutId'];
			$warehouse    = $assemblyData['warehouse'];
			$autoAssemblyWipWarehouse	= $assemblyData['autoAssemblyWipWarehouse'];
			$return = false;
			$afterReleaseCheckData = $this->db->get_where('product_assembly',array('autoAssembly' => '1','createdInventoryId > ' => '0','isInventoryTransfer' => '1','isInventoryRelease' => '0','isAssembly' => '0', 'createdId' => $createdId))->result_array();
			$this->deleteLogs['Reverse Assembly']['Available Assembly'] = $afterReleaseCheckData;
			if($afterReleaseCheckData){
				$return = true;
			}
			if(!$return){
				if($goodsOutId){
					$externalTransferProductDatas = array(); 
					$updateStatusDatas = array(
						'removeAssemblyStatus' => 0,
						'removeStockTranferId' => '',
						'removeExternalTransferId' => '',
					);
					$fetchMovementUrl = '/warehouse-service/goods-movement-search?goodsNoteId='.$goodsOutId;
					$fetchMovementDatasTemps = reset($this->brightpearl->getCurl($fetchMovementUrl));
					$this->deleteLogs['Reverse Assembly']['Fetch Goods Movement']['URL'] 	  = $fetchMovementUrl;
					$this->deleteLogs['Reverse Assembly']['Fetch Goods Movement']['Response'] = $fetchMovementDatasTemps;
					if($fetchMovementDatasTemps['results']){
						foreach($fetchMovementDatasTemps['results'] as $fetchMovementData){
							if($fetchMovementData['4'] > 0){ //qty
								$defaultLocation = $this->getDefaultWarehouseLocation($fetchMovementData['6']);
								$externalTransferProductDataskey = $fetchMovementData['1'].$defaultLocation;
								if(isset($externalTransferProductDatas[$externalTransferProductDataskey])){
									$externalTransferProductDatas[$externalTransferProductDataskey]['quantity'] +=  abs($fetchMovementData['4']);
								}
								else{
									$externalTransferProductDatas[$externalTransferProductDataskey] = array(
										'quantity'   	 => abs($fetchMovementData['4']),
										'productId'  	 => $fetchMovementData['1'],
										'fromLocationId' => $defaultLocation,
									);
								}
							}
						}
					}
					if($externalTransferProductDatas){
						$stockTransferCreateUrl = 'warehouse-service/warehouse/'.$autoAssemblyWipWarehouse.'/stock-transfer';
						$stockTransferCreateUrlRequset = array(
							'targetWarehouseId' => $warehouse,
							'reference'		    => 'Remove Asmb: '.$createdId,
						);
						$createdInventoryIds = @reset($this->brightpearl->getCurl($stockTransferCreateUrl,'POST',json_encode($stockTransferCreateUrlRequset),'json'));
						$this->deleteLogs['Reverse Assembly']['Stock Transfer']['URL'] 		= $stockTransferCreateUrl;
						$this->deleteLogs['Reverse Assembly']['Stock Transfer']['Request'] 	= $stockTransferCreateUrlRequset;
						$this->deleteLogs['Reverse Assembly']['Stock Transfer']['Response'] = $createdInventoryIds;
						if(!isset($createdInventoryIds['errors'])){
							$stockTransferId = $createdInventoryIds;
							$updateStatusDatas = array(
								'removeAssemblyStatus' => 1,
								'removeStockTranferId' => $stockTransferId,
								'removeExternalTransferId' => '',
							);
							$externalTransferCreateUrl = '/warehouse-service/warehouse/'.$autoAssemblyWipWarehouse.'/external-transfer';
							$request = array(
								'targetWarehouseId'   => $warehouse,
								'stockTransferId' 	  => $stockTransferId,
								'transferredProducts' => array_values($externalTransferProductDatas),
							);
							$externalTransferCreateUrlRequsetRes = @reset($this->brightpearl->getCurl($externalTransferCreateUrl,'POST',json_encode($request),'json'));
							$this->deleteLogs['Reverse Assembly']['External Transfer']['URL'] 		= $externalTransferCreateUrl;
							$this->deleteLogs['Reverse Assembly']['External Transfer']['Request'] 	= $request;
							$this->deleteLogs['Reverse Assembly']['External Transfer']['Response'] = $externalTransferCreateUrlRequsetRes;
							if(!isset($externalTransferCreateUrlRequsetRes['errors'])){
								$updateStatusDatas = array(
									'removeAssemblyStatus' => 2,
									'removeStockTranferId' => $stockTransferId,
									'removeExternalTransferId' => reset($externalTransferCreateUrlRequsetRes),
								);
								$eventUrl = '/warehouse-service/goods-note/goods-out/'.reset($externalTransferCreateUrlRequsetRes).'/event';
								$eventRequst = array(
									'events' => array(
										array(
											'eventCode' 	=> 'SHW',
											'occured' 		=> date('c'),
											'eventOwnerId' 	=> $config['defaultOwnerId'],
										),
									),
								);
								$eventRes = @reset($this->brightpearl->getCurl($eventUrl,'POST',json_encode($eventRequst),'json'));
								$this->deleteLogs['Reverse Assembly']['Ship Event']['URL']		= $eventUrl;
								$this->deleteLogs['Reverse Assembly']['Ship Event']['Request'] 	= $eventRequst;
								$this->deleteLogs['Reverse Assembly']['Ship Event']['Response']	= $eventRes;
								if(!isset($eventRes['errors'])){
									$updateStatusDatas = array(
										'removeAssemblyStatus' => 3,
										'removeStockTranferId' => $stockTransferId,
										'removeExternalTransferId' => reset($externalTransferCreateUrlRequsetRes),
									);
									$url = '/warehouse-service/warehouse/'.$warehouse.'/quarantine/release';
									$releaseDefaultLocation = $this->getDefaultWarehouseLocation($warehouse);
									foreach($externalTransferProductDatas as $externalTransferProductData){
										$saveAsemblyIdData = $saveAsemblyIdDatas[$externalTransferProductData['productId']];
										$toLocationId = $releaseDefaultLocation;
										if($saveAsemblyIdData['locationId']){
											$toLocationId = $saveAsemblyIdData['locationId'];
										}
										$releaseDataRequest = array(
											'productId'     => $externalTransferProductData['productId'],
											'quantity'	    => abs($externalTransferProductData['quantity']),
											'toLocationId' 	=> $toLocationId,
										);
										$releaseResDatas = $this->brightpearl->getCurl($url,'POST',json_encode($releaseDataRequest),'json');
										$this->deleteLogs['Reverse Assembly']['Quarantine Release']['URL'][]		= $eventUrl;
										$this->deleteLogs['Reverse Assembly']['Quarantine Release']['Request'][] 	= $releaseDataRequest;
										$this->deleteLogs['Reverse Assembly']['Quarantine Release']['Response'][]	= $releaseResDatas;
										$releaseResData = reset($releaseResDatas);
										if(!isset($releaseResData['errors'])){
											$returnAssemblyIds[] = $createdId;
											$updateStatusDatas = array(
												'removeAssemblyStatus' => 4,
												'removeStockTranferId' => $stockTransferId,
												'removeExternalTransferId' => reset($externalTransferCreateUrlRequsetRes),
											);
										}else{
											$errorResults[$createdId][] = $releaseResDatas;
											$notDeleteAssemblyIds[] 	= $createdId;
										}
									}
								}else{
									$errorResults[$createdId][] = $eventRes;
									$notDeleteAssemblyIds[] 	= $createdId;
								}
							}else{
								$errorResults[$createdId][] = reset($externalTransferCreateUrlRequsetRes);
								$notDeleteAssemblyIds[] 	= $createdId;
							}
						}else{
							$errorResults[$createdId][] = $createdInventoryIds;
							$notDeleteAssemblyIds[] 	= $createdId;
						}
					}
				}
				else{
					$returnAssemblyIds[] = $createdId;
				}
			}else{
				$returnAssemblyIds[] = $createdId;
			}
			//updatedatabase 
			$this->db->where(array('createdId' => $createdId))->update('product_assembly', $updateStatusDatas);
			
			file_put_contents($this->deleteLogPath,json_encode($this->deleteLogs,JSON_PRETTY_PRINT),FILE_APPEND); 
			if($errorResults){
				$this->load->library('mailer');
				$asemID = reset(array_keys($errorResults));
				$subject = 'Assembly('.$asemID.') Deletion Error Message';
				$body = 'Hi, '.$config['name'].'<br><br>
				<p>We are getting this error message below when trying to delete this assembly : </p>
				<p>'.json_encode($errorResults).'</p>
				<br><br>
				';
				$body .= '			
				<br><br>
				Thanks & Regards<br>
				BSITC Team';
				$from = array('alert@businesssolutionsinthecloud.com' => 'Alert');
				$this->mailer->send($config['autoAssemblyEmail'],$subject,$body,$from);
			}
			// end assembly loop
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
		$user_session_data = $this->session->userdata('login_user_data');
		$warehouseList = $this->getWarehouseMaster();
        $groupAction     = $this->input->post('customActionType');
        $records         = array();
        $notDeleteAssemblyIds         = array();
        $deletedAutoAssemblyIds         = array();
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
			if (trim($this->input->post('orderId'))) {
                $where['orderId'] = trim($this->input->post('orderId'));
            }
			if (trim($this->input->post('assignToUserId'))) {
                $where['assignToUserId'] = trim($this->input->post('assignToUserId'));
            }
			if (trim($this->input->post('assemblyType'))) {
				if($this->input->post('assemblyType') == '2'){
					$where['isOrderAssembly'] = '1';
				}elseif($this->input->post('assemblyType') == '3'){
					$where['autoAssembly'] = '1';
				}elseif($this->input->post('assemblyType') == '1'){
					 $where['autoAssembly'] = '0';
					 $where['isOrderAssembly'] = '0';
					 $where['isImportAssembly'] = '0';
				}elseif($this->input->post('assemblyType') == '4'){
					$where['autoAssembly'] = '0';
					$where['isOrderAssembly'] = '0';
					$where['isImportAssembly'] = '1';
				}else{
					$where['isOrderAssembly'] = '';
					$where['autoAssembly'] = '';
					$where['isImportAssembly'] = '';
				}
            }
        }
        if (trim($this->input->post('updated_from'))) {
            $query->where('date(created) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim($this->input->post('updated_to'))) {
            $query->where('date(created) <= ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }
		if($user_session_data['accessLabel'] == '2'){
			$query->where('assignToUserId', $user_session_data['user_id']);
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
            $query->where('date(created) <= ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }
		$accessLabelData 	 = array('1' => 'Admin', '2' => 'Employee', '3' => 'Manager');
		$accessStatusColor   = array('1' => 'success', '2' => 'info', '3' => 'warning');
        $status              = array('0' => 'WIP', '1' => 'Completed', '2' => 'Updated', '3' => 'Error', '4' => 'Archive');
        $statusColor         = array('0' => 'warning', '1' => 'success', '2' => 'info', '3' => 'warning', '4' => 'danger');
        $displayProRowHeader = array('id', '', 'username','assignToUserId','orderId','createdId','warehouse', 'productId', 'sku', 'name', 'costingMethod','status', 'created');
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
		if($user_session_data['accessLabel'] == '2'){
			$query->where('assignToUserId', $user_session_data['user_id']);
		}

		$query->where('isAssemblyDeleted', '0');
		if(!$this->globalConfig['enableImportAssembly']){
			$query->where('isImportAssembly', '0');
		}
        $datas = $query->select('id,productId,createdId,receipId,sku,created,name,status, autoAssembly,username,warehouse,isOrderAssembly,costingMethod,isImportAssembly,orderId,assignToUserId,assignByUserId')->limit($limit, $start)->get('product_assembly')->result_array();
		$usersMappings = array();
		$usersMappingTemps = $query->select('firstname,lastname,user_id,is_active,accessLabel')->get_where('admin_user', array('is_active' => '1'))->result_array();
		foreach($usersMappingTemps as $usersMappingTemp){
			$usersMappings[$usersMappingTemp['user_id']] = $usersMappingTemp;
		}
        foreach ($datas as $data) {
			$enableEdit = '';$enablePrint = '';
			if($data['status'] == '0'){
				$enableEdit = '<li><a class="actioneditbtn" href="' . base_url('products/assembly/addNewAssembly/' . base64_encode($data['name']. ' -~- ' . $data['sku'] ) . '/'.$data['createdId'] ) . '" title="Edit Assembly">Edit Assembly</a></li>';
			}
			//if($data['status']){
				$enablePrint = '<li><a class="actioneditbtn" target="_blank" href="' . base_url('products/assembly/generateBarcode/' . $data['createdId']) . '" title="Generate Barcode">Generate Barcode</a></li>';
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
			}elseif($data['isImportAssembly']){
				$assemblyType = '<span class="label label-sm label-info bade" style="background-color:#df6743a8">Import Assembly</span>';
			}
			$assignToVal = "";
			if($data['assignToUserId']){
				$assignToVal =  ucwords($usersMappings[$data['assignToUserId']]['firstname']) . ' '. ucwords($usersMappings[$data['assignToUserId']]['lastname']) . '<span class="badge badge-'.$accessStatusColor[$usersMappings[$data['assignToUserId']]['accessLabel']].'">('.ucwords($accessLabelData[$usersMappings[$data['assignToUserId']]['accessLabel']]).')</span>';
			}else{
				$assignToVal = '<span class="badge badge-secondary"> Not Assigned </span>';
			}
			$costingMethod = "";
			if($data['costingMethod'] == 'fifo'){
				$costingMethod = "FIFO";
			}else{
				$costingMethod = "Cost Pricelist";
			}
            $records["data"][] = array(
                $workInProgress,
                $assemblyType,
                ucwords($data['username']),
                $assignToVal,
                $data['orderId'] != "" ? $data['orderId'] : "N/A",
                $data['createdId'],
                $warehouseList[$data['warehouse']]['warehouseName'],
                $data['productId'],
                $data['sku'],
                $data['name'],
                $costingMethod,
               '<span class="label label-sm label-' . $statusColor[$data['status']] . '">' . $status[$data['status']] . '</span>',
                date('M d,Y h:i:s a',strtotime($data['created'])),
				'<div class="btn-group">
					<a class="btn btn-circle btn-default dropdown-toggle" href="javascript:;" data-toggle="dropdown">
						<i class="fa fa-share"></i>
						<span class="hidden-xs"> Tools </span>
						<i class="fa fa-angle-down"></i>
					</a>
					<div class="dropdown-menu pull-right">
						<li>
							<a target="_blank" class="actioneditbtn" href="' . base_url('products/assembly/viewassembly/' . $data['createdId']) . '"> View Assembly </a>
						</li>
						'.$enableEdit.'
						'.$enablePrint.'
						<li><a class="actioneditbtn" target="_blank" href="' . base_url('products/assembly/viewlog/' . $data['createdId']) . '" title="View Log">View Log</a></li>
					</div>
				</div>',
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
