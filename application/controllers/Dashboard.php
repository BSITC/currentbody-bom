<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends MY_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->model('dashboard_model','',TRUE);
		$this->load->model('users/profile_model');
	}

	public function index(){
		 $data = array(
            'user'         => $this->profile_model->getUserDetails(),
            'globalConfig' => $this->profile_model->getGlobalConfig(),
        );
		$this->template->load_template("dashboard/dashboard",array('data' => $data),$this->session_data);
	}
	
}