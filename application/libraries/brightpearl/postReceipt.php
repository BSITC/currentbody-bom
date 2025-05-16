<?php
$this->reInitialize();
foreach($this->accountDetails as $account1Id => $accountDetails){
	$query          = $this->ci->db;
	if ($orderId) {
		$query->where_in('orderId', $orderId);
	} 
	$datas     = $query->get_where('purchase_dispatch',array('status' => '0'))->result_array(); 
	$rowsDatass = array();$count = 0; 
	foreach ($datas as $data) {
		if($data['orderId']){	
			if(@$rowsDatass[trim(strtolower($data['orderId']))][trim(strtolower($data['rowId']))]){
				$rowsDatass[trim(strtolower($data['orderId']))][trim(strtolower($data['rowId']))]['qty'] += $data['qty'];
			}
			else{
				$rowsDatass[trim(strtolower($data['orderId']))][trim(strtolower($data['rowId']))] = $data;  
			}
		}
	}
	$rowsDatass = array_chunk($rowsDatass,1000,true);$warehouseDefaultLocations = array();
	foreach($rowsDatass as $rowsDatas){
		$ignoreGoodsDatas = array();
		$this->config = $this->accountConfig[$account1Id];	
		$allOrderIds = array_keys($rowsDatas);$shippedGoodsIds = array();$allBpGoodsInfos = array();$allBpOrderInfo = array();$completedOrderIds = array();
		if($allOrderIds){
			// check dispatch has been completed already
			$orderDatas = $this->getResultById($allOrderIds,'/order-service/order/',$account1Id);
			foreach($orderDatas as $tempResponse){
				$orderId = $tempResponse['id'];
				if(($tempResponse['stockStatusCode'] == 'POA')||($tempResponse['stockStatusCode'] == 'DSA')){
					$completedOrderIds[] = $orderId;
					unset($rowsDatas[$orderId]);
				}						
				else{
					$allBpOrderInfo[$orderId] = $tempResponse;
				}
			}
			if($completedOrderIds){
				$this->ci->db->where_in('orderId',$completedOrderIds)->update('purchase_order',array('dispatchConfirmation' => '','status' => '3'));
				$this->ci->db->where_in('orderId',$completedOrderIds)->update('purchase_dispatch',array('status' => '1'));
			}
		}
				
		$saveOrderInfos = array();$dispatchedItemDatass = array();
		$allOrderIds = array_keys($rowsDatas);
		if(!$allOrderIds){continue;}
		$tempsDatas = $this->ci->db->where_in(array('orderId' => $allOrderIds))->select('orderId,createdRowData')->get_where('purchase_order')->result_array();
		foreach($tempsDatas as $tempsData){
			$saveOrderInfos[$tempsData['orderId']] = $tempsData;
		}
		$tempsDatas = $this->ci->db->where_in(array('orderId' => $allOrderIds))->select('orderId,sku,rowId,qtyMessage,productId')->get_where('purchase_dispatch',array('status' => '0'))->result_array();
		foreach($tempsDatas as $tempsData){
			if(@$dispatchedItemDatass[$tempsData['orderId']][$tempsData['rowId']]){
				$dispatchedItemDatass[$tempsData['orderId']][$tempsData['rowId']]['qtyMessage'] += $tempsData['qtyMessage'];
			}
			else{
				$dispatchedItemDatass[$tempsData['orderId']][$tempsData['rowId']] = $tempsData;
			}
		}
		$processedOrderIds = array();
		if($rowsDatas)
		foreach($rowsDatas as $orderId => $row){
			$bpOrderInfo = $allBpOrderInfo[$orderId];
			if(@!$bpOrderInfo){continue;}					
			$saveOrderInfo = @$saveOrderInfos[$orderId];
			$createdRowData = @json_decode($saveOrderInfo['createdRowData'],true);
			if($bpOrderInfo['stockStatusCode'] == 'POA'){
				$this->ci->db->where(array('orderId' => $orderId))->update('purchase_order',array('dispatchConfirmation' => '','status' => '3'));
				$this->ci->db->where(array('orderId' => $orderId))->update('purchase_dispatch',array('status' => '1'));
				continue;
			}			
			if(@$ignoreGoodsDatas[$orderId]){
				continue;
			}				
			// check GON is fully dispatched?	
			$dispatchedItemDatas = @$dispatchedItemDatass[$orderId];			
			if(!$dispatchedItemDatas){
				continue;
			}
			if(@$warehouseDefaultLocations[$bpOrderInfo['warehouseId']]){
				$destinationLocationId = $warehouseDefaultLocations[$bpOrderInfo['warehouseId']];
			}
			else{
				$destinationLocationId = $this->getCurl('/warehouse-service/warehouse/'.$bpOrderInfo['warehouseId'].'/location/default','get','','json', $account1Id)[$account1Id];	
				$warehouseDefaultLocations[$bpOrderInfo['warehouseId']] = $destinationLocationId;
			}
			$createDatas = array();
			foreach($bpOrderInfo['orderRows'] as $rowId => $orderRows){
				if(@$orderRows['composition']['bundleParent']) { continue; }
				$orQty = $orderRows['quantity']['magnitude'];
				$receiptQty = @$dispatchedItemDatas[$rowId]['qtyMessage'];
				if($receiptQty > 0){					
					$createDatas[] = array(
						'productId' 			=> $orderRows['productId'],
						'purchaseOrderRowId' 	=> $rowId,
						'quantity' 				=> $receiptQty,
						'destinationLocationId' => $destinationLocationId,
						'productValue' 			=> array(
							"currency" 			=> $bpOrderInfo['currency']['accountingCurrencyCode'],
							"value"  			=> sprintf("%.4f",($orderRows['itemCost']['value'])),
						),
					);
				}
			}
			if(!$createDatas){continue;}
			$createGoodsOutRequest = array(
				'transfer' 		=> 'false',
				'warehouseId' 	=> $bpOrderInfo['warehouseId'],
				'goodsMoved' 	=> $createDatas,
				'receivedOn' 	=> date('c'),
			);
			$res = $this->getCurl('/warehouse-service/order/'.$orderId.'/goods-note/goods-in','POST',json_encode($createGoodsOutRequest),'json', $account1Id)[$account1Id];
			$createdRowData['Create Goods-in Request:'] = $createGoodsOutRequest;
			$createdRowData['Create Goods-in Response:'] = $res;
			if(!isset($res['errors'])){
				$processedOrderIds[$orderId] = $orderId;
				$this->ci->db->where(array('orderId' => $orderId))->update('purchase_order',array('status' => '2','dispatchConfirmation' => '0'));
				$this->ci->db->where_in('orderId',$orderId)->update('purchase_dispatch',array('status' => '1'));
			} 			
			$this->ci->db->where(array('orderId' => $orderId))->update('purchase_order',array('createdRowData' => json_encode($createdRowData)));
		}		
		if($processedOrderIds){
			$tempResponses = $this->getResultById($processedOrderIds,'/order-service/order/',$account1Id);
			$completedOrderIds = array();
			foreach($tempResponses as $tempResponse){
				$orderId = $tempResponse['id'];
				if(($tempResponse['stockStatusCode'] == 'POA')||($tempResponse['stockStatusCode'] == 'DSA')){
					$completedOrderIds[] = $orderId;
				}				
			}				
			if($completedOrderIds){
				$this->ci->db->where_in('orderId',$completedOrderIds)->update('purchase_order',array('dispatchConfirmation' => '','status' => '3'));
				$this->ci->db->where_in('orderId',$completedOrderIds)->update('purchase_dispatch',array('status' => '1'));
			} 
		}	
	}
}
?>