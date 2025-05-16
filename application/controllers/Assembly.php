<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
	class Assembly extends CI_Controller {
	public $file_path,$productMapping;
	public function __construct(){
		parent::__construct();
	}
	/*Start OAuth2.0*/
	public function generateToken(){
		$return = array();
		$object			= file_get_contents("php://input");
		$data = json_decode($object,true);
		if($this->input->method()!="post"){
			$return['error'] = "Please send request in post method";
			echo json_encode($return);
			die();
		}else{
			$Client_id = $data['client_id'];
			$Client_secret = $data['client_secret'];
			$grant_type = $data['grant_type'];
			$clientDetails = $this->db->get_where('oauth_clients', array('client_id' => $Client_id,'client_secret' => $Client_secret,'grant_types' => $grant_type))->row_array();
			if(!$clientDetails){
				$return['error'] = "Invalid Authentication Details!";
				echo json_encode($return);
				die();
			}else{
				$tokenDetails = $this->db->get_where('oauth_access_tokens', array('DATE(expires) >' => date('Y-m-d H:i:s'), 'client_id' => $Client_id))->row_array();
				if($tokenDetails && $tokenDetails['expires'] > date('Y-m-d H:i:s')){
					$access_token = str_replace("Bearer", "", $tokenDetails['access_token']);
					$return['access_token'] = trim($access_token);
					$return['scope'] = "oauth";
					$return['token_type'] = "Bearer";
					$return['expire'] = strtotime($tokenDetails['expires']);
					echo json_encode($return);
					die();
				}else{
					$token = openssl_random_pseudo_bytes(30);
					$token = bin2hex($token);
					$expireDate = date('Y-m-d H:i:s', strtotime('+7 days'));
					$insertArrays = array('access_token' => $token,'client_id' => $Client_id,'user_id' => 'bomapp','expires' => $expireDate,'scope' => 'bsitc');
					$this->db->insert('oauth_access_tokens',$insertArrays);

					$return['access_token'] = $token;
					$return['scope'] = "oauth";
					$return['token_type'] = "Bearer";
					$return['expires_in'] = strtotime($expireDate);
					echo json_encode($return);
					die();
					// echo "Client_id :".$Client_id;
					// echo "Client_secret :".$Client_secret;
					// echo "grant_type :".$grant_type;
					// $method = $this->request->getMethod();
					// echo 'method :'.$method;
					// $headers=array();
					// foreach (getallheaders() as $name => $value) {
					//     $headers[$name] = $value;
					// }
					// print_r($headers);die();
				}
			}
		}
		die();
	}
	/*End OAuth2.0*/
	/*Start Import With OAuth2*/
	public function importApi(){
		header("Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type,      Accept");
		$headers=array();$return = array();
		foreach (getallheaders() as $name => $value) {
			$headers[$name] = $value;
		} 
		if(!@$headers['Authorization']){
			$return['error'] = "Authorization Token is missing";
			echo json_encode($return);
			die();
		}else{
			// chmod("/var/www/vhosts/bsolbomtest.bsitc-apps.com/httpdocs/bomdevapp/index.php", 0777);
			//echo "IP ".$this->get_client_ip()."\nStart time : ".$startTime."\nServer Info : ".json_encode($_SERVER)."\nRequest url :".$url."\n".$object;
			$access_token = str_replace("Bearer", "", $headers['Authorization']);
			$tokenDetails = $this->db->get_where('oauth_access_tokens',array('access_token' => trim($access_token)))->row_array();
			if(!$tokenDetails){
				$return['error'] = "Authorization Token is Invalid";
				echo json_encode($return);
				die();
			}else if($tokenDetails['expires'] < date('Y-m-d H:i:s')){
				$return['error'] = "Authorization Token is expired";
				echo json_encode($return);
				die();
			}else{
				$url = $_SERVER['REQUEST_URI'];
				$startTime = strtotime("now");
				$object = file_get_contents("php://input"); 
				$filepath = dirname(dirname(dirname(__FILE__))).'/assemblyImportAPI/'.date('Y').'/'.date('m').'/'.date('d').'/'.basename($url);
				if(!is_dir(($filepath))){
					mkdir(($filepath),0777,true);
					chmod(($filepath), 0777);
				}
				$startFileName = date('Y-m-d_H-i-s ').uniqid();
				$myfile = fopen($filepath.'/'.$startFileName.'.txt', "w");
				fwrite($myfile, "\nStart time : ".$startTime."\nServer Info : ".json_encode($_SERVER)."\nRequest url :".$url."\n Request data : ".$object);				
				if($object){
					$data 	= json_decode($object, true);
					if($data){
						if(count($data)<='200'){
							$missingKeys = array();
							$missingValues = array();
							$fixedColumnHeaders = array('sku', 'warehouse', 'recipe_name', 'qty', 'reference');
							foreach($data as $datas){
								foreach($datas as $key =>$datass){
									if(!in_array($key,$fixedColumnHeaders)){
										$missingKeys[] = $key;
									}
									if($key!="reference" && $datass==''){
										$missingValues[] = $key;
									}
								}
							}
							if(!empty($missingKeys)){
								$missingKeyss = array_unique($missingKeys);
								$return['error'] = "Invalid array keys found - ".implode(",",$missingKeyss);
								fwrite($myfile, "\n error : Invalid array keys found - ".implode(",",$missingKeyss));	
								echo json_encode($return);
								die();
							}elseif(!empty($missingValues)){
								$missingValuess = array_unique($missingValues);
								$return['error'] = "Missing values found in the data!";
								fwrite($myfile, "\n error : Missing values found in the data! ");
								echo json_encode($return);
								die();
							}else{
								$uniqueFileId 	= 'IA'.strtoupper(uniqid()).time();
								fwrite($myfile, "\n success : 200. unique_import_id : ".$uniqueFileId);
								$this->db->insert('api_import_data', array('uniqueFileId'=>$uniqueFileId,'data'=>$object));
								$return['success'] = "200";
								$return['unique_import_id'] = $uniqueFileId;
								echo json_encode($return);
								die();
								
							}
						}else{
							fwrite($myfile, "\n error : Please send up to 200 items in a single import.");
							$return['error'] = "Please send up to 200 items in a single import.";
							echo json_encode($return);
							die();
							
						}
					}else{
						fwrite($myfile, "\n error : Please input bom details to perform assembly. ");
						$return['error'] = "Please input bom details to perform assembly.!";
						echo json_encode($return);
						die();
					}
				}
				die();
			}
		}
	}
	public function importInDatabase(){
		$data =	array();
		$data = $this->db->get_where('api_import_data',['status' =>'0'])->result_array();
		if(!empty($data)){
			$productMapping = array();
			$productMappingTemps = $this->db->select('productId,sku')->get_where('products',array('isLive' => '1','isBOM' => '1'))->result_array();
			if($productMappingTemps){
				foreach($productMappingTemps as $productMappingTemp){
					$productMapping[trim(strtolower($productMappingTemp['sku']))] = $productMappingTemp;
				}
			}
			$productBomMappings = array();
			$bomRecipeDatas = array();
			$porductBomTemps = $this->db->select('productId, recipename,receipeId')->get_where('product_bom')->result_array();
			if($porductBomTemps){
				foreach($porductBomTemps as $porductBomTemp){
					$productBomMappings[trim($porductBomTemp['productId'])] = $porductBomTemp['recipename'];
					$bomRecipeDatas[$porductBomTemp['productId']][trim(strtolower($porductBomTemp['recipename']))] = $porductBomTemp;
				}
			}
			$warehouseMappings = array();
			$warehouseMappingsTemps = $this->db->select('warehouseName, warehouseId')->get_where('warehouse_master')->result_array();
			if($warehouseMappingsTemps){
				foreach($warehouseMappingsTemps as $warehouseMappingsTemp){
					$warehouseMappings[trim(strtolower($warehouseMappingsTemp['warehouseName']))] = $warehouseMappingsTemp;
				}
			}
			foreach($data as $datas){
				$importDatas 	= json_decode($datas['data'],true);
				$uniqueFileId 	= $datas['uniqueFileId'];
				$importID 		= $datas['id'];
				$importFileDatas = array();
				foreach($importDatas as $importDatass){
						$importFileDatas[] = array(
							'bomSku'		=> trim($importDatass['sku']),
							'warehouse'		=> trim($importDatass['warehouse']),
							'receipeId'		=> trim($importDatass['recipe_name']),
							'qty'			=> trim($importDatass['qty']),
							'reference'		=> trim($importDatass['reference']), 
						);
				}
				if($importFileDatas){
					$totalNoOfBom = count($importFileDatas);
					$fileName = 'APIImport'.strtoupper(uniqid()).time();
					$newname 		= FCPATH.'assemblyImport/'.$fileName;
					if(!is_dir(dirname($newname))) { @mkdir(dirname($newname),0777,true);@chmod(dirname($newname), 0777); }
					$fp1 = fopen($newname.'.csv', "w");
					$importColumnHeaders = array('bom sku', 'warehouse', 'recipename', 'qty', 'reference');
					fputcsv($fp1, $importColumnHeaders);
					foreach($importFileDatas as $importFileDatass){
						fputcsv($fp1, $importFileDatass);
					}
					$saveAssemblyFileData = array(
						'uniqueFileId' 	 => $uniqueFileId,
						'importFileName' => $fileName,
						'toShowFilename' => $fileName,
						'isProcessed' 	 => '0',
						'totalBom' 	 	 => $totalNoOfBom,
					);
					$res = $this->db->insert('import_assembly',$saveAssemblyFileData);
					if($res){
						$this->db->where('id',$importID)->update('api_import_data',array('status'=>'1'));
						$chunkFileDatas = array_chunk($importFileDatas, 500);
						foreach($chunkFileDatas as $chunkFileData){
							$saveAssemblyFileBomDatas = array();
							foreach($chunkFileData as $key => $importFileData){
								$bomProductId = $productMapping[trim(strtolower($importFileData['bomSku']))];
								$saveAssemblyFileBomDatas[] = array(
									'uniqueFileId'	=> $uniqueFileId,
									'bomSku'		=> $importFileData['bomSku'],
									'bomProductId'	=> $bomProductId['productId'],
									'warehouse'		=> $warehouseMappings[trim(strtolower($importFileData['warehouse']))]['warehouseId'],
									'warehouseName'	=> $importFileData['warehouse'],
									'receipeId'		=> $bomRecipeDatas[$bomProductId['productId']][trim(strtolower($importFileData['receipeId']))]['receipeId'],
									'receipeName'	=> $importFileData['receipeId'],
									'qty'			=> $importFileData['qty'],
									'reference'		=> $importFileData['reference'],
								);
							}
							if($saveAssemblyFileBomDatas){
								$res1 = $this->db->insert_batch('import_assembly_bom', $saveAssemblyFileBomDatas);
							}
						}
					}
				}

			}			
		}
	}
	
}