<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Brightpearl
{
    public $apiurl, $headers, $accountDetails, $accountConfig, $account2Details, $account1id, $account2Id, $getByIdKey, $authToken;
    public function __construct()
    {
        $this->ci      = &get_instance();
        $this->headers = array();
    }
    public function reInitialize($account1Id = '')
    {
        $account1Key = '';
        $account2Key = '';
        if (strtolower($this->ci->globalConfig['account1Liberary']) == 'brightpearl') {
            $this->accountDetails  = @$this->ci->account1Account;
            $this->account2Details = @$this->ci->account2Account;
            $this->accountConfig   = @$this->ci->account1Config;
            $account1Key           = 'account1Id';
            $account2Key           = 'id';
        } else {
            $this->accountDetails  = $this->ci->account2Account;
            $this->account2Details = $this->ci->account1Account;
            $this->accountConfig   = $this->ci->account2Config;
            $account1Key           = 'account1Id';
            $account2Key           = 'id';
        }
        if ($account1Id) {
            $tempAccount                       = $this->accountDetails[$account1Id];
            $this->accountDetails              = array();
            $this->accountDetails[$account1Id] = $tempAccount;
        }
        $tempAccount2 = array();
        foreach ($this->account2Details as $account2id => $account2Details) {
            foreach ($account2Details as $account2Detail) {
                if ($account2Detail[$account1Key]) {
                    $tempAccount2[$account2Detail[$account1Key]][$account2Detail[$account2Key]] = $account2Detail;
                } else {
                    foreach ($this->accountDetails as $account1Id => $accountDetails) {
                        foreach ($accountDetails as $accountDetail) {
                            $tempAccount2[$accountDetail['id']][$account2Detail[$account2Key]] = $account2Detail;
                        }
                    }
                }
            }
        }
        $this->account2Details = array();
        $this->account2Details = $tempAccount2;
        $tempAccount           = array();
        foreach ($this->accountDetails as $accountDetails) {
            foreach ($accountDetails as $accountDetail) {
                $tempAccount[$accountDetail['id']] = $accountDetail;
            }
        }
        $this->accountDetails = array();
        $accountConfigTmp     = array();
        $this->accountDetails = $tempAccount;
        foreach ($this->accountConfig as $accountConfig) {
            @$accountConfigTmp[$accountConfig['brightpearlAccountId']] = $accountConfig;
        }
        $this->accountConfig = array();
        $this->accountConfig = $accountConfigTmp;
    }
    public function generateToken($accountId = '')
    {
        $this->reInitialize();
        foreach ($this->accountDetails as $accountId => $accountDetail) {
            $postDatas = array(
                'apiAccountCredentials' => array(
                    'emailAddress' => $accountDetail['email'],
                    'password'     => $accountDetail['password'],
                ),
            );
            $ch = curl_init($accountDetail['authUrl']);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postDatas));
            $response = json_decode(curl_exec($ch), true);
            if ($response['response']) {
                $this->authToken[$accountId] = $response['response'];
            }
        }
    }
    public function getCurl($suburl, $method = 'GET', $field = '', $type = 'json', $account2Id = '')
    {
        $returnData = array();$accountDetails = array();
        if (@$account2Id) {
            foreach ($this->accountDetails as $t1) {
                if ($t1['id'] == $account2Id) {
                    $accountDetails = array($t1);
                }
            }
        } else {
            $accountDetails = $this->accountDetails;
        }
        foreach ($accountDetails as $accountDetail) {
			usleep(50);
            if (@!$this->authToken[$accountDetail['id']]) {
                $this->generateToken($accountDetail);
            }
            $this->appurl = $accountDetail['url'] . '/';
            $url          = $this->appurl . ltrim($suburl, "/");
            if (is_array($field)) {
                $postvars = http_build_query($field);
            } else {
                $postvars = $field;
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if ($postvars) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("brightpearl-auth: " . $this->authToken[$accountDetail['id']], 'Content-Type: application/json'));
            $results    = json_decode(curl_exec($ch), true);
            $account1Id = ($accountDetail['id']) ? ($accountDetail['id']) : ($accountDetail['account1Id']);
            $return     = $results;
            if (@$results['response']) {
                $return = $results['response'];
                if (strtolower($method) == 'get') {
                    if ($this->getByIdKey) {
                        $return = array();
                        foreach ($results['response'] as $result) {
                            $return[$result[$this->getByIdKey]] = $result;
                        }
                    }
                }
            }
            $returnData[$account1Id] = $return;
        }
        return $returnData;
    }
    public function fetchProducts($productIds = '')
    {
        $datas    = $this->ci->db->order_by('id', 'desc')->get_where('cron_management', array('type' => 'product'))->row_array();
        $cronTime = ($datas['saveTime']) ? ($datas['saveTime']) : ('');
        $saveTime = date('Y-m-d\TH:i:s',strtotime('-5 hours')); 
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
                    $response = $this->getCurl($url, "OPTIONS", '', 'json', $account1Id);
                    if (@$response[$account1Id]) {
                        foreach ($response[$account1Id]['getUris'] as $getUris) {
                            $url      = '/product-service/' . $getUris;
                            $response = $this->getCurl($url, 'GET', '', 'json', $account1Id);
                            foreach ($response[$account1Id] as $result) {
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
                                    $response1 = $this->getCurl($url1, "GET", '', 'json', $account1Id)[$account1Id];
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
                $this->ci->db->insert('cron_management', array('type' => 'product', 'runTime' => $cronTime, 'saveTime' => $saveTime));
            }
            if (is_string($productIds)) {
                $productIds = array($productIds);
            }
            if (!$productIds) {continue;}
            $productIds = array_unique($productIds);
            sort($productIds);
            $productIds = array_chunk($productIds, '200');
            $returnKey  = 0;
            foreach ($productIds as $productId) {
                $this->getProductPricelist($productId);
                $value    = implode(",", $productId);
                $url1     = '/product-service/product/' . $value . '?includeOptional=customFields';
                $resDatas = $this->getCurl($url1, "GET", '', 'json', $account1Id);
                foreach ($account2Ids as $account2Id) {
                    $saveAccId1     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
                    $saveAccId2     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;
                    $bdatas         = $this->ci->db->get_where('product_bundle', array('account1Id' => $account1Id))->result_array();
                    $bundleProducts = @array_column($bdatas, 'productId');
                    foreach ($resDatas[$account1Id] as $resData) {
                        if ($resData['id'] < 1007) {
                            continue;
                        }
                        $isLIve = '1';$isBOM = '0';
                        if ($resData['status'] != 'LIVE') {
                            $isLIve = '0';
                        }
                        if ((@$resData['composition']['bundle']) && (!in_array($resData['id'], $bundleProducts))) {
                            $saveBundleProId[$saveAccId1][] = $resData['id'];
                        }
                        if ($this->config['productCustField']) {
                            if (!@$resData['customFields'][$this->config['productCustField']]) {
                                $isLIve = '0';
                                if (@!$this->saveProductIds[$resData['id']]) {
                                    continue;
                                }
                            }

                        }
                        if (@$resData['customFields'][$this->config['bomCustomField']]) {
                            $isBOM = '1';                               
                        }
                        $color  = '';
                        $size   = '';
                        $length = '';
                        if (@$resData['variations']) {
                            foreach ($resData['variations'] as $variations) {
                                if (strtolower($variations['optionName']) == 'colour') {$color = @$variations['optionValue'];}
                                if (strtolower($variations['optionName']) == 'size') {$size = @$variations['optionValue'];}
                                if (strtolower($variations['optionName']) == 'sleeve length') {$length = @$variations['optionValue'];}
                            }
                        }

                        $newSku = $resData['salesChannels']['0']['productName'];
                        if ($color) {$newSku .= ' - ' . $color;}
                        if ($length) {$newSku .= ' - ' . $length;}
                        $return[$saveAccId1][$returnKey] = array(
                            'account1Id'     => $saveAccId1,
                            'account2Id'     => $saveAccId2,
                            'productId'      => $resData['id'],
                            'name'           => $resData['salesChannels']['0']['productName'],
                            'sku'            => @$resData['identity']['sku'],
                            'productGroupId' => @$resData['productGroupId'],
                            'newSku'         => @$newSku,
                            'ean'            => @$resData['identity']['ean'],
                            'isStockTracked'  => @$resData['stock']['stockTracked'],
                            'barcode'        => @($resData['identity']['barcode']) ? ($resData['identity']['barcode']) : '',
                            'upc'            => @$resData['identity']['upc'],
                            'isBundle'       => @($resData['composition']['bundle']) ? ($resData['composition']['bundle']) : '0',
                            'color'          => $color,
                            'isBOM'          => $isBOM,
                            'isLIve'         => $isLIve,
                            'isBundle'       => @($resData['composition']['bundle']) ? ($resData['composition']['bundle']) : '0',
                            'size'           => $size,
                            'length'         => $length,
                            'shortDesc'      => $resData['salesChannels']['0']['shortDescription']['text'],
                            'description'    => $resData['salesChannels']['0']['description']['text'],
                            'created'        => date('Y-m-d H:i:s', strtotime($resData['createdOn'])),
                            'updated'        => date('Y-m-d H:i:s', strtotime(@$resData['updatedOn'])),
                            'params'         => json_encode($resData),
                        );
                        $returnKey++;
                    }
                }
            }
        }
        if ($saveBundleProId) {
            foreach ($saveBundleProId as $account1Id => $saveBundlePro) {
                foreach ($saveBundlePro as $account2Id => $bpId) {
                    $this->ci->db->insert('product_bundle', array('productId' => $bpId, 'account1Id' => $saveAccId1));
                }
            }
        }
        return $return;
    }
	public function fetchInventoradvice(){
		 $proDatas = $this->ci->db->select('max(productId) as max, min(productId) as min')->get_where('products',array('isLive' => '1'))->row_array();
		$productIds = $proDatas['min'].'-'.$proDatas['max'];
        $url = '/warehouse-service/product-availability/'.$productIds.'?includeOptional=breakDownByLocation';
        $this->reInitialize();
        $return = array();
        $results           = $this->getCurl($url);
        foreach ($results as $account1Id => $result) {
            $account2Ids     = $this->account2Details[$account1Id];
            foreach ($account2Ids as $account2Id) {
                $saveAccId1 = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
                $saveAccId2 = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;
                foreach ($result as $productId => $row) {
                    $return[$productId] = $row;
                }
            }
        }
        return $return; 
	}
    public function fetchCustomers($customerIds = '')
    {
        $datas    = $this->ci->db->order_by('id', 'desc')->get_where('cron_management', array('type' => 'customers'))->row_array();
        $cronTime = ($datas['saveTime']) ? ($datas['saveTime']) : ('');
        $saveTime = date('Y-m-d\TH:i:s',strtotime('-5 hours'));
        $this->reInitialize();
        $return = array();
        foreach ($this->accountDetails as $account1Id => $accountDetails) {
            $this->config    = $this->accountConfig[$account1Id];
            $saveBundleProId = array();
            $account2Ids     = $this->account2Details[$account1Id];
            if (!$customerIds) {
                $customerIds = array();
                if (!$cronTime) {
                    $url      = '/contact-service/contact';
                    $response = $this->getCurl($url, "OPTIONS", '', 'json', $account1Id);
                    if (@$response[$account1Id]) {
                        foreach ($response[$account1Id]['getUris'] as $getUris) {
                            $url      = '/contact-service/' . $getUris;
                            $response = $this->getCurl($url, 'GET', '', 'json', $account1Id);
                            foreach ($response[$account1Id] as $result) {
                                $customerIds[] = $result['contactId'];
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
                                    $customerIds[] = $result['0'];
                                }

                            }
                            if ($response[$account1Id]['metaData']) {
                                for ($i = 500; $i <= $response[$account1Id]['metaData']['resultsAvailable']; $i = ($i + 500)) {
                                    $url1      = $url . '&firstResult=' . $i;
                                    $response1 = $this->getCurl($url1, "GET", '', 'json', $account1Id);
                                    if ($response1['results']) {
                                        foreach ($response1['results'] as $result) {
                                            $customerIds[] = $result['0'];
                                        }

                                    }

                                }
                            }
                        }
                    }
                }
                $customerIds = array_unique($customerIds);
                if (!$customerIds) {
                    continue;
                }
                //$this->ci->db->insert('cron_management', array('type' => 'product', 'runTime' => $cronTime, 'saveTime' => $saveTime));
            }
            if (is_string($customerIds)) {
                $customerIds = array($customerIds);
            }
            if (!$customerIds) {continue;}
            $customerIds = array_unique($customerIds);
            sort($customerIds);
            $customerIds = array_chunk($customerIds, '200');
            $returnKey   = 0;
            foreach ($customerIds as $customerId) {
                $value    = implode(",", $customerId);
                $url1     = '/contact-service/contact/' . $value . '?includeOptional=customFields,postalAddresses';
                $resDatas = $this->getCurl($url1, "GET", '', 'json', $account1Id);
                foreach ($account2Ids as $account2Id) {
                    $saveAccId1     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
                    $saveAccId2     = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;
                    $bdatas         = $this->ci->db->get_where('product_bundle', array('account1Id' => $account1Id))->result_array();
                    $bundleProducts = @array_column($bdatas, 'customerId');
                    foreach ($resDatas[$account1Id] as $fetchedCustomers) {
                        if (@$this->config['customerCustomField']) {
                            if (!@$response['customFields'][$this->config['customerCustomField']]) {
                                if (!$this->directCustomer) {
                                    continue;
                                }
                            }
                        }
                        $delAddress                                                       = @$fetchedCustomers['postalAddresses'][$fetchedCustomers['postAddressIds']['DEL']];
                        $return[$saveAccId1][$fetchedCustomers['contactId']]['customers'] = array(
                            'account1Id'   => $saveAccId1,
                            'account2Id'   => $saveAccId2,
                            'customerId'   => @$fetchedCustomers['contactId'],
                            'email'        => @$fetchedCustomers['communication']['emails']['PRI']['email'],
                            'fname'        => @$fetchedCustomers['firstName'],
                            'lname'        => @$fetchedCustomers['lastName'],
                            'phone'        => @$fetchedCustomers['telephones']['PRI'],
                            'addressFname' => @$fetchedCustomers['firstName'],
                            'addressLname' => @$fetchedCustomers['lastName'],
                            'address1'     => @$delAddress['addressLine1'],
                            'address2'     => @$delAddress['addressLine2'],
                            'city'         => @$delAddress['addressLine3'],
                            'state'        => @$delAddress['addressLine4'],
                            'zip'          => @$delAddress['postalCode'],
                            'company'      => (@$fetchedCustomers['organisation']['name']) ? ($fetchedCustomers['organisation']['name']) : (''),
                            'countryCode'  => $delAddress['countryIsoCode'],
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
                            'countryCcode' => @$delAddress['countryIsoCode'],
                            'params'       => json_encode($delAddress),
                        );
                    }
                }
            }
        }
        return $return;
    }
    public function getProductStock($productIds = array()){
        sort($productIds);
        $url = '/warehouse-service/product-availability/'.implode(",", $productIds).'?includeOptional=breakDownByLocation';
        $this->reInitialize();
        $results           = $this->getCurl($url);
        return $results;
    }
    public function getProductPriceList($proIds, $priceListId = '0')
    {
        $this->reInitialize();
        $this->getByIdKey = '';
        if (!$proIds) {return false;}
        sort($proIds);
        $proIds = array_unique($proIds);
        $proIds = array_chunk($proIds, 200);
        $return = array();
        foreach ($proIds as $proId) {
            if (!$priceListId) {
                $url = '/product-service/product-price/' . implode(",", $proId);
            } else {
                $url = '/product-service/product-price/' . implode(",", $proId) . '/price-list/' . $priceListId;
            }
            $results = $this->getCurl($url);
            foreach ($results as $accountId => $result) {
                foreach ($result as $row) {
                    foreach ($row['priceLists'] as $priceLists) {
                        $return[$row['productId']][$priceLists['priceListId']] = (@$priceLists['quantityPrice']['1']) ? ($priceLists['quantityPrice']['1']) : '0.00';
                    }
                }
            }
        }
        return $return;
    }
    public function postStockCorrection($url,$postData = array(),$accountId = ''){
        $return = array();
        if($postData){
            $this->getByIdKey = '';
            $this->reInitialize();
            $return           = $this->getCurl($url, "POST", json_encode($postData), 'json', $accountId);
        }
        return $return;
    }
    public function getAllShippingMethod($accountId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = 'id';
        $url              = '/warehouse-service/shipping-method';
        $return           = $this->getCurl($url);
        $this->getByIdKey = '';
        return $return;
    }
    public function getAllLocation($accountId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = 'id';
        $url              = '/warehouse-service/warehouse';
        $return           = $this->getCurl($url);
        $this->getByIdKey = '';
        return $return;
    }
    public function getAllChannel($accountId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = 'id';
        $url              = '/product-service/channel';
        $return           = $this->getCurl($url);
        $this->getByIdKey = '';
        return $return;
    }
    public function getAllOrderStatus($accountId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = 'statusId';
        $url              = '/order-service/order-status';
        $results          = $this->getCurl($url);
        $return           = array();
        foreach ($results as $accountIId => $result) {
            foreach ($result as $orderStatusId => $orderStatus) {
                $return[$accountIId][$orderStatusId]       = $orderStatus;
                $return[$accountIId][$orderStatusId]['id'] = $orderStatus['statusId'];
            }
        }
        $this->getByIdKey = '';
        return $return;
    }

    public function getAllCategoryMethod($accountId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = 'id';
        $url              = '/product-service/brightpearl-category';
        $return           = $this->getCurl($url);
        $this->getByIdKey = '';
        return $return;
    }
    public function getAllShippingyMethod($accountId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = 'id';
        $url              = '/warehouse-service/shipping-method';
        $return           = $this->getCurl($url);
        $this->getByIdKey = '';
        return $return;
    }
    public function getAllTax($accountId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = 'id';
        $url              = '/accounting-service/tax-code';
        $results          = $this->getCurl($url);
        $return           = array();
        foreach ($results as $accountIId => $result) {
            foreach ($result as $taxId => $tax) {
                $return[$accountIId][$taxId]       = $tax;
                $return[$accountIId][$taxId]['name'] = $tax['code'];
            }
        }
        $this->getByIdKey = '';
        return $return;
    }
    public function getSeason($accountId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = 'id';
        $url              = '/product-service/season';
        $return           = $this->getCurl($url);
        $this->getByIdKey = '';
        return $return;
    }
    public function getAllPriceList($accountId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = 'id';
        $url              = '/product-service/price-list';
        $results          = $this->getCurl($url);
        $return           = array();
        foreach ($results as $accountIId => $result) {
            foreach ($result as $priceListId => $pricelist) {
                $return[$accountIId][$priceListId]         = $pricelist;
                $return[$accountIId][$priceListId]['name'] = $pricelist['code'];
            }
        }
        $this->getByIdKey = '';
        return $return;
    }
	public function getProductPrice($productId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = 'id';
        $url              = '/product-service/price-list';
        $results          = $this->getCurl($url);
        $return           = array();
        foreach ($results as $accountIId => $result) {
            foreach ($result as $priceListId => $pricelist) {
                $return[$accountIId][$priceListId]         = $pricelist;
                $return[$accountIId][$priceListId]['name'] = $pricelist['code'];
            }
        }
        $this->getByIdKey = '';
        return $return;
    }
	

    public function getAccountInfo($accountId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = '';
        $url              = '/integration-service/account-configuration';
        $return           = $this->getCurl($url);
        return $return;
    }
    public function getDefaultWarehouseLocation($locationId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = '';
        $url              = '/warehouse-service/warehouse/'.$locationId.'/location/default';
        $return           = $this->getCurl($url);
        return $return;
    }
	public function getInfo($locationId = ''){
        $this->reInitialize();
        $this->getByIdKey = '';
        $url              = 'integration-service/account-configuration';
        $return           = $this->getCurl($url);
        return $return;
    }
	public function getAllWarehouseLocation($locationId = '')
    {
        $this->reInitialize();
        $this->getByIdKey = '';
        $url              = '/warehouse-service/location-search';
		$return = array();
		foreach($this->accountDetails as $accountId => $accountDetails){
			$response           = $this->getCurl($url,'get','','json',$accountId)[$accountId];
			foreach($response['results'] as  $result){
				$return[$accountId][$result['0']] = array(
					'id' => $result['0'],
					'name' => $result['3'].'.'.$result['4'].'.'.$result['5'].'.'.$result['6'],
				);
			}	
			if ($response['metaData']) {
				for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
					$url1      = $url . '?firstResult=' . $i;
					$response1 = $this->getCurl($url1, "GET", '', 'json', $accountId)[$accountId];
					if ($response1['results']) {
						foreach ($response1['results'] as $result) {
							$return[$accountId][$result['0']] = array(
								'id' => $result['0'],
								'name' => $result['3'].'.'.$result['4'].'.'.$result['5'].'.'.$result['6'],
							);
						}

					}

				}
			}							
		}
        return $return;
    }
	

}
