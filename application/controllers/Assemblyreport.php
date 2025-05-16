<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Assemblyreport extends CI_Controller {
	public function __construct(){
		parent::__construct();
	}
	public function generateAsseblyReport($reportType){
		if(!$reportType){
			return false;
		}
		$assemblyDateDatas = array();$numberOfManualAsseblies = array();
		$autoAssemblies = array();$totalNoOfAssembliesAtFifos = array();$totalNoOfManualAssembliesAtFifos = array();$totalNoOfBoms = array();$productsBomDatas = array();$isPrivateAppEnabled = false;$warehouseMappings = array();$warehouseBinLocationMappings = array();$gitcommitVersion = "";
		$commitDetails = shell_exec('bash -x '.FCPATH.'/gitcommit.sh');
		if($commitDetails){
			$commitDetails = explode(" ", $commitDetails);
			$gitcommitVersion 	   = $commitDetails['1'];
			$gitcommitVersion	   = str_replace("Author:", "" , $gitcommitVersion);
			$gitcommitVersion	   = trim(str_replace("Merge:", "" , $gitcommitVersion));
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
					
					$noOfBomWithoutHavingAnySetAsDefaultReceipe[$productId] = $totalNoOfBoms[$productId];
				}
			}
		}
		
		//all assembly datas
		$assemblyDatas = $this->db->order_by('id', 'asc')->get_where('product_assembly')->result_array();
		if($assemblyDatas){
			$allAssemblies = array();
			foreach($assemblyDatas as $assemblyData){
				if(!$assemblyData['autoAssembly']){
					$numberOfManualAsseblies[$assemblyData['createdId']][] = $assemblyData;
				}
				if($assemblyData['autoAssembly'])
					$autoAssemblies[$assemblyData['createdId']][] = $assemblyData;
				
				if(strtolower($assemblyData['costingMethod']) == 'fifo')
					$totalNoOfAssembliesAtFifos[$assemblyData['createdId']][] = $assemblyData;
				
				$allAssemblies[$assemblyData['createdId']][] = $assemblyData;
			}
			if($autoAssemblies){
				$standardAutoAssemblies = array(); $reorderAutoAssemblies = array();$standardAutoAssembliesAtFifo = array();$standardAutoAssembliesAtCostPrices = array();$reorderAutoAssembliesAtCostPrices = array();
				$reorderAutoAssembliesAtFifo = array();
				foreach($autoAssemblies as $assemblyId => $autoAssembly){
					foreach($autoAssembly as $autoAssembl){
						if(!$autoAssembl['isInventoryTransfer']){
							$standardAutoAssemblies[$assemblyId][] = $autoAssembl;
							//with fifo method
							if(strtolower($autoAssembl['costingMethod']) == 'fifo')
								$standardAutoAssembliesAtFifo[$assemblyId][] = $autoAssembl;
							else
								$standardAutoAssembliesAtCostPrices[$assemblyId][] = $autoAssembl;
						}
						
						if($autoAssembl['isInventoryTransfer']){
							$reorderAutoAssemblies[$assemblyId][] = $autoAssembl;
							//with fifo method
							if(strtolower($autoAssembl['costingMethod']) == 'fifo')
								$reorderAutoAssembliesAtFifo[$assemblyId][] = $autoAssembl;
							else
								$reorderAutoAssembliesAtCostPrices[$assemblyId][] = $autoAssembl;
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
				$isAssembly = array();$isNotAssembly = array();$NumberofAssembliesAtCostPrice = array();
				foreach($allAssemblies as $assemblyId => $allAssemblie){
					foreach($allAssemblie as $allAssembli){
						if($allAssembli['isAssembly']){
							$isAssembly[$assemblyId][] = $allAssembli;
						}
						if(!$allAssembli['isAssembly']){
							$isNotAssembly[$assemblyId][] = $allAssembli;
						}
						if(strtolower($allAssembli['costingMethod']) != "fifo"){
							$NumberofAssembliesAtCostPrice[$assemblyId][] = $allAssembli;
						}
					}
				}
				$AssemblieswithonlyFGrecords = array();$Assemblieswithonlycomponentsrecords = array();
				foreach($allAssemblies as $assemblyId => $allAssemblie){
					if($isAssembly[$assemblyId] && !$isNotAssembly[$assemblyId]){
						$AssemblieswithonlyFGrecords[$assemblyId]= $allAssemblie;
					}elseif(!$isAssembly[$assemblyId] && $isNotAssembly[$assemblyId]){
						$Assemblieswithonlycomponentsrecords[$assemblyId] = $allAssemblie;
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
								$NotMatchSourceAndTargetWarehouseDatas[$assemblyId] = $allAssemblies[$assemblyId];
							}
						}
					}
				}
			}
			if($numberOfManualAsseblies){
				$NumberOfManualAssembliesAtCostPrices = array();
				foreach($numberOfManualAsseblies as $assemblyId => $numberOfManualAsseblie){
					foreach($numberOfManualAsseblie as $numberOfManualAssebli){
						if(strtolower($numberOfManualAssebli['costingMethod']) != 'fifo'){
							$NumberOfManualAssembliesAtCostPrices[$numberOfManualAssebli['createdId']][] = $numberOfManualAssebli;
						}
					}
				}
			}
			//all assembly datas
			$allDisAssemblies = array();
			$disAssemblyDatas = $this->db->order_by('id', 'asc')->get_where('product_deassembly')->result_array();
			if($disAssemblyDatas){
				foreach($disAssemblyDatas as $disAssemblyData){
					$allDisAssemblies[$disAssemblyData['createdId']][] = $disAssemblyData;
				}
			}
			/*======================= Assembly Generation Start =============================*/
			if($reportType == "all_Assemblies_In_DB"){
				//Total number of assemblies in db
				$this->generateAssemblyCsv($allAssemblies, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_manual_assemblies"){
				//Number of manual assemblies
				$this->generateAssemblyCsv($numberOfManualAsseblies, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);	
				
			}elseif($reportType == "Number_of_standard_auto_assemblies"){
				//Number of standard auto-assemblies
				$this->generateAssemblyCsv($standardAutoAssemblies, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);	
				
			}elseif($reportType == "Number_of_reorder_point_assemblies"){
				//Number of reorder point assemblies
				$this->generateAssemblyCsv($reorderAutoAssemblies, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);	
				
			}elseif($reportType == "Number_of_assemblies_at_FIFO"){
				//Number of assemblies at FIFO
				$this->generateAssemblyCsv($totalNoOfAssembliesAtFifos, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_manual_assemblies_at_FIFO"){
				//Number of manual assemblies at FIFO
				$this->generateAssemblyCsv($totalNoOfManualAssembliesAtFifos, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);	
				
			}elseif($reportType == "Number_of_standard_auto_assemblies_at_FIFO"){
				//Number of standard auto-assemblies at FIFO
				$this->generateAssemblyCsv($standardAutoAssembliesAtFifo, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_reorder_point_assemblies_at_FIFO"){
				//Number of reorder point assemblies at FIFO
				$this->generateAssemblyCsv($reorderAutoAssembliesAtFifo, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);	
				
			}elseif($reportType == "Number_of_assemblies_created_with_different_warehouse"){
				//Number of assemblies created with components taken from more than 1 warehouse (FG created at 0)
				$this->generateAssemblyCsv($NotMatchSourceAndTargetWarehouseDatas, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_BOMs_in_db"){
				//Number of BOMs in db
				$this->generateBomCsv($totalNoOfBoms, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_BOMs_without_components_in_db"){
				//Number of BOMs without components in db
				$this->generateBomCsv($noOfBomWithoutComponents, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_BOMs_with_auto_assembly_checked"){
				//Number of BOMs with auto-assembly checked
				$this->generateBomCsv($noOfBomWithAutoassemblyChecked, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_BOMs_with_auto_price_update_checked"){
				//Number of BOMs with auto-price update checked
				$this->generateBomCsv($noOfBomWithAutoPriceUpdateChecked, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "BOMs_with_components_and_1_recipe_not_marked_as_default"){
				//Number of BOMs with auto-price update checked
				$this->generateBomCsv($noOfBomWithoutHavingAnySetAsDefaultReceipe, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Total_no_of_disassemblies"){
				//Number of BOMs with auto-price update checked
				$this->generateDisAssemblyCsv($allDisAssemblies, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Assemblies_with_only_FG_record"){
				//Number of BOMs with auto-price update checked
				$this->generateAssemblyCsv($AssemblieswithonlyFGrecords, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Assemblies_with_only_components_records"){
				//Number of BOMs with auto-price update checked
				$this->generateAssemblyCsv($Assemblieswithonlycomponentsrecords, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_assemblies_at_Cost_price"){
				//Number of BOMs with auto-price update checked
				$this->generateAssemblyCsv($NumberofAssembliesAtCostPrice, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_standard_auto_assemblies_at_cost_price"){
				//Number of BOMs with auto-price update checked
				$this->generateAssemblyCsv($standardAutoAssembliesAtCostPrices, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_manual_assemblies_at_cost_price"){
				//Number of BOMs with auto-price update checked
				$this->generateAssemblyCsv($NumberOfManualAssembliesAtCostPrices, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}elseif($reportType == "Number_of_reorder_assemblies_at_cost_price"){
				//Number of BOMs with auto-price update checked
				$this->generateAssemblyCsv($reorderAutoAssembliesAtCostPrices, $gitcommitVersion, $reportType, $brightpearlAcountDetails['accountName']);
				
			}else{
				echo "<b>Error: ".$reportType. " invalid Report type</b>"; die;
			}
			/*======================= Assembly Generation End =============================*/
			
		}
	}
	
	public function allAssemblyReport(){
		$scriptUrl = $_SERVER['REQUEST_SCHEME'].':'.base_url();
		$assemblyDateDatas = array();$numberOfManualAsseblies = array();
		$autoAssemblies = array();$totalNoOfAssembliesAtFifos = array();$totalNoOfManualAssembliesAtFifos = array();$totalNoOfBoms = array();$productsBomDatas = array();$isPrivateAppEnabled = "N";$warehouseMappings = array();$warehouseBinLocationMappings = array();$gitcommitVersion = "";
		$commitDetails = shell_exec('bash -x '.FCPATH.'/gitcommit.sh');
		if($commitDetails){
			$commitDetails = explode(" ", $commitDetails);
			$gitcommitVersion 	   = $commitDetails['1'];
			$gitcommitVersion	   = str_replace("Author:", "" , $gitcommitVersion);
			$gitcommitVersion	   = trim(str_replace("Merge:", "" , $gitcommitVersion));
		}
		//Private App check
		$brightpearlAcountDetails = $this->db->order_by('id', 'asc')->get_where('account_brightpearl_account')->row_array();
		if($brightpearlAcountDetails){
			if($brightpearlAcountDetails['reference'] != 'null' && $brightpearlAcountDetails['token'] != 'null')
				$isPrivateAppEnabled = "Y";
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
		
		//all assembly datas
		$assemblyDatas = $this->db->order_by('id', 'asc')->get_where('product_assembly')->result_array();
		if($assemblyDatas){
			$allAssemblies = array();$allNumberOfAssebliesDatas = array();
			foreach($assemblyDatas as $assemblyData){
				if(!$assemblyData['autoAssembly']){
					$numberOfManualAsseblies[$assemblyData['createdId']][] = $assemblyData;
				}
				if($assemblyData['autoAssembly'])
					$autoAssemblies[$assemblyData['createdId']][] = $assemblyData;
				
				if(strtolower($assemblyData['costingMethod']) == 'fifo')
					$totalNoOfAssembliesAtFifos[$assemblyData['createdId']][] = $assemblyData;
				
				$allAssemblies[$assemblyData['createdId']][] = $assemblyData;
				
				/* if($assemblyData['isAssembly']){
				} */
					$allNumberOfAssebliesDatas[$assemblyData['createdId']] = $assemblyData;
			}
			if($autoAssemblies){
				$standardAutoAssemblies = array(); $reorderAutoAssemblies = array();$standardAutoAssembliesAtFifo = array();$standardAutoAssembliesAtCostPrices = array();$reorderAutoAssembliesAtCostPrices = array();
				$reorderAutoAssembliesAtFifo = array();
				foreach($autoAssemblies as $assemblyId => $autoAssembly){
					foreach($autoAssembly as $autoAssembl){
						if(!$autoAssembl['isInventoryTransfer']){
							$standardAutoAssemblies[$assemblyId][] = $autoAssembl;
							//with fifo method
							if(strtolower($autoAssembl['costingMethod']) == 'fifo')
								$standardAutoAssembliesAtFifo[$assemblyId][] = $autoAssembl;
							else
								$standardAutoAssembliesAtCostPrices[$assemblyId][] = $autoAssembl;
						}
						
						if($autoAssembl['isInventoryTransfer']){
							$reorderAutoAssemblies[$assemblyId][] = $autoAssembl;
							//with fifo method
							if(strtolower($autoAssembl['costingMethod']) == 'fifo')
								$reorderAutoAssembliesAtFifo[$assemblyId][] = $autoAssembl;
							else
								$reorderAutoAssembliesAtCostPrices[$assemblyId][] = $autoAssembl;
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
								$NotMatchSourceAndTargetWarehouseDatas[$assemblyId] = $allAssemblies[$assemblyId];
							}
						}
					}
				}
			}
			if($allAssemblies){
				$isAssembly = array();$isNotAssembly = array();
				$NumberofAssembliesAtCostPrice = array(); $NumberOfStandardAutoAssembliesAtCostPrice = array();
				foreach($allAssemblies as $assemblyId => $allAssemblie){
					foreach($allAssemblie as $allAssembli){
						if($allAssembli['isAssembly']){
							$isAssembly[$assemblyId][] = $allAssembli;
						}
						if(!$allAssembli['isAssembly']){
							$isNotAssembly[$assemblyId][] = $allAssembli;
						}
						if(strtolower($allAssembli['costingMethod']) != "fifo"){
							$NumberofAssembliesAtCostPrice[$assemblyId][] = $allAssembli;
						}
					}
				}
				$AssemblieswithonlyFGrecords = array();$Assemblieswithonlycomponentsrecords = array();
				foreach($allAssemblies as $assemblyId => $allAssemblie){
					if($isAssembly[$assemblyId] && !$isNotAssembly[$assemblyId]){
						$AssemblieswithonlyFGrecords[$assemblyId]= $allAssemblie;
					}elseif(!$isAssembly[$assemblyId] && $isNotAssembly[$assemblyId]){
						$Assemblieswithonlycomponentsrecords[$assemblyId] = $allAssemblie;
					}
					
					
				}
			}
			$earliestAsseblyId = "";$latestAsseblyIds = "";
			if($allNumberOfAssebliesDatas){
				$earliestAsseblyId = reset($allNumberOfAssebliesDatas);
				$earliestAsseblyId = $earliestAsseblyId['created'];
				$latestAsseblyIds = end($allNumberOfAssebliesDatas);
				$latestAsseblyIds = $latestAsseblyIds['created'];
			}
			$allDisAssemblies = array();
			$disAssemblyDatas = $this->db->order_by('id', 'asc')->get_where('product_deassembly')->result_array();
			if($disAssemblyDatas){
				foreach($disAssemblyDatas as $disAssemblyData){
					$allDisAssemblies[$disAssemblyData['createdId']][] = $disAssemblyData;
				}
			}
			if($numberOfManualAsseblies){
				$NumberOfManualAssembliesAtCostPrices = array();
				foreach($numberOfManualAsseblies as $assemblyId => $numberOfManualAsseblie){
					foreach($numberOfManualAsseblie as $numberOfManualAssebli){
						if(strtolower($numberOfManualAssebli['costingMethod']) != 'fifo'){
							$NumberOfManualAssembliesAtCostPrices[$numberOfManualAssebli['createdId']][] = $numberOfManualAssebli;
						}
					}
				}
			}
			/*======================= Assembly Generation Start =============================*/
			//Total number of assemblies in db
			$totalNoOfAssemblies 						     = count(array_keys($allNumberOfAssebliesDatas));
			
			//Number of manual assemblies
			$totalNumberOfManualAsseblies 					 = count(array_keys($numberOfManualAsseblies));
			
			//Number of standard auto-assemblies
			$totalstandardAutoAssemblies 					 = count(array_keys($standardAutoAssemblies));
			
			//Number of reorder point assemblies
			$totalreorderAutoAssemblies 					 = count(array_keys($reorderAutoAssemblies));
			
			////Number of assemblies at FIFO
			$totaltotalNoOfAssembliesAtFifos 				 = count(array_keys($totalNoOfAssembliesAtFifos));
			
			//Number of manual assemblies at FIFO
			$totalNoOfManualAssembliesAtFifo 				 = count(array_keys($totalNoOfManualAssembliesAtFifos));
			
			//Number of standard auto-assemblies at FIFO
			$totalstandardAutoAssembliesAtFifo 				 = count(array_keys($standardAutoAssembliesAtFifo));
			
			//Number of reorder point assemblies at FIFO
			$totalreorderAutoAssembliesAtFifo 				 = count(array_keys($reorderAutoAssembliesAtFifo));
			
			//Number of assemblies created with components taken from more than 1 warehouse (FG created at 0)
			$totalNotMatchSourceAndTargetWarehouseDatas 	 = count(array_keys($NotMatchSourceAndTargetWarehouseDatas));
			
			////Number of BOMs in db
			$totalNoOfBom 									 = count(array_keys($totalNoOfBoms));
			
			//Number of BOMs without components in db
			$totalnoOfBomWithoutComponents 					 = count(array_keys($noOfBomWithoutComponents));
			
			//Number of BOMs with auto-assembly checked
			$totalnoOfBomWithAutoassemblyChecked 			 = count(array_keys($noOfBomWithAutoassemblyChecked));
			
			////Number of BOMs with auto-price update checked
			$totalnoOfBomWithAutoPriceUpdateChecked 		 = count(array_keys($noOfBomWithAutoPriceUpdateChecked));
			
			// Assemblies with only FG record
			$AssemblieswithonlyFGrecord 					 = count(array_keys($AssemblieswithonlyFGrecords));
			
			
			// Assemblies with only components record
			$Assemblieswithonlycomponentsrecord 			 = count(array_keys($Assemblieswithonlycomponentsrecords));
			
			//Number of BOMs with auto-price update checked
			$totalnoOfBomWithoutHavingAnySetAsDefaultReceipe = count(array_keys($noOfBomWithoutHavingAnySetAsDefaultReceipe));
			
			$totalnoofdisassemblies 						 = count(array_keys($allDisAssemblies));
			
			$NumberofAssembliesAtCostPric 					 = count(array_keys($NumberofAssembliesAtCostPrice));
			
			$standardAutoAssembliesAtCostPrice 				 = count(array_keys($standardAutoAssembliesAtCostPrices));
			
			$NumberOfManualAssembliesAtCostPrice			 = count(array_keys($NumberOfManualAssembliesAtCostPrices));
			
			$reorderAutoAssembliesAtCostPrice			 	 = count(array_keys($reorderAutoAssembliesAtCostPrices));
			
			$reconsilatonCheckCalucation					 =  ($totalNoOfAssemblies - ($totalNumberOfManualAsseblies + $totalstandardAutoAssemblies + $totalreorderAutoAssemblies + $AssemblieswithonlyFGrecord + $Assemblieswithonlycomponentsrecord));
			$costingMethodCheckCalculation 					 = ($totaltotalNoOfAssembliesAtFifos-($totalstandardAutoAssembliesAtFifo +$totalNoOfManualAssembliesAtFifo + $totalreorderAutoAssembliesAtFifo));
			
			$costingMethodCheckCalculation2					 = $NumberofAssembliesAtCostPric - ($standardAutoAssembliesAtCostPrice + $NumberOfManualAssembliesAtCostPrice + $reorderAutoAssembliesAtCostPrice);
			$identifyStockAdjIssueCount 					 = $this->identifyStockAdjIssueCount();
			$reportDatas = array(
				array(
					'row1' => '<b>Miscellaneous Check<b>', 'row2' => 'Assembly Date Check', 'row3' => '', 'row4' => ''
				),array(
					'row1' => '', 'row2' => '', 'row3' => '', 'row4' => ''
				),
				array(
					'row1' => '', 'row2' => 'Earliest assembly date', 'row3' => $earliestAsseblyId, 'row4' => ''
				),
				array(
					'row1' => '', 'row2' => 'Latest assembly date', 'row3' => $latestAsseblyIds, 'row4' => ''
				),
				array(
					'row1' => '', 'row2' => 'Commit version of app', 'row3' => $gitcommitVersion, 'row4' => ''
				),
				array(
					'row1' => '', 'row2' => 'Private app active Y/N', 'row3' => $isPrivateAppEnabled, 'row4' => ''
				),array(
					'row1' => '', 'row2' => '', 'row3' => '', 'row4' => ''
				),
				array(
					'row1' => '', 'row2' => '<b>Particulars</b>', 'row3' => '<b>Number</b>', 'row4' => ''
				),
				array(
					'row1' => '<b>Check 1</b>', 'row2' => 'Reconciliation of number of assembly records', 'row3' => $totalNoOfAssemblies, 'row4' =>  $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/all_Assemblies_In_DB')
				),
				array(
					'row1' => '<b>a)</b>', 'row2' => 'Number of manual assemblies', 'row3' => $totalNumberOfManualAsseblies, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_manual_assemblies')
				),
				array(
					'row1' => '<b>b)</b>', 'row2' => 'Number of standard auto-assemblies', 'row3' => $totalstandardAutoAssemblies, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_standard_auto_assemblies')
				),
				array(
					'row1' => '<b>c)</b>', 'row2' => 'Number of reorder assemblies', 'row3' => $totalreorderAutoAssemblies, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_reorder_point_assemblies')
				),
				array(
					'row1' => '<b>d)</b>', 'row2' => 'Assemblies with only FG record', 'row3' => $AssemblieswithonlyFGrecord, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Assemblies_with_only_FG_record')
				),
				array(
					'row1' => '<b>e)</b>', 'row2' => 'Assemblies with only components record', 'row3' => $Assemblieswithonlycomponentsrecord, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Assemblies_with_only_components_records')
				),
				array(
					'row1' => '', 'row2' => '<span style="font-weight:bold;color:red;">Check</span>', 'row3' => '<span style="font-weight:bold;color:red;">'.$reconsilatonCheckCalucation.'</span>', 'row4' => ''
				),
				array(
					'row1' => '', 'row2' => '', 'row3' => '', 'row4' => ''
				),
				array(
					'row1' => '<b>Check 2</b>', 'row2' => '<b>Costing method Check</b>', 'row3' => '', 'row4' => ''
				),
				array(
					'row1' => '<b>A</b>', 'row2' => 'Number of assemblies at FIFO', 'row3' => $totaltotalNoOfAssembliesAtFifos, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_assemblies_at_FIFO')
				),
				array(
					'row1' => '', 'row2' => 'Number of standard auto-assemblies at FIFO', 'row3' => $totalstandardAutoAssembliesAtFifo, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_standard_auto_assemblies_at_FIFO')
				),
				array(
					'row1' => '', 'row2' => 'Number of manual assemblies at FIFO', 'row3' => $totalNoOfManualAssembliesAtFifo, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_manual_assemblies_at_FIFO')
				),
				array(
					'row1' => '', 'row2' => 'Number of reorder assemblies at FIFO', 'row3' => $totalreorderAutoAssembliesAtFifo,'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_reorder_point_assemblies_at_FIFO')
				),
				array(
					'row1' => '', 'row2' => '<span style="font-weight:bold;color:red;">Check</span>', 'row3' => '<span style="font-weight:bold;color:red;">'.$costingMethodCheckCalculation.'</span>', 'row4' => ''
				),
				array(
					'row1' => '', 'row2' => '', 'row3' => ' ', 'row4' => ''
				),
				array(
					'row1' => '<b>B</b>', 'row2' => 'Number of assemblies at Cost price', 'row3' => $NumberofAssembliesAtCostPric, 'row4' =>  $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_assemblies_at_Cost_price')
				),
				array(
					'row1' => '', 'row2' => 'Number of standard auto-assemblies at cost price', 'row3' => $standardAutoAssembliesAtCostPrice, 'row4' =>  $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_standard_auto_assemblies_at_cost_price')
				),
				array(
					'row1' => '', 'row2' => 'Number of manual assemblies at cost price', 'row3' => $NumberOfManualAssembliesAtCostPrice, 'row4' =>  $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_manual_assemblies_at_cost_price')
				),
				array(
					'row1' => '', 'row2' => 'Number of reorder assemblies at cost price', 'row3' => $reorderAutoAssembliesAtCostPrice, 'row4' =>  $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_reorder_assemblies_at_cost_price')
				),
				array(
					'row1' => '', 'row2' => '<span style="font-weight:bold;color:red;">Check</span>', 'row3' => '<span style="font-weight:bold;color:red;">'.$costingMethodCheckCalculation2.'</span>', 'row4' => ''
				),
				array(
					'row1' => '', 'row2' => '', 'row3' => '', 'row4' => ''
				),
				array(
					'row1' => '', 'row2' => '<b>BOM Checks</b>', 'row3' => '', 'row4' => ''
				),
				array(
					'row1' => '<b>Check 3</b>', 'row2' => 'Number of BOMs in db', 'row3' => $totalNoOfBom, 'row4' =>  $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_BOMs_in_db')
				),
				array(
					'row1' => '<b>Check 4</b>', 'row2' => 'Number of BOMs without components in db', 'row3' => $totalnoOfBomWithoutComponents, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_BOMs_without_components_in_db')
				),
				array(
					'row1' => '<b>Check 5</b>', 'row2' => 'Number of BOMs with auto-assembly checked', 'row3' => $totalnoOfBomWithAutoassemblyChecked, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_BOMs_with_auto_assembly_checked')
				),
				array(
					'row1' => '<b>Check 6</b>', 'row2' => 'Number of BOMs with auto-price update checked', 'row3' => $totalnoOfBomWithAutoPriceUpdateChecked, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_BOMs_with_auto_price_update_checked')
				),
				array(
					'row1' => '<b>Check 7</b>', 'row2' => 'Number of BOMs with components and 1 recipe not marked as default (FG created at 0)', 'row3' => $totalnoOfBomWithoutHavingAnySetAsDefaultReceipe, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/BOMs_with_components_and_1_recipe_not_marked_as_default') 
				),
				array(
					'row1' => '<b>Check 8</b>', 'row2' => 'Number of assemblies created with components taken from more than 1 warehouse (FG created at 0)', 'row3' => $totalNotMatchSourceAndTargetWarehouseDatas, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Number_of_assemblies_created_with_different_warehouse') 
				),array(
					'row1' => '<b>Check 9</b>', 'row2' => 'Consistency check between stock movements in db and in Brightpearl (assembly by assembly)', 'row3' => $identifyStockAdjIssueCount, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/identifyStockAdjIssue') 
				),array(
					'row1' => '<b>Check 10</b>', 'row2' => 'Total no. of Disassemblies', 'row3' => $totalnoofdisassemblies, 'row4' => $_SERVER['REQUEST_SCHEME'].':'.base_url('Assemblyreport/generateAsseblyReport/Total_no_of_disassemblies') 
				),
			);
			$subject = 'Assembly Report '.date("Y-m-d");
			$html .= '<table class="table table-hover">
			<thead>';
			foreach($reportDatas as $reportData){
				$background = "";
				if(!$reportData['row1'] && !$reportData['row2'] && !$reportData['row3'] && !$reportData['row4']){
					$background = 'style="background-color:#cccc"';
				}
				$downloadReport = '<td '.$background.'><a target="_blank" href="'.$reportData['row4'].'" class="btn btn-success green-meadow ">Download report</td>';
				if(!$reportData['row4']){
					$downloadReport = '<td '.$background.'></td>';
				}
				 $html .= ' <tr>
					<td '.$background.'>'.$reportData['row1'].'</td>
					<td '.$background.'>'.$reportData['row2'].'</td>
					<td '.$background.'>'.$reportData['row3'].'</td>
					'.$downloadReport.'
				  </tr>';
			}
			$html .='</thead></table>'; 
			$this->template->load_template("report",array('data' => $html),$this->session_data);
			/*======================= Assembly Generation End =============================*/
			
		}
	}
	public function identifyStockAdjIssueCount(){
		$this->brightpearl->reInitialize();
		$productMappings = array();
		$productMappingsTemps = $this->db->select("productId,sku")->get("products")->result_array();
		foreach($productMappingsTemps as $productMappingsTemp){
			$productMappings[$productMappingsTemp['productId']] = $productMappingsTemp;
		}
		$warehouseMappings = array();
		// warehouse mapping
		$savedWarehouseDataTemps = reset($this->brightpearl->getAllLocation()); 
		if($savedWarehouseDataTemps){
			foreach($savedWarehouseDataTemps as $savedWarehouseDataTemp){
				$warehouseMappings[$savedWarehouseDataTemp['id']] = array(
					'id' => $savedWarehouseDataTemp['id'],
					'name' => $savedWarehouseDataTemp['name'],
				);
			}
		}
		foreach($this->brightpearl->accountDetails as $accountId => $accountDetails){
			$url = 'warehouse-service/goods-movement-search?goodsNoteTypeCode=SC';
			$response      = $this->brightpearl->getCurl($url,'get','','json',$accountId)[$accountId];
			$header = array_column($response['metaData']['columns'],'name');
			$ids = array();
			if ($response['results']) {
				foreach ($response['results'] as $results) {
					$searchOrderResult = array_combine($header,$results);
					$ids[$searchOrderResult['warehouseId']][] = $searchOrderResult['goodsNoteId'];
				}
				if ($response['metaData']) {
					for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
						$url1      = $url . '&firstResult=' . $i;
						$response1 = $this->brightpearl->getCurl($url1,'get','','json',$accountId)[$accountId];
						if ($response1['results']) {
							foreach ($response1['results'] as $results) {
								$searchOrderResult = array_combine($header,$results);
								$ids[$searchOrderResult['warehouseId']][] = $searchOrderResult['goodsNoteId'];
							}
						}
					}
				}
			}
			$foundAssemblyLists = array();
			foreach($ids as $wareId => $id){
				$id = array_unique($id);
				sort($id);
				$idTemps = array_chunk($id,200);
				foreach($idTemps as $idTemp){
					$url = 'warehouse-service/warehouse/'.$wareId.'/stock-correction/'.implode(",",$idTemp);
					$response1 = $this->brightpearl->getCurl($url,'get','','json',$accountId)[$accountId];
					foreach($response1 as $response){
						if(substr_count(strtolower($response['reason']),'assembly')){
							foreach($response['goodsMoved'] as $goodsMoved){
								$foundAssemblyLists[$response['reason']][$goodsMoved['productId']][] = $goodsMoved;
							}
						}
					}
				}
			}
			$onlyPositiveAssemblys = array();
			foreach($foundAssemblyLists as $reason => $foundAssemblyList){
				$negativeFound = 0;
				foreach($foundAssemblyList as $proId => $foundAssemblyLis){
					foreach($foundAssemblyLis as $key => $foundAssemblyL){
						if($foundAssemblyL['quantity'] < 0){
							$negativeFound = 1;
						}
					}
				}
				if(!$negativeFound){
					$onlyPositiveAssemblys[$reason] = $foundAssemblyList;
				}
			}
			
			$generateCsv = array();
			foreach($foundAssemblyLists as $reson => $foundAssemblyList){
				$assemblyIdTemp = preg_split("/Assembly Id/i",$reson);
				$assemblyId = end($assemblyIdTemp);
				$assemblyId = trim($assemblyId);
				$assemblyId = trim($assemblyId,":");
				$assemblyId = trim($assemblyId);
				foreach($foundAssemblyList as $productId => $foundAssemblyLiss){	
					foreach($foundAssemblyLiss as $foundAssemblyLis){
						$warehouseName = "";$sku = "";$qty = "";$warehouseId = "";
						$sku = $productMappings[$productId]['sku'];
						$qty = $foundAssemblyLis['quantity'];
						$price = $foundAssemblyLis['productValue']['value'];
						$warehouseId = $foundAssemblyLis['destinationLocationId'];
						$createdOn = gmdate('Y-m-d H:i:s',strtotime($foundAssemblyLis['createdOn']));
						$warehouseName = $warehouseMappings[$warehouseId]['name'];
						if(!$warehouseName){
							$warehouseName = $warehouseId; // assigning warehouseid if warehouse name not found from mapping
						}
						$generateCsv[$assemblyId] = array(
							$assemblyId,
							$sku,
							$productId,
							$qty,
							$warehouseName,
							$price,
							$createdOn,
						);
					}
				}
			}
			$assemblyCount = 0;
			if($generateCsv){
				$assemblyCount = count(array_keys($generateCsv));
			}
		}
		return $assemblyCount;
	}
	
	public function identifyStockAdjIssue(){
		$this->brightpearl->reInitialize();
		$productMappings = array();
		$productMappingsTemps = $this->db->select("productId,sku")->get("products")->result_array();
		foreach($productMappingsTemps as $productMappingsTemp){
			$productMappings[$productMappingsTemp['productId']] = $productMappingsTemp;
		}
		$warehouseMappings = array();
		$savedWarehouseDataTemps = reset($this->brightpearl->getAllLocation());
		if($savedWarehouseDataTemps){
			foreach($savedWarehouseDataTemps as $savedWarehouseDataTemp){
				$warehouseMappings[$savedWarehouseDataTemp['id']] = array(
					'id' => $savedWarehouseDataTemp['id'],
					'name' => $savedWarehouseDataTemp['name'],
				);
			}
		}
		
		foreach($this->brightpearl->accountDetails as $accountId => $accountDetails){
			$locationurl	= '/warehouse-service/location-search';
			$locationresponse		= $this->brightpearl->getCurl($locationurl,'get','','json',$accountId)[$accountId];
			$locationsIds = array();
			foreach($locationresponse['results'] as  $result){
				$warehouseNamess = "";
				$warehouseNamess = $result['3'].'.'.$result['4'].'.'.$result['5'].'.'.$result['6'];
				$warehouseNamess = str_replace('..', '', $warehouseNamess);
				$warehouseNamess = str_replace('...', '', $warehouseNamess);
				$locationsIds[$result['0']] = array(
					'id' => $result['0'],
					'name' => rtrim($warehouseNamess, '.'),
					'warehouseId' => $result['1'],
					'accountId' => $accountId,
				);
			}
			$url = 'warehouse-service/goods-movement-search?goodsNoteTypeCode=SC';
			$response      = $this->brightpearl->getCurl($url,'get','','json',$accountId)[$accountId];
			$header = array_column($response['metaData']['columns'],'name');
			$ids = array();
			if ($response['results']) {
				foreach ($response['results'] as $results) {
					$searchOrderResult = array_combine($header,$results);
					$ids[$searchOrderResult['warehouseId']][] = $searchOrderResult['goodsNoteId'];
				}
				if ($response['metaData']) {
					for ($i = 500; $i <= $response['metaData']['resultsAvailable']; $i = ($i + 500)) {
						$url1      = $url . '&firstResult=' . $i;
						$response1 = $this->brightpearl->getCurl($url1,'get','','json',$accountId)[$accountId];
						if ($response1['results']) {
							foreach ($response1['results'] as $results) {
								$searchOrderResult = array_combine($header,$results);
								$ids[$searchOrderResult['warehouseId']][] = $searchOrderResult['goodsNoteId'];
							}
						}

					}
				}
			}
			$foundAssemblyLists = array();
			foreach($ids as $wareId => $id){
				$id = array_unique($id);
				sort($id);
				$idTemps = array_chunk($id,200);
				foreach($idTemps as $idTemp){
					$url = 'warehouse-service/warehouse/'.$wareId.'/stock-correction/'.implode(",",$idTemp);
					$response1 = $this->brightpearl->getCurl($url,'get','','json',$accountId)[$accountId];
					foreach($response1 as $response){
						if(substr_count(strtolower($response['reason']),'assembly')){
							foreach($response['goodsMoved'] as $goodsMoved){
								$foundAssemblyLists[$response['reason']][$goodsMoved['productId']][] = $goodsMoved;
							}
						}
					}
				}
			}
			$onlyPositiveAssemblys = array();
			foreach($foundAssemblyLists as $reason => $foundAssemblyList){
				$negativeFound = 0;
				foreach($foundAssemblyList as $proId => $foundAssemblyLis){
					foreach($foundAssemblyLis as $key => $foundAssemblyL){
						if($foundAssemblyL['quantity'] < 0){
							$negativeFound = 1;
						}
					}
				}
				if(!$negativeFound){
					$onlyPositiveAssemblys[$reason] = $foundAssemblyList;
				}
			}
			$generateCsv = array(
				array(
					'BOM ID',
					'SKU',
					'ProductId',
					'Qty',
					'Warehouse',
					'Price',
					'Created',
				)
			);
			foreach($foundAssemblyLists as $reson => $foundAssemblyList){
				$assemblyIdTemp = preg_split("/Assembly Id/i",$reson);
				$assemblyId = end($assemblyIdTemp);
				$assemblyId = trim($assemblyId);
				$assemblyId = trim($assemblyId,":");
				$assemblyId = trim($assemblyId);
				foreach($foundAssemblyList as $productId => $foundAssemblyLiss){	
					foreach($foundAssemblyLiss as $foundAssemblyLis){
						$warehouseName = "";$sku = "";$qty = "";$price = "";$warehouseId = "";
						$sku = $productMappings[$productId]['sku'];
						$qty = $foundAssemblyLis['quantity'];
						$price = $foundAssemblyLis['productValue']['value'];
						$destinationLocationId = $foundAssemblyLis['destinationLocationId'];
						$createdOn = gmdate('Y-m-d H:i:s',strtotime($foundAssemblyLis['createdOn']));
						$warehouseId = $locationsIds[$destinationLocationId]['warehouseId'];
						$warehouseName = $warehouseMappings[$warehouseId]['name'];
						if(!$warehouseName){
							$warehouseName = $warehouseId; // assigning warehouseid if warehouse name not found from mapping
						}
						$generateCsv[] = array(
							$assemblyId,
							$sku,
							$productId,
							$qty,
							$warehouseName,
							$price,
							$createdOn,
						);
					}
				}
			}
			
			error_reporting('0');
			$filename = "BP-AS-DATA-" . date('Y-m-d') . ".csv"; 
			$f = fopen('php://memory', 'w'); 
			foreach($generateCsv as $finalLi){
				fputcsv($f, $finalLi); 
			}
			fseek($f, 0); 
			header('Content-Type: text/csv'); 
			header('Content-Disposition: attachment; filename="' . $filename . '";'); 
			fpassthru($f); 
			die();
		}
	}
	
	public function getBinLocation(){
		// warehouse bin location mapping
		$warehouseBinLocationMappings = array();
        $warehouseBinLocationDataTemps = $this->db->get_where('warehouse_binlocation')->result_array();
		if($warehouseBinLocationDataTemps){
			foreach($warehouseBinLocationDataTemps as $warehouseBinLocationDataTemp){
				$warehouseBinLocationMappings[$warehouseBinLocationDataTemp['warehouseId']][$warehouseBinLocationDataTemp['id']] = array(
					'id' => $warehouseBinLocationDataTemp['id'],
					'name' => $warehouseBinLocationDataTemp['name'],
				);
			}
		}
		return $warehouseBinLocationMappings;
	}
	public function getWarehouseMaster(){
		$savedWarehouseDataTemps = $this->db->get_where('warehouse_master')->result_array();
		$warehouseLists = array();
		if($savedWarehouseDataTemps){
			foreach($savedWarehouseDataTemps as $savedWarehouseDataTemp){
				$warehouseLists[$savedWarehouseDataTemp['warehouseId']] = $savedWarehouseDataTemp;
			}
		}
		return $warehouseLists;
	}
	public function generateAssemblyCsv($reportDatas, $gitcommitVersion = "", $reportType = "", $accountCode = ""){
		// warehouse mapping
		$getWarehouseMaster = array();
		$savedWarehouseDataTemps = reset($this->brightpearl->getAllLocation());
		if($savedWarehouseDataTemps){
			foreach($savedWarehouseDataTemps as $savedWarehouseDataTemp){
				$getWarehouseMaster[$savedWarehouseDataTemp['id']] = array(
					'id' => $savedWarehouseDataTemp['id'],
					'name' => $savedWarehouseDataTemp['name'],
				);
			}
		}
		$fileName = $this->getFileName($reportType, $accountCode, $gitcommitVersion);
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . basename($fileName));
		$file   = fopen('php://output', 'w');
		$header = array('Assembly Id','Warehouse','Product Id','Bom Sku','Cost Method', 'Is Bom', 'Created Date');
		fputcsv($file, $header); 
		foreach ($reportDatas as $assemblyId => $reportData) {
			foreach($reportData as $report){
				$row = array(
					$report['createdId'],
					$getWarehouseMaster[$report['warehouse']]['warehouseName'],
					$report['productId'],
					$report['sku'],
					$report['costingMethod'],
					$report['isAssembly'] == 1 ? "BOM" : "Component",
					$report['created'],
				);
				fputcsv($file, $row);
			}
		}
		fclose($file);
	}
	public function generateDisAssemblyCsv($reportDatas, $gitcommitVersion = "", $reportType = "", $accountCode = ""){
		// warehouse mapping
		$getWarehouseMaster = array();
		$savedWarehouseDataTemps = reset($this->brightpearl->getAllLocation());
		if($savedWarehouseDataTemps){
			foreach($savedWarehouseDataTemps as $savedWarehouseDataTemp){
				$getWarehouseMaster[$savedWarehouseDataTemp['id']] = array(
					'id' => $savedWarehouseDataTemp['id'],
					'name' => $savedWarehouseDataTemp['name'],
				);
			}
		}
		$fileName = $this->getFileName($reportType, $accountCode, $gitcommitVersion);
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . basename($fileName));
		$file   = fopen('php://output', 'w');
		$header = array('Disassembly Id','Warehouse','Product Id','Sku','Is Bom', 'Created Date');
		fputcsv($file, $header); 
		foreach ($reportDatas as $assemblyId => $reportData) {
			foreach($reportData as $report){
				$row = array(
					$report['createdId'],
					$getWarehouseMaster[$report['warehouse']]['name'],
					$report['productId'],
					$report['sku'],
					$report['isDeassembly'] == 1 ? "BOM" : "Component",
					$report['created'],
				);
				fputcsv($file, $row);
			}
		}
		fclose($file);
	}
	
	public function generateBomCsv($reportDatas, $gitcommitVersion = "", $reportType = "", $accountCode = ""){
		$fileName = $this->getFileName($reportType, $accountCode, $gitcommitVersion);
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . basename($fileName));
		$file   = fopen('php://output', 'w');
		$header = array('Bom Product Id','Bom Sku','Created Date');
		fputcsv($file, $header);
		foreach ($reportDatas as $productId => $report) {
			$row = array(
				$report['productId'],
				$report['sku'],
				$report['created'],
			);
			fputcsv($file, $row);
		}
		fclose($file);
	}
	public function getFileName($fileType = '', $account_code = '', $gitcommitVersion = ''){
		$fileName = $account_code . '_'.date("Ymd") . $gitcommitVersion;
		if($fileType){
			$fileName .= '_'.$fileType.'.csv';
		}
        return $fileName;
    }
} 