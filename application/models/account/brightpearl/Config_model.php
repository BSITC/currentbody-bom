<?php
class Config_model extends CI_Model{
	public function get($type = ''){
		$data = array();
		$data['data'] =  $this->db->get('account_brightpearl_config')->result_array();
		$saveAccounts =  $this->db->get('account_brightpearl_account')->result_array();
		foreach($saveAccounts as $saveAccount){
			$key = ($saveAccount['account1Id'])?($saveAccount['account1Id']):($saveAccount['id']);
			$data['saveAccount'][$key] =  $saveAccount;
		}
		if($type == 'account1'){
			$data['accountinfo'] =  $this->{$this->globalConfig['account1Liberary']}->getAccountInfo();
			$pricelistss   = $this->{$this->globalConfig['account1Liberary']}->getAllPriceList();
			$costPriceLists = array();
			foreach($pricelistss as $account1Id => $pricelists){
				foreach($pricelists as $Id => $pricelist){
					if($pricelist['priceListTypeCode'] == 'BUY'){
						$costPriceLists[$account1Id][$Id] = $pricelist;
					}
				}
			}
			$data['pricelist']  = $costPriceLists;
			$data['warehouse']   = $this->{$this->globalConfig['account1Liberary']}->getAllLocation();
			$data['getAllWarehouseLocation']   = $this->{$this->globalConfig['account1Liberary']}->getAllWarehouseLocation();
			$data['orderstatus']     = $this->{$this->globalConfig['account1Liberary']}->getAllOrderStatus();
		}
		else{
			$data['accountinfo'] =  $this->{$this->globalConfig['account2Liberary']}->getAccountInfo();
			$data['pricelist'] =  $this->{$this->globalConfig['account2Liberary']}->fetchProducts();
			$data['warehouse']   = $this->{$this->globalConfig['account2Liberary']}->getAllLocation();
		}
		return $data;
	}
	public function delete($id){
		$this->db->where(array('id' => $id))->delete('account_brightpearl_config');
	}
	public function save($data){
		/* if($data['defaultAutoAssembyLocation']){
			$defaultAutoAssembyLocation = $this->getBinLocationByWarehouse($data['defaultAutoAssembyWarehouse'], $data['defaultAutoAssembyLocation']);
			$data['defaultAutoAssembyLocation']  = $defaultAutoAssembyLocation['id'];
		} */
		$shopifyAccount = $this->db->get_where('account_brightpearl_account', array('id' => $data['brightpearlAccountId']))->row_array();
		if($data['dateType'] == "standard"){
			$data['deliveryDateCustomField'] = "";
		}
		$data['name'] = $shopifyAccount['accountName'];
		if($data['costPriceListbom'] != 'fifo'){
			$data['costPriceListbom'] = $data['costPriceListbomNonTrack'];
		}
		if($data['fetchSalesOrderStatusExclude']){
			$data['fetchSalesOrderStatusExclude'] = array_filter($data['fetchSalesOrderStatusExclude']);
			$data['fetchSalesOrderStatusExclude'] = implode(',', $data['fetchSalesOrderStatusExclude']);
		}else{
			$data['fetchSalesOrderStatusExclude'] = "";
		}
		if($data['displayWarehouses']){
			$data['displayWarehouses'] = array_filter($data['displayWarehouses']);
			$data['displayWarehouses'] = implode(',', $data['displayWarehouses']);
		}else{
			$data['displayWarehouses'] = "";
		}
		if($data['id']){
			$status = $this->db->where(array('id' => $data['id']))->update('account_brightpearl_config',$data);
		}
		else{			
			$saveConfig = $this->db->get_where('account_brightpearl_config', array('brightpearlAccountId' => $data['brightpearlAccountId']))->row_array();
			if($saveConfig){
				$data['id'] = $saveConfig['id'];
				$status = $this->db->where(array('id' => $data['id']))->update('account_brightpearl_config',$data);
			}
			else{
				$status = $this->db->insert('account_brightpearl_config',$data);
				$data['id'] = $this->db->insert_id();
			}
		}
		$data = $this->db->get_where('account_brightpearl_config',array('id' => $data['id'] ))->row_array();
		if($data['id']){
			$data['status'] = '1';
		}
		return $data;
	}
	public function getBinLocationByWarehouse($warehouseId, $binLocation){
		return $this->db->get_where('warehouse_binlocation',array('name' => $binLocation,'warehouseId' => $warehouseId))->row_array();
	}
	
	
}
?>