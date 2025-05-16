<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sales extends MY_Controller {
	function __construct(){
		parent::__construct();			
		$this->load->model('sales/sales_model','',TRUE);
		$this->load->model('products/assembly_model','',TRUE);
	}
	public function index(){
		$data = array();
		$data['warehouseList'] = $this->assembly_model->getWarehouseMaster();
		$this->template->load_template("sales/sales",$data,$this->session_data);
	}
	public function getSales(){
		$records = $this->sales_model->getSales();
		echo json_encode($records);
	}
	public function salesInfo($orderId = ''){
		$data['salesInfo'] = $this->db->get_where('sales_order',array('orderId' => $orderId))->row_array();
		$this->template->load_template("sales/salesInfo",$data,$this->session_data);
	}
	public function salesItem($orderId){
		$data = array();
		$data['orderInfo'] = $this->db->get_where('sales_order',array('orderId' => $orderId))->row_array();
		$data['items'] = $this->sales_model->getSalesItem($orderId);
		$this->template->load_template("sales/salesItem",$data,@$this->session_data); 
	}
}