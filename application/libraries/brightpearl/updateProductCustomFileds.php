<?php
$this->reInitialize();
foreach ($this->accountDetails as $account1Id => $accountDetails) {
	$this->config    = $this->accountConfig[$account1Id];
	$query = $this->ci->db;
	if($productIds){
		$query->where_in('productId',$productIds);
	}
	$proDatas = $query->get_where('products',array('account1Id' => $account1Id,'isDataPosted' => '0'))->result_array();
	$updatedProductList = array();
	foreach($proDatas as $proData){
		$config2 = $this->account2Config[$proData['account2Id']];
		// start adding season		
		//end of adding season
		$customFiledUrl = '/product-service/product/'.$proData['productId'].'/custom-field';
		$updateCustomRequest = array();
		if($config2['customCountryOfOrigin']){
			$updateCustomRequest[] = array(
				"op" 	=> "add",
				"path" 	=> "/".$config2['customCountryOfOrigin'],
				"value"	=> $proData['countryOfOrigin']
			);
		}
		if($config2['customCustomsNumber']){
			$updateCustomRequest[] = array(
				"op" 	=> "add",
				"path" 	=> "/".$config2['customCustomsNumber'],
				"value"	=> $proData['hsCode']
			);
		}
		if($config2['customCustomsNumber']){
			$updateCustomRequest[] = array(
				"op" 	=> "add",
				"path" 	=> "/".$config2['customFabric'],
				"value"	=> $proData['fabric']
			);
		}
		$res = $this->getCurl($customFiledUrl,'PATCH',json_encode($updateCustomRequest),'json',$account1Id)[$account1Id];
		if(!isset($res['errors'])){
			$this->ci->db->where_in('productId',$proData['productId'])->update('products',array('isDataPosted' => '1'));
		}
	}	
}
?>