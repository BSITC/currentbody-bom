<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once(APPPATH.'libraries/barcode/examples/tcpdf_include.php');
class Assembly extends MY_Controller {
	function __construct(){
		parent::__construct();			
		$this->load->model('products/assembly_model','',TRUE);
	}
	public function index(){
		$data = array();
		$user_session_data = $this->session->userdata('login_user_data');
		if($user_session_data['accessLabel'] == '3'){
			$accessLabelArray = array(
				'2',
				'3'
			);
			$this->db->where_in('accessLabel', $accessLabelArray);
		}
		$data['assignUserList'] = $this->db->select('user_id,username, firstname,lastname')->get_where('admin_user', array('is_active' => '1'))->result_array();
		$data['warehouseList'] = $this->assembly_model->getWarehouseMaster();
		$this->template->load_template("products/assembly",$data);
	}
	public function getProduct(){
		$records = $this->assembly_model->getProduct();
		echo json_encode($records);
	}
	public function fetchProducts($productId = ''){
		$this->assembly_model->fetchProducts($productId);
	}
	public function postProducts($productId = ''){
		$this->assembly_model->postProducts($productId);
	}
	public function generateBarcode($assemblyId = ''){
		if(!$assemblyId){
			return false;
		}  
		$this->brightpearl->reInitialize();
		$savedAssemblyDatas = $this->db->get_where('product_assembly',array('createdId' => $assemblyId, 'isAssembly' => '1'))->row_array();
		if($savedAssemblyDatas){
			$this->db->where_in('productId', $savedAssemblyDatas['productId']);
			$bomProductData = $this->db->select('productId,sku,name,CostPrice,retailPrice,isStockTracked,params,account1Id')->get_where('products')->row_array();
			$paramData 	 = json_decode($bomProductData['params'], true);
			$url 		 = '/product-service/product/'.$bomProductData['productId'];
			$Pdata 		 = $this->{$this->globalConfig['fetchProduct']}->getCurl($url);
			$productData = reset($Pdata[$bomProductData['account1Id']]);
			$cellvariantionName = array();$cellvariantion = '';$lengthVariation = '';$lengthVariationDatas = array();
			if($productData['variations']){
				foreach($productData['variations'] as $variation){
					if(strtolower($variation['optionName']) == 'color'){
						$cellvariantionName[] = $variation['optionValue'];
					}
					if(strtolower($variation['optionName']) == 'length'){
						$lengthVariationDatas[] = $variation['optionValue'];
						
					}
				}
			}
			if($cellvariantionName){
				$cellvariantionName = array_filter($cellvariantionName);
				$cellvariantionName = array_unique($cellvariantionName);
				$cellvariantion 	= implode(',', $cellvariantionName);
			}
			if($lengthVariationDatas){
				$lengthVariationDatas = array_filter($lengthVariationDatas);
				$lengthVariationDatas = array_unique($lengthVariationDatas);
				$lengthVariation 	  = implode(',', $lengthVariationDatas);
			}
			$priceList 	 = $this->{$this->globalConfig['fetchProduct']}->getProductPriceList($bomProductData['productId']);
			$priceListId = $this->{$this->globalConfig['fetchProduct']}->accountConfig[$bomProductData['account1Id']]['costPriceListbom'];
			$bomPrice 	 = sprintf ("%.2f",$priceList[$bomProductData['productId']][$priceListId]);
			$width		 =	'101';
			$height		 =	'25';
			$pageLayout  = 	array($width, $height); 
			$pdf 		 = new TCPDF('P', 'mm', $pageLayout, true, 'UTF-8', false);
			$pdf->SetAutoPageBreak(TRUE, 0);
			$pdf->SetCreator(PDF_CREATOR);
			//$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
			$pdf->setTitle("");
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
			$pdf->setMargins(2,5,0,0);
			// set default monospaced font
			$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

			// set font
			$pdf->SetFont('helvetica', '', 9);
			  
			// define barcode style
			$style = array(
							'position' => 'C',
							'align' => 'C',
							'stretch' => false,
							'fitwidth' => true,
							'cellfitalign' => '',
							'hpadding' => '2.5',
							'vpadding' => '0',
							'fgcolor' => array(0,0,0),
							'bgcolor' => false, //,
							'text' => false,
							'font' => 'helvetica',
							'fontsize' => 8,
							'stretchtext' => 4
						);
			// add a page ----------
			if($savedAssemblyDatas['qty'] > 0 && !empty($productData['identity']['barcode'])){
				for($i = '1'; $i <= (int)$savedAssemblyDatas['qty']; $i++){
					$pdf->AddPage('A13');
					// CODE 128 AUTO
					$pdf->setCellPaddings( 0, 0, 0, 0);
					$cellName = $productData['identity']['sku'];
					$dimension = $productData['stock']['dimensions'];
					$lengthInYards =  (($dimension['length']*$dimension['width']*$dimension['height'])/46656);
					$secondCell = "";
					/* if($bomPrice > 0){
						if($lengthInYards <= 0){
							$secondCell .= '$'.$bomPrice;
						}else{
							$secondCell .= '$'.$bomPrice.' | ';
						}
					} */
					if($cellvariantion){
						$secondCell .= $cellvariantion.',';
					}
					/* if($lengthInYards > 0){
						$secondCell .= 'Length: '.sprintf ("%.4f",$lengthInYards).'yd';
					} */
					if($lengthVariation){
						$secondCell .= $lengthVariation;
					}
					if($productData['identity']['sku']){
						$secondCell .= ' | '.$productData['identity']['sku'];
					}
					if($bomProductData['productId']){
						$secondCell .= ' | PID: '.$bomProductData['productId']; 
					}
					$pdf->Cell(0, 0, $productData['salesChannels']['0']['productName'], 0, 1,'C');
					$pdf->Cell(0, 0, $secondCell, 0, 1,'C');
					$pdf->write1DBarcode($productData['identity']['barcode'], 'C128', 0, '', 50, 5.2, 0.5, $style, 'N');
					$pdf->Ln();	
					
				} 
				$filename = $productData['identity']['sku'].strtotime(date('Y-m-d h:s')).'.pdf';
				$pdf->Output($filename, 'I');
			}
		}
	}

