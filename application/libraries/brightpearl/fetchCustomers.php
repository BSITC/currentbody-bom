<?php
$this->reInitialize();
$return = array();
foreach ($this->accountDetails as $account1Id => $accountDetails) {
	$this->config    = $this->accountConfig[$account1Id];
	$saveBundleProId = array();
	$account2Ids     = $this->account2Details[$account1Id];
	if (!$customerIds) {
		$customerId = array();
		if (!$cronTime) {
			$url      = '/contact-service/contact/';
			$response = $this->getCurl($url, "OPTIONS", '', 'json', $account1Id)[$account1Id];
			if (@$response['getUris']){
				foreach ($response['getUris'] as $getUris) {
					$url      = '/contact-service/' . $getUris;
					$response = $this->getCurl($url, 'GET', '', 'json', $account1Id)[$account1Id];
					foreach ($response as $result) {
						$customerId[] = $result['contactId'];
					}
				}
			}
		} else {
			$urls = array('/contact-service/contact-search?updatedOn=' . $cronTime . '/', '/contact-service/contact-search?createdOn=' . $cronTime . '/');
			foreach ($urls as $url) {
				$response = $this->getCurl($url, "GET", '', 'json', $account1Id);
				if (@$response[$account1Id]) {
					if ($response[$account1Id]) {
						foreach ($response[$account1Id]['results'] as $result) {
							$customerId[] = $result['0'];
						}
					}
					if ($response[$account1Id]['metaData']) {
						for ($i = 500; $i <= $response[$account1Id]['metaData']['resultsAvailable']; $i = ($i + 500)) {
							$url1      = $url . '&firstResult=' . $i;
							$response1 = $this->getCurl($url1, "GET", '', 'json', $account1Id);
							if ($response1['results']) {
								foreach ($response1['results'] as $result) {
									$customerId[] = $result['0'];
								}

							}

						}
					}
				}
			}
		}
		$customerIds = array_unique($customerId);
		if (!$customerIds) {
			continue;
		}
	}  
	if (is_string($customerIds)) {
		$customerIds = array($customerIds);
	}
	if (!$customerIds) {continue;}
	$resDatas = $this->getResultById($customerIds,'/contact-service/contact/',$account1Id,200,0,'?includeOptional=customFields,postalAddresses');
	$bdatas = $this->ci->db->get_where('product_bundle', array('account1Id' => $account1Id))->result_array();
	$bundleProducts = @array_column($bdatas, 'productId');
	$returnKey = 0;
	$updatedTimes = array();
	foreach ($resDatas as $fetchedCustomers) {		
		foreach($account2Ids as $account2Id){
			$saveAccId1     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
			$saveAccId2     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;
			$bilAddress = @$fetchedCustomers['postalAddresses'][$fetchedCustomers['postAddressIds']['BIL']];
			$delAddress = @$fetchedCustomers['postalAddresses'][$fetchedCustomers['postAddressIds']['DEL']];
			$return[$saveAccId1][$fetchedCustomers['contactId']]['customers'] = array(
				'account1Id'   => $saveAccId1,
				'account2Id'   => $saveAccId2,
				'customerId'   => @$fetchedCustomers['contactId'],
				'email'        => @$fetchedCustomers['communication']['emails']['PRI']['email'],
				'fname'        => @$fetchedCustomers['firstName'],
				'lname'        => @$fetchedCustomers['lastName'],
				'phone'        => @$fetchedCustomers['communication']['telephones']['PRI'], 
				'addressFname' => @$fetchedCustomers['firstName'],
				'addressLname' => @$fetchedCustomers['lastName'],
				'address1'     => @$delAddress['addressLine1'],
				'address2'     => @$delAddress['addressLine2'],
				'city'         => @$delAddress['addressLine3'],
				'state'        => @$delAddress['addressLine4'],
				'zip'          => @$delAddress['postalCode'],
				'company'      => (@$fetchedCustomers['organisation']['name']) ? (@$fetchedCustomers['organisation']['name']) : (''),
				'countryCode'  => @$delAddress['countryIsoCode'],
				'isSupplier'   => @$fetchedCustomers['relationshipToAccount']['isSupplier'],
				'created'      => date('Y-m-d H:i:s', strtotime($fetchedCustomers['createdOn'])),
				'updated'      => date('Y-m-d H:i:s', strtotime($fetchedCustomers['updatedOn'])),
				'params'       => json_encode($fetchedCustomers),
			);
			$return[$saveAccId1][$fetchedCustomers['contactId']]['address'][] = array(
				'account1Id'   => $saveAccId1,
				'account2Id'   => $saveAccId2,
				'addressId'    => @$fetchedCustomers['postAddressIds']['DEL'],
				'customerId'   => @$fetchedCustomers['contactId'],
				'fname'        => @$fetchedCustomers['firstName'],
				'lname'        => @$fetchedCustomers['lastName'],
				'address1'     => @$delAddress['addressLine1'],
				'address2'     => @$delAddress['addressLine2'], 
				'city'         => @$delAddress['addressLine3'],
				'state'        => @$delAddress['addressLine4'],
				'zip'          => @$delAddress['postalCode'],
				'countryCode'  => @$delAddress['countryIsoCode'],
				'type' 		   => 'ST',
				'params'       => json_encode($delAddress),
			);
			$return[$saveAccId1][$fetchedCustomers['contactId']]['address'][] = array(
				'account1Id'   => $saveAccId1,
				'account2Id'   => $saveAccId2,
				'addressId'    => @$fetchedCustomers['postAddressIds']['BIL'],
				'customerId'   => @$fetchedCustomers['contactId'],
				'fname'        => @$fetchedCustomers['firstName'],
				'lname'        => @$fetchedCustomers['lastName'],
				'address1'     => @$bilAddress['addressLine1'],
				'address2'     => @$bilAddress['addressLine2'], 
				'city'         => @$bilAddress['addressLine3'],
				'state'        => @$bilAddress['addressLine4'],
				'zip'          => @$bilAddress['postalCode'],
				'countryCode'  => @$bilAddress['countryIsoCode'],
				'type' 		   => 'BY',
				'params'       => json_encode($delAddress), 
			);
			$updatedTimes[] = strtotime(@$fetchedCustomers['updatedOn']);
		}
	} 
}
?>