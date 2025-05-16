<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Billsofmaterials extends MY_Controller {
	function __construct(){
		parent::__construct();			
		$this->load->model('products/billsofmaterials_model','',TRUE);
	}
	public function index(){
		$data = array();
		$this->template->load_template("products/billsofmaterials",$data);
	}
	public function getProduct(){
		$records = $this->billsofmaterials_model->getProduct();
		echo json_encode($records);
	}
	public function fetchProducts($productId = ''){
		$this->billsofmaterials_model->fetchProducts($productId);
	}
	public function postProducts($productId = ''){
		$this->billsofmaterials_model->postProducts($productId);
	}
    public function editbom($productId){
        $data = array();
        $data['productId'] = $productId;
		$accountId = 1;
		$compIds = array();
        $data['products']  = $this->db->get_where('products',array('productId' => $productId))->row_array();
        $billcomponents    = $this->db->get_where('product_bom',array('productId' => $productId))->result_array();
		$productMappings = array();
		$data['allproducts'] = $this->db->select('productId,sku,name,CostPrice, CostPrice as price')->get_where('products')->result_array();
		foreach ($data['allproducts'] as $allproducts) {
			if($allproducts['sku']){
				@$data['productBySku'][$allproducts['sku']] = $allproducts;
				$productMappings[$allproducts['productId']] = $allproducts;
			}
		}
        foreach ($billcomponents as $billcomponent) {
			$billcomponent['price'] = $productMappings[$billcomponent['componentProductId']]['CostPrice'] ? $productMappings[$billcomponent['componentProductId']]['CostPrice'] : 0.00;
			$data['billcomponents'][$billcomponent['receipeId']]['items'][] = $billcomponent;
			$data['billcomponents'][$billcomponent['receipeId']]['recipe'] = array(
				'recipeId' 		=> $billcomponent['receipeId'],
				'recipename' 	=> $billcomponent['recipename'],
				'bomQty' 		=> $billcomponent['bomQty'],
				'isPrimary' 	=> $billcomponent['isPrimary'],
				'recipeOrder' 	=> $billcomponent['recipeOrder'],
			);
        }
		$data['getAllWarehouseLocation']   = $this->{$this->globalConfig['account1Liberary']}->getAllWarehouseLocation('', 'yes');
        $this->template->load_template("products/editbom",$data);
    }
    public function saveReceipe($productId){		
        $dataArray = $this->input->post('data');
        if(($dataArray)&&(is_array($dataArray))){
			$autoAssemble 		= (int) $this->input->post('autoAssemble');
			$autoBomPriceUpdate = (int) $this->input->post('autoBomPriceUpdate');
			$binlocation = (int) $this->input->post('binlocation');
			$this->db->where(array('productId' => $productId))->update('products',array('autoBomPriceUpdate' => $autoBomPriceUpdate,'autoAssemble' => $autoAssemble,'binlocation' => $binlocation));
            $this->billsofmaterials_model->saveReceipe($productId,$dataArray);
        }
        $this->editbom($productId);
    }
    public function deletebom($bomid = ''){
        if($bomid){
            $this->db->where(array('id' => $bomid ))->delete('product_bom');
        }
    }

    public function exportboms(){
        error_reporting('0');
		$allLocation   = @$this->{$this->globalConfig['account1Liberary']}->getAllWarehouseLocation();
		$productDatas = array();
		$productDatasTemps = $this->db->select('productId,sku,autoAssemble,account1Id,binlocation,autoBomPriceUpdate')->order_by('productId','desc')->get('products')->result_array();
		foreach($productDatasTemps as $productDatasTemp){
			$productDatas[$productDatasTemp['productId']] = $productDatasTemp;
		}
        $proDatas = $this->db->get('product_bom')->result_array();
        $filepath = date('Ymd') . 'BOM.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . basename($filepath));
        $file   = fopen('php://output', 'w');
        $header = array('BOMId','BOMQty','AutoAssembly','AutoPriceUpdate','DefaultBinLocation','compProdId','compProdName','compProdSku','compProdQty','RecipeId','RecipeName','PrimaryRecipe','recipeOrder', 'deleteBom');
        fputcsv($file, $header); 
        foreach ($proDatas as $key => $proData) {
            $row = 	array(
						$proData['productId'],
						$proData['bomQty'],
						$productDatas[$proData['productId']]['autoAssemble'] == "" ? '0' : $productDatas[$proData['productId']]['autoAssemble'],
						$productDatas[$proData['productId']]['autoBomPriceUpdate'] == "" ? '0' : $productDatas[$proData['productId']]['autoBomPriceUpdate'],
						@$allLocation[$productDatas[$proData['productId']]['account1Id']][$productDatas[$proData['productId']]['binlocation']]['name'],
						$proData['componentProductId'],
						$proData['name'],
						$proData['sku'],
						$proData['qty'],
						$proData['receipeId'],
						$proData['recipename'],
						$proData['isPrimary'],
						$proData['recipeOrder'],
						''
					);
            fputcsv($file, $row);
        }
        fclose($file);
    }
	public function getWarehouseBinLocationFromDB(){
		$warehouseBinlocationsTemps = $this->db->get_where('warehouse_binlocation')->result_array();
		$warehouseBinlocations = array();
		if($warehouseBinlocationsTemps){
			foreach($warehouseBinlocationsTemps as $warehouseBinlocationsTemp){
				$warehouseBinlocations[$warehouseBinlocationsTemp['accountId']][$warehouseBinlocationsTemp['id']] = $warehouseBinlocationsTemp;
			}
		}
		return $warehouseBinlocations;
	}
    public function importboms(){
		ini_set('max_input_time', 3000000);
		ini_set('max_execution_time', 3000000);
        error_reporting('0');
		$allLocations   = @$this->getWarehouseBinLocationFromDB();
		$locations = array();
		foreach($allLocations as $allLocation){
			foreach($allLocation as $locId => $locs){
				$locations[$locs['name']] = $locs;
			}
		}
        $columnRow = 3;
        if ($_FILES['uploadprefile']['tmp_name']) {
            $file  = fopen($_FILES['uploadprefile']['tmp_name'], "r");
            $count = 0; $autoAssembleProductIds = array();$deletedProList = array();$updateAsseblyProductIds = array();
            while (!feof($file)) {
                $row = fgetcsv($file);
                if ($count++ == 0) {
                    $columnRow = count($row); 
                    continue;
                }
                if($row){
					$productId = $row['0'];
					//if($productId!= 1007){continue;}
					$receipeId = $row['9'];
					$componentProductId = $row['5'];
					$binlocation = '';
					if($row['4']){
						$binlocation = @$locations[$row['4']]['id'];
					}
					if($row['13']){
						$this->db->where(array('productId' => $productId,'componentProductId' => $componentProductId,'receipeId' => $receipeId))->delete('product_bom');
					}
					else{
						if(!$receipeId){
							$saveRow = $this->db->select('receipeId')->order_by('receipeId','DESC')->where(array('productId' => $productId))->get('product_bom')->row_array();		
							$receipeId = ($saveRow['total'] > 0)? sprintf("%02s", ($row['receipeId'] + 1)):'1';
						}
						if(@!$deletedProList[$productId]){
							$this->db->where(array('productId' => $productId))->delete('product_bom');
							$deletedProList[$productId] = $productId;
						}
						$autoPriceUpdateCheck = ($row['3'])?($row['3']):0;
						$autoAssembleCheck = ($row['2'])?($row['2']):0;						
						$autoAssembleProductIds[$productId] = array(
							'productId'			 => $productId,
							'autoAssemble' 		 => $autoAssembleCheck,
							'autoBomPriceUpdate' => $autoPriceUpdateCheck, 
							'binlocation' 		 => $binlocation,
						);
						//$updateAsseblyProductIds[$productId] = $productId;
						$insertArray = array(
							'productId' 			=> $productId,
							'bomQty' 				=> $row['1'],
							'componentProductId' 	=> $componentProductId,
							'name' 					=> $row['6'],
							'sku' 					=> $row['7'], 
							'qty' 					=> $row['8'],
							'receipeId' 			=> $receipeId,
							'recipename' 			=> $row['10'],
							'isPrimary' 			=> $row['11'],
							'recipeOrder' 			=> $row['12'],
						);
						$this->db->replace('product_bom',$insertArray); 
					}                    
                }
            }
			if($autoAssembleProductIds){
				$updateOrder = 200;
				$autoAssembleProductIdss = array_chunk($autoAssembleProductIds,$updateOrder,true); 
				foreach($autoAssembleProductIdss as $autoAssembleProductId){
					$this->db->update_batch('products', $autoAssembleProductId,'productId'); 
				}
			}
            fclose($file);
			/* if($updateAsseblyProductIds){
				foreach($updateAsseblyProductIds as $productId => $updateAsseblyProductId){
					$productLogs = $this->db->get_where('product_bom',array('productId' => $productId))->result_array();
					if($productLogs){
						$path = FCPATH.'logs'.DIRECTORY_SEPARATOR.'bom'.DIRECTORY_SEPARATOR . $productId. DIRECTORY_SEPARATOR. date("Ymd-His-").strtoupper(uniqid()).'.logs';
						if(!is_dir(dirname($path))) { mkdir(dirname($path),0777,true);chmod(dirname($path), 0777); }
						$logs = json_encode($productLogs);
						file_put_contents($path,$logs,FILE_APPEND);
					}
				}			 
			} */
        }
        redirect($_SERVER['HTTP_REFERER'] , 'refresh');
    }

}
