<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Deassembly extends MY_Controller {
	function __construct(){
		parent::__construct();			
		$this->load->model('products/deassembly_model','',TRUE);
	}
	public function index(){
		$data = array();
		$this->template->load_template("products/deassembly",$data);
	}
	public function getProduct(){
		$records = $this->deassembly_model->getProduct();
		echo json_encode($records);
	}
	public function fetchProducts($productId = ''){
		$this->deassembly_model->fetchProducts($productId);
	}
	public function postProducts($productId = ''){
		$this->deassembly_model->postProducts($productId);
	}
    public function editdeassembly($productId){
        $data = array();
        $proIdList = array();
        $proIdList[] = $productId;
        $data['productId'] = $productId;
        $data['products'] = $this->db->get_where('products',array('productId' => $productId))->row_array();
        $billcomponents = $this->db->get_where('product_bom',array('productId' => $productId))->result_array();
        foreach ($billcomponents as $billcomponent) {
           $data['billcomponents'][$billcomponent['receipeId']][] = $billcomponent;
           if($billcomponent['componentProductId'])
                $proIdList[] = $billcomponent['componentProductId'];
        }
        $data['warehouseList'] = $this->deassembly_model->warehouseList();
        $data['productStock'] = $this->deassembly_model->getProductStock($proIdList);           
        $data['getAllPriceList'] = $this->deassembly_model->getAllPriceList();  
        $data['allproducts'] = $this->db->select('productId,sku,name,CostPrice,retailPrice,isStockTracked')->get_where('products')->result_array();
		$data['getAllWarehouseLocation']   = $this->{$this->globalConfig['account1Liberary']}->getAllWarehouseLocation();
		foreach($data['getAllWarehouseLocation'] as $getAllWarehouseLocation){
			$data['getAllWarehouses'] = $getAllWarehouseLocation;
		}
        foreach ($data['allproducts'] as $allproducts) {
            @$data['productBySku'][$allproducts['sku']] = $allproducts;
        }
        $this->template->load_template("products/editdeassembly",$data);
    }
	public function viewdeassembly($createdId){
        $data = array();      
		$data['warehouseList'] = $this->deassembly_model->warehouseList();
		$warehouse_binlocation = $this->db->get('warehouse_binlocation')->result_array(); 
		$warehouseLocationMapping = array();
		foreach($warehouse_binlocation as $warehouse_binlocatio){
			$warehouseLocationMapping[$warehouse_binlocatio['warehouseId']][$warehouse_binlocatio['id']] = $warehouse_binlocatio;
		}
		$data['getAllWarehouseLocation']   = $warehouseLocationMapping;
		$recipeDatas = $this->db->get('product_bom')->result_array();
		foreach($recipeDatas as $recipeData){
			$data['recipeData'][$recipeData['productId']][$recipeData['receipeId']] = $recipeData;
		}
        $data['allproducts'] = $this->db->get_where('product_deassembly',array('createdId' => $createdId))->result_array();   
        $this->template->load_template("products/viewdeassembly",$data);
    }
	
	public function editdeassemblyajax($sku){
		$sku = base64_decode($sku);
		$sku = rawurldecode($sku);
		$customeName = explode(' -~- ',$sku);	
		$customeName = end($customeName);	
        $data = array();
        $proIdList = array();
        $data['products'] = $this->db->or_where(array('sku' => $sku,'name' => $sku,'sku' => $customeName))->get_where('products')->row_array(); 
        $data['productId'] = $data['products']['productId']; 
        $proIdList[] = $data['products']['productId'];
        $billcomponents = $this->db->get_where('product_bom',array('productId' => $data['products']['productId']))->result_array();
		$componentProductsList = array();
        foreach ($billcomponents as $billcomponent) {
           $data['billcomponents'][$billcomponent['receipeId']][] = $billcomponent;
           if($billcomponent['componentProductId']){
                $proIdList[] = $billcomponent['componentProductId'];
			   $componentProductsList[] = $billcomponent['componentProductId'];
		   }
        }
		if($componentProductsList)
			$componentProductsList = array_unique($componentProductsList);
		
		$productMappings = array();
		$this->db->where_in('productId', $componentProductsList);
		$productMappingsTemps = $this->db->select('*')->get_where('products')->result_array();
		foreach($productMappingsTemps as $productMappingsTemp){
			$productMappings[strtolower($productMappingsTemp['productId'])] = $productMappingsTemp;
		}
		$saveBinDatasss = array();
		$saveBinDatasTempss = $this->db->get_where('warehouse_binlocation')->result_array();
		foreach($saveBinDatasTempss as $saveBinDatasTemp){
			$saveBinDatasss[$saveBinDatasTemp['warehouseId']][$saveBinDatasTemp['id']] = $saveBinDatasTemp['name'];
		}
		
		$defaultWarehouseBinlocation = array();
		foreach($data['billcomponents'] as $recId => $components){
			foreach($components as $component){
				$pData = $productMappings[$component['componentProductId']];
				if($pData){
					$params = json_decode($pData['params'], true);
					foreach($params['warehouses'] as $pWarehouseId => $productWarehouses){
						if($productWarehouses['defaultLocationId']){
							$defaultWarehouseBinlocation[$component['componentProductId']][$pWarehouseId] = $saveBinDatasss[$pWarehouseId][$productWarehouses['defaultLocationId']]; 
						}
					}
				}
			}
		}
		$data['defaultWarehouseBinlocation'] = $defaultWarehouseBinlocation;
		if($proIdList){
			$data['warehouseList'] = $this->deassembly_model->warehouseListDb();
			$data['productStock'] = $this->deassembly_model->getProductStock($proIdList);           
			$data['getAllPriceList'] = $this->deassembly_model->getAllPriceList();  
			$data['allproducts'] = $this->db->select('productId,sku,name,CostPrice,retailPrice,isStockTracked')->get_where('products')->result_array();
			$data['getAllWarehouseLocation']   = $this->{$this->globalConfig['account1Liberary']}->getAllWarehouseLocation();
			foreach($data['getAllWarehouseLocation'] as $getAllWarehouseLocation){
				$data['getAllWarehouses'] = $getAllWarehouseLocation;
			}
			foreach ($data['allproducts'] as $allproducts) {
				@$data['productBySku'][strtolower($allproducts['sku'])] = $allproducts;
			}
			$config = reset($this->{$this->globalConfig['account1Liberary']}->accountConfig);
			$priceListId = $config['costPriceListbom'];
			if($priceListId == 'fifo'){
				$priceListId = $config['costPriceListbomNonTrack'];
			}
			$allproducts = $this->{$this->globalConfig['account1Liberary']}->getProductPriceList($proIdList,$priceListId);
			foreach($allproducts as $pId => $allproduct){
				foreach($allproduct as $allprod){					
					$data['getProductPrice'][$pId] = $allprod;
				}				
			}
		}
		
        $this->template->load_template("products/editdeassembly",$data,'','0'); 
    }	 
	public function addNewDeassembly(){
		$data = array();
		$temps = $this->db->select('productId,sku,name,CostPrice,retailPrice')->get_where('products',array('isBOM' => '1'))->result_array();
		$data['allproducts'] = array();
		foreach($temps as $temp){
			$data['allproducts'][] = array(
				'productId' => $temp['productId'],
				'sku' 		=> $temp['sku'],
				'name' 		=> trim(preg_replace('/\s+/', ' ', $temp['name'])),
				'CostPrice' => $temp['CostPrice'],
				'retailPrice' => $temp['retailPrice'],
				'customName' => trim(preg_replace('/\s+/', ' ', $temp['name'])) .' -~- '. trim($temp['sku']),
			);
		}
		$this->template->load_template("products/addnewdeassembly",$data);
	}
    public function saveDeassembly(){
        $datas = $this->input->post('data');
        $this->deassembly_model->saveDeassembly($datas);
    }
	public function viewlog($assemblyId = ""){
		$data = array();
		if(!$assemblyId){
			echo "Disassembly Id Missing!";
			return false;
		}
		$filePath 	  = FCPATH.'logs'.DIRECTORY_SEPARATOR.'disassembly'.DIRECTORY_SEPARATOR.$assemblyId.'.json';
		$assemblyDatas = file_get_contents($filePath);
		if($assemblyDatas){
			$data['disassemblyLogData'] = json_decode($assemblyDatas, true);
		}else{
			$data['disassemblyLogData'] = "";
		}
		$this->template->load_template("products/disassemblylog",$data);
    }
}
