<?php
if(!$cronTime){$cronTime = date('Y-m-d\TH:i:s\/',strtotime('-10 days'));}
$this->reInitialize($accountId);
$return = array();
foreach ($this->accountDetails as $account1Id => $accountDetails) {
	$goodsIds = array(); $orderGoodsInfos = array();
	$this->config = $this->accountConfig[$account1Id];
	$saveChannelId = explode(",",$this->config['channelId']);
	if($this->config['fetchSalesOrderStatus']){
		$saveOrderStatusId = explode(",",$this->config['fetchSalesOrderStatus']);
	}			
	$account2Ids     = $this->account2Details[$account1Id]; 
	$warehouseSales = $this->config['warehouseSales'];
	if($warehouseSales){ 
		$warehouseSales  = explode(",",$warehouseSales);
	}
	else{
		$warehouseSales = explode(",",$this->config['warehouse']);
	}
	$url = '/warehouse-service/goods-note/goods-out-search?shipped=false&createdOn='.$cronTime;
	$response = $this->getCurl($url, "GET", '', 'json', $account1Id)[$account1Id];  	
	if (@$response['results']) {
		foreach ($response['results'] as $result) {
			if($result['4']){continue;}
			if($warehouseSales){
				if(!in_array($result['8'],$warehouseSales)){ 
					continue;
				}
			}
			$goodsOrderId = $result['6'];$channelId = $result['14'];
			if(@$goodsOrderId){
				if(in_array($channelId, $saveChannelId)){
					$orderGoodsInfos[$goodsOrderId][$result['0']] = $result['0'];
					$goodsIds[$result['0']] = $result['0'];
				}
			}
		}
		if ($response['metaData']) {
			for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
				$url1      = $url . '&firstResult=' . $i;
				$response1 = $this->getCurl($url1, "GET", '', 'json', $account1Id)[$account1Id];
				if ($response1['results']) {
					foreach ($response1['results'] as $result) {
						if($result['4']){continue;}
						if($warehouseSales){
							if(!in_array($result['8'],$warehouseSales)){   
								continue;
							}
						}
						$goodsOrderId = $result['6'];$channelId = $result['14'];
						if(@$goodsOrderId){
							if(in_array($channelId, $saveChannelId)){
								$orderGoodsInfos[$goodsOrderId][$result['0']] = $result['0'];
								$goodsIds[$result['0']] = $result['0'];
							}
						}
					}
				}
			}
		}
	}
	$foundOrderIds = array();
	if($orderIds){
		if(!is_array($orderIds)){
			$orderIds = array($orderIds);
			$foundOrderIds = array();
			foreach($orderIds as $orderId){
				if(@!$orderGoodsInfos[$orderId]){
					$foundOrderIds[] = $orderId;
				}
			}
		}
	}
	else{
		$orderSearchUrl = '/order-service/order-search?updatedOn='.$cronTime;
		$response = $this->getCurl($orderSearchUrl, "get", '', 'json', $account1Id)[$account1Id];
		if (@$response['results']) {
			foreach ($response['results'] as $result) {
				if($warehouseSales){
					if(!in_array($result['14'],$warehouseSales)){ 
						continue;
					}
				}												
				if($result['4'] > 2){
					continue;
				}
				$orderId = $result['0'];
				if(@!$orderGoodsInfos[$orderId]){
					$foundOrderIds[] = $orderId;
				}
			}
			if ($response['metaData']) {
				for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
					$url1      = $url . '&firstResult=' . $i;
					$response1 = $this->getCurl($url1, "GET", '', 'json', $account1Id)[$account1Id];
					if ($response1['results']) {
						foreach ($response1['results'] as $result) {
							if($warehouseSales){
								if(!in_array($result['14'],$warehouseSales)){ 
									continue;
								}
							}
							if($result['4'] > 2){ 
								continue;
							}
							$orderId = $result['0'];
							if(@!$orderGoodsInfos[$orderId]){
								$foundOrderIds[] = $orderId;
							}
						}
					}
				}
			}
		}
	} 
	if($foundOrderIds){			
		sort($foundOrderIds);
		$foundOrderIds = array_chunk($foundOrderIds,200);
		foreach($foundOrderIds as $foundOrderId){
			$url = '/warehouse-service/order/'.implode(",",$foundOrderId).'/goods-note/goods-in';
			$response = $this->getCurl($url, "GET", '', 'json', $account1Id)[$account1Id];
			foreach($response as $result){
				if($result['goodsNoteStatus'] == 'OPEN'){
					$isGoodsInCorrectWarehouse = 1;				 		
					if($warehouseSales){
						foreach($result['goodsMoved'] as $goodsMoved){								
							if(!in_array($goodsMoved['warehouseId'],$warehouseSales)){ 
								$isGoodsInCorrectWarehouse = 0;
								break;
							}
						}
					}
					if($isGoodsInCorrectWarehouse){
						$orderGoodsInfos[$result['orderId']][$result['goodsNoteId']] = $result['goodsNoteId'];
						$goodsIds[$result['goodsNoteId']] = $result['goodsNoteId'];
					}
				}	
			} 					
		}
	}
	//$ordserIdsTest = array_keys($orderGoodsInfos);
	$saveCronTime = array();
	if($orderGoodsInfos){				
		$orderIds = array_keys($orderGoodsInfos);
		$orderDatas = $this->getResultById($orderIds,'/order-service/order/',$account1Id,200,0,'?includeOptional=customFields,nullCustomFields');
		$goodsDatas = $this->getResultById($goodsIds,'/warehouse-service/order/*/goods-note/goods-out/',$account1Id,500,1);
		foreach ($account2Ids as $account2Id) {
			$saveAccId1     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
			$saveAccId2     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;
			foreach($orderDatas as $OrderInfoList){
				$orderId 	= $OrderInfoList['id'];								
				if($OrderInfoList['shippingStatusCode'] == 'ASS'){
					continue;
				}								
				$statusId 	= $OrderInfoList['orderStatus']['orderStatusId'];
				if(in_array($statusId, $saveOrderStatusId)){ continue; }
				$delAddress = $OrderInfoList['parties']['delivery'];
				$billAddress = $OrderInfoList['parties']['billing'];
				$return[$account1Id][$orderId]['orders'] = array(
					'account1Id'    => $saveAccId1,
					'account2Id'    => $saveAccId2,
					'orderId'       => $orderId,
					'delAddressName'=> $delAddress['addressFullName'],
					'delPhone' 		=> $delAddress['telephone'],
					'customerId' 	=> $OrderInfoList['parties']['customer']['contactId'],
					'orderNo'    	=> @($OrderInfoList['reference']), 
					'totalAmount'   => $OrderInfoList['totalValue']['total'],
					'totalTax'      => $OrderInfoList['totalValue']['taxAmount'],
					'shippingMethod'=> @$OrderInfoList['delivery']['shippingMethodId'],
					'deliveryDate'	=> @$OrderInfoList['delivery']['deliveryDate'],
					'currency'		=> @$OrderInfoList['currency']['orderCurrencyCode'],
					'created'       => date('Y-m-d H:i:s', strtotime($OrderInfoList['createdOn'])),
					'rowData'       => json_encode($OrderInfoList),
				);
				$return[$account1Id][$orderId]['address'][] = array(
					'account1Id'    => $saveAccId1, 
					'account2Id'    => $saveAccId2,
					'orderId'       => $orderId,
					'fname' 		=> $delAddress['addressFullName'],
					'companyName'   => @$delAddress['companyName'],
					'line1'   		=> @$delAddress['addressLine1'],
					'line2' 		=> @$delAddress['addressLine2'],
					'line3'      	=> @$delAddress['addressLine3'],
					'line4'      	=> @$delAddress['addressLine4'],
					'postalCode'    => @$delAddress['postalCode'],
					'countryName'   => @$delAddress['countryIsoCode'],
					'telephone'     => @$delAddress['telephone'],
					'email'      	=> @$delAddress['email'],
					'type'   	 	=> 'ST',
				);
				$return[$account1Id][$orderId]['address'][] = array(
					'account1Id'    => $saveAccId1, 
					'account2Id'    => $saveAccId2,
					'orderId'       => $orderId,
					'fname' 		=> $billAddress['addressFullName'],
					'companyName'   => @$billAddress['companyName'],
					'line1'   		=> @$billAddress['addressLine1'],
					'line2' 		=> @$billAddress['addressLine2'],
					'line3'      	=> @$billAddress['addressLine3'],
					'line4'      	=> @$billAddress['addressLine4'],
					'postalCode'    => @$billAddress['postalCode'],
					'countryName'   => @$billAddress['countryIsoCode'],
					'telephone'     => @$billAddress['telephone'],
					'email'      	=> @$billAddress['email'],
					'type'   	 	=> 'BY',
				);
				foreach($OrderInfoList['orderRows'] as $rowId => $orderRows){
					$return[$account1Id][$orderId]['items'][$rowId] = array(
						'account1Id'  	=> $saveAccId1, 
						'account2Id'  	=> $saveAccId2,
						'orderId'     	=> $orderId,
						'rowId'     	=> $rowId,
						'productId' 	=> $orderRows['productId'],
						'sku' 			=> @$orderRows['productSku'], 
						'qty' 			=> $orderRows['quantity']['magnitude'],
						'price' 		=> @$orderRows['rowValue']['rowNet']['value'] / $orderRows['quantity']['magnitude'],
						'tax' 			=> $orderRows['rowValue']['rowTax']['value'] / $orderRows['quantity']['magnitude'],
						'rowData' 		=> $orderRows['productId'],
					);
				}
			}				
			foreach($goodsDatas as $goodsId => $goodsInfo){
				if($goodsInfo['transfer']){ continue; }
				$orderId = $goodsInfo['orderId'];	
				$saveCronTime[] = strtotime($goodsInfo['createdOn']);
				foreach($goodsInfo['orderRows'] as $rowId => $orderRows){
					foreach($orderRows as $orderRow){
						if(@$return[$account1Id][$orderId]['goodsInfo'][$goodsId][$rowId]){
							$return[$account1Id][$orderId]['goodsInfo'][$goodsId][$rowId]['qty'] += $orderRow['quantity'];
						}
						else{
							if(@$return[$account1Id][$orderId]){
								$return[$account1Id][$orderId]['goodsInfo'][$goodsId][$rowId] = array(
									'account1Id'  	=> $saveAccId1, 
									'account2Id'  	=> $saveAccId2,
									'orderId'     	=> $orderId,
									'rowId'     	=> $rowId,
									'warehouseId'   => $goodsInfo['warehouseId'],
									'sequence'     	=> $goodsInfo['sequence'],
									'goodsOoutId'   => $goodsId,
									'productId' 	=> $orderRow['productId'],
									'qty' 			=> $orderRow['quantity'],
									'rowData' 		=> json_encode($goodsInfo),
									'itemRowData' 	=> json_encode($orderRows),
								);
							}
						}
					}
				}
			}
		}			
		
	}
}
?>