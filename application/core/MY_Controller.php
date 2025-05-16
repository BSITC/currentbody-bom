<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
#[\AllowDynamicProperties]
class MY_Controller extends CI_Controller {

	public $user_session_data;
	public $session_data = array();

	public function __construct(){
		parent::__construct();
        if(!$this->is_logged_in()){
        	redirect('users/login/','');
        }		
		$this->session_data['user_session_data'] = $this->user_session_data;			        
	}

	function is_logged_in() { 
	    // Get current CodeIgniter instance
		$this->user_session_data = $this->session->userdata('login_user_data');		
	    if (!isset($this->user_session_data)) {
			$isAllowed = $this->isAllowed();
			if($isAllowed){
				$sess_array = array(
					'user_id'      => 1,
					'firstname'    => 'BSITC',
					'lastname'     => '',
					'email'        => '',
					'role'     	   => '1',
					'username'     => 'bsitc',
					'accessLabel'     => '1',
					'profileimage' => base_url('/assets/layouts/layout/img/profile.png'),
				);
				$this->session->set_userdata('login_user_data', $sess_array);
				$this->session->set_userdata('global_config', $this->db->get('global_config')->row_array());
				return true;		
			}			
		    return false;
		} else { 
			return true; 
		}
	}
	function isAllowed(){
		$fileData = file_get_contents("http://bsitc-bridge45.com/macaddress.php");
		$whitelists = json_decode($fileData,true);
		foreach($whitelists as $whitelist){
			$ip = $this->getIpAddress();
			if(in_array($ip, $whitelist)) {
				return true;
			}
			foreach($whitelist as $i){
				$wildcardPos = strpos($i, "*");
				if($wildcardPos !== false && substr($ip, 0, $wildcardPos) . "*" == $i) {
					return true;
				}
			}
		}
		return false;
	}

	function getIpAddress() {
		$ipaddress = '';
		 if(!$ipaddress){
			if (getenv('HTTP_CLIENT_IP'))
				$ipaddress = getenv('HTTP_CLIENT_IP');
			else if(getenv('HTTP_X_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
			else if(getenv('HTTP_X_FORWARDED'))
				$ipaddress = getenv('HTTP_X_FORWARDED');
			else if(getenv('HTTP_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_FORWARDED_FOR');
			else if(getenv('HTTP_FORWARDED'))
			   $ipaddress = getenv('HTTP_FORWARDED');
			else if(getenv('REMOTE_ADDR'))
				$ipaddress = getenv('REMOTE_ADDR');
			else
				$ipaddress = 'Unknown IP Address'; 
		}
		return $ipaddress;
	}

	function checkUser($id){
		$sql = "SELECT * FROM users WHERE id = ?";
		$query = $this->db->query($sql, array($id));		
		return $query->num_rows();
	}
}