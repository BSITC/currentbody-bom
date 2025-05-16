<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
#[\AllowDynamicProperties]
class Brightpearl
{
    public $apiurl, $headers, $accountDetails, $accountConfig, $account2Details, $account1id, $account2Id, $getByIdKey, $authToken,$response;
    public function __construct(){
		$this->ci			= &get_instance();
		$this->headers = array();
		$this->devToken = 'PxurwyOcrRloIbmq/n6hybcD41MUdyNOcMrEZlvJGIo=';
		$this->devSecrete = 'XDrmqBOo9KFLU09GWw+OVuwHv4ne8Zp9OPmi5oO9JwM=';		
		$this->appToken = 'e4ceac2e-aac7-4714-8b94-80f0b07b79be';		
		$authToken = base64_encode(hash_hmac("sha256", $this->appToken, $this->devSecrete,true));
        $this->headers = array(
			'Content-Type:application/json;charset=UTF-8',
			'brightpearl-dev-ref:bsitcbpdev',
			'brightpearl-app-ref:bsitc-flexasm',
			'brightpearl-account-token:'.$authToken,
		);
    }
    public function reInitialize($account1Id = ''){
		$this->accountDetails 	= $this->ci->account1Account;
		$this->account2Details = array();$this->account2Config = array();$this->accountConfig = array();		
		foreach($this->ci->account1Config as $account1Config){
			$this->accountConfig[$account1Config[$this->ci->globalConfig['account1Liberary'].'AccountId']] = $account1Config;
		}
		foreach($this->ci->account2Account as $account2Id => $account2Account){
			$this->account2Details[$account2Account['account1Id']][$account2Id] = $account2Account;
		}		
		foreach($this->ci->account2Config as $account2Id => $account2Account){
			$this->account2Config[$account2Account[$this->ci->globalConfig['account2Liberary'].'AccountId']] = $account2Account;
		}
    } 	
    public function generateToken($accountId = ''){
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
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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
        $returnData = array();
        if (@$account2Id) { 
            foreach ($this->accountDetails as $t1) {
                if ($t1['id'] == $account2Id) {
                    $accountDetails = array($t1);
                }
            }
        } else {
            $accountDetails = $this->accountDetails;
        }
		$orgSubUrl = $suburl;
        foreach ($accountDetails as $accountDetail) {
			$milliseconds = round(microtime(true) * 1000);
			$calMin = sprintf("%.5f",($milliseconds / 60000));
			$nextMilisec = ((int)$calMin + 1) - $calMin;
			$nextMilisec = (int)((int) ($nextMilisec * 100)) / 1.666;
			$remainingSec = $nextMilisec + 3;
			$limitName = (int)($calMin); 
			//$limitName = date('Y-m-d H-i');
			$insertData = array(
				'name' => $limitName,
				'limitcount' => 0,
			);
			$sql = $this->ci->db->insert_string('api_call', $insertData) . ' ON DUPLICATE KEY UPDATE limitcount=limitcount + 1';
			$this->ci->db->query($sql);
			$limitRate = $this->ci->db->get_where('api_call',array('name' => $limitName))->row_array();
			if($limitRate['limitcount'] >= 190){
				sleep($remainingSec);
			}
			else if($limitRate['limitUsed']){
				sleep($remainingSec);
			} 
			//usleep(300);
            if(strlen($accountDetail['reference']) > 6){
				$this->headers['2'] = 'brightpearl-app-ref:'.$accountDetail['reference'];
				$authToken = base64_encode(hash_hmac("sha256", $accountDetail['token'], $this->devSecrete,true));
				$this->headers['3'] = 'brightpearl-account-token:'.$authToken;
			}
			else{
				if (@!$this->authToken[$accountDetail['id']]) {
					$this->generateToken($accountDetail);
				}
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
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if ($postvars) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
            }
           if(strlen($accountDetail['reference']) > 6){
				curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
			}
			else{
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("brightpearl-auth: " . $this->authToken[$accountDetail['id']], 'Content-Type: application/json'));
			}
			$bpResponse = curl_exec($ch);
            $results    = json_decode($bpResponse, true);
			$curlInfo = curl_getinfo($ch);
			if(strtoupper($method) == 'POST'){
				$filepath = FCPATH.'logs/brightpearl/'.date('Y').'/'.date('m').'/'.date('d').'/'.basename($suburl);			
				if(!is_dir(($filepath))){
					mkdir(($filepath),0777,true);
					chmod(($filepath), 0777);
				}			
				$startFileName = date('Y-m-d H-i-s ').uniqid();
				$myfile = fopen($filepath.'/'.$startFileName.'.logs', "w");		
				
				fwrite($myfile, "Start time : ".date('c')."\n\rRequest url :".$url."\n\rMethod :".$method."\n\rRequest data :".$postvars."\n\rCurl Info :".json_encode($curlInfo)."\n\rReponse Data :".$bpResponse);	
				fclose($myfile); 
			}
			//$this->ci->db->insert('bp_log',array('url' => $url,'request' => json_encode($postvars),'response' => $bpResponse));
			$account1Id = ($accountDetail['id']) ? ($accountDetail['id']) : ($accountDetail['account1Id']);
			$this->response[$account1Id] = $results;
			if($curlInfo['http_code'] == '503'){
				sleep(10);
				return $this->getCurl($orgSubUrl,$method,$field,$type,$account2Id); 
			}
			if(@$results['response'] == 'You have sent too many requests. Please wait before sending another request'){
				$insertData = array(
					'name' => $limitName,
					'limitUsed' => 1,
				);
				$this->ci->db->where(array('name' => $limitName))->update('api_call',$insertData);
				$remainingSec = $remainingSec + 5;
				$retry_count++;
				sleep($remainingSec);
				return $this->getCurl($orgSubUrl,$method,$field,$type,$account2Id);
			}
			else{
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
        }		
        return $returnData;
    }
	function range_string($number_array){
		sort($number_array);
		$previous_number = intval(array_shift($number_array)); 
		$range = false;
		$range_string = "" . $previous_number; 
		foreach ($number_array as $number) {
		  $number = intval($number);
		  if ($number == $previous_number + 1) {
			$range = true;
		  }
		  else {
			if ($range) {
			  $range_string .= "-$previous_number";
			  $range = false;
			}
			$range_string .= ",$number";
		  }
		  $previous_number = $number;
		}
		if ($range) {
		  $range_string .= "-$previous_number";
		}
		return $range_string;
	}
	public function getResultById($ids,$subUrl,$account1Id,$chunkLimit = 200,$returnSameKey = 0,$searchUrl = ''){
		$return = array();
		if($ids){
			$ids = array_unique($ids);sort($ids);$ids = array_chunk($ids,$chunkLimit);
			foreach($ids as $id){
				$range = $this->range_string($id);
				$url = rtrim($subUrl,"/").'/'.$range;
				if($searchUrl){
					$url = $url.$searchUrl;
				}
				$response = $this->getCurl($url, "GET", '', 'json', $account1Id)[$account1Id];
				if(@!$response['errors']){
					if(!$returnSameKey){
						$return = @array_merge($return,$response);
					}
					else{
						foreach($response as $key => $res){
							$return[$key] = $res;
						}
					}
				}
			}
		}
		return $return;
	}
	public function getResultByIdNew($ids,$subUrl,$account1Id,$chunkLimit = 200,$returnSameKey = 0,$searchUrl = ''){
		$return = array();
		if($ids){
			$accountId = '1';
			$ids = array_unique($ids);sort($ids);$ids = array_chunk($ids,$chunkLimit);
			foreach($ids as $id){
				$range = $this->range_string($id);
				$url = rtrim($subUrl,"/").'/'.$range;
				if($searchUrl){
					$url = $url.$searchUrl;
				}
				$response = $this->getCurl($url, "GET", '', 'json', $account1Id)[$account1Id];
				$responses = $this->response[$accountId]['response'];
				if(@$responses){
					foreach($responses as $key => $res){
						$return[$key] = $res;
					}
				}
			}
		}
		return $return;
	}
	public function fetchProducts($productIds = '', $cronTime=''){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR . APPNAME . DIRECTORY_SEPARATOR .'fetchProducts.php')){
			if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR . APPNAME . DIRECTORY_SEPARATOR . CLIENTCODE . DIRECTORY_SEPARATOR .'fetchProducts.php')){
				require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'.DIRECTORY_SEPARATOR . APPNAME. DIRECTORY_SEPARATOR . CLIENTCODE. DIRECTORY_SEPARATOR .'fetchProducts.php');
			}
			else{				
				require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'.DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchProducts.php');
			}
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'fetchProducts.php');
		}
		return array( 'return' => $return,'saveTime' => @max($updatedTimes) );
    }
	public function fetchCustomers($customerIds = '', $cronTime=''){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchCustomers.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchCustomers.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'fetchCustomers.php');
		}		
		return array( 'return' => $return,'saveTime' => @max($updatedTimes) );
    }
	
	public function fetchSales($orderIds = '', $accountId = '',$cronTime = ''){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchGoodsSales.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchGoodsSales.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'fetchGoodsSales.php');
			//require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'fetchSales.php');
		}
		return array('return' => $return,'saveTime' => @max($saveCronTime)); 
    }	
	
	public function fetchSalesPayment(){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchSalesPayment.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchSalesPayment.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'fetchSalesPayment.php');
		}
	}
	
	public function postSalesPayment(){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postSalesPayment.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postSalesPayment.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'postSalesPayment.php');
		}
	}
	
	public function fetchPurchase($orderIds = '', $accountId = '',$cronTime = ''){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchPurchase.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchPurchase.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'fetchPurchase.php');
		}
		return array('return' => $return,'saveTime' => @max($saveCronTime)); 
    }	
	
	public function postPurchasePayment(){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postPurchasePayment.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postPurchasePayment.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'postPurchasePayment.php');
		}
	}
	
	public function postReceipt($orderId = ''){ 
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postReceipt.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postReceipt.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'postReceipt.php');
		}
	}
	
	public function postSalesCreditPayment(){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postSalesCreditPayment.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postSalesCreditPayment.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'postSalesCreditPayment.php');
		}
	}
	public function postPurchaseCreditPayment(){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postPurchaseCreditPayment.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postPurchaseCreditPayment.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'postPurchaseCreditPayment.php');
		}
	}
	
	
	public function postGoodsDispatch($orderId = ''){ 
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postGoodsDispatch.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postGoodsDispatch.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'postGoodsDispatch.php');
		}
	}
	
	public function postSalesCreditConfimation($orderId = ''){ 
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postSalesCreditConfimation.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postSalesCreditConfimation.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'postSalesCreditConfimation.php');
		}
	}
	
	public function postAcknowledgement($orderId = ''){ 
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postAcknowledgement.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'postAcknowledgement.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'postAcknowledgement.php');
		}
	}	
	public function updateProductCustomFileds($productIds = '', $cronTime=''){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'updateProductCustomFileds.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'updateProductCustomFileds.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'updateProductCustomFileds.php');
		}
    }	
	public function fetchSalesCredit($orderIds = '', $accountId = '',$cronTime = ''){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchSalesCredit.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchSalesCredit.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'fetchSalesCredit.php');
		}
		return array('return' => $return,'saveTime' => @max($saveCronTime)); 
	}
	public function fetchPurchaseCredit($orderIds = '', $accountId = '',$cronTime = ''){
		if(file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchPurchaseCredit.php')){
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .APPNAME. DIRECTORY_SEPARATOR .'fetchPurchaseCredit.php');
		}
		else{
			require_once(dirname(__FILE__). DIRECTORY_SEPARATOR .'brightpearl'. DIRECTORY_SEPARATOR .'fetchPurchaseCredit.php');
		}
		return array('return' => $return,'saveTime' => @max($saveCronTime)); 
	}
	
	public function fetchInventoradvice($reqProductId){
		 $proDatas = $this->ci->db->select('max(productId) as max, min(productId) as min')->get_where('products',array('isLive' => '1'))->row_array();
		$productIds = $proDatas['min'].'-'.$proDatas['max'];
        $url = '/warehouse-service/product-availability/'.$productIds.'?includeOptional=allocatedOrders';
        $this->reInitialize();
        $return = array();
        $results           = $this->getCurl($url);
        foreach ($results as $account1Id => $result) {
            $account2Ids     = $this->account2Details[$account1Id];
            foreach ($account2Ids as $account2Id) {
                $saveAccId1 = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
                $saveAccId2 = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;
                foreach ($result as $productId => $row) {
                    $return[$saveAccId1][$saveAccId2][$productId] = $row;
                }
            }
        }
        $return = $this->fetchBundleProductData($return,$reqProductId);
        return $return;
    }
	public function fetchBundleProductData($return,$productId = ''){
		if($productId){  
			$this->ci->db->where_in('productId',$productId);
		}
		$datas = $this->ci->db->get_where('product_bundle')->result_array();
		if($datas){
			if(@!$this->accountDetails){
				$this->reInitialize();
			}
			foreach($datas as $data){
				$account1Id = $data['account1Id'];
				$productId = $data['productId'];
				$url      = '/warehouse-service/bundle-availability/' . $data['productId'];
				$response = @$this->getCurl($url,'get','','json',$account1Id)[$account1Id][$data['productId']];
				if($response['total']){
					$account2Ids     = $this->account2Details[$account1Id];
					foreach ($account2Ids as $account2Id) {
						$saveAccId1 = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
						$saveAccId2 = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;
						$return[$saveAccId1][$saveAccId2][$productId] = $response;
					}
				}
			}
		}
		return $return; 
	}
	public function getProductStockAssembly($productIds = array()){
        if(!is_array($productIds)){
            $productIds = array($productIds);
        }
        sort($productIds);
        $url = '/warehouse-service/product-availability/'.implode(",", $productIds).'?includeOptional=breakDownByLocation';
        $this->reInitialize();
        $return = array();
        $results           = $this->getCurl($url);
        foreach ($results as $account1Id => $result) {
            $account2Ids     = $this->account2Details[$account1Id];
            foreach ($account2Ids as $account2Id) {
                $saveAccId1 = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
                $saveAccId2 = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;
                foreach ($result as $productId => $row) {
                    $return[$saveAccId1][$productId] = $row;
                }
            }
        }
        return $return;
    }
	public function getProductStock($productIds = array()){
        if(!is_array($productIds)){
            $productIds = array($productIds);
        }
        sort($productIds);
        $url = '/warehouse-service/product-availability/'.implode(",", $productIds);
        $this->reInitialize();
        $return = array();
        $results           = $this->getCurl($url);
        foreach ($results as $account1Id => $result) {
            $account2Ids     = $this->account2Details[$account1Id];
            foreach ($account2Ids as $account2Id) {
                $saveAccId1 = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account1Id) : $account2Id['id'];
                $saveAccId2 = ($this->ci->globalConfig['account1Liberary'] == 'brightpearl') ? ($account2Id['id']) : $account1Id;
                foreach ($result as $productId => $row) {
                    $return[$saveAccId1][$saveAccId2][$productId] = $row;
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
	public function getQuarantineLocation($wareHouseId,$accountId = ''){
		$this->reInitialize();
        $this->getByIdKey = '';
		$getQuarantineLocationUrl = '/warehouse-service/warehouse/'.$wareHouseId.'/location/quarantine';
		$return = $this->getCurl($getQuarantineLocationUrl, "GET", '', 'json');
        return $return;
    }
	public function postInternalTransfer($url,$postData = array(),$accountId = ''){
        $return = array();
        if($postData){
            $this->getByIdKey = '';
            $this->reInitialize();
            $return           = $this->getCurl($url, "POST", json_encode($postData), 'json', $accountId);
        }
        return $return;
    }
	public function releaseAssemblies($url,$postData = array(),$accountId = ''){
        $return = array();
        if($postData){
            $this->getByIdKey = '';
            $this->reInitialize();
            $return           = $this->getCurl($url, "POST", json_encode($postData), 'json', $accountId);
        }
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
	public function postSync($sku = ''){
		$this->reInitialize();
        $query          = $this->ci->db;
        $createProDatas = array();
        $notFoundList   = array();
        if ($sku) {
            if (is_array($sku)) {
                $query->where_in('sku', $sku);
            } else {
                $query->where(array('sku' => $sku));
            }
        }
        $stockResults = $query->where_in('status', array('0'))->get_where('stock_sync', array('sendTo' => 'brightpearl'))->result_array();
		$allDatas = array();
		foreach($stockResults as $stockResult){	
			if(isset($allDatas[$stockResult['account1Id']][$stockResult['account1WarehouseId']][$stockResult['productId']])){
				$allDatas[$stockResult['account1Id']][$stockResult['account1WarehouseId']][$stockResult['productId']]['account1StockQty'] += $stockResult['account1StockQty'];
				$allDatas[$stockResult['account1Id']][$stockResult['account1WarehouseId']][$stockResult['productId']]['account2StockQty'] += $stockResult['account2StockQty'];
				$allDatas[$stockResult['account1Id']][$stockResult['account1WarehouseId']][$stockResult['productId']]['adjustmentQty'] += $stockResult['adjustmentQty'];
			}
			else{ 
				$allDatas[$stockResult['account1Id']][$stockResult['account1WarehouseId']][$stockResult['productId']] = $stockResult; 
			}
		}
        foreach ($allDatas as $account1Id => $allDatass) {
			$this->config = $this->accountConfig[$account1Id];
			foreach ($allDatass as $wareHouseId => $allData) {
				$url            = '/warehouse-service/warehouse/' . $wareHouseId . '/stock-correction';
				$defaultLocaton = $this->getCurl('/warehouse-service/warehouse/' . $wareHouseId . '/location/default','get','','json',$account1Id)[$account1Id];
				$corrections    = array();$correctionReason = '';$priceListProductId = array();$productPriceList = array();
				foreach ($allData as $proId => $row) {
					if($row['adjustmentQty'] > 0){
						$priceListProductId[$row['productId']] = $row['productId'];
					}
				}
				if($priceListProductId){
					$productPriceList = $this->getProductPriceList($priceListProductId,$this->config['defaultProductPriceList'],$account1Id);
				}
				foreach ($allData as $proId => $row) {
					$notAdjustedQty = '';
					if ($row['adjustmentQty'] == '0') {continue;}
					if (($row['account1StockQty'] == '0') && ($row['adjustmentQty'] < 0)) {
						if ($type != 'adjustment') {
							$notAdjustedQty = $row['adjustmentQty'];
							$this->ci->db->where(array('id' => $row['id']))->update('stock_sync', array('notAdjustedQty' => $notAdjustedQty));
							$this->ci->db->where(array('adjustmentId' => $row['id']))->update('stock_sync_log', array('notAdjustedQty' => $notAdjustedQty));
						}
						continue;
					}
					$price = '';
					if ($row['adjustmentQty'] < 0) {
						$temp1 = (-1) * $row['adjustmentQty'];
						if ($temp1 > $row['account1StockQty']) {
							$row['adjustmentQty'] = (@$row['account1StockQty'] < 0) ? ($row['account1StockQty']) : ('-' . @$row['account1StockQty']);
							if ($type != 'adjustment') {
								$notAdjustedQty = $temp1 - $row['account1StockQty'];
								$notAdjustedQty = '-' . $notAdjustedQty;
								$this->ci->db->where(array('id' => $row['id']))->update('stock_sync', array('notAdjustedQty' => $notAdjustedQty));
								$this->ci->db->where(array('adjustmentId' => $row['id']))->update('stock_sync_log', array('notAdjustedQty' => $notAdjustedQty));
							}
						}
					}
					else{
						$price = $productPriceList[$row['productId']][$this->config['defaultProductPriceList']];
						if(!$price){
							$price = 0.00;
						}
					}
					$corrections[] = array(
						'quantity'   => $row['adjustmentQty'],
						'productId'  => $row['productId'],
						'reason'     => 'Stock alignment',
						'locationId' => $defaultLocaton,
						'cost'       => array(
							'currency' => $this->config['currencyCode'],
							'value'    => (float)$price,
						),
					);
				}
				$proDuctIds  = array_column($allData, 'productId');
				$stockArray = array('corrections' => $corrections);
				$res        = $this->getCurl($url, 'POST', json_encode($stockArray),'json',$account1Id)[$account1Id];
				if (@!$res['errors']) {
					$proDuctIds = array_chunk($proDuctIds,200);
					foreach($proDuctIds as $proDuctId){ 
						if (@$type == 'adjustment') { 
							$this->ci->db->where_in('productId', $proDuctId)->update('stock_adjustment', array('status' => '1'));
						} else {
							$this->ci->db->where_in('productId', $proDuctId)->where(array('account1Id' => $account1Id))->update('stock_sync', array('status' => '1'));
						}
					}
				} 
			} 
        }
    }
    public function getProductPriceList($proIds, $priceListId = ''){
        $this->reInitialize();
        $this->getByIdKey = '';
        if (!$proIds) {return false;}
		if(is_string($proIds)){
			$proIds = array($proIds);
		}
		foreach($this->accountDetails as $account1Id => $accountDetails){
			if($priceListId != ''){
				$result = $this->getResultById($proIds,'/product-service/product-price/',$account1Id,200,0,'/price-list/' . $priceListId);
			}
			else{
				$result = $this->getResultById($proIds,'/product-service/product-price/',$account1Id);
			}
			foreach ($result as $row) {
				foreach ($row['priceLists'] as $priceLists) {
					$return[$row['productId']][$priceLists['priceListId']] = (@$priceLists['quantityPrice']['1']) ? ($priceLists['quantityPrice']['1']) : '0.00';
				}
			}
		}
        return $return;
    }
	 public function getProductPriceListNew($proIds = array(), $priceListId = '0',$getAllProductPrice = 0){
        $this->reInitialize();
        $this->getByIdKey = '';
        if (!$proIds) {
			if(!$getAllProductPrice){ return false;	}
			else{
				$proIds = array_column($this->ci->db->select('productId')->get_where('products',array('isLive' => '1'))->result_array(),'productId');
				if(!$proIds){return false;}
			}
		}
		if(is_string($proIds)){
			$proIds = array($proIds);
		}
		foreach($this->accountDetails as $account1Id => $accountDetails){
			$result = $this->getResultById($proIds,'/product-service/product-price/',$account1Id);
			foreach ($result as $row) {
				foreach ($row['priceLists'] as $priceLists) {
					if($getAllProductPrice){
						$return[$account1Id][$row['productId']][$priceLists['priceListId']] = (@$priceLists['quantityPrice']['1']) ? ($priceLists['quantityPrice']['1']) : '0.00';

					}
					else{
						$return[$row['productId']][$priceLists['priceListId']] = (@$priceLists['quantityPrice']['1']) ? ($priceLists['quantityPrice']['1']) : '0.00';
					}
				}
			}
		}
        return $return;
    }
	public function getSkuByPricelistID($proIds, $priceListId = ''){
		 $this->reInitialize();
        $this->getByIdKey = '';
        if (!$proIds) {return false;}
		if(is_string($proIds)){
			$proIds = array($proIds);
		}
		foreach($this->accountDetails as $account1Id => $accountDetails){
			$returnwithSku = array();
			if($priceListId != ''){
				$result = $this->getResultById($proIds,'/product-service/product-price/',$account1Id,200,0,'/price-list/' . $priceListId);
			}
			else{
				$result = $this->getResultById($proIds,'/product-service/product-price/',$account1Id);
			}
			foreach ($result as $row) {
				foreach ($row['priceLists'] as $priceLists) {
					$returnwithSku[$row['productId']][$priceLists['priceListId']] = (@isset($priceLists['sku']) && !empty($priceLists['sku'])) ? $priceLists['sku'] : "";
				}
			}
		}
		
        return $returnwithSku;
	}
	public function updateOrderStatus($orderIdsLists = array(),$statusId = '',$accountId = ''){
		if(!$orderIdsLists){ return false; }
		if(!$statusId){ return false; } 
		if(!is_array($orderIdsLists)){
			$orderIdsLists = array($orderIdsLists);
		}
		foreach($orderIdsLists as $orderIdsList){
			$url = '/order-service/order/'.$orderIdsList.'/status';
			$request = array(
				'orderStatusId' => $statusId,
				'orderNote' 	=> array(
					'text'		=> 'Updating order status',
					'isPublic'	=> true,
				)
			);
			if($accountId){
				$res = $this->getCurl($url,'PUT',json_encode($request),'json',$accountId)[$accountId];
			}
			else{
				$res = $this->getCurl($url,'PUT',json_encode($request),'json');
			}
		}
	}
	public function getAllShippingMethod($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/warehouse-service/shipping-method';
		$return = array();
        $returns           = $this->getCurl($url);
		foreach($returns as $account1Id => $retur){
			foreach($retur as $re){
				$return[$account1Id][$re['id']] = $re;
			}
		}
        return $return;
    }
	public function getExchangeRate($accountId = ''){
		if(@!$this->accountDetails[$accountId]){
			$this->reInitialize($accountId);
		}
		$url      = '/accounting-service/exchange-rate/';
        $response = $this->getCurl($url,'get','json','',$accountId);
        return $response;
    }
	
    public function getAllCurrency($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/accounting-service/currency-search';
        $returnDatas      = $this->getCurl($url);
        $return = array();
        foreach ($returnDatas as $accountId => $returnData) {
            foreach ($returnData['results'] as $key => $results) {
                $return[$accountId][$results['0']] = array(
                    'id' => $results['0'],
                    'name' => $results['1'],
                    'code' => $results['2'],
                    'symbol' => $results['3'],
                );
            }
        }
        $this->getByIdKey = '';
        return $return;
    }
    public function getAllLocation($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/warehouse-service/warehouse';
        $return = array();
        $returns           = $this->getCurl($url);
		foreach($returns as $account1Id => $retur){
			foreach($retur as $re){
				$return[$account1Id][$re['id']] = $re;
			}
		}
        return $return;
    }
	public function getAllWarehouseLocation($accountId = '', $byWarehouse = ''){
		$this->reInitialize();
		$this->getByIdKey = '';
		$url              = '/warehouse-service/location-search';
		$return = array();
		$saveBinDatas = array();
		$saveBinDatasTemps = $this->ci->db->get_where('warehouse_binlocation')->result_array();
		foreach($saveBinDatasTemps as $saveBinDatasTemp){
			$accountId = $saveBinDatasTemp['accountId'];
			$config = $this->accountConfig[$accountId];
			if($byWarehouse){
				$wareHouseId = $config['warehouse'];
				if($saveBinDatasTemp['warehouseId'] && $saveBinDatasTemp['warehouseId'] == $wareHouseId){
					$saveBinDatas[$saveBinDatasTemp['accountId']][$saveBinDatasTemp['id']] = $saveBinDatasTemp;
				}
			}
			else{
				$saveBinDatas[$saveBinDatasTemp['accountId']][$saveBinDatasTemp['id']] = $saveBinDatasTemp;
			}
		}
		
		if(!$saveBinDatasTemps){
			foreach($this->accountDetails as $accountId => $accountDetails){				
				$config = $this->accountConfig[$accountId];
				$wareHouseId = '';
				if($byWarehouse){
					$wareHouseId = $config['warehouse'];
				}
				$response = $this->getCurl($url,'get','','json',$accountId)[$accountId];
				foreach($response['results'] as  $result){
					if($wareHouseId){
						if($result['1'] && $result['1'] == $wareHouseId){
							/* if((count($result['3']) > 0) &&(count($result['4']) > 0) &&(count($result['5']) > 0) &&(count($result['6']) > 0)){ */
								$warehouseNames = "";
								$warehouseNames = $result['3'].'.'.$result['4'].'.'.$result['5'].'.'.$result['6'];
								$warehouseNames = str_replace('..', '', $warehouseNames);
								$warehouseNames = str_replace('...', '', $warehouseNames);
								$return[$accountId][$result['0']] = array(
									'id' => $result['0'],
									'name' => rtrim($warehouseNames, '.'),
									'warehouseId' => $result['1'],
									'accountId' => $accountId,
								);
							/* } */					
						}
					}else{
						/* if((count($result['3']) > 0) &&(count($result['4']) > 0) &&(count($result['5']) > 0) &&(count($result['6']) > 0)){ */
								$warehouseNamess = "";
								$warehouseNamess = $result['3'].'.'.$result['4'].'.'.$result['5'].'.'.$result['6'];
								$warehouseNamess = str_replace('..', '', $warehouseNamess);
								$warehouseNamess = str_replace('...', '', $warehouseNamess);
								$return[$accountId][$result['0']] = array(
									'id' => $result['0'],
									'name' => rtrim($warehouseNamess, '.'),
									'warehouseId' => $result['1'],
									'accountId' => $accountId,
								);
							/* } */		
					}
				}	
				if ($response['metaData']) {
					for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
						$url1      = $url . '?firstResult=' . $i;
						$response1 = $this->getCurl($url1, "GET", '', 'json', $accountId)[$accountId];
						if ($response1['results']) {
							foreach ($response1['results'] as $result) {
								if($result['1'])
								/* if((count($result['3']) > 0) &&(count($result['4']) > 0) &&(count($result['5']) > 0) &&(count($result['6']) > 0)){  */
									$warehouseName = "";
									$warehouseName = $result['3'].'.'.$result['4'].'.'.$result['5'].'.'.$result['6'];
									$warehouseName = str_replace('..', '', $warehouseName);
									$warehouseName = str_replace('...', '', $warehouseName);
									$return[$accountId][$result['0']] = array(
										'id' => $result['0'],
										'name' => rtrim($warehouseName, '.'),
										'warehouseId' => $result['1'],
										'accountId' => $accountId,
									);
								/* } */
							}

						}

					}
				}							
			}
			foreach($return as $accId => $returns){
				$returnsDatas = array_values($returns);
				if($returnsDatas){
					$this->ci->db->insert_batch('warehouse_binlocation',$returnsDatas);
				}
			}
			$saveBinDatasTemps = $this->ci->db->get_where('warehouse_binlocation')->result_array();
			foreach($saveBinDatasTemps as $saveBinDatasTemp){
				$saveBinDatas[$saveBinDatasTemp['accountId']][$saveBinDatasTemp['id']] = $saveBinDatasTemp;
			}
		}
		return $saveBinDatas;
    }
	
	public function getAllWarehouseLocationInsert($accountId = '', $byWarehouse = ''){
		$this->ci->db->truncate('warehouse_binlocation');
		$this->reInitialize();
		$this->getByIdKey = '';
		$url              = '/warehouse-service/location-search';
		$return = array();
		$saveBinDatas = array();
		$saveBinDatasTemps = $this->ci->db->get_where('warehouse_binlocation')->result_array();
		foreach($saveBinDatasTemps as $saveBinDatasTemp){
			$accountId = $saveBinDatasTemp['accountId'];
			$config = $this->accountConfig[$accountId];
			if($byWarehouse){
				$wareHouseId = $config['warehouse'];
				if($saveBinDatasTemp['warehouseId'] && $saveBinDatasTemp['warehouseId'] == $wareHouseId){
					$saveBinDatas[$saveBinDatasTemp['accountId']][$saveBinDatasTemp['id']] = $saveBinDatasTemp;
				}
			}
			else{
				$saveBinDatas[$saveBinDatasTemp['accountId']][$saveBinDatasTemp['id']] = $saveBinDatasTemp;
			}
		}
		
		if(!$saveBinDatasTemps){
			foreach($this->accountDetails as $accountId => $accountDetails){				
				$config = $this->accountConfig[$accountId];
				$wareHouseId = '';
				if($byWarehouse){
					$wareHouseId = $config['warehouse'];
				}
				$response = $this->getCurl($url,'get','','json',$accountId)[$accountId];
				foreach($response['results'] as  $result){
					if($wareHouseId){
						if($result['1'] && $result['1'] == $wareHouseId){
							/* if((count($result['3']) > 0) &&(count($result['4']) > 0) &&(count($result['5']) > 0) &&(count($result['6']) > 0)){ */
								$warehouseNames = "";
								$warehouseNames = $result['3'].'.'.$result['4'].'.'.$result['5'].'.'.$result['6'];
								$warehouseNames = str_replace('..', '', $warehouseNames);
								$warehouseNames = str_replace('...', '', $warehouseNames);
								$return[$accountId][$result['0']] = array(
									'id' => $result['0'],
									'name' => rtrim($warehouseNames, '.'),
									'warehouseId' => $result['1'],
									'accountId' => $accountId,
								);
							/* } */					
						}
					}else{
						/* if((count($result['3']) > 0) &&(count($result['4']) > 0) &&(count($result['5']) > 0) &&(count($result['6']) > 0)){ */
								$warehouseNamess = "";
								$warehouseNamess = $result['3'].'.'.$result['4'].'.'.$result['5'].'.'.$result['6'];
								$warehouseNamess = str_replace('..', '', $warehouseNamess);
								$warehouseNamess = str_replace('...', '', $warehouseNamess);
								$return[$accountId][$result['0']] = array(
									'id' => $result['0'],
									'name' => rtrim($warehouseNamess, '.'),
									'warehouseId' => $result['1'],
									'accountId' => $accountId,
								);
							/* } */		
					}
				}	
				if ($response['metaData']) {
					for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
						$url1      = $url . '?firstResult=' . $i;
						$response1 = $this->getCurl($url1, "GET", '', 'json', $accountId)[$accountId];
						if ($response1['results']) {
							foreach ($response1['results'] as $result) {
								if($result['1'])
								/* if((count($result['3']) > 0) &&(count($result['4']) > 0) &&(count($result['5']) > 0) &&(count($result['6']) > 0)){  */
									$warehouseName = "";
									$warehouseName = $result['3'].'.'.$result['4'].'.'.$result['5'].'.'.$result['6'];
									$warehouseName = str_replace('..', '', $warehouseName);
									$warehouseName = str_replace('...', '', $warehouseName);
									$return[$accountId][$result['0']] = array(
										'id' => $result['0'],
										'name' => rtrim($warehouseName, '.'),
										'warehouseId' => $result['1'],
										'accountId' => $accountId,
									);
								/* } */
							}

						}

					}
				}							
			}
			foreach($return as $accId => $returns){
				$returnsDatas = array_values($returns);
				if($returnsDatas){
					$this->ci->db->insert_batch('warehouse_binlocation',$returnsDatas);
				}
			}
			$saveBinDatasTemps = $this->ci->db->get_where('warehouse_binlocation')->result_array();
			foreach($saveBinDatasTemps as $saveBinDatasTemp){
				$saveBinDatas[$saveBinDatasTemp['accountId']][$saveBinDatasTemp['id']] = $saveBinDatasTemp;
			}
		}
		return $saveBinDatas;
    }
	
    public function getAllChannel($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/product-service/channel';
		$return = array();
        $returns           = $this->getCurl($url);
		foreach($returns as $account1Id => $retur){
			foreach($retur as $re){
				$return[$account1Id][$re['id']] = $re;
			}
		}
		return $return;
    }
    public function getAllOrderStatus($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/order-service/order-status';
        $results          = $this->getCurl($url);
        $return           = array();
        foreach ($results as $accountIId => $result) {
            foreach ($result as $orderStatusId => $orderStatus) {
                $return[$accountIId][$orderStatus['statusId']]       = $orderStatus;
                $return[$accountIId][$orderStatus['statusId']]['id'] = $orderStatus['statusId'];
            }
        }
        $this->getByIdKey = '';
        return $return;
    }
    
	public function getAllCategoryMethod($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/product-service/brightpearl-category';
        $return = array();
        $returns           = $this->getCurl($url);
		foreach($returns as $account1Id => $retur){
			foreach($retur as $re){
				$return[$account1Id][$re['id']] = $re;
			}
		}
		return $return;
    }
    public function getAllTax($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = 'id';
        $url              = '/accounting-service/tax-code';
        $results          = $this->getCurl($url);
        $return           = array();
        foreach ($results as $accountIId => $result) {
            foreach ($result as $taxId => $tax) {
                $return[$accountIId][$taxId]         = $tax;
                $return[$accountIId][$taxId]['name'] = $tax['code'];
            }
        }
        $this->getByIdKey = '';
        return $return;
    }
    public function getSeason($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = '';
		$return = array();
        $returns           = $this->getCurl($url);
		foreach($returns as $account1Id => $retur){
			foreach($retur as $re){
				$return[$account1Id][$re['id']] = $re;
			}
		}
    }
    public function getAllPriceList($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/product-service/price-list';
        $results          = $this->getCurl($url);
        $return           = array();
        foreach ($results as $accountIId => $result) {
            foreach ($result as $priceListId => $pricelist) {
                $return[$accountIId][$pricelist['id']]         = $pricelist;
                $return[$accountIId][$pricelist['id']]['name'] = $pricelist['code'];
            }
        }
        $this->getByIdKey = '';
        return $return;
    }
	public function nominalCode($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/accounting-service/nominal-code-search';
        $results          = $this->getCurl($url);
        $return           = array();
        foreach ($results as $accountIId => $result) { 
            foreach ($result['results'] as  $nominalCodes) {
                $return[$accountIId][$nominalCodes['0']]       = $nominalCodes;
                $return[$accountIId][$nominalCodes['0']]['id'] = $nominalCodes['0'];
                $return[$accountIId][$nominalCodes['0']]['name'] = '( '.$nominalCodes['0'] . ' ) '. $nominalCodes['1'];
            }
        }
        $this->getByIdKey = '';
        return $return;
    }
    public function getAllTag($accountId = ''){
        $this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/contact-service/tag';
        $results          = $this->getCurl($url);
        $return           = array();
        foreach ($results as $accountIId => $result) {
            foreach ($result as $priceListId => $pricelist) {
                $return[$accountIId][$priceListId]         = $pricelist;
                $return[$accountIId][$priceListId]['name'] = $pricelist['tagName'];
                $return[$accountIId][$priceListId]['id'] = $pricelist['tagId'];
            }
        }
        $this->getByIdKey = '';
        return $return;
    }
	public function getAllSalesrep(){
		$this->reInitialize($accountId);
		$this->getByIdKey = '';
		$url = '/contact-service/contact-search?isStaff=true';
		$resultss           = $this->getCurl($url);
		$return = array();
		foreach($resultss as $accountId => $results){
			foreach($results['results'] as $result){
				$return[$accountId][$result['0']] = array('id' => $result['0'],'name' => $result['4'] .' '. $result['5']);
			}
		}
		return $return;
	}
	public function getAllBrand($accountId = ''){
		$this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/product-service/brand-search';
        $resultss =  $this->getCurl($url);
        $return = array();
		foreach($resultss as $accountId => $results){
			foreach($results['results'] as $result){
				$return[$accountId][$result['0']] = array('id' => $result['0'],'name' => $result['1']);
			} 
		}		
        return $return;
    }
	public function getAllChannelMethod($accountId = ''){
		$this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/product-service/channel';
        $resultss =  $this->getCurl($url);
        $return = array();
		foreach($resultss as $accountId => $results){
			foreach($results as $result){
				$return[$accountId][$result['id']] = $result;
			} 
		}		
        return $return;
    }
	public function getAllLeadsource($accountId = ''){
		$this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/contact-service/lead-source';
        $resultss =  $this->getCurl($url);
        $return = array();
		foreach($resultss as $accountId => $results){
			foreach($results as $result){
				$return[$accountId][$result['id']] = $result;
			} 
		}		
        return $return;
    }
	public function getAllPaymentMethod($accountId = ''){
		$this->reInitialize($accountId);
        $this->getByIdKey = '';
        $url              = '/accounting-service/payment-method-search';
        $resultss =  $this->getCurl($url);
        $return = array();
		foreach($resultss as $accountId => $results){
			foreach($results['results'] as $result){
				$result['id'] = $result['1'];
				$result['name'] = $result['2'];
				$return[$accountId][$result['id']] = $result;
			} 
		}
        return $return;
    }	
    public function getAccountInfo($accountId = ''){
        $this->reInitialize();
        $this->getByIdKey = '';
        $url              = '/integration-service/account-configuration';
        $return           = $this->getCurl($url);
        return $return;
    }
	public function getAllAllowance(){
		$datasTemps = $this->ci->db->get('products')->result_array();
		$return = array();
		foreach($datasTemps as $datasTemp){
			$datasTemp['id'] = $datasTemp['productId'];
			$datasTemp['name'] = $datasTemp['sku'];
			$return[$datasTemp['account1Id']][$datasTemp['productId']] = $datasTemp;
		}
		return $return;
	}
	public function getProductPriceListUpdated($proIds = array(), $priceListId = '0',$getAllProductPrice = 0){
        $this->reInitialize();
        $this->getByIdKey = '';
        if (!$proIds) {
			if(!$getAllProductPrice){ return false;	}
			else{
				$proIds = array_column($this->ci->db->select('productId')->get_where('products',array('isLive' => '1'))->result_array(),'productId');
				if(!$proIds){return false;}
			}
		}
		if(is_string($proIds)){
			$proIds = array($proIds);
		}
		foreach($this->accountDetails as $account1Id => $accountDetails){
			$result = $this->getResultById($proIds,'/product-service/product-price/',$account1Id);
			foreach ($result as $row) {
				foreach ($row['priceLists'] as $priceLists) {
					if($getAllProductPrice){
						$return[$account1Id][$row['productId']][$priceLists['priceListId']] = (@$priceLists['quantityPrice']['1']) ? ($priceLists['quantityPrice']['1']) : '0.00';

					}
					else{
						$return[$row['productId']][$priceLists['priceListId']] = (@$priceLists['quantityPrice']['1']) ? ($priceLists['quantityPrice']['1']) : '0.00';
					}
				}
			}
		}
        return $return;
    }
}
