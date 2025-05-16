<?php
if(!$cronTime){$cronTime = date('Y-m-d\TH:i:s\/',strtotime('-30 days'));}
$this->reInitialize($accountId);
$return = array();
foreach ($this->accountDetails as $account1Id => $accountDetails) {
	$orderIds = array();
	$this->config = $this->accountConfig[$account1Id];			
	$account2Ids     = $this->account2Details[$account1Id]; 
	$warehouseSales = $this->config['warehouseSales'];
	if($warehouseSales){ 
		$warehouseSales  = explode(",",$warehouseSales);
	}
	else{
		$warehouseSales = explode(",",$this->config['warehouse']);
	}
	$url = '/order-service/order-search?orderTypeId=2&orderStatusId='.$this->config['fetchPurchaseStatus'].'&updatedOn='.$cronTime;
	$response = $this->getCurl($url, "GET", '', 'json', $account1Id)[$account1Id];  
	if (@$response['results']) {
		foreach ($response['results'] as $result) {
			$orderIds[$result['0']] = $result['0'];
		}
		if ($response['metaData']) {
			for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
				$url1      = $url . '&firstResult=' . $i;
				$response1 = $this->getCurl($url1, "GET", '', 'json', $account1Id)[$account1Id];
				if ($response1['results']) {
					foreach ($response1['results'] as $result) {
						$orderIds[$result['0']] = $result['0'];
					}
				}
			}
		}
	}
	$saveCronTime = array();
	if($orderIds){			
		sort($orderIds);
		$orderDatas = $this->getResultById($orderIds,'/order-service/order/',$account1Id,200,0,'?includeOptional=customFields,nullCustomFields');
		foreach ($account2Ids as $account2Id) {
			$saveAccId1     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
			$saveAccId2     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;
			foreach($orderDatas as $OrderInfoList){
				$orderId 	= $OrderInfoList['id'];												
				$delAddress = $OrderInfoList['parties']['delivery'];
				$billAddress = $OrderInfoList['parties']['billing'];
				$return[$account1Id][$orderId]['orders'] = array(
					'account1Id'    => $saveAccId1,
					'account2Id'    => $saveAccId2,
					'orderId'       => $orderId,
					'delAddressName'=> $delAddress['addressFullName'],
					'customerEmail' => ($delAddress['email'])?($delAddress['email']):($billAddress['email']),
					'delPhone' 		=> $delAddress['telephone'],
					'customerId' 	=> $OrderInfoList['parties']['supplier']['contactId'],
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
				$saveCronTime[] = strtotime($OrderInfoList['updatedOn']);
			}
		}
	}
}
?>