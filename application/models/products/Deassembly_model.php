<?php
class Deassembly_model extends CI_Model
{
	public $productMapping,$logs = array(),$path,$deleteLogs = array(),$deleteLogPath;
    public function __construct()
    {
        parent::__construct();
    }
    public function getProductStock($productIds)
    {
        $return = array();
        if ($productIds) {
            $productIds   = array_unique($productIds);
            $proListDatas = $this->{$this->globalConfig['fetchProduct']}->getProductStockAssembly($productIds);
            foreach ($proListDatas as $proListData) {
                foreach ($proListData as $proIds => $proList) {
                    $return[$proIds] = $proList;
                }
            }
        }
        return $return;
    }
	
    public function warehouseList()
    {
        $return        = array();
        $locationLists = $this->{$this->globalConfig['fetchProduct']}->getAllLocation();
        foreach ($locationLists as $locationList) {
            foreach ($locationList as $warehouseId => $location) {
                $return[$warehouseId] = $location;
            }
        }
        return $return;
    }
	
	public function warehouseListDb()
    {
		$return = array();
        $savedWarehouseDataTemps = $this->db->get_where('warehouse_master')->result_array();
		$warehouseLists = array();
		if($savedWarehouseDataTemps){
			foreach($savedWarehouseDataTemps as $savedWarehouseDataTemp){
				$return[$savedWarehouseDataTemp['warehouseId']] = array(
					'id' => $savedWarehouseDataTemp['warehouseId'],
					'name' => $savedWarehouseDataTemp['warehouseName'],
				);
			}
		}
        return $return;
    }
    public function getAllPriceList()
    {
        $return           = array();
        $getAllPriceLists = $this->{$this->globalConfig['fetchProduct']}->getAllPriceList();
        foreach ($getAllPriceLists as $getAllPriceList) {
            foreach ($getAllPriceList as $listId => $getAllPrice) {
                $return[$listId] = $getAllPrice;
            }
        }
        return $return;
    }
    public function getDefaultWarehouseLocation($locationId)
    {
        $getDefaultWarehouseLocation = $this->{$this->globalConfig['fetchProduct']}->getDefaultWarehouseLocation($locationId);
        $return                      = array_shift($getDefaultWarehouseLocation);
        return $return;
    }
    public function getProductPrice($productId)
    {
        $getAllPriceLists = $this->{$this->globalConfig['fetchProduct']}->getProductPriceList($productId);
        return $getAllPriceLists;
    }

