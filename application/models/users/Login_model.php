<?php
class Login_model extends CI_Model
{
	public function login($username, $password)
	{
		$this -> db -> select('*');
		$this -> db -> from(ADMIN_USER);
		$this -> db -> where('username', $username);
		$this -> db -> where('password', MD5($password));
		//$this -> db -> where('is_active', 1);
		$this -> db -> limit(1);
		$query = $this -> db -> get();

		if($query -> num_rows() == 1){
			return $query->result();
		}else {
			return false;
		}
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
 	public function getUserByEmail($email){
		if(!$email){
			return false;
		}
		$this -> db -> select('*');
		$this -> db -> from(ADMIN_USER);
		$this -> db -> where('email', $email);
		$this -> db -> where('is_active', 1);
		$this -> db -> limit(1);
		$query = $this->db->get();
		$getEmail = $query->result()['0']->email;
		if($getEmail){
			return $query->result();
		}else {
			return false;
		}
	}
	public function getverifyCode($verifyCode){
		if(!$verifyCode){
			return false;
		}
		$this -> db -> select('*');
		$this -> db -> from(ADMIN_USER);
		$this -> db -> where('varificationCode', $verifyCode);
		$this -> db -> where('is_active', 1);
		$this -> db -> limit(1);
		$query = $this->db->get();
		$getEmail = $query->result()['0']->varificationCode;
		if($getEmail){
			return true;
		}else {
			return false;
		}
	}
	public function updateUserDataByEmail($email,$varificationCode){
		$data = array();
		$this->db->where('email', $email);
		$data['varificationCode'] = $varificationCode;
		return $this->db->update(ADMIN_USER, $data);	
	}
	public function updateForgetPassword($newpassword,$varificationCode){
		$data = array();
		$this->db->where('varificationCode', $varificationCode);
		$data['password'] = $newpassword;
		return $this->db->update(ADMIN_USER, $data);	
	}
	public function updateVerification($varificationCode){
		$data = array();
		$this->db->where('varificationCode', $varificationCode);
		$data['varificationCode'] = '';
		return $this->db->update(ADMIN_USER, $data);	
	}
	
}
?>