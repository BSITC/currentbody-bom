<?php
$this->reInitialize();
$return = array();
foreach ($this->accountDetails as $account1Id => $accountDetails) {
	$this->config    = $this->accountConfig[$account1Id];
	$saveBundleProId = array();
	$account2Ids     = $this->account2Details[$account1Id];
	if (!$productIds) {
		$productIds = array();
		if (!$cronTime) {
			$url      = '/product-service/product';
			$response = $this->getCurl($url, "OPTIONS", '', 'json', $account1Id)[$account1Id];
			if (@$response['getUris']){
				foreach ($response['getUris'] as $getUris) {
					$url      = '/product-service/' . $getUris;
					$response = $this->getCurl($url, 'GET', '', 'json', $account1Id)[$account1Id];
					foreach ($response as $result) {
						$productIds[] = $result['id'];
					}
				}
			}
		} else {
			$urls = array('/product-service/product-search?updatedOn=' . $cronTime . '/', '/product-service/product-search?createdOn=' . $cronTime . '/');
			foreach ($urls as $url) {
				$response = $this->getCurl($url, "GET", '', 'json', $account1Id);
				if (@$response[$account1Id]) {
					if ($response[$account1Id]) {
						foreach ($response[$account1Id]['results'] as $result) {
							$productIds[] = $result['0'];
						}
					}
					if ($response[$account1Id]['metaData']) {
						for ($i = 500; $i <= $response[$account1Id]['metaData']['resultsAvailable']; $i = ($i + 500)) {
							$url1      = $url . '&firstResult=' . $i;
							$response1 = $this->getCurl($url1, "GET", '', 'json', $account1Id);
							if ($response1['results']) {
								foreach ($response1['results'] as $result) {
									$productIds[] = $result['0'];
								}

							}

						}
					}
				}
			}
		}
		$productIds = array_unique($productIds);
		if (!$productIds) {
			continue;
		}
	}  
	if (is_string($productIds)) {
		$productIds = array($productIds);
	}
	if (!$productIds) {continue;}
	$resDatas = $this->getResultById($productIds,'/product-service/product/',$account1Id,200,0,'?includeOptional=customFields,nullCustomFields');
	$bdatas = $this->ci->db->get_where('product_bundle', array('account1Id' => $account1Id))->result_array();
	$bundleProducts = @array_column($bdatas, 'productId');
	$returnKey = 0;
	$updatedTimes = array();
	foreach ($resDatas as $resData) {
		$productId = $resData['id'];
		if($productId < 1007){ continue; }
		foreach($account2Ids as $account2Id){
			$saveAccId1     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
			$saveAccId2     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;	
			$isLIve = '1';
			if ($resData['status'] != 'LIVE') {
				$isLIve = '0';
			}
			if ((@$resData['composition']['bundle']) && (!in_array($resData['id'], $bundleProducts))) {
				$saveBundleProId[$saveAccId1][] = array('productId' => $resData['id'], 'account1Id' => $account1Id);
			}
			if ($this->config['productCustField']) {
				if (!@$resData['customFields'][$this->config['productCustField']]) {
					$isLIve = '0';
					if (@!$this->saveProductIds[$resData['id']]) {
						continue;
					}
				}
			}
			$color  = '';$size   = ''; $length = '';
			if (@$resData['variations']) {
				foreach ($resData['variations'] as $variations) {
					if ((strtolower($variations['optionName']) == 'color')||(strtolower($variations['optionName']) == 'colour')) {
						$color = @$variations['optionValue'];
					}
					if (strtolower($variations['optionName']) == 'size') {
						$size = @$variations['optionValue'];
					}
					if (strtolower($variations['optionName']) == 'sleeve length') {
						$length = @$variations['optionValue'];
					}
				}
			}
			$newSku = $resData['salesChannels']['0']['productName'];                     
			if ($color) {$newSku .= ' - ' . $color;}
			if ($length) {$newSku .= ' - ' . $length;}
			if ($this->config['productStyleNumber']) {
				if (@$resData['customFields'][$this->config['productStyleNumber']]) {
					$newSku = $resData['customFields'][$this->config['productStyleNumber']];
				}
			} 
			if($resData['updatedOn'])
			$updatedTimes[] = strtotime(@$resData['updatedOn']);
			$return[$saveAccId1][$returnKey] = array(
				'account1Id'     => $saveAccId1,
				'account2Id'     => $saveAccId2,
				'productId'      => $resData['id'],
				'name'           => $resData['salesChannels']['0']['productName'],
				'sku'            => @$resData['identity']['sku'],
				'newSku'         => @$account2IdsList.$newSku,  
				'ean'            => @$resData['identity']['ean'],
				'barcode'        => @($resData['identity']['barcode']) ? ($resData['identity']['barcode']) : '',
				'upc'            => @$resData['identity']['upc'],
				'isBundle'       => @($resData['composition']['bundle']) ? ($resData['composition']['bundle']) : '0',
				'color'          => $color,
				'isLIve'         => $isLIve,
				'size'           => $size,
				'description'    => $resData['salesChannels']['0']['description']['text'],
				'created'        => @date('Y-m-d H:i:s', strtotime($resData['createdOn'])),
				'updated'        => $resData['updatedOn'] ? @date('Y-m-d H:i:s', strtotime(@$resData['updatedOn'])) : '',
				'params'         => json_encode($resData),
			);
			$returnKey++; 
		}
	}            
	if ($saveBundleProId) {
		foreach ($saveBundleProId as $account1Id => $saveBundlePro) {
			$saveBundlePro = array_chunk($saveBundlePro,1000);
			foreach ($saveBundlePro as $saveBundle) {						
				$this->ci->db->insert_batch('product_bundle', $saveBundle);
			}
		}
	}			
}
?>