    public function saveDeassembly($datas){
		
        if (!$datas['productId']) {
			return false;
		}
		$logtime = date('c'); $this->logs = array(); 
		$createdId = uniqid('DIS'.date('s'));
		$this->path = FCPATH.'logs'.DIRECTORY_SEPARATOR.'disassembly'.DIRECTORY_SEPARATOR . $createdId.'.json';
		if(!is_dir(dirname($this->path))) { @mkdir(dirname($this->path),0777,true);@chmod(dirname($this->path), 0777); }
		
		$user_session_data  = $this->session->userdata('login_user_data');
		$isError			= 0;
		$config				= $this->db->get('account_' . $this->globalConfig['fetchProduct'] . '_config')->row_array();
		$priceList			= $this->getProductPrice($datas[$datas['receipeid']]['productId']);
		
		$defaultWareHouse[$datas['targetwarehouse']] = $this->getDefaultWarehouseLocation($datas['targetwarehouse']);
		@$postStockTransferArrays[$datas['targetwarehouse']][] = array(
			'quantity'  => (-1) * $datas['qtydiassemble'],
			'productId' => $datas['productId'],
			'locationId' => ($datas['targetBinLocation'])?($datas['targetBinLocation']):($defaultWareHouse[$datas['targetwarehouse']]),
			'sku' => $datas['sku'],
			'name' => $datas['name'],
			'reason'    => 'Disassembly id: '.$createdId,                
		);
		$saveReceipeDatas = $this->db->get_where('product_bom', array('productId' => $datas['productId'], 'receipeid' => $datas['receipeid']))->result_array();
		$saveReceipe      = array();
		$billcomponents = $datas['billcomponents'];			
		foreach ($saveReceipeDatas as $saveReceipeData) {
			$receipeId = $saveReceipeData['receipeId'];
			if(isset($billcomponents[$receipeId][$saveReceipeData['componentProductId']])){
				$saveReceipeData['qty'] = $billcomponents[$receipeId][$saveReceipeData['componentProductId']]['qty'];
			}
			$saveReceipe[$saveReceipeData['componentProductId']] = $saveReceipeData;
		}
		$this->logs['Log time'] 	  = $logtime;
		$this->logs['Post Request']   = $datas;
		$this->logs['Saved Receipe']  = $saveReceipe;
		$this->logs['Costing Method'] = $datas['costingmethod'];
		foreach ($datas[$datas['receipeid']]['productId'] as $key => $productId) {
			$sourceWareHouseId = @$datas[$datas['receipeid']]['sourcewarehouse'][$key];
			$sourceBinLocation = @$datas[$datas['receipeid']]['sourceBinLocation'][$key];
			$sourceBinLocation = $this->getBinLocationByWarehouse($sourceWareHouseId, $sourceBinLocation);
			if($sourceWareHouseId){
				if(@!$defaultWareHouse[$sourceWareHouseId]){
					@$defaultWareHouse[$sourceWareHouseId] = $this->getDefaultWarehouseLocation($sourceWareHouseId);
				}	
				$price = $datas[$datas['receipeid']]['deassemblyPrice'][$key];
				if(!$price){
					$price = $priceList[$productId][$datas['costingmethod']];
				}
				if(!$price){
					$price = 0.00;
				}
				if($saveReceipe[$productId]['qty']){
					$qty = (int)($saveReceipe[$productId]['qty'] * ($datas['qtydiassemble'] / $saveReceipe[$productId]['bomQty']));
					if($qty != 0){
						$postStockTransferArrays[$datas[$datas['receipeid']]['sourcewarehouse'][$key]][] = array(
							'quantity'  =>  ($saveReceipe[$productId]['qty'] * ($datas['qtydiassemble'] / $saveReceipe[$productId]['bomQty'])),
							'productId' => $productId,
							'locationId' => ($sourceBinLocation['id'])?($sourceBinLocation['id']):($defaultWareHouse[$sourceWareHouseId]),
							'sku'		=> $saveReceipe[$productId]['sku'],
							'name'		=> $saveReceipe[$productId]['name'],
							'reason'    => 'Disassembly of product. Disassembly id: '.$createdId,
							'cost'      => array(
								'currency' => $config['currencyCode'],
								'value'    => $price,
							),
						);
					}
				}
			}
		}
		$this->logs['Stock Correction']['Pre Request Data'] = $postStockTransferArrays;
		foreach ($postStockTransferArrays as $warehouseId => $postStockTransferArray){
			$url            = '/warehouse-service/warehouse/' . $warehouseId . '/stock-correction';
			$postStockTrans['corrections'] = array();
			$count          = 0;
			foreach ($postStockTransferArray as $key => $postStockTransfers) {
				$postStockTrans['corrections'][] = $postStockTransfers;
			}
			$getLocation = $this->{$this->globalConfig['fetchProduct']}->response;
			$results = $this->{$this->globalConfig['fetchProduct']}->postStockCorrection($url, $postStockTrans);
			$this->logs['Stock Correction']['Location'] = $getLocation;
			$this->logs['Stock Correction']['URL'] 		= $url;
			$this->logs['Stock Correction']['Request'] 	= $postStockTrans;
			$this->logs['Stock Correction']['Response'] = $results;
			foreach ($results as $accountId => $result) {
				foreach ($result as $key => $rows) {
					if($key === 'errors'){
						$isError = true;
					}
					else{
						if($rows){
							$saveArray = array(
								'productId'         => @$postStockTrans['corrections'][$key]['productId'],
								'sku'         		=> @$postStockTrans['corrections'][$key]['sku'],
								'name'         		=> @$postStockTrans['corrections'][$key]['name'],
								'receipId'          => @$datas['receipeid'],
								'qty'               => @$postStockTrans['corrections'][$key]['quantity'],
								'isDeassembly'      => @($postStockTrans['corrections'][$key]['quantity'] < 0)?'1':'0',
								'warehouse'         => @$warehouseId, 
								'locationId'        => @$postStockTrans['corrections'][$key]['locationId'],
								'currencyCode'      => @$postStockTrans['corrections'][$key]['cost']['currency'],
								'price'             => @$postStockTrans['corrections'][$key]['cost']['value'],
								'createdTransferId' => @$rows,
								'createdId' 		=> $createdId,
								'status' 			=> '1',
								'username' 			=> @$user_session_data['username'],
								'ip' 				=> @$_SERVER['REMOTE_ADDR'],
							);
							$this->db->insert('product_deassembly', $saveArray);
						}
					}                        
				}
			}
		}
		if($this->logs){
			file_put_contents($this->path,json_encode($this->logs),FILE_APPEND); 
		}
		if($isError == '1'){
			echo json_encode(array('status' => '0','message' => json_encode($results)));
		}
		else{
			echo json_encode(array('status' => '1','message' => 'Disassembly  successfully created<br> Created id : <b>'.$createdId.'</b>'));
		}
		die();
    }    
	public function getBinLocationByWarehouse($warehouseId, $binLocation){
		return $this->db->get_where('warehouse_binlocation',array('name' => $binLocation,'warehouseId' => $warehouseId))->row_array();
	}
    public function getProduct()
    {
        $groupAction     = $this->input->post('customActionType');
        $records         = array();
        $records["data"] = array();
        if ($groupAction == 'group_action') {
            $ids = $this->input->post('id');
            if ($ids) {
                $status = $this->input->post('customActionName');
                if ($status != '') {
                    $this->db->where_in('id', $ids)->update('product_deassembly', array('status' => $status));
                    $records["customActionStatus"]  = "OK"; // pass custom message(useful for getting status of group actions)
                    $records["customActionMessage"] = "Group action successfully has been completed. Well done!"; // pass custom message(useful for getting status of group actions)
                }
            }
        }

        $where = array('isDeassembly' => '1');
        $query = $this->db;
        if ($this->input->post('action') == 'filter') {
            if (trim($this->input->post('productId'))) {
                $where['productId'] = trim($this->input->post('productId'));
            }
            if (trim($this->input->post('createdId'))) {
                $where['createdId'] = trim($this->input->post('createdId'));
            }            
            if (trim($this->input->post('sku'))) {
                $where['sku'] = trim($this->input->post('sku'));
            }
			if (trim($this->input->post('name'))) {
                $where['name'] = trim($this->input->post('name'));
            }           
            if (trim($this->input->post('status')) >= '0') {
                $where['status'] = trim($this->input->post('status'));
            }
			if (trim($this->input->post('username'))) {
                $where['username'] = trim($this->input->post('username'));
            } 
        }
        if (trim((string)$this->input->post('updated_from')) ?? '') {
            $query->where('date(created) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim((string)$this->input->post('updated_to')) ?? '') {
            $query->where('date(created) <= ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }

        $totalRecord = @$query->get('product_deassembly')->num_rows(); 
        $limit       = intval($this->input->post('length'));
        $limit       = $limit < 0 ? $totalRecord : $limit;
        $start       = intval($this->input->post('start'));

        $query = $this->db;
        if (trim((string)$this->input->post('updated_from')) ?? '') {
            $query->where('date(created) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim((string)$this->input->post('updated_to')) ?? '') {
            $query->where('date(created) <= ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }

        $status              = array('0' => 'Work in Progess', '1' => 'Completed', '2' => 'Updated', '3' => 'Error', '4' => 'Archive');
        $statusColor         = array('0' => 'default', '1' => 'success', '2' => 'info', '3' => 'warning', '4' => 'danger');
        $displayProRowHeader = array('id', 'username', 'createdId', 'productId', 'sku', 'name','status', 'created');
        if ($this->input->post('order')) {
            foreach ($this->input->post('order') as $ordering) {
                if (@$displayProRowHeader[$ordering['column']]) {
                    $query->order_by($displayProRowHeader[$ordering['column']], $ordering['dir']);
                }
            }
        }
        $datas = $query->select('id,productId,createdId,receipId,sku,created,name,status,username')->limit($limit, $start)->get('product_deassembly')->result_array();
        foreach ($datas as $data) {
            $records["data"][] = array(
                '<input type="checkbox" name="id[]" value="' . $data['id'] . '">',
				ucwords($data['username']),
                $data['createdId'],
                $data['productId'], 
                $data['sku'],
				$data['name'],
				'<span class="label label-sm label-' . $statusColor[$data['status']] . '">' . $status[$data['status']] . '</span>',
                date('M d,Y h:i:s a',strtotime($data['created'])),
				'<div class="btn-group">
					<a class="btn btn-circle btn-default dropdown-toggle" href="javascript:;" data-toggle="dropdown">
						<i class="fa fa-share"></i>
						<span class="hidden-xs"> Tools </span>
						<i class="fa fa-angle-down"></i>
					</a>
					<div class="dropdown-menu pull-right">
						<li>
							<a target="_blank" class="actioneditbtn" href="' . base_url('products/deassembly/viewdeassembly/' . $data['createdId']) . '"> View Disassembly </a>
						</li>
						<li><a class="actioneditbtn" target="_blank" href="' . base_url('products/deassembly/viewlog/' . $data['createdId']) . '" title="View Log">View Log</a></li>
					</div>
				</div>',
            );
        }
        $draw                       = intval($this->input->post('draw'));
        $records["draw"]            = $draw;
        $records["recordsTotal"]    = $totalRecord;
        $records["recordsFiltered"] = $totalRecord;
        return $records;
    }
    public function saveReceipe($productId, $datas){
        if (is_array($datas)) {
            foreach ($datas['sku'] as $i => $sku) {
                $qty       = @$datas['qty'][$i];
                $name      = @$datas['name'][$i];
                $receipeid = @$datas['receipeid'][$i];
                $id        = @$datas['savebomid'][$i];
                if (($sku) && ($qty)) {
                    $saveArray = array(
                        'productId' => $productId,
                        'sku'       => $sku,
                        'name'      => $name,
                        'qty'       => $qty,
                        'receipeid' => ($receipeid) ? ($receipeid) : '1',
                    );
                    if ($id) {
                        $saveArray['id'] = $id;
                        $this->db->where(array('id' => $saveArray['id']))->update('product_bom', $saveArray);
                    } else {
                        $this->db->insert('product_bom', $saveArray);
                    }
                }
            }
        }
    }
}
