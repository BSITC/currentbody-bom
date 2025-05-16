<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends MY_Controller {

	public function __construct(){
		parent::__construct();
	}

	public function generateToken(){
		$this->brightpearl->generateToken();
	}
	public function fetchProducts(){
		$this->brightpearl->fetchProducts();
	}
	public function bpInfo(){
		$this->brightpearl->reIntialize();
		$info = $this->brightpearl->getInfo();
		echo "<pre>";print_r($info); echo "</pre>";die(__FILE__.' : Line No :'.__LINE__);
	}
	public function mailerTest(){
		$this->load->library('mailer');
		echo "<pre>";print_r($this->mailer); echo "</pre>";die(__FILE__.' : Line No :'.__LINE__);
		$appName		= $this->globalConfig['app_name'];
		$subject = 'Test Mail';
		$from = array('info@bsitc-apps.com' => 'Info');
		$body = 'Hi,<br><br>
		<p>This is test mail</p>
		
		<br><br>

		';
		$body .= '			
		<br><br><br>			
		Thanks & Regards<br>
		BSITC Team'; 
		$to = "dean@businesssolutionsinthecloud.com";
		
		$res = $this->mailer->send($to,$subject,$body,$from);
		echo "<pre>";print_r($res); echo "</pre>";die(__FILE__.' : Line No :'.__LINE__);
	}
	
	public function fetchSales(){
		$this->nuorder->fetchSales();
	}
	public function updateGonOnAssembly(){
		die("stopped for now");
		$this->brightpearl->reInitialize();
		$fetchDatas = array();
		foreach($this->brightpearl->accountDetails as $account1Id => $accountDetails){
			$url = '/warehouse-service/stock-transfer';
			$response = $this->brightpearl->getCurl($url, "GET", '', 'json', $account1Id)[$account1Id];
			foreach ($response as $result) {
				$assemblyId = "";
				$assemblyId = str_replace("Asmb id: ","", $result['reference']);
				if(!$result['goodsOutNoteId']){
					continue;
				}
				$fetchDatas[$assemblyId] = $result;
			}
			
			if($fetchDatas){
				foreach($fetchDatas as $assemblyId => $fetchData){
					//echo $assemblyId;
					
					//echo "<pre>fetchDatas";print_r($fetchData); echo "</pre>";
					$this->db->where(array('createdId' => $assemblyId))->update('product_assembly',array('goodsOutId' => $fetchData['goodsOutNoteId']));
					echo "<br>".$this->db->last_query(); 
					//die(__FILE__.' : Line No :'.__LINE__); 
				}
			}
			
		}
	}
	public function multipleAssemblyAlerts(){
		$this->brightpearl->reInitialize();
		foreach($this->brightpearl->accountDetails as $accountId => $accountDetails){
			$getMovementUrl = '/warehouse-service/goods-movement-search?goodsNoteTypeCode=SC&updatedOn='.date('Y-m-d',strtotime('-15 days')).'/';
			$getMovementDatas      = $this->brightpearl->getCurl($getMovementUrl,'get','','json',$accountId)[$accountId];
			$goodsMovmentsSearchDatas = array();
			if($getMovementDatas['results']){
				foreach($getMovementDatas['results'] as $getMovementData){
					if($getMovementData['2'] || $getMovementData['3']){
						//continue;
					}
					//warehouseId = 6, goodsOutId = 7
					if($getMovementData['7']){
						$goodsMovmentsSearchDatas[$getMovementData['6']][] = $getMovementData['7'];
					}
				}	
				if ($getMovementDatas['metaData']['resultsAvailable'] > 500) {
					for ($i = 500; $i <= $getMovementDatas['metaData']['resultsAvailable']; $i = ($i + 500)) {
						$url1      = $getMovementUrl . '&firstResult=' . $i;
						$response1 = $this->brightpearl->getCurl($url1, "GET", '', 'json', $accountId)[$accountId];
						if ($response1['results']) {
							foreach($response1['results'] as $getMovementData){
								if($getMovementData['2'] || $getMovementData['3']){
									//continue;
								}
								//warehouseId = 6, goodsOutId = 7
								if($getMovementData['7']){
									$goodsMovmentsSearchDatas[$getMovementData['6']][] = $getMovementData['7'];
								}
							}
						}
					}
				}
					
			}
			$finalArray = array();
			if($goodsMovmentsSearchDatas){
				foreach($goodsMovmentsSearchDatas as $warehouseId => $goodsMovmentsSearchData){					
					$response = $this->brightpearl->getResultById($goodsMovmentsSearchData,'/warehouse-service/warehouse/'.$warehouseId.'/stock-correction/',$accountId);
					foreach($response as $respons){
						$reason = $respons['reason'];
						if(substr_count($reason,'Assembly of product. Assembly id: AS')){
							$goodsNoteId = $respons['goodsNoteId'];
							$goodsMoveds = $respons['goodsMoved'];
							if($goodsMoveds){
								if(!$goodsMoveds['0']){
									$goodsMoveds = array($goodsMoveds);
								}
								foreach($goodsMoveds as $goodsMoved){
									$finalArray[$reason][$goodsMoved['productId']][] = $goodsMoved;
								}
							}
						}
					}
				}
			}
			$issueList = array();
			foreach($finalArray as $aseemblyIdTemps => $finalArra){
				$aseemblyId = explode(": ",$aseemblyIdTemps)['1'];
				$findPositive = 0;$findNegative = 0;
				foreach($finalArra as $proId => $finalAr){
					if(count($finalAr) > 1){
						$qty = array_sum(array_column($finalAr,'quantity'));
						$tempPData = $finalAr['0'];
						$tempPData['quantity'] = $qty;
						if($qty == 0){
							$finalArra[$proId] = array($tempPData);
						}
						else{
							$issueList[$aseemblyId][$proId] = $finalArra;
							break;
						}
					}
					foreach($finalAr as $finalA){
						if($finalA['quantity'] > 0){
							$findPositive = 1;
						}
						if($finalA['quantity'] < 0){
							$findNegative = 1;
						}
						
					}
				}
				if((!$findPositive) || (!$findNegative)){
					$issueList[$aseemblyId] = $finalArra;
				}
			}
			if($issueList){
				$this->load->library('mailer');
				$appName		= $this->globalConfig['app_name'];
				$filepath = FCPATH.'logs/emailalert/'.date('Y').'/'.date('m').'/'.date('d').'/'.basename($suburl);			
				if(!is_dir(($filepath))){
					mkdir(($filepath),0777,true);
					chmod(($filepath), 0777);
				}	
				$filename = date('Y-m-d H-i-s ').uniqid().'.logs';
				file_put_contents($filepath.$filename,json_encode($issueList, JSON_PRETTY_PRINT));
				
				$subject = 'Alert '.$appName.' -  BOM not correctly created';
				$from = array('info@bsitc-apps.com' => 'Info');
				$body = 'Hi,<br><br>
				<p>No of effected assembly : '.count($issueList).'.</p>
				<p>AssemblyIds : '.implode(", ", array_keys($issueList)).'.</p>
				
				<br><br>

				';
				$body .= '			
				<br><br><br>			
				Thanks & Regards<br>
				BSITC Team'; 
				$to = "dean@businesssolutionsinthecloud.com,hitesh@businesssolutionsinthecloud.com,aherve@businesssolutionsinthecloud.com,jaina@businesssolutionsinthecloud.com";
				
				$res = $this->mailer->send($to,$subject,$body,$from,$filepath.$filename);
				unlink($filepath.$filename);
			}			
		}
	}
	
	public function calculateFifoPrice(){
		$this->brightpearl->reInitialize();
		$correctionIdss = array(
			"2" => array(
				'8974',
				'8975',
			)
		);
		$saveReceipeDatas = json_decode('{"34413":{"id":"256","productId":"34416","componentProductId":"34413","receipeId":"1","recipename":"TESTR1","name":"Test Component 1","sku":"TESTC1","qty":"2.00","bomQty":"1","createdInventoryId":"","isInventoryTransfer":"0","isInventoryRelease":"0","created":"2022-02-08 08:42:41","updated":"2022-02-08 16:42:41","isPrimary":"1","updatedBy":"bomappqa","ip":"59.94.144.27","recipeOrder":"0"},"34414":{"id":"257","productId":"34416","componentProductId":"34414","receipeId":"1","recipename":"TESTR1","name":"Test Component 2","sku":"TESTC2","qty":"4.00","bomQty":"1","createdInventoryId":"","isInventoryTransfer":"0","isInventoryRelease":"0","created":"2022-02-08 08:42:41","updated":"2022-02-08 16:42:41","isPrimary":"1","updatedBy":"bomappqa","ip":"59.94.144.27","recipeOrder":"0"}}',true);
		$increasePrice = 0;
		$datas = array(
			'qtydiassemble' => '3'
		);
		foreach($this->brightpearl->accountDetails as $account1Id => $accountDetails){
			if($correctionIdss){
				$fetchCorrectionDatas = array();
				foreach($correctionIdss as $wid => $correctionIds){
					$correctionIds = array_filter($correctionIds);
					$correctionIds = array_filter($correctionIds);
					sort($correctionIds);
					$fetchCurrectionUrl = '/warehouse-service/warehouse/'.$wid.'/stock-correction/'.implode(",",$correctionIds);		
					$fetchCorrectionDatasTemps = $this->brightpearl->getCurl($fetchCurrectionUrl);
					foreach($fetchCorrectionDatasTemps as $accId => $fetchCorrectionData){
						if($fetchCorrectionData){							
							foreach($fetchCorrectionData as $fetchCorrectionDat){
								foreach($fetchCorrectionDat['goodsMoved'] as $goodsMoved){
									@$fetchCorrectionDatas[$goodsMoved['productId']]['productValue'] += ($goodsMoved['productValue']['value'] * abs($goodsMoved['quantity']));
									@$fetchCorrectionDatas[$goodsMoved['productId']]['quantity'] += abs($goodsMoved['quantity']);
								}
							}
						}
					}
				}
				$finalAmount = 0;
				if($fetchCorrectionDatas){
					foreach($fetchCorrectionDatas as $correctionProductId => $correctionAmount){
						$correctionAmountCal = $correctionAmount['productValue'];
						$finalAmount += $correctionAmountCal;
					}
				}
				if($finalAmount > 0){ 
					$finalAmount =  $finalAmount / $datas['qtydiassemble'];
					$finalAmount =  $finalAmount / $saveReceipeDatas[$correctionProductId]['bomQty'];
					$postStockTransferArray['0']['cost']['value'] = sprintf("%.4f",($finalAmount + $nonTrackedItemPrice));
				}					
			}
			echo "<pre>";print_r($postStockTransferArray); echo "</pre>";die(__FILE__.' : Line No :'.__LINE__);
		}
								
	}
}