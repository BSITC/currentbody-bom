<?php
$this->reInitialize();
foreach($this->accountDetails as $account1Id => $accountDetails){
	$query          = $this->ci->db;
	if ($orderId) {
		$query->where_in('orderId', $orderId);
	} 
	$datas     = $query->where_in('dispatchConfirmation', array('1'))->order_by('goodsOoutId','asc')->get_where('sales_goodsout',array('account1Id' => $account1Id))->result_array(); 
	$rowsDatass = array();$count = 0; 
	foreach ($datas as $data) {
		if($data['orderId']){	
			if(@$rowsDatass[trim(strtolower($data['orderId']))][trim(strtolower($data['goodsOoutId']))][$data['productId']]){
				$rowsDatass[trim(strtolower($data['orderId']))][trim(strtolower($data['goodsOoutId']))][$data['productId']]['qty'] += $data['qty'];
			}
			else{
				$rowsDatass[trim(strtolower($data['orderId']))][trim(strtolower($data['goodsOoutId']))][$data['productId']] = $data;  
			}
		}
	}
	$rowsDatass = array_chunk($rowsDatass,1000,true);
	foreach($rowsDatass as $rowsDatas){ 
		$ignoreGoodsDatas = array();
		$this->config = $this->accountConfig[$account1Id];	
		$allOrderIds = array_keys($rowsDatas);$shippedGoodsIds = array();$allBpGoodsInfos = array();$allBpOrderInfo = array();$completedOrderIds = array();
		if($allOrderIds){
			// check dispatch has been completed already
			$orderDatas = $this->getResultById($allOrderIds,'/order-service/order/',$account1Id);
			foreach($orderDatas as $tempResponse){
				$orderId = $tempResponse['id'];
				if($tempResponse['shippingStatusCode'] == 'ASS'){
					$completedOrderIds[] = $orderId;
					unset($rowsDatas[$orderId]);
				}						
				else{
					$allBpOrderInfo[$orderId] = $tempResponse;
				}
			}
			if($completedOrderIds){
				$this->ci->db->where_in('orderId',$completedOrderIds)->update('sales_goodsout',array('status' => '2','dispatchConfirmation' => '2','message' => ''));
				$this->ci->db->where_in('orderId',$completedOrderIds)->update('sales_order',array('dispatchConfirmation' => '','status' => '3'));
				$this->ci->db->where_in('orderId',$completedOrderIds)->update('sales_dispatch',array('status' => '1'));
			}
		}
		// check goods dispatch has been completed already
		$allOrderIds = array_keys($rowsDatas);
		if($allOrderIds){
			sort($allOrderIds);
			$orderIdsForGoods = array_chunk($allOrderIds,500);
			foreach($orderIdsForGoods as $allOrderId){
				sort($allOrderId);
				$goodsUrl = '/warehouse-service/order/'.implode(",",$allOrderId).'/goods-note/goods-out';
				$tempResponses = $this->getCurl($goodsUrl,'get','','json', $account1Id)[$account1Id]; 
				foreach($tempResponses as $goodsId => $tempResponse){
					$orderId = $tempResponse['orderId'];
					if($tempResponse['status']['shipped']){
						$shippedGoodsIds[] = $goodsId;
						unset($rowsDatas[$orderId][$goodsId]);
					}
					else{
						$allBpGoodsInfos[$orderId][$goodsId] = $tempResponse;
					}
				}
			}	
			if($shippedGoodsIds){
				$this->ci->db->where_in('goodsOoutId',$shippedGoodsIds)->update('sales_goodsout',array('status' => '2','dispatchConfirmation' => '2','message' => '')); 
				$this->ci->db->where_in('goodsId',$shippedGoodsIds)->update('sales_dispatch',array('status' => '1'));
			}
		}		
		$saveOrderInfos = array();$dispatchedItemDatass = array();
		$allOrderIds = array_keys($rowsDatas);
		if(!$allOrderIds){continue;}
		$tempsDatas = $this->ci->db->where_in(array('orderId' => $allOrderIds))->select('orderId,createdRowData')->get_where('sales_order')->result_array();
		foreach($tempsDatas as $tempsData){
			$saveOrderInfos[$tempsData['orderId']] = $tempsData;
		}
		$tempsDatas = $this->ci->db->where_in(array('orderId' => $allOrderIds))->select('trackingRef,warehouseId,shippingMethod,goodsId,qtyMessage,productId,rowId,isCancelled')->get_where('sales_dispatch',array('status' => '0'))->result_array();
		foreach($tempsDatas as $tempsData){
			if(@$dispatchedItemDatass[$tempsData['goodsId']][$tempsData['productId']]){
				$dispatchedItemDatass[$tempsData['goodsId']][$tempsData['productId']]['qtyMessage'] += $tempsData['qtyMessage'];
			}
			else{
				$dispatchedItemDatass[$tempsData['goodsId']][$tempsData['productId']] = $tempsData;
			}
		}
		$processedOrderIds = array();
		if($rowsDatas)
		foreach($rowsDatas as $orderId => $rows){
			$allBpGoodsInfo = $allBpGoodsInfos[$orderId];
			$bpOrderInfo = $allBpOrderInfo[$orderId];
			foreach($rows as $goodsId => $row){
				$bpGoodsInfo = $allBpGoodsInfo[$goodsId];					
				if(@!$bpOrderInfo){continue;}					
				if(@!$bpGoodsInfo){continue;}					
				$saveOrderInfo = @$saveOrderInfos[$orderId];
				$createdRowData = @json_decode($saveOrderInfo['createdRowData'],true);
				if($bpOrderInfo['shippingStatusCode'] == 'ASS'){
					$this->ci->db->where(array('orderId' => $orderId))->update('sales_goodsout',array('status' => '2','dispatchConfirmation' => '2','message' => ''));
					$this->ci->db->where(array('orderId' => $orderId))->update('sales_order',array('dispatchConfirmation' => '','status' => '3'));
					$this->ci->db->where(array('orderId' => $orderId))->update('sales_dispatch',array('status' => '1'));
					continue;
				}				
				if($bpOrderInfo['orderStatus']['orderStatusId'] == '5'){
					$this->ci->db->where(array('orderId' => $orderId))->update('sales_goodsout',array('status' => '2','dispatchConfirmation' => '2','message' => ''));
					$this->ci->db->where(array('orderId' => $orderId))->update('sales_order',array('dispatchConfirmation' => '','status' => '5'));
					$this->ci->db->where(array('orderId' => $orderId))->update('sales_dispatch',array('status' => '1'));
					continue;
				}			
				if(@$ignoreGoodsDatas[$goodsId]){
					continue;
				}	
				// check GON is fully dispatched?
								
				
				$dispatchedItemDatas = @$dispatchedItemDatass[$goodsId];
				if(!$dispatchedItemDatas){
					$dispatchedItemDatas = $this->ci->db->get_where('sales_dispatch',array('orderId' => $orderId))->result_array();
				}
				if(!$dispatchedItemDatas){
					continue;
				}
				$trackingRefs = array_unique(array_column($dispatchedItemDatas,'trackingRef'));
				if(!$trackingRefs){
					$trackingRefs = array($row['trackingRef']);
				}
				$shippingMethodId = $bpOrderInfo['delivery']['shippingMethodId'];
				$shipRef = array_unique(array_column($dispatchedItemDatas,'shippingMethod'));
				if(@$shipRef['0']){
					$mappedShipping = $this->ci->db->get_where('mapping_shipping',array('account2ShippingId' => $shipRef['0']))->row_array();
					if(@$mappedShipping['account1ShippingId']){
						$shippingMethodId = $mappedShipping['account1ShippingId'];
					}
				}
				$newGoodsCreated = 0;					
				// If any cancelled delete GON and recreate GON for dispatched items
				$isFullyDispatched = 1;$isCancelled = 0;$isFullyCancelled = 1;$cancelledItems = array();
				foreach($row as $productId => $orItemDatas){
					if(@$dispatchedItemDatas[$productId]['qtyMessage'] < $orItemDatas['qty']){
						$isFullyDispatched = 0;
						break;
					}
				}
				if(!$isFullyDispatched){continue;}
				foreach($dispatchedItemDatas as $proId => $dispatchedItemData){
					if($dispatchedItemData['isCancelled']){
						$isCancelled = 1;
						$cancelledItems[$proId] = $dispatchedItemData;
					}
					else{
						$isFullyCancelled = 0;
					}
				}
				if($isCancelled){
					$deleteGoodsUrl = '/warehouse-service/order/'.$orderId.'/goods-note/goods-out/'.$goodsId;
					$res = $this->getCurl( $deleteGoodsUrl, "DELETE", '', 'json' , $account1Id )[$account1Id];
					$createdRowData['Process goods-out '.$goodsId.' delete goodsId'] = $request;
					$createdRowData['Process goods-out '.$goodsId.' delete goodsId'] = $res;
					$this->ci->db->where(array('orderId' => $orderId))->update('sales_order',array('createdRowData' => json_encode($createdRowData),'cancelRequest' => '1')); 
					if(!isset($res['errors'])){ 
						$this->ci->db->update('sales_goodsout',array('status' => '3','dispatchConfirmation' => '0'),array('goodsOoutId' => $goodsId));
						$updateOrderStatusUrl = '/order-service/order/'.$orderId.'/status';
						$orderUpdateRequest = array(
							'orderStatusId' => $this->config['orderPartiallyCancelStatus'],
						);
						if($isFullyCancelled){							
							$orderUpdateRequest = array(
								'orderStatusId' => $this->config['orderFullyCancelStatus'],
							);
							$this->ci->db->where(array('orderId' => $orderId))->update('sales_order',array('createdRowData' => json_encode($createdRowData),'status' => '5')); 
						}	
						else{
							$this->ci->db->where(array('orderId' => $orderId))->update('sales_order',array('createdRowData' => json_encode($createdRowData),'status' => '4'));  
						}
						$orderStatusRes = $this->getCurl($updateOrderStatusUrl, 'PUT', json_encode($orderUpdateRequest),'json',$account1Id)[$account1Id];
						$createdRowData['Process goods-out '.$goodsId.' update status'] = $orderUpdateRequest;
						$createdRowData['Process goods-out '.$goodsId.' update status response'] = $orderStatusRes;
						$createDatas = array();$createRowDetails = array();
						foreach($bpOrderInfo['orderRows'] as $rowId => $orderRows){
							$orQty = $orderRows['quantity']['magnitude'];
							if($orQty <= $cancelledItems[$orderRows['productId']]['qtyMessage']){
								$cancelledItems[$orderRows['productId']]['qtyMessage'] -= $orQty;
								continue;
							}
							else{
								$orQty = $orQty - $cancelledItems[$orderRows['productId']]['qtyMessage'];
								$cancelledItems[$orderRows['productId']]['qtyMessage'] = 0;
							}
							if($orQty > 0){
								$createDatas[] = array(
									'productId' 			=> $orderRows['productId'],
									'salesOrderRowId' 		=> $rowId,
									'quantity' 				=> $orQty,
								);
								$orItemDatas = @$row[$orderRows['productId']];
								if($orItemDatas){									
									$orItemDatas['status'] = 1;
									$orItemDatas['rowId'] = $rowId;
									$orItemDatas['isNewCreated'] = 1;
									$orItemDatas['qty'] = $orQty;
									unset($orItemDatas['id']);
									unset($orItemDatas['goodsOoutId']);
									$createRowDetails[] = $orItemDatas;
								}
							}
						}
						if($createDatas){ 
							$createGoodsOutRequest = array(
								'warehouses' => array(
									array(
										'releaseDate' 	=> date('c'),
										'warehouseId' 	=> $bpOrderInfo['warehouseId'],
										'transfer' 		=> false,
										'products' 		=> $createDatas,
									)
								),
								'priority' 		=> false,
							);
							$goodsUrl = '/warehouse-service/order/'.$orderId.'/goods-note/goods-out';
							$goodsOutIds = $this->getCurl($goodsUrl,'post',json_encode($createGoodsOutRequest),'josn',$account1Id)[$account1Id];
							if(@!$goodsOutIds['errors']){
								foreach($goodsOutIds as $goodsId){
									foreach($createRowDetails as $createRowDetail){
										$createRowDetail['goodsOoutId'] = $goodsId;
										$this->ci->db->insert('sales_goodsout',$createRowDetail);
									}
								}
							}
							else{
								if($createRowDetails){
									$this->ci->db->insert_batch('sales_goodsout',$createRowDetails);
								}
							}								
						}
						$this->ci->db->where(array('orderId' => $orderId))->update('sales_order',array('createdRowData' => json_encode($createdRowData))); 
					}
					else{
						continue; 
					}
				}
				// Update trackingRef 
				$goodsUrl = '/warehouse-service/goods-note/goods-out/'.$goodsId;
				$request = array();					
				if($shippingMethodId){
					$request['shipping']['shippingMethodId'] = $shippingMethodId;
				}				
				if($trackingRefs){
					$request['shipping']['reference'] = implode(",",$trackingRefs); 
				}													
				if($request){
					$request['priority'] = 'false';
					$res = $this->getCurl( $goodsUrl, "PUT", json_encode($request), 'json' , $account1Id )[$account1Id];
					$createdRowData['Process goods-out '.$goodsId.' update shopping goods out request'] = $request;
					$createdRowData['Process goods-out '.$goodsId.' update shopping goods out response'] = $res;
				}		
				// complete GON				
				$goodsEventUrl = '/warehouse-service/goods-note/goods-out/'.$goodsId.'/event'; 
				$request = array(
					'events' => array(
						array(  
							'eventCode' 	=> 'SHW',
							'occured' 		=> date('c',strtotime('10 min')),
							'eventOwnerId' 	=> $bpOrderInfo['createdById'],
						),
					),
				);						
				$ignoreGoodsDatas[$goodsId] = $goodsId;
				$res = $this->getCurl( $goodsEventUrl, "POST", json_encode($request), 'json' , $account1Id )[$account1Id];
				$createdRowData['Process goods-out '.$goodsId.' complete goods out request'] = $request;
				$createdRowData['Process goods-out '.$goodsId.' complete goods out response'] = $res;
				if(!isset($res['errors'])){
					$processedOrderIds[$orderId] = $orderId;
					$this->ci->db->where(array('goodsOoutId' => $goodsId))->update('sales_goodsout',array('dispatchConfirmation' => '2','status' => '2','message' => ''));
					$this->ci->db->where(array('orderId' => $orderId))->update('sales_order',array('status' => '2','dispatchConfirmation' => '0'));
				} 
				else{
					foreach($res['errors'] as $errors){
						if(substr_count(strtolower($errors['message']),'cannot be edited')){
							$this->ci->db->where(array('goodsOoutId' => $goodsId))->update('sales_goodsout',array('dispatchConfirmation' => '2','status' => '2','message' => ''));
							$this->ci->db->where(array('orderId' => $orderId))->update('sales_order',array('dispatchConfirmation' => '','status' => '3'));
							$this->ci->db->where(array('goodsId' => $goodsId))->update('sales_dispatch',array('status' => '1'));
							$processedOrderIds[$orderId] = $orderId;
							break;
						}
					}
				}
				$this->ci->db->where(array('orderId' => $orderId))->update('sales_order',array('createdRowData' => json_encode($createdRowData)));
			}			
		}		
		if($processedOrderIds){
			$tempResponses = $this->getResultById($processedOrderIds,'/order-service/order/',$account1Id);
			$completedOrderIds = array();
			foreach($tempResponses as $tempResponse){
				$orderId = $tempResponse['id'];
				if($tempResponse['shippingStatusCode'] == 'ASS'){
					$completedOrderIds[] = $orderId;
				}				
			}				
			if($completedOrderIds){
				$this->ci->db->where_in('orderId',$completedOrderIds)->update('sales_goodsout',array('status' => '2','dispatchConfirmation' => '2'));
				$this->ci->db->where_in('orderId',$completedOrderIds)->update('sales_order',array('dispatchConfirmation' => '','status' => '3'));
				$this->ci->db->where_in('orderId',$completedOrderIds)->update('sales_dispatch',array('status' => '1'));
			} 
		}	
	}
}
?>