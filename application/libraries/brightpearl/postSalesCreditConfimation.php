<?php
$this->reInitialize();
foreach($this->accountDetails as $account1Id => $accountDetails){
	$query          = $this->ci->db;
	if ($orderId) {
		$query->where_in('orderId', $orderId);
	} 
	$datas     = $query->where_in('dispatchConfirmation', array('1'))->get_where('sales_credit_order',array('account1Id' => $account1Id))->result_array(); 
	$rowsDatass = array();$count = 0; 
	$orderIds = array_column($datas,'orderId');
	$bpOrderDatasTemps = $this->getResultById($orderIds,'/order-service/order/',$account1Id);
	$dispatchedDatasTemps = $this->ci->db->get_where('sales_credit_dispatch',array('status' => '0'))->result_array();
	$dispatchedDatas = array();$bpOrderDatas = array();
	foreach($dispatchedDatasTemps as $dispatchedDatasTemp){
		if(@$dispatchedDatas[$dispatchedDatasTemp['orderId']][$dispatchedDatasTemp['rowId']]){
			$dispatchedDatas[$dispatchedDatasTemp['orderId']][$dispatchedDatasTemp['rowId']]['qtyMessage'] += $dispatchedDatasTemp['qtyMessage'];
		}
		else{
			$dispatchedDatas[$dispatchedDatasTemp['orderId']][$dispatchedDatasTemp['rowId']] = $dispatchedDatasTemp;
		}
	}
	foreach($bpOrderDatasTemps as $bpOrderDatasTemp){
		if($bpOrderDatasTemp['stockStatusCode'] == 'SCA'){
			$this->ci->db->where(array('orderId' => $bpOrderDatasTemp['id']))->update('sales_credit_dispatch',array('status' => '1'));
			$this->ci->db->where(array('orderId' => $bpOrderDatasTemp['id']))->update('sales_credit_order',array('dispatchConfirmation' => '0','status' => '3'));
		}
		else{
			$bpOrderDatas[$bpOrderDatasTemp['id']] = $bpOrderDatasTemp;			
		}
	}
	foreach($datas as $data){
		$orderId = $data['orderId'];
		$dispatchedData = @$dispatchedDatas[$orderId];
		$orderSaveData = json_decode($data['rowData'],true);
		$createdRowData = json_decode($createdRowData,true);
		if(!$dispatchedData){continue;}
		$bpOrderData = $bpOrderDatas[$orderId];
		$warehouseId = $orderSaveData['warehouseId'];
		$warehouseLocation = $this->getCurl('/warehouse-service/warehouse/' . $warehouseId . '/location/default','get','','json',$account1Id)[$account1Id];
		$warehouseDamageLocation = $this->getCurl('/warehouse-service/warehouse/' . $warehouseId . '/location/quarantine','get','','json',$account1Id)[$account1Id];
		$goodsMoved = array();
		foreach($bpOrderData['orderRows'] as $rowId => $orderRows){
			if(@$dispatchedData[$rowId]){
				$dispatchRow = $dispatchedData[$rowId];
				$destinationLocationId = $warehouseLocation;
				if($dispatchRow['isDamaged']){
					$destinationLocationId = $warehouseDamageLocation;
				}				
				$goodsMoved[] = array(
					'productId' => $orderRows['productId'],
					'purchaseOrderRowId' => $rowId,
					'quantity' => $dispatchRow['qtyMessage'],
					'destinationLocationId' => $destinationLocationId,
					'productValue' => array(
						'currency' => $orderRows['itemCost']['currencyCode'],
						'value' => $orderRows['itemCost']['value'],
					),
				);
			}
		}
		if($goodsMoved){
			$requst = array(
				'transfer' 		=> false,
				'warehouseId' 	=> $warehouseId,
				'goodsMoved' 	=> $goodsMoved,
				'receivedOn' 	=> date('c'),
			);
			$response = $this->getCurl('/warehouse-service/order/' . $orderId . '/goods-note/goods-in','POST',json_encode($requst),'json',$account1Id)[$account1Id];
			$createdRowData['post Sales Credit '.date('YmdHis').' Request'] = $requst;			
			$createdRowData['post Sales Credit '.date('YmdHis').' Response'] = $response;			
			$this->ci->db->where(array('orderId' => $orderId))->update('sales_credit_order',array('createdRowData' => json_encode($createdRowData)));
			if(@!$response['errors']){
				$this->ci->db->where(array('orderId' => $orderId))->update('sales_credit_dispatch',array('status' => '1'));
				$this->ci->db->where(array('orderId' => $orderId))->update('sales_credit_order',array('dispatchConfirmation' => '0','status' => '2'));
			}
		} 
	}
	if($orderIds){
		$bpOrderDatasTemps = $this->getResultById($orderIds,'/order-service/order/',$account1Id);
		foreach($bpOrderDatasTemps as $bpOrderDatasTemp){
			if($bpOrderDatasTemp['stockStatusCode'] == 'SCA'){
				$this->ci->db->where(array('orderId' => $bpOrderDatasTemp['id']))->update('sales_credit_dispatch',array('status' => '1'));
				$this->ci->db->where(array('orderId' => $bpOrderDatasTemp['id']))->update('sales_credit_order',array('dispatchConfirmation' => '0','status' => '3'));
			}
			else{
				$bpOrderDatas[$bpOrderDatasTemp['id']] = $bpOrderDatasTemp;			
			}
		}
	}
}
?>