<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Assemblyalert extends CI_Controller {
	public function __construct(){
		parent::__construct();
	}	
	public function emailNotification(){
		$totalNoOfAssemblies = array();$assemblyDateDatas = array();$numberOfManualAsseblies = array();
		$autoAssemblies = array();$totalNoOfAssembliesAtFifos = array();$totalNoOfManualAssembliesAtFifos = array();$totalNoOfBoms = array();$productsBomDatas = array();$isPrivateAppEnabled = false;$warehouseMappings = array();$warehouseBinLocationMappings = array();$gitcommitVersion = "";
		$commitDetails = shell_exec('bash -x '.FCPATH.'/gitcommit.sh');
		if($commitDetails){
			$commitDetails	  = explode(" ", $commitDetails);
			$gitcommitVersion = $commitDetails['1'];
			$gitcommitVersion = str_replace("Author:", "" , $gitcommitVersion);
		}
		
		//Private App check
		$brightpearlAcountDetails = $this->db->order_by('id', 'asc')->get_where('account_brightpearl_account')->row_array();
		if($brightpearlAcountDetails){
			if($brightpearlAcountDetails['reference'] != 'null' && $brightpearlAcountDetails['token'] != 'null')
				$isPrivateAppEnabled = true;
		}
		//product datas
		$productsDatas = $this->db->order_by('id', 'asc')->get_where('products')->result_array();
		if($productsDatas){
			foreach($productsDatas as $productsData){
				if($productsData['isBOM'])
					$totalNoOfBoms[$productsData['productId']] = $productsData;
			}
		}
		
		// all bom datas
		$productsBomDataTemps = $this->db->order_by('id', 'asc')->get_where('product_bom')->result_array();
		if($productsBomDataTemps){
			foreach($productsBomDataTemps as $productsBomDataTemp){
				$productsBomDatas[$productsBomDataTemp['productId']][] = $productsBomDataTemp;
			}
		}
		
		if($totalNoOfBoms){
			$noOfBomWithoutComponents = array();$noOfBomWithAutoassemblyChecked = array();$noOfBomWithAutoPriceUpdateChecked = array();$noOfBomWithSetDefaultReceipesDatas = array();
			foreach($totalNoOfBoms as $productId => $totalNoOfBom){
				$productsBomData = $productsBomDatas[$productId];
				if(!$productsBomData)
					$noOfBomWithoutComponents[$productId] = $totalNoOfBom;
				
				if($productsBomData){
					if($totalNoOfBom['autoAssemble'])
						$noOfBomWithAutoassemblyChecked[$productId] = $totalNoOfBom;
					
					if($totalNoOfBom['autoBomPriceUpdate'])
						$noOfBomWithAutoPriceUpdateChecked[$productId] = $totalNoOfBom;
					
					foreach($productsBomData as $productsBomDat){
						$noOfBomWithSetDefaultReceipesDatas[$productId][$productsBomDat['receipeId']][] = $productsBomDat;
					}
					
				}
			}
			if($noOfBomWithSetDefaultReceipesDatas){
				$noOfBomWithoutHavingAnySetAsDefaultReceipe = array();
				foreach($noOfBomWithSetDefaultReceipesDatas as $productId => $noOfBomWithSetDefaultReceipesData){
					$isPrimary = false;
					foreach($noOfBomWithSetDefaultReceipesData as $receipeId => $noOfBomWithSetDefaultReceipesDat){
						foreach($noOfBomWithSetDefaultReceipesDat as $noOfBomWithSetDefaultReceipes){
							if($noOfBomWithSetDefaultReceipes['isPrimary']){
								$isPrimary = true;
								break;
							}
						}
					}
					if($isPrimary)
						continue;
					
					$noOfBomWithoutHavingAnySetAsDefaultReceipe[$productId] = $noOfBomWithSetDefaultReceipesData;
				}
			}
		}
		// warehouse mapping
        $savedWarehouseDataTemps = $this->db->get_where('warehouse_master')->result_array();
		if($savedWarehouseDataTemps){
			foreach($savedWarehouseDataTemps as $savedWarehouseDataTemp){
				$warehouseMappings[$savedWarehouseDataTemp['warehouseId']] = array(
					'id' => $savedWarehouseDataTemp['warehouseId'],
					'name' => $savedWarehouseDataTemp['warehouseName'],
				);
			}
		}
		// warehouse bin location mapping
        $warehouseBinLocationDataTemps = $this->db->get_where('warehouse_binlocation')->result_array();
		if($warehouseBinLocationDataTemps){
			foreach($warehouseBinLocationDataTemps as $warehouseBinLocationDataTemp){
				$warehouseBinLocationMappings[$warehouseBinLocationDataTemp['warehouseId']][$warehouseBinLocationDataTemp['id']] = array(
					'id' => $warehouseBinLocationDataTemp['id'],
					'name' => $warehouseBinLocationDataTemp['name'],
				);
			}
		}
		
		//all assembly datas
		$assemblyDatas = $this->db->order_by('id', 'asc')->get_where('product_assembly')->result_array();
		if($assemblyDatas){
			$allAssemblies = array();
			foreach($assemblyDatas as $assemblyData){
				$totalNoOfAssemblies[] = $assemblyData['createdId'];
				$assemblyDateDatas[] = array(
					'createdId' => $assemblyData['createdId'],
					'created' 	=> $assemblyData['created'],
				);
				if(!$assemblyData['autoAssembly'])
					$numberOfManualAsseblies[$assemblyData['createdId']] = $assemblyData['createdId'];
				
				if($assemblyData['autoAssembly'])
					$autoAssemblies[$assemblyData['createdId']][] = $assemblyData;
				
				if(strtolower($assemblyData['costingMethod']) == 'fifo')
					$totalNoOfAssembliesAtFifos[$assemblyData['createdId']][] = $assemblyData;
				
				$allAssemblies[$assemblyData['createdId']][] = $assemblyData;
			}
			if($autoAssemblies){
				$standardAutoAssemblies = array(); $reorderAutoAssemblies = array();$standardAutoAssembliesAtFifo = array();
				$reorderAutoAssembliesAtFifo = array();
				foreach($autoAssemblies as $assemblyId => $autoAssembly){
					foreach($autoAssembly as $autoAssembl){
						if(!$autoAssembl['isInventoryTransfer']){
							$standardAutoAssemblies[$assemblyId][] = $autoAssembl;
							//with fifo method
							if(strtolower($autoAssembl['costingMethod']) == 'fifo')
								$standardAutoAssembliesAtFifo[$assemblyId][] = $autoAssembl;
						}
						
						if($autoAssembl['isInventoryTransfer']){
							$reorderAutoAssemblies[$assemblyId][] = $autoAssembl;
							//with fifo method
							if(strtolower($autoAssembl['costingMethod']) == 'fifo')
								$reorderAutoAssembliesAtFifo[$assemblyId][] = $autoAssembl;
						}
					}
				}
			}
			if($totalNoOfAssembliesAtFifos){
				foreach($totalNoOfAssembliesAtFifos as $assembliesId => $totalNoOfAssembliesAtF){
					foreach($totalNoOfAssembliesAtF as $totalNoOfAssembliesAt){
						if((strtolower($totalNoOfAssembliesAt['costingMethod']) == "fifo") && (!$totalNoOfAssembliesAt['autoAssembly'])){
							$totalNoOfManualAssembliesAtFifos[$assembliesId] = $totalNoOfAssembliesAtF;
						}
					}
				}
			}
			if($allAssemblies){
				$assemblieParantDatas = array();$assemblieComponentDatas = array();$NotMatchSourceAndTargetWarehouseDatas = array();
				foreach($allAssemblies as $assemblyId => $allAssemblie){
					foreach($allAssemblie as $allAssembli){
						if($allAssembli['isAssembly'])
							$assemblieParantDatas[$assemblyId][$allAssembli['warehouse']] = $allAssemblie;
						
						if(!$allAssembli['isAssembly'])
							$assemblieComponentDatas[$assemblyId][$allAssembli['warehouse']] = $allAssemblie;	
					}
				}
				if($assemblieParantDatas){
					foreach($assemblieParantDatas as $assemblyId => $assemblieParantData){
						foreach($assemblieParantData as $warehouseId => $assemblieParant){
							if(!$assemblieComponentDatas[$assemblyId][$warehouseId]){
								$NotMatchSourceAndTargetWarehouseDatas[$assemblyId] = $assemblieParant;
							}
						}
					}
				}
				
			}
			
			//Total number of assemblies in db
			$totalNoOfAssemblies    			= array_filter($totalNoOfAssemblies);
			$totalNoOfAssemblies    			= count(array_unique($totalNoOfAssemblies));
			
			//Earliest assembly date
			$earliestAssemblyDate   			= reset($assemblyDateDatas);
			
			//Latest assembly date
			$latestAssemblyDate     			= end($assemblyDateDatas);
			
			//Number of manual assemblies
			$numberOfManualAsseblie 			= count($numberOfManualAsseblies);
			
			//Number of standard auto-assemblies
			$noOfStandardAutoAssemblies 		= count(array_keys($standardAutoAssemblies));
			
			//Number of reorder point assemblies
			$noOfReorderAutoAssemblies 			= count(array_keys($reorderAutoAssemblies));
			
			//Number of assemblies at FIFO
			$totalNoOfAssembliesAtFifo 			= count(array_keys($totalNoOfAssembliesAtFifos));
			
			//Number of manual assemblies at FIFO
			$totalNoOfManualAssembliesAtFifo 	= count(array_keys($totalNoOfManualAssembliesAtFifos));
			
			//Number of standard auto-assemblies at FIFO
			$standardAutoAssembliesAtFifo 		= count(array_keys($standardAutoAssembliesAtFifo));
			
			//Number of reorder point assemblies at FIFO
			$reorderAutoAssembliesAtFifo 		= count(array_keys($reorderAutoAssembliesAtFifo));
			
			//Number of BOMs in db
			$totalNoOfBom 				 		= count(array_keys($totalNoOfBoms)); 
			
			//Number of BOMs without components in db
			$noOfBomWithoutComponent			= count(array_keys($noOfBomWithoutComponents)); 
			
			//Number of BOMs with auto-assembly checked
			$noOfBomWithAutoassemblyChecked 	= count(array_keys($noOfBomWithAutoassemblyChecked)); 
			
			//Number of BOMs with auto-price update checked
			$noOfBomWithAutoPriceUpdateChecked 	= count(array_keys($noOfBomWithAutoPriceUpdateChecked));
			
			//Number of BOMs with components and 1 recipe not marked as default (FG created at 0)
			$noOfBomWithoutHavingAnySetAsDefaultReceipe = count(array_keys($noOfBomWithoutHavingAnySetAsDefaultReceipe));
			
			//Number of assemblies created with components taken from more than 1 warehouse (FG created at 0)
			$NotMatchSourceAndTargetWarehouseData = count(array_keys($NotMatchSourceAndTargetWarehouseDatas));
			
		}
	}  
} 