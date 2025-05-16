<?php
class Profile_model extends CI_Model
{
	public function getUserDetails(){
		if(@!$this->session_data){			
			return @$this->db->get_where('admin_user')->row_array();
		}
		else{
			return @$this->db->get_where('admin_user',array('user_id' => $this->session_data['user_session_data']['user_id']))->row_array();			
		}
	}
	public function getGlobalConfig(){
		return $this->db->get('global_config')->row_array();
	}
	public function saveBasic($data){
		if($this->session_data['user_session_data']['user_id']){
			$this->db->where(array('user_id' => $this->session_data['user_session_data']['user_id']))->update('admin_user',$data);
		}
	}
	public function updatePassword($data){
		$this->db->where(array('user_id' => $this->session_data['user_session_data']['user_id'] , 'password' => md5($data['password'])))->update('admin_user', array('password' => md5($data['newpassword'])));
		return $this->db->affected_rows();
	}
	public function update( $data = array() ){
		if(count($data)>0)
		{
			$this->db->where('user_id', $data['user_id']);
			return $this->db->update(ADMIN_USER, $data);	
 		}else{ 
			return false;
		}
	}
	public function uploadedProfiePic($data){
		$this->db->where(array('user_id' => $this->session_data['user_session_data']['user_id']))->update('admin_user',$data);
		return $this->db->affected_rows();
	}
	public function saveGlobalConfig($data){
		$getGlobalConfig = $this->getGlobalConfig();
		if(!$data['account2Name'])
			$data['account2Name'] 	  = 'brightpearl';
		
		if(!$data['account2Liberary'])
			$data['account2Liberary'] = 'brightpearl';
		
		if(!$data['fetchProduct'])
			$data['fetchProduct'] 	  = 'brightpearl';
	
		if(!$data['postProduct'])
			$data['postProduct']      = 'brightpearl';
		
		$this->db->where(array('id' => $getGlobalConfig['id']))->update('global_config',$data);
	}
 	
	
}
?>