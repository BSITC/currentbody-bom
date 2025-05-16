<?php
if(@!$cronTime){$cronTime = date('Y-m-d\TH:i:s\/',strtotime('-1 days'));}
$this->reInitialize();
$return = array();
foreach ($this->accountDetails as $account1Id => $accountDetails) {
	$paymentMathodMappings = array();
	$paymentMathodMappingsTemps = $this->ci->db->get_where('mapping_paymentpurchase',array('account1Id' => $account1Id))->result_array(); 
	foreach($paymentMathodMappingsTemps as $paymentMathodMappingsTemp){
		$paymentMathodMappings[$paymentMathodMappingsTemp['account2PaymentId']] = $paymentMathodMappingsTemp;
	}
	$this->config = $this->accountConfig[$account1Id];	
	$orderDatas = $this->ci->db->get_where('purchase_credit_order',array('isPaymentCreated' => '0','sendPaymentTo' => 'brightpearl'))->result_array();
	$exchangeDatas = $this->getExchangeRate($account1Id)[$account1Id];
	foreach($orderDatas as $orderData){
		$orderRowData = json_decode($orderData['rowData'],true);
		$createdRowData = json_decode($orderData['createdRowData'],true);
		$paymentMethod = $this->config['defaultPaymentMethod'];
		if(isset($paymentMathodMappings[$orderData['paymentMethod']])){
			$paymentMethod = $paymentMathodMappings[$orderData['paymentMethod']]['account1PaymentId'];
		}
		$payurl                 = '/accounting-service/supplier-payment';
		$paymentDetails = json_decode($orderData['paymentDetails'],true);
		$amount = 0;$totalReceivedPaidAmount = array_sum(array_column($paymentDetails,'amount'));
		foreach($paymentDetails as $paymentDetail){
			if(($paymentDetail['sendPaymentTo'] == 'brightpearl')&&($paymentDetail['status'] == '0')){
				$amount += $paymentDetail['amount'];
			}
		}
		$customerPaymentRequest = array(
			"paymentMethodCode" => $paymentMethod,
			"paymentType"       => "RECEIPT",
			"orderId"           => $orderData['orderId'],
			"currencyIsoCode"   => strtoupper($orderRowData['currency']['accountingCurrencyCode']),
			"exchangeRate"      => '1',
			"amountPaid"        => $amount,
			"paymentDate"       => date('c'),
			"journalRef"        => "Purchase credit for order : " . $orderData['orderId'],
		);
		$customerPaymentRequestRes = $this->getCurl( $payurl, "POST", json_encode($customerPaymentRequest), 'json' , $account1Id )[$account1Id];
		$createdRowData['send payment to brightpearl request'] 		= $customerPaymentRequest;
		$createdRowData['send payment to brightpearl response'] 	= $customerPaymentRequestRes;
		$this->ci->db->where(array('orderId' => $orderData['orderId']))->update('purchase_credit_order',array('createdRowData' => json_encode($createdRowData)));
		if(!isset($customerPaymentRequestRes['errors'])){
			foreach($paymentDetails as $key => $paymentDetail){
				if($paymentDetail['sendPaymentTo'] == 'brightpearl'){
					$paymentDetails[$key]['status'] = '1';
				}
			}	
			$paymentDetails[$customerPaymentRequestRes] = array(
				'amount' 		=> '0.00',
				'sendPaymentTo' => 'brightpearl',
				'status' 		=> '1',
			);
			if($totalReceivedPaidAmount >= $orderRowData['totalValue']['total']){
				$updateArray = array(
					'isPaymentCreated' 	=> '1',
					'status' 			=> '3',
				);
			}
			$updateArray['paymentDetails'] = json_encode($paymentDetails);
			$this->ci->db->where(array('orderId' => $orderData['orderId']))->update('sales_credit_order',$updateArray); 
		}
	}	
}
?>