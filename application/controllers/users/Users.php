<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class users extends MY_Controller {
	
	public function __construct(){
		parent::__construct();
		$this->load->library('form_validation');		
		$this->load->model('users/users_model','',TRUE);
	}
	public function index(){
		$data = array();
		$this->template->load_template("users/users",array("data"=>$data));
	}
	public function getUsers(){
		$records = $this->users_model->getUsers();
		echo json_encode($records);
	}
	public function save(){
		$data = $this->input->post('data');
		$data['password'] = md5($data['password']);
		$res = $this->users_model->save($data);
		echo json_encode($res);
		die();
	}
	
	public function delete($id){
		if($id){
			echo $this->users_model->delete($id);
		}
	}
}
?>