    public function editassembly($productId){
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
        $data['warehouseList'] = $this->assembly_model->warehouseList();
        $data['productStock'] = $this->assembly_model->getProductStock($proIdList);           
        $data['getAllPriceList'] = $this->assembly_model->getAllPriceList();  
        $data['allproducts'] = $this->db->select('productId,sku,name,CostPrice,retailPrice,isStockTracked')->get_where('products')->result_array();
        foreach ($data['allproducts'] as $allproducts) {
            @$data['productBySku'][$allproducts['sku']] = $allproducts;
        }
        $this->template->load_template("products/editassembly",$data);
    }
	public function viewassembly($createdId, $printView = ""){
        $data = array();      
		$data['assignUserList'] = $this->db->select('user_id,username, firstname,lastname')->get_where('admin_user', array('is_active' => '1'))->result_array();
		$data['warehouseList'] = $this->assembly_model->warehouseList();
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
        $data['allproducts'] = $this->db->get_where('product_assembly',array('createdId' => $createdId))->result_array();   
		if($printView){
			$this->printAssebmblyView($createdId,$data);
		}
        $this->template->load_template("products/viewassembly",$data);
    }
	
	public function printAssebmblyView($createdId, $data){
		$printHtml = "";
		$warehouseList = "";$getAllWarehouseLocation="";$recipeData="";$allproducts="";
		if($createdId){
			$warehouseList = $data['warehouseList'];
			$getAllWarehouseLocation = $data['getAllWarehouseLocation'];
			$recipeData  = $data['recipeData'];
			$allproducts = $data['allproducts'];
			if($allproducts){
				$printHtml .= '<div class="portlet containerss"><div class="portlet-title"><div class="caption" style="width: 100%;"><div class="table-container">';
				$printHtml .= '<table border="1" class="table table-striped table-bordered table-hover"><thead><tr><th style="width:200px;">Assembly&nbsp;&nbsp;&nbsp;&nbsp; Id</th><th>Product ID</th><th>Product SKU</th><th>Product Name</th><th>Warehouse</th><th>Bin Location</th><th>Qty</th><th>Recipe</th><th>Created By</th><th>Status</th><th>Created</th></tr></thead><tbody>';
				foreach($allproducts as $allproduct){
					if($allproduct['isAssembly'] == '1'){
						$status = "Work in Progress";
						if($allproduct['status'] == '1'){
							$status = "Completed";
						}
						$printHtml .= '<tr>
							<td style="word-break:break-all;width:200px;">'.$allproduct['createdId'].'</td>
							<td>'.$allproduct['productId'].'</td>
							<td style="word-break:break-all">'.$allproduct['sku'].'</td>
							<td style="word-break:break-all">'.$allproduct['name'].'</td>
							<td style="word-break:break-all">'.$warehouseList[$allproduct['warehouse']]['name'].'</td>
							<td>'.@$getAllWarehouseLocation[$allproduct['receipId']][$allproduct['locationId']]['name'].'</td>
							<td>'.$allproduct['qty'].'</td>
							<td>'.@$recipeData[$allproduct['receipId']]['recipename'].'</td>
							<td>'.$allproduct['username'].'</td>
							<td>'.$status.'</td>
							<td>'.date('M d,Y H:i:s',strtotime($allproduct['created'])).'</td> 
						</tr>';
					}
				}
				$printHtml .= '</tbody></table>';
				$printHtml .= '</div></div></div><div class="portlet-body"><div class="portlet-title"><div class="caption" style="width: 100%;"><h3 class="page-title"> Component Details</h3></div></div><div class="table-container"><table class="table table-striped table-bordered table-hover table-checkable receipecontainer datatable_products" id="datatable_products">	<thead><tr><th width="5%">Recipe #</th><th width="20%">Component Brightpearl Product Id</th><th width="20%">Component Brightpearl Product SKU</th><th width="20%">Component Brightpearl Product Name</th><th width="10%">Warehouse</th><th width="10%">Bin Location</th><th width="5%">Qty</th></tr></thead><tbody>';
			 
				foreach($allproducts as $allproduct){
					if($allproduct['isAssembly'] == '0'){
						$printHtml .='<tr>
							<td>'.$allproduct['receipId'].'</td>
							<td>'.$allproduct['productId'].'</td>
							<td>'.$allproduct['sku'].'</td>
							<td>'.$allproduct['name'].'</td>
							<td>'.@$warehouseList[$allproduct['warehouse']]['name'].'</td>
							<td>'.@$getAllWarehouseLocation[$allproduct['receipId']][$allproduct['locationId']]['name'].'</td>
							<td>'.$allproduct['qty'].'</td>
						</tr>';
					}
				}
				$printHtml .='</tbody></table></div></div></div>';
			}
		}
		echo $printHtml;exit();
	}
	public function editassemblyajax($sku = '',$assemblyId = ''){
		$sku = base64_decode($sku);
		$sku = rawurldecode($sku);
        $data = array();
        $proIdList = array();
		$customeName = explode(' -~- ',$sku);	
		$customeName = end($customeName);
        $data['products'] = $this->db->or_where(array('sku' => $sku,'name' => $sku,'sku' => $customeName))->get_where('products')->row_array(); 
        $data['productId'] = $data['products']['productId']; 
        $proIdList[] = $data['products']['productId'];
        $billcomponents = $this->db->get_where('product_bom',array('productId' => $data['products']['productId']))->result_array();
		$savedAssemblyTempDatas = array();
		if($assemblyId){
			$savedAssemblyDatas = $this->db->get_where('product_assembly',array('createdId' => $assemblyId, 'isAssembly !=' => '1'))->result_array();
			if($savedAssemblyDatas){
				foreach($savedAssemblyDatas as $savedAssemblyData){
					$savedAssemblyTempDatas[$savedAssemblyData['receipId']][$savedAssemblyData['productId']] = $savedAssemblyData;
				}
			}			
		}
	 
		foreach ($billcomponents as $billcomponent) {
		   $data['billcomponents'][$billcomponent['receipeId']][] = $billcomponent;
		   if($billcomponent['componentProductId']){
				$proIdList[] = $billcomponent['componentProductId'];
		   }
		  
		}

        $data['warehouseList'] = $this->assembly_model->warehouseListDb();
        $data['productStock'] = $this->assembly_model->getProductStock($proIdList); 
        $data['getAllPriceList'] = $this->assembly_model->getAllPriceList();  
        $data['savedAssemblyTempDatas'] = $savedAssemblyTempDatas;  
        $data['allproducts'] = $this->db->select('productId,sku,name,CostPrice,retailPrice,isStockTracked')->get_where('products')->result_array();
		$locationTemps   = $this->{$this->globalConfig['account1Liberary']}->getAllWarehouseLocation();
		$locationTempsDatas = array();
		foreach($locationTemps as $key => $locationTemp){
			foreach($locationTemp as $locationTem){
				$locationTempsDatas[$key][$locationTem['warehouseId']][$locationTem['name']] = $locationTem;
			}
		}
		foreach($locationTempsDatas as $lkey => $locationTempsData){
			ksort($locationTempsData);
			foreach($locationTempsData as $locationTempsDat){
				ksort($locationTempsDat);
				foreach($locationTempsDat as $lid =>  $locationTempsDa){
					$data['getAllWarehouseLocation'][$lkey][$locationTempsDa['id']]   = $locationTempsDa;
				}
			}
		}
		$data['orgWarehouseLocation'] = $locationTemps;
		$data['assemblyData'] = @$assemblyData;		
        foreach ($data['allproducts'] as $allproducts) {
            @$data['productBySku'][strtolower($allproducts['sku'])] = $allproducts;
        }
		$data['assemblyId'] = @$assemblyId;
		$assemblyDatas   = '';
		if($assemblyId){
			$assemblyDatas = $this->db->get_where('product_assembly',array('createdId' => $assemblyId))->result_array();
		}

		$data['assemblyDatas'] = @$assemblyDatas;
		$warehouseLocationAllDatas = array();
		$warehouseTempsLocations = $this->db->select('id,name')->get_where('warehouse_binlocation')->result_array();
		foreach($warehouseTempsLocations as $warehouseTempsLocation){
			$warehouseLocationAllDatas[$warehouseTempsLocation['id']] = $warehouseTempsLocation;
		}
		$data['warehouseLocationAllDatas'] = $warehouseLocationAllDatas;
		$assignUserListTemps = array();
		if($user_session_data['accessLabel'] == '3'){
			$accessLabelArray = array(
				'2',
				'3'
			);
			$this->db->where_in('accessLabel', $accessLabelArray);
		}
		$data['assignUserList'] = $this->db->select('user_id, firstname,lastname')->get_where('admin_user', array('is_active' => '1'))->result_array();
        $this->template->load_template("products/editassembly",$data,'','0'); 
    }

