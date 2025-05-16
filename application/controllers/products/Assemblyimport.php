<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Assemblyimport extends MY_Controller {
	function __construct(){
		parent::__construct();			
		$this->load->model('products/assemblyimport_model','',TRUE);
		$this->load->model('products/assembly_model','',TRUE);
		$this->load->library('session');
	}
	
	public function index(){
		$data = array();
		$this->template->load_template("products/assemblyimport",$data);
	}
	public function getProduct(){
		$records = $this->assemblyimport_model->getProduct();
		echo json_encode($records);
	}
    public function saveImportDatas($assemblyId = ""){
        $datas = $this->input->post('data');
        $this->assemblyimport_model->saveImportData($datas, $assemblyId);
    }
	 public function importAssembly(){
        if ($_FILES['uploadprefile']['tmp_name']) {
			$newname = "";
			$uniqueFileId 	= 'IA'.strtoupper(uniqid()).time();
            $file			= fopen($_FILES['uploadprefile']['tmp_name'], "r");
			$fileName		= uniqid().$_FILES['uploadprefile']['name'];
			$saveFileName 	= $_FILES['uploadprefile']['name'];
			$ext 			= pathinfo($fileName, PATHINFO_EXTENSION);$isFileUploaded = false;
			$newname 		= FCPATH.'assemblyImport/'.$fileName;
			if(!is_dir(dirname($newname))) { @mkdir(dirname($newname),0777,true);@chmod(dirname($newname), 0777); }
			if(strtolower($ext) == "csv"){
				$importFileDatas = array();
				$productMapping = array();
				$productMappingTemps = $this->db->select('productId,sku')->get_where('products',array('isLive' => '1'))->result_array();
				if($productMappingTemps){
					foreach($productMappingTemps as $productMappingTemp){
						$productMapping[trim(strtolower($productMappingTemp['sku']))] = $productMappingTemp;
					}
				}
				while (!feof($file)) {
					$row = fgetcsv($file);
					if($row){
						$importFileDatas[] = array(
							'bomSku'		=> trim($row['0']),
							'warehouse'		=> trim($row['1']),
							'receipeId'		=> trim($row['2']),
							'qty'			=> trim($row['3']),
							'reference'		=> trim($row['4']), 
						);
					}
				}
				if($importFileDatas){
					$csvFieldColumns = reset($importFileDatas);
					$this->validateRequiredFields($csvFieldColumns);
					$warehouseMappings = array();
					$warehouseMappingsTemps = $this->db->select('warehouseName, warehouseId')->get_where('warehouse_master')->result_array();
					if($warehouseMappingsTemps){
						foreach($warehouseMappingsTemps as $warehouseMappingsTemp){
							$warehouseMappings[trim(strtolower($warehouseMappingsTemp['warehouseName']))] = $warehouseMappingsTemp;
						}
					}
					$bomRecipeDatas = array();
					$bomDatasTemps = $this->db->select('productId,recipename,receipeId')->get_where('product_bom')->result_array();
					foreach($bomDatasTemps as $bomDatasTemp){ 
						$bomRecipeDatas[$bomDatasTemp['productId']][trim(strtolower($bomDatasTemp['recipename']))] = $bomDatasTemp;
					}
					$totalNoOfBom = count($importFileDatas) - 1;
					if($totalNoOfBom <= 0){
						$this->session->set_flashdata('errormessage', 'Please input bom details to perform assembly.!');
						redirect($_SERVER['HTTP_REFERER'] , 'refresh');
					}
					$saveAssemblyFileData = array(
						'uniqueFileId' 	 => $uniqueFileId,
						'importFileName' => $fileName,
						'toShowFilename' => $saveFileName,
						'isProcessed' 	 => '0',
						'totalBom' 	 	 => $totalNoOfBom,
					);
					$res = $this->db->insert('import_assembly',$saveAssemblyFileData);
					if($res){
						$chunkFileDatas = array_chunk($importFileDatas, 500);
						foreach($chunkFileDatas as $chunkFileData){
							$saveAssemblyFileBomDatas = array();
							foreach($chunkFileData as $key => $importFileData){
								if($key == 0){
									continue;
								}
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
								$this->db->insert_batch('import_assembly_bom', $saveAssemblyFileBomDatas);
							}
						}
					}else{
						$this->session->set_flashdata('errormessage', 'Some unexpected error occured while saving the assembly data!');
						redirect($_SERVER['HTTP_REFERER'] , 'refresh');
					}
				}else{
					$this->session->set_flashdata('errormessage', 'Please upload file correctly!');
					redirect($_SERVER['HTTP_REFERER'] , 'refresh');
				}
				move_uploaded_file($_FILES['uploadprefile']['tmp_name'], $newname);
			}else{
				$this->session->set_flashdata('errormessage', 'File format should be CSV!');
				redirect($_SERVER['HTTP_REFERER'] , 'refresh');
			}
        }else{
			$this->session->set_flashdata('errormessage', 'Please upload file correctly!');
			redirect($_SERVER['HTTP_REFERER'] , 'refresh');
		}
		$this->session->set_flashdata('successmessage', 'Assembly file has been uploaded sucessfully!');
        redirect($_SERVER['HTTP_REFERER'] , 'refresh');
    }
	public function validateRequiredFields($csvFieldColumns = array()){
		if(!$csvFieldColumns){
			$this->session->set_flashdata('errormessage', 'Assembly import columns are missing! ');
			redirect($_SERVER['HTTP_REFERER'] , 'refresh');;
		}
		$fixedColumnHeaders = array('bom sku', 'warehouse', 'recipeid', 'qty', 'reference');
		$notMatchedColums = array();
		foreach($csvFieldColumns as $csvFieldColumn){
			$fieldColumn = trim(strtolower($csvFieldColumn));
			if (!in_array($fieldColumn, $fixedColumnHeaders)){
				$notMatchedColums[] = $fieldColumn;
			}
		}
		if($notMatchedColums){
			$this->session->set_flashdata('errormessage', 'Missing required fields: ' . implode(',', $notMatchedColums));
			redirect($_SERVER['HTTP_REFERER'] , 'refresh');
		}
	}
	
	public function processImportedAssemblies($uniqueFileId = "", $isFromJobs = ""){
		$this->brightpearl->reInitialize();
		if($uniqueFileId){
			$this->db->where('uniqueFileId', $uniqueFileId);
		}
		$importAssemblyDatasTemps = $this->db->get_where('import_assembly',array('isProcessed' => '0'))->result_array();
		if(!$importAssemblyDatasTemps){
			if($isFromJobs){
				return false;
			}else{
				$this->session->set_flashdata('successmessage', 'No pending assembly to process!');
				redirect($_SERVER['HTTP_REFERER'] , 'refresh');				
			}
		}
		$this->config = $this->db->get('account_brightpearl_config')->row_array();
		$orderCostPriceListbom  = $this->config['costPriceListbom'];
		$importAssemblyDatas = array();$productMapping = array();$warehouseMappings = array();$warehouseBinMappings = array();
		foreach($importAssemblyDatasTemps as $importAssemblyDatasTemp){
			$importAssemblyDatas[$importAssemblyDatasTemp['uniqueFileId']] = $importAssemblyDatasTemp;
		}
		$productMappingTemps = $this->db->select('productId,sku,name,params,binlocation, isBundle')->get_where('products',array('isLive' => '1'))->result_array();
		if($productMappingTemps){
			foreach($productMappingTemps as $productMappingTemp){
				$productMapping[trim(strtolower($productMappingTemp['productId']))] = $productMappingTemp;
			}
		}
		$warehouseMappingsTemps = $this->db->select('warehouseName, warehouseId')->get_where('warehouse_master')->result_array();
		if($warehouseMappingsTemps){
			foreach($warehouseMappingsTemps as $warehouseMappingsTemp){
				$warehouseMappings[trim(strtolower($warehouseMappingsTemp['warehouseName']))] = $warehouseMappingsTemp;
			}
		}
		$warehouseBinMappingsTemps = $this->db->select('*')->get_where('warehouse_binlocation')->result_array();
		if($warehouseBinMappingsTemps){
			foreach($warehouseBinMappingsTemps as $warehouseBinMappingsTemp){
				$warehouseBinMappings[$warehouseBinMappingsTemp['warehouseId']][] = $warehouseBinMappingsTemp;
			}
		}
		if(!$importAssemblyDatas){
			if(!$isFromJobs){
				$this->session->set_flashdata('errormessage', 'No pending assembly to process!');
				redirect($_SERVER['HTTP_REFERER'] , 'refresh');
			}
		}
		foreach($importAssemblyDatas as $uniqueFileId => $importAssemblyData){
			$createdAssebliesDatas = array();
			$assemblyImportDatasTemps = $this->db->get_where('import_assembly_bom',array('status' => '0', 'uniqueFileId' => $uniqueFileId))->result_array();
			if(!$assemblyImportDatasTemps){continue;}
			if($assemblyImportDatasTemps){
				$assemblyImportDatas = array();$bomProductIdDatas = array();$errorMessages = array();$bomSkuNotfoundDatas = array();
				foreach($assemblyImportDatasTemps as $assemblyImportDatasTemp){
					$productDatas = $productMapping[$assemblyImportDatasTemp['bomProductId']];
					if(!$productDatas){
						$errorMessages[] = array(
							'message' => 'BOM SKu not found!',
							'id' 	  => $assemblyImportDatasTemp['id']
						);
						continue;
					}
					if($productDatas['isBundle']){
						$errorMessages[] = array(
							'message' => 'BOM SKu is Bundle!',
							'id' 	  => $assemblyImportDatasTemp['id']
						);
						continue;
					}
					$assemblyImportDatas[$assemblyImportDatasTemp['bomProductId']][] = $assemblyImportDatasTemp;
				}
				if($assemblyImportDatas){
					$bomDatasTemps = $this->db->get_where('product_bom')->result_array();
					$bomSavedDatas = array();
					foreach($bomDatasTemps as $bomDatasTemp){
						$bomSavedDatas[$bomDatasTemp['productId']][] = $bomDatasTemp;
					}
					$assemblyImportDatasChunks = array_chunk($assemblyImportDatas, '5', true);
					if($assemblyImportDatasChunks){
						foreach($assemblyImportDatasChunks as $assemblyImportDatasChun){
							$bomDatas = array();$componentProductIds = array();
							foreach($assemblyImportDatasChun as $bomProductId => $assemblyImportData){
								if($bomSavedDatas[$bomProductId]){
									foreach($bomSavedDatas[$bomProductId] as $bomSavedData){
										$bomDatas[$bomSavedData['productId']][$bomSavedData['receipeId']][$bomSavedData['componentProductId']] = $bomSavedData;
										$compProductData = $productMapping[$bomSavedData['componentProductId']];
										if(!$compProductData){
											continue;
										}
										$compProduct = json_decode($compProductData['params'], true);
										if(!$compProduct['stock']['stockTracked']){
											continue;
										}
										$componentProductIds[] = $bomSavedData['componentProductId'];
									}
								}
							}
							$productStocks = $this->assembly_model->getProductStock($componentProductIds);
							if(!$productStocks){
								continue;
							}
							$saveImportAssemblyDatas = array();
							foreach($assemblyImportDatasChun as $bomProductId => $assemblyImportData){
								foreach($assemblyImportData as $assemblyImport){
									if(!$assemblyImport['bomProductId']){continue;}
									$bomData = "";
									$bomData = $bomDatas[$assemblyImport['bomProductId']];
									$bomDatabyRecipeId = $bomData[$assemblyImport['receipeId']];
									if(!$bomDatabyRecipeId){
										$errorMessages[] = array(
											'message' => 'Recipe not found or components not found in recipe!',
											'id' 	  => $assemblyImport['id']
										);
										continue;
									}
									if(!$bomData){
										$errorMessages[] = array(
											'message' => 'Recipe not found or components not found in recipe!',
											'id' 	  => $assemblyImport['id']
										);
										continue;
									}
									$isComponentsBundle = array();
									foreach($bomDatabyRecipeId as $compProId => $componentItemData){
										$bunProductData = $productMapping[$compProId];
										if($bunProductData['isBundle']){
											$isComponentsBundle[] = $compProId;
										}
									}
									if($isComponentsBundle){
										$errorMessages[] = array(
											'message' => "Product Id(s) ".implode(',', $isComponentsBundle)." are bundle type.",
											'id' 	  => $assemblyImport['id']
										);
									}
									$bomSaveQty = reset($bomDatabyRecipeId)['bomQty'];
									$tempcomponentItemDatas = array();$isAvailabe = 1;$noStockForCompData = array();
									foreach($bomDatabyRecipeId as $compProId => $componentItemData){
										$compProduct2 = "";
										if(!$productMapping[$compProId]){
											continue;
										}
										$compProduct2 = $productMapping[$compProId];
										$compProduct2s = json_decode($compProduct2['params'], true);
										if(!$compProduct2s['stock']['stockTracked']){
											continue;
										}
										$proStocks = @$productStocks[$compProId]['warehouses'][$assemblyImport['warehouse']];
										if($proStocks['byLocation']){
											foreach($proStocks['byLocation'] as $binLoationId => $proStock){
												if($proStock['onHand']){
													if($componentItemData['qty'] <= 0){
														$tempcomponentItemDatas[$compProId][$binLoationId] = 0;
														continue;
													}else{
														$tempcomponentItemDatas[$compProId][$binLoationId] = @( $proStock['onHand'] * $componentItemData['bomQty'] ) / $componentItemData['qty'];
													}
												}
												else{
													$isAvailabe = 0;
												}
											}						
										}else{
											$noStockForCompData[] = $compProduct2['sku'];
											$isAvailabe = 0;
										}
									}
									$assembleCalQty = $assemblyImport['qty'];
									if(!$isAvailabe){
										$errorMessages[] = array(
											'message' => 'Stock not available for components sku: '. implode(',', $noStockForCompData),
											'id' => $assemblyImport['id']
										);
										continue; 
									}
									$checkAssemblePossibleQty = $this->checkAssemblePossibleQtyNew($assembleCalQty, $tempcomponentItemDatas);
									if(@$checkAssemblePossibleQty['assembleCalQty'] <= 0){ continue;}
									if($checkAssemblePossibleQty['assembleCalQty'] < $bomSaveQty){ continue;}
									if($bomSaveQty <= 0 ){ continue; }
									$maxAssembleCalQty = $checkAssemblePossibleQty['assembleCalQty'] - ($checkAssemblePossibleQty['assembleCalQty'] % $bomSaveQty);
									if($maxAssembleCalQty >= $assembleCalQty){
										$assembleCalQty = $assembleCalQty;
									}
									if(($assembleCalQty % $bomSaveQty) != 0){
										$errorMessages[] = array(
											'message' => 'Qty to assemble must be multiple of BOM Recipe Qty('.$bomSaveQty.')',
											'id' => $assemblyImport['id']
										);
										continue;
									}
									$targetwarehouseId = "";
									$targetwarehouseId = $assemblyImport['warehouse'];
									$increaseBinLocation = @$this->getBinLocationByWarehouseDefault($targetwarehouseId);
									$additionalArray = array();$sourceBinLocationIds = array();
									foreach($tempcomponentItemDatas as $componentsId => $tempcomponentItemData){
										$additionalArray['productId'][]			= $componentsId;
										$additionalArray['sku'][]				= $productMapping[$componentsId]['sku'];
										$additionalArray['name'][]				= $productMapping[$componentsId]['name'];
										$additionalArray['sourcewarehouse'][]   =  $targetwarehouseId;
										foreach($tempcomponentItemData as $locationKeys => $tempcomponentItemDat){
											$sourceBinLocationIds[] = $locationKeys;
										}
									}
									$additionalArray['sourceBinLocation'] =  $sourceBinLocationIds;
									$saveImportAssemblyDatas[] = array(
										'productId' 	  				=> $bomProductId,
										'sku' 			  				=> $productMapping[$bomProductId]['sku'],
										'name' 			  				=> $productMapping[$bomProductId]['name'],
										'assemblyId' 	  				=> '',
										'receipeid' 	  				=> $assemblyImport['receipeId'],
										'billcomponents'  				=> $bomDatabyRecipeId,
										'qtydiassemble'					=> $assembleCalQty,
										'targetwarehouse' 				=> $assemblyImport['warehouse'],
										'costingmethod'   				=> $orderCostPriceListbom,
										'targetBinLocation' 			=> $increaseBinLocation['name'],
										'autoAssemblyWipWarehouse'		=> '',
										'finalBIn'						=> '',
										$assemblyImport['receipeId']    => $additionalArray,
										'btnsaveworkinprogress' 		=> '0',
										'autoCompleteReorderAssembly' 	=> '0',
										'autoAssembly' 					=> '0', 
										'isImportAssembly' 				=> '1', 
										'orderId' 						=> '', 
										'isOrderAssembly'       		=> '0', 
										'reference'       				=> $assemblyImport['reference'], 
										'uniqueFileId'       			=> $uniqueFileId, 
										'bomInsertedId'       			=> $assemblyImport['id'],
									);
								}
							}
							if($saveImportAssemblyDatas){
								foreach($saveImportAssemblyDatas as $saveImportAssemblyData){
									$createdAssebliesDatas = $this->assembly_model->saveAssembly($saveImportAssemblyData); 
								}
							}
						}
					}
				}
				$isAlreadySaved = false;
				if($createdAssebliesDatas){
					$errorUpdateDatas = array();
					$errorUpdateDatas['isProcessed'] = '1';
					if($errorMessages){
						$isAlreadySaved = true;
						$batchUpdate = array_chunk($errorMessages,200);
						foreach($batchUpdate as $update){
							$this->db->update_batch('import_assembly_bom',$update,'id');
						}
						$errorUpdateDatas['isProcessedWithError'] = '1';
					}
					$this->db->where(array('uniqueFileId' => $uniqueFileId))->update('import_assembly', $errorUpdateDatas);
					if(!$isFromJobs){
						if($errorUpdateDatas['isProcessedWithError']){
							$this->session->set_flashdata('errormessage', 'Assembly(ies) created with errors');
						}else{
							$this->session->set_flashdata('successmessage', 'Assembly(ies) created successfully');
						}
						redirect($_SERVER['HTTP_REFERER'] , 'refresh');						
					} 
				}
				if(!$isAlreadySaved){
					$errorUpdateDatas = array();
					$errorUpdateDatas['isProcessed'] = '1';
					if($isFromJobs && $errorMessages){
						$errorUpdateDatas['isProcessedWithError'] = '1';
						$batchUpdate = array_chunk($errorMessages,200);
						foreach($batchUpdate as $update){
							$this->db->update_batch('import_assembly_bom',$update,'id');
						}
						$this->db->where(array('uniqueFileId' => $uniqueFileId))->update('import_assembly', $errorUpdateDatas); 
					}else{
						if($errorMessages){
							$errorUpdateDatas['isProcessedWithError'] = '1';
							$batchUpdate = array_chunk($errorMessages,200);
							foreach($batchUpdate as $update){
								$this->db->update_batch('import_assembly_bom',$update,'id');
							}
						}
						$this->db->where(array('uniqueFileId' => $uniqueFileId))->update('import_assembly',$errorUpdateDatas);
						$this->session->set_flashdata('errormessage', 'Assembly processed with errors!');
						redirect($_SERVER['HTTP_REFERER'] , 'refresh');
					}
				}
			}else{
				if(!$isFromJobs){
					$this->session->set_flashdata('successmessage', 'No pending assembly to process!');
					redirect($_SERVER['HTTP_REFERER'] , 'refresh');						
				}
			}
		}
	}
	
	function checkAssemblePossibleQty($assembleCalQty, $tempcomponentItemDatas){
		$return = array('assembleCalQty' => $assembleCalQty,'locationId' => '');
		if($assembleCalQty > 0){
			foreach($tempcomponentItemDatas as $compId => $tempcomponentItemData){
				if($tempcomponentItemData){
					arsort($tempcomponentItemData);
					foreach($tempcomponentItemData as $binLocation => $qty){
						if($qty <= 0){
							$return = array('assembleCalQty' => $assembleCalQty,'locationId' => '');
							return $return;
						}
						else if($qty >= $assembleCalQty){
							$return = array('assembleCalQty' => $assembleCalQty,'locationId' => $binLocation);
						}
						else{
							$this->checkAssemblePossibleQty($qty,$tempcomponentItemDatas);
						}
					}					
				}
				else{
					$return = array('assembleCalQty' => $assembleCalQty,'locationId' => '');
					return $return;
				}
			}
		}
		return $return;
	}
	function checkAssemblePossibleQtyNew($assembleCalQty, $tempcomponentItemDatas){
		$return = array('assembleCalQty' => $assembleCalQty,'locationId' => '');
		if($assembleCalQty > 0){
			foreach($tempcomponentItemDatas as $compId => $tempcomponentItemData){
				if($tempcomponentItemData){
					arsort($tempcomponentItemData);
					foreach($tempcomponentItemData as $binLocation => $qty){
						if($qty <= 0){
							$return = array('assembleCalQty' => $assembleCalQty,'locationId' => '');
							return $return;
						}
						else if($qty >= $assembleCalQty){
							$return = array('assembleCalQty' => $qty,'locationId' => $binLocation);
							return $return;
						}
						else{
							$this->checkAssemblePossibleQty($qty,$tempcomponentItemDatas);
						}
					}					
				}
				else{
					$return = array('assembleCalQty' => $assembleCalQty,'locationId' => '');
					return $return;
				}
			}
		}
		return $return;
	}
	public function getBinLocationByWarehouse($warehouseId, $binLocation){
		return $this->db->get_where('warehouse_binlocation',array('name' => $binLocation,'warehouseId' => $warehouseId))->row_array();
	}
	public function getBinLocationByWarehouseDefault($warehouseId){
		return $this->db->get_where('warehouse_binlocation',array('isDefaultLocation' => '1','warehouseId' => $warehouseId))->row_array();
	}
	public function downloadSample(){
		$finalHeader = array('BOM SKU','WAREHOUSE','RECIPEID','QTY','REFERENCE');
		$csvDatas = array();
		$csvDatas[] = $finalHeader; 
		$saveFilename = "Import-Assembly-Sample-File.csv";
		$fp = fopen('php://output', 'wb');
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'.$saveFilename.'"');
		foreach ($csvDatas as $line){
			fputcsv($fp,$line, ',');
		}
		fclose($fp);
	}
	public function importInfo($uniqueFileId){
		if(!$uniqueFileId){
			return false;
		}
        $data = array();      
        $data['data'] = $this->db->get_where('import_assembly_bom',array('uniqueFileId' => $uniqueFileId))->result_array();   
        $this->template->load_template("importassembly/importassemblyview",$data);
    }
}
