<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Products extends MY_Controller {
	function __construct(){
		parent::__construct();			
		$this->load->model('products/products_model','',TRUE);
	}
	public function index(){
		$data = array();
		$this->template->load_template("products/products",$data);
	}
	public function getProduct(){
		$records = $this->products_model->getProduct();
		echo json_encode($records);
	}
	public function fetchProducts($productId = ''){
		$this->products_model->fetchProducts($productId);
	}
	public function postProducts($productId = ''){
		$this->products_model->postProducts($productId);
	}
	public function preproducts()
    {
        $data = array();
		$this->template->load_template("products/preproducts",$data);
    }
    public function getPreproductsProduct(){
		$records = $this->products_model->getProduct();
		echo json_encode($records);
	}
	public function getVarient(){
		$productGroupId = $this->input->post('productGroupId');
		$color = $this->input->post('color');
		$length = $this->input->post('length');
		$datas = $this->db->select('sku,name,ean,upc,productId,productGroupId,color,size,length')->get_where('products',array('productGroupId' => $productGroupId,'color' => $color,'length' => $length))->result_array();
		$str = '<table class ="table" ><thead> <tr><th>Product Id</th><th>Name</th><th>SKU</th><th>Sleeve length</th><th>Color</th><th>Size</th></tr></thead><tbody>';
		foreach($datas as $data){
			$str .= '<tr><td>'.$data['productId'].'</td><td>'.$data['name'].'</td><td>'.$data['sku'].'</td><td>'.$data['length'].'</td><td>'.$data['color'].'</td><td>'.$data['size'].'</td></tr>';
		}
		$str .= '</tbody></table>';
		echo $str;		
	}
	public function downlaodProductCsv()
    {
        error_reporting('0');
        $proDatas = $this->db->group_by('newSku')->get('products')->result_array();
        $filepath = date('Ymd') . 'PRE.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . basename($filepath));
        $file   = fopen('php://output', 'w');
        $header = array('Style Number', 'Prebook');
        fputcsv($file, $header);
        foreach ($proDatas as $key => $proData) {
            $row = array($proData['newSku'], ($proData['prebook']) ? ('Y') : 'N');
            fputcsv($file, $row);
        }
        fclose($file);
        die();
    }
    public function uploadprebook()
    {
        error_reporting('0');
        $columnRow = 3;
        if ($_FILES['uploadprefile']['tmp_name']) {
            $file  = fopen($_FILES['uploadprefile']['tmp_name'], "r");
            $count = 0;
            while (!feof($file)) {
                $row = fgetcsv($file);
                if ($count++ == 0) {
                	$columnRow = count($row);
                    continue;
                }
                if($row){
                	$status = '0';$prebookUpdateToday = 0;                	
                	if($columnRow > 10){
                		if(strtolower(trim($row['13'])) == 'prebook'){
	                		$status = '1'; $prebookUpdateToday = '1';
	                	}   
                		$this->db->where(array('newSku' => trim($row['0'])))->update('products',array('prebook' => $status , 'prebookUpdateToday' => $prebookUpdateToday ));
                	}
                	else{
                		if(strtolower(trim($row['1'])) == 'y'){
	                		$status = '1'; $prebookUpdateToday = '1';
	                	}
	                	$this->db->where(array('newSku' => trim($row['0'])))->update('products',array('prebook' => $status , 'prebookUpdateToday' => $prebookUpdateToday )); 
	                }
                }
            }
            fclose($file);
        }
        redirect($_SERVER['HTTP_REFERER'] , 'refresh');
    }

}
