<?php
if(!$cronTime){$cronTime = date('Y-m-d\TH:i:s\/',strtotime('-30 days'));}
$this->reInitialize($accountId);
$return = array();
foreach ($this->accountDetails as $account1Id => $accountDetails){
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
	$url = '/order-service/order-search?orderTypeId=4&orderStatusId='.$this->config['fetchPurchaseCredit'].'&updatedOn='.$cronTime;
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
				$return[$account1Id][$orderId]['orders'] = array(
					'account1Id'    => $saveAccId1,
					'account2Id'    => $saveAccId2,
					'orderId'       => $orderId,
					'delAddressName'=> $OrderInfoList['parties']['supplier']['addressFullName'],
					'delPhone' 		=> $OrderInfoList['parties']['supplier']['telephone'],
					'customerEmail' => $OrderInfoList['parties']['supplier']['email'],
					'customerId'    => $OrderInfoList['parties']['supplier']['contactId'],
					'reference'    	=> $OrderInfoList['reference'], 
					'parentOrderId' => $OrderInfoList['parentOrderId'], 
					'warehouse'   	=> $OrderInfoList['warehouseId'], 
					'totalAmount'   => $OrderInfoList['totalValue']['total'],
					'totalTax'      => $OrderInfoList['totalValue']['taxAmount'],
					'shippingMethod'=> @$OrderInfoList['delivery']['shippingMethodId'],
					'created'       => date('Y-m-d H:i:s', strtotime($OrderInfoList['createdOn'])),
					'rowData'       => json_encode($OrderInfoList),
				);
				$address = $OrderInfoList['parties']['delivery'];
				$return[$account1Id][$orderId]['address'][] = array(
					'account1Id'    => $saveAccId1, 
					'account2Id'    => $saveAccId2,
					'orderId'       => $orderId,
					'fname' 		=> @$address['addressFullName'],
					'companyName'   => @$address['companyName'],
					'line1'   		=> @$address['addressLine1'],
					'line2' 		=> @$address['addressLine2'],
					'line3'      	=> @$address['addressLine3'],
					'line4'      	=> @$address['addressLine4'],
					'postalCode'    => @$address['postalCode'],
					'countryName'   => @$address['countryIsoCode'],
					'telephone'     => @$address['telephone'],
					'email'      	=> @$address['email'],
					'type'   	 	=> 'ST',
				);		
				$address = $OrderInfoList['parties']['billing'];
				$return[$account1Id][$orderId]['address'][] = array(
					'account1Id'    => $saveAccId1,
					'account2Id'    => $saveAccId2, 
					'orderId'       => $orderId,
					'fname' 		=> @$address['addressFullName'],
					'companyName'   => @$address['companyName'],
					'line1'   		=> @$address['addressLine1'],
					'line2' 		=> @$address['addressLine2'],
					'line3'      	=> @$address['addressLine3'],
					'line4'      	=> @$address['addressLine4'],
					'postalCode'    => @$address['postalCode'],
					'countryName'   => @$address['countryIsoCode'],
					'telephone'     => @$address['telephone'],
					'email'      	=> @$address['email'],
					'type'   	 	=> 'BY',
				);
				foreach ($OrderInfoList['orderRows'] as $rowId => $items) {
					$return[$account1Id][$orderId]['items'][] = array(
						'account1Id' => $saveAccId1,
						'account2Id' => $saveAccId2,
						'orderId'    => $orderId, 
						'rowId'    	 => $rowId, 
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
				$saveCronTime[] = strtotime($OrderInfoList['updatedOn']);
			}
		}
	}
}
?>