	public function addNewAssembly($name = '',$assemblyId = ''){
		$data = array();
		$temps = $this->db->select('productId,sku,name,CostPrice,retailPrice')->get_where('products',array('isBOM' => '1'))->result_array();
		$data['allproducts'] = array();
		foreach($temps as $temp){
			$temp['customName'] = trim(preg_replace('/\s+/', ' ', $temp['name'])) .' -~- '. trim($temp['sku']);
			$data['allproducts'][] = $temp;
		}
		$data['name'] = $name;
		$data['assemblyId']   = $assemblyId;
		$assemblyData   = '';
		if($assemblyId){
			$assemblyData = $this->db->get_where('product_assembly',array('createdId' => $assemblyId))->result_array();
		}

		$data['assemblyData'] = $assemblyData;
		$warehouseLocationAllDatas = array();
		$warehouseTempsLocations = $this->db->select('id,name')->get_where('warehouse_binlocation')->result_array();
		foreach($warehouseTempsLocations as $warehouseTempsLocation){
			$warehouseLocationAllDatas[$warehouseTempsLocation['id']] = $warehouseTempsLocation;
		}
		$user_session_data = $this->session->userdata('login_user_data');
		if($user_session_data['accessLabel'] == '3'){
			$accessLabelArray = array(
				'2',
				'3'
			);
			$this->db->where_in('accessLabel', $accessLabelArray);
		}
		$data['assignUserList'] = $this->db->select('user_id,username, firstname,lastname')->get_where('admin_user', array('is_active' => '1'))->result_array();
		$data['warehouseLocationAllDatas'] = $warehouseLocationAllDatas; 
		$this->template->load_template("products/addnewassembly",$data);
	}
	
    public function saveassembly($assemblyId = ""){
        $datas = $this->input->post('data');
        $this->assembly_model->saveassembly($datas, $assemblyId);
    }
	public function saveAssignUserId($assemblyData = ""){
		$assemblyId 	= explode("~",$assemblyData)['0'];
		$assignedUserId = explode("~",$assemblyData)['1'];
        return $this->assembly_model->saveAssignUserId($assignedUserId, $assemblyId);
    }
	public function viewlog($assemblyId = ""){
		$data = array();
		if(!$assemblyId){
			echo "Assembly Id Missing!";
			return false;
		}
		$filePath 	  = FCPATH.'logs'.DIRECTORY_SEPARATOR.'assembly'.DIRECTORY_SEPARATOR.$assemblyId.'.json';
		$assemblyDatas = file_get_contents($filePath);
		if($assemblyDatas){
			$data['assemblyLogData'] = json_decode($assemblyDatas, true);
		}else{
			$data['assemblyLogData'] = "";
		}
		$this->template->load_template("products/assemblylog",$data);
    }
}
