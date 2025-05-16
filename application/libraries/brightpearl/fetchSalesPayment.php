<?php
if(@!$cronTime){$cronTime = date('Y-m-d\TH:i:s\/',strtotime('-1 days'));}
$this->reInitialize();
$return = array();
$pendingPayments = array();
$pendingPaymentsTemps = $this->ci->db->select('orderId,createOrderId,isPaymentCreated,sendPaymentTo,paymentDetails')->get_where('sales_order',array('isPaymentCreated' => '0','orderId <>' => ''))->result_array();
foreach($pendingPaymentsTemps as $pendingPaymentsTemp){
	$pendingPayments[$pendingPaymentsTemp['orderId']] = $pendingPaymentsTemp;
}
$batchUpdates = array();
foreach ($this->accountDetails as $account1Id => $accountDetails) {
	$orderIds = array();
	$this->config = $this->accountConfig[$account1Id];	
	if($orgOrderIds){
		if(is_string($orgOrderIds)){
			$orgOrderIds = array($orgOrderIds);
		}
		$orgOrderIds = array_unique($orgOrderIds);
		$orgOrderIds = array_chunk($orgOrderIds,200);
		foreach($orgOrderIds as $orderId){
			$orderId = $this->range_string($orderId);
			$url = '/accounting-service/customer-payment-search?orderId='.$orderId;
			$paymentDatas = $this->getCurl($url,'GET','','json',$accountId)[$accountId];
			if(@$paymentDatas['results']){
				foreach($paymentDatas['results'] as $result){
					$orderId = $result['5'];
					$paymentId = $result['0'];
					$amount = $result['9'];
					if(!isset($pendingPayments[$orderId])){continue;}
					if(!isset($batchUpdates[$orderId]['paymentDetails'])){
						$paymentDetails = @json_decode($pendingPayments[$orderId]['paymentDetails'],true);
					}
					else{
						$paymentDetails = $batchUpdates[$orderId]['paymentDetails'];
					}
					if(@$paymentDetails[$paymentId]['amount'] > 0){continue;}
					@$paymentDetails[$paymentId] = array(
						'amount' 		=> $amount,
						'sendPaymentTo' => $this->ci->globalConfig['account2Liberary'],
						'status' 		=> '0',
					);
					$batchUpdates[$orderId] = array(
						'paymentDetails' 	=> $paymentDetails,
						'orderId' 			=> $orderId,
						'sendPaymentTo' 	=> $this->ci->globalConfig['account2Liberary'],
					);
				}
			}
		}
		if($orderIds){
			foreach($orderIds as $orderId => $paymentData){
				$this->ci->db->where(array('orderId' => $orderId,'isPaymentCreated' => '0','sendPaymentTo' => ''))->update('sales_order',$paymentData);
			}
		}
		return $return;
	}
	else{
		$url = '/accounting-service/customer-payment-search?createdOn='.$cronTime;
		$response = $this->getCurl($url, "GET", '', 'json', $account1Id)[$account1Id]; 
		if (@$response['results']) {
			foreach ($response['results'] as $result) {
				$orderId = $result['5'];
				$paymentId = $result['0'];
				$amount = $result['9'];
				if(!isset($pendingPayments[$orderId])){continue;}
				if(!isset($batchUpdates[$orderId]['paymentDetails'])){
					$paymentDetails = @json_decode($pendingPayments[$orderId]['paymentDetails'],true);
				}
				else{
					$paymentDetails = $batchUpdates[$orderId]['paymentDetails'];
				}
				if(@$paymentDetails[$paymentId]['amount'] > 0){continue;}
				@$paymentDetails[$paymentId] = array(
					'amount' 		=> $amount,
					'sendPaymentTo' => $this->ci->globalConfig['account2Liberary'],
					'status' 		=> '0',
				);
				$batchUpdates[$orderId] = array(
					'paymentDetails' 	=> $paymentDetails,
					'orderId' 			=> $orderId,
					'sendPaymentTo' 	=> $this->ci->globalConfig['account2Liberary'],
				);
			}
			if ($response['metaData']) {
				for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
					$url1      = $url . '&firstResult=' . $i;
					$response1 = $this->getCurl($url1, "GET", '', 'json', $account1Id)[$account1Id];
					if ($response1['results']) {
						foreach ($response1['results'] as $result) {
							$orderId = $result['5'];
							$paymentId = $result['0'];
							$amount = $result['9'];
							if(!isset($pendingPayments[$orderId])){continue;}
							if(!isset($batchUpdates[$orderId]['paymentDetails'])){
								$paymentDetails = @json_decode($pendingPayments[$orderId]['paymentDetails'],true);
							}
							else{
								$paymentDetails = $batchUpdates[$orderId]['paymentDetails'];
							}
							if(@$paymentDetails[$paymentId]['amount'] > 0){continue;}
							@$paymentDetails[$paymentId] = array(
								'amount' 		=> $amount,
								'sendPaymentTo' => $this->config['account2Liberary'],
								'status' 		=> '1',
							);
							$batchUpdates[$orderId] = array(
								'paymentDetails' 	=> $paymentDetails,
								'orderId' 			=> $orderId,
								'sendPaymentTo' 	=> $this->config['account2Liberary'],
							);
						}
					}
				}
			}
		}		
	}
	if($batchUpdates){
		foreach($batchUpdates as $key => $batchUpdate){
			$batchUpdates[$key]['paymentDetails'] = json_encode($batchUpdate['paymentDetails']);
		} 
		$batchUpdates = array_chunk($batchUpdates,200);
		foreach($batchUpdates as $batchUpdate){
			if($batchUpdate){
				$this->ci->db->update_batch('sales_order',$batchUpdate,'orderId');
			}
		}
	}
}
?>