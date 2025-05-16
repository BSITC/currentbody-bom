<?php
$this->reInitialize();
foreach($this->accountDetails as $account1Id => $accountDetails){
	$config = $this->accountConfig[$account1Id];
	$query          = $this->ci->db;
	if ($orderId) {
		$query->where_in('orderId', $orderId);
	} 
	$datas     = $query->where_in('orderAcknowledged', array('1'))->get_where('sales_order',array('account1Id' => $account1Id,'status' => '1'))->result_array(); 
	$rowsDatass = array();$count = 0; 
	foreach ($datas as $data) {
		if($data['orderId']){			
			$rowsDatass[trim(strtolower($data['orderId']))] = $data;  
		}
	}
	foreach($rowsDatass as $rowsDatas){
		$url = '/order-service/order/'.$rowsDatas['orderId'].'/status';
		$orderStatusId = $config['orderAckSuccessfullStatus'];
		if($rowsDatas['orderFailedAcknowledged']){
			$orderStatusId = $config['orderAckErrorStatus'];
		}
		$request = array(
			'orderStatusId' => $orderStatusId,
		);
		$res = $this->getCurl($url,'PUT',json_encode($request),'json',$account1Id)[$account1Id]; 
		if(@!$res['errors']){
			$this->ci->db->where(array('orderId' => $rowsDatas['orderId']))->update('sales_order',array('status' => '2'));
			$url = '/order-service/order/'.$rowsDatas['orderId'].'/note';
			$request = array(
				'text' => 'Order acknowledged by Radial',
			);
			$res = $this->getCurl($url,'POST',json_encode($request),'json',$account1Id)[$account1Id];
			if($rowsDatas['ackMessage']){
				$request = array(
					'text' => $rowsDatas['ackMessage'],
				);
				$res = $this->getCurl($url,'POST',json_encode($request),'json',$account1Id)[$account1Id];
			}
		}
	}
}
?>