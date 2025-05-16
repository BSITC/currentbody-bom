<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Account extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->library('form_validation');		
		$this->load->model('account/'. $this->globalConfig['account1Liberary'] .'/account_model');	
	}
	public function index(){
		$data = array();
		$type = 'account1';
		$data = $this->account_model->get($type);
		$data['type'] = $type;
		$this->template->load_template("account/". $this->globalConfig['account1Liberary'] ."/account",array("data"=>$data));		
	}
	public function save(){
		$data = $this->input->post('data');		
		$res = $this->account_model->save($data);
		echo json_encode($res);
		die();
	}
	public function delete($id){
		if($id){
			echo $this->account_model->delete($id);
		}
	}
}
?>