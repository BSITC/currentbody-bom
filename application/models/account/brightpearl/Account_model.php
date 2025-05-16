<?php
class Account_model extends CI_Model{
	public function get(){
		$data = array();
		$data['data'] =  $this->db->get('account_brightpearl_account')->result_array();
		$account1IdTemps = $this->db->get('account_'.$this->globalConfig['account1Liberary'].'_account')->result_array();
		$account1Id = array();
		foreach ($account1IdTemps as $account1IdTemp) {
			$account1Id[$account1IdTemp['id']] = $account1IdTemp;
		}
		$data['account1Id'] =  $account1Id;
		return $data;
	}
	public function delete($id){
		$this->db->where(array('id' => $id))->delete('account_brightpearl_account');
	}
	public function save($data){
		$data['name'] = $data['accountName'];
		$data['url'] = 'https://ws-'.$data['dcCode'].'.brightpearl.com/public-api/'.$data['accountName'];
		$data['authUrl'] = 'https://ws-'.$data['dcCode'].'.brightpearl.com/'.$data['accountName'].'/authorise';
		if($data['id']){
			$data['status'] = $this->db->where(array('id' => $data['id']))->update('account_brightpearl_account',$data);
		}
		else{
			$data['status'] =  $this->db->insert('account_brightpearl_account',$data);
			$data['id'] = $this->db->insert_id();

		}
		return $data;
	}
}
?>