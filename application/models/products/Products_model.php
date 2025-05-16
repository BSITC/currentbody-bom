<?php
class Products_model extends CI_Model
{
	public $ci;
    public function __construct()
    {
        parent::__construct();
    }
    public function fetchProducts($productId = '' ){
		$this->ci      = &get_instance();
		$datas    = $this->ci->db->order_by('id', 'desc')->get_where('cron_management', array('type' => 'bpproduct'))->row_array();
		$cronTime = ($datas['saveTime']) ? (date('Y-m-d\TH:i:s',$datas['saveTime'])) : ('');
		$saveTime = date('Y-m-d\TH:i:s',strtotime('-5 days'));
		$productDatass = $this->{$this->globalConfig['fetchProduct']}->fetchProducts($productId, $cronTime);
		$productDatas = $productDatass['return'];
		if(@$productDatass['saveTime']){
			$saveTime = $productDatass['saveTime'] - (60*560);
		}
		$saveDatasTemps = $this->db->select('id,productId,account1Id,account2Id,sku,size,color,size,status')->get('products')->result_array();
		$saveDatas = array();
		$saveId2Datas = array();
		foreach ($saveDatasTemps as $saveDatasTemp) {
			$key = @trim(preg_replace("/[^a-zA-Z_0-9\s_\.\(\)\-]/", "", strtolower($saveDatasTemp['account1Id'].'_'.$saveDatasTemp['account2Id'].'_'.$saveDatasTemp['productId'])));
			$saveDatas[$key] = $saveDatasTemp;
		}
		$batchInsert = array(); $batchUpdate = array();
		foreach ($productDatas as $account1Id => $productData) {
			foreach ($productData as $row) {
				if((!$row['account1Id'])){
					continue;
				}
				$key =  @trim(preg_replace("/[^a-zA-Z_0-9\s_\.\(\)\-]/", "", strtolower($row['account1Id'].'_'.$row['account2Id'].'_'.$row['productId'])));
				if(@$saveDatas[$key]){
					$row['id'] = $saveDatas[$key]['id'];
					$this->db->where(array('id' => $row['id']))->update('products',$row);
					$afftectedRows = $this->db->affected_rows();
					if($afftectedRows){
						$row['status'] = ($saveDatas[$key]['status'])?(2):0;
						$batchUpdate[] = $row; 
						$path = FCPATH.'logs'.DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR . $row['productId']. DIRECTORY_SEPARATOR. date("Ymd-His-").strtoupper(uniqid()).'.logs';
						if(!is_dir(dirname($path))) { mkdir(dirname($path),0777,true);chmod(dirname($path), 0777); }
						$logs = $row['params'];
						file_put_contents($path,$logs,FILE_APPEND);
					}
				}
				else{
					if($row['isBundle']){
						continue;
					}
					$batchInsert[] = $row;
					$path = FCPATH.'logs'.DIRECTORY_SEPARATOR.'products'.DIRECTORY_SEPARATOR . $row['productId']. DIRECTORY_SEPARATOR. date("Ymd-His-").strtoupper(uniqid()).'.logs';
					if(!is_dir(dirname($path))) { mkdir(dirname($path),0777,true);chmod(dirname($path), 0777); }
					$logs = $row['params'];
					file_put_contents($path,$logs,FILE_APPEND);
				}
			}
		}		
		$checkInsertFlag = false;
		if($batchInsert){
			$batchInserts = array_chunk($batchInsert, 1000);
			$checkInsertFlag = true;
			foreach ($batchInserts as $key => $batchInsert) {
				$insertedBatchRes = $this->db->insert_batch('products', $batchInsert);
			}
		}
		if($batchUpdate){
			$batchUpdates = array_chunk($batchUpdate, 1000);
			$checkInsertFlag = true;
			foreach ($batchUpdates as $key => $batchUpdate) {
				$batchUpdateRes = $this->db->update_batch('products',$batchUpdate, 'id');
			}
		}
		if($checkInsertFlag){
			$this->ci->db->insert('cron_management', array('type' => 'bpproduct', 'runTime' => $cronTime, 'saveTime' => $saveTime)); 
		}
		$this->fetchProductsPrice();
    }
	public function fetchProductsPrice($productId = ''){
		$this->brightpearl->reInitialize();
		$productMappingss = array();
		$temps = $this->ci->db->select('productId,CostPrice,account1Id')->get_where('products')->result_array();
		foreach($temps as $temp){
			$productMappingss[$temp['account1Id']][$temp['productId']] = $temp;
		}
		if(!$productMappingss){
			return false;
		}
		foreach($productMappingss as $account1Id => $productMappings){
			$productIdlist 	 = array_keys($productMappings);$batchUpdate = array();
			$config = $this->{$this->globalConfig['fetchProduct']}->accountConfig[$account1Id];
			$priceInfosDatas = $this->{$this->globalConfig['fetchProduct']}->getProductPriceListNew($productIdlist,'0','1');
			if(!$priceInfosDatas[$account1Id]){
				return false;
			}
			foreach($priceInfosDatas[$account1Id] as $productId => $priceInfo){
				$productMapping = $productMappings[$productId];
				if(!$productId){continue;}
				if(!$productMapping){continue;}
				$costPrice = 0.00;
				$defaultCostPriceListId  = $config['costPriceListbom'];
				if($defaultCostPriceListId == 'fifo'){
					$defaultCostPriceListId = $config['costPriceListbomNonTrack'];
				}
				$costPrice = $priceInfo[$defaultCostPriceListId];
				if($costPrice != $productMapping['costPrice']){
					$batchUpdate[] = array(
						'productId' 	=> $productId,
						'CostPrice' 	=> $costPrice ? $costPrice : 0.00,
					);
				}
			}
			if($batchUpdate){
				$batchUpdate = array_chunk($batchUpdate,1000);
				foreach($batchUpdate as $update){
					$this->ci->db->update_batch('products',$update,'productId');
				}
			}
		}
	}
	
    public function postProducts($productId = ''){
       $this->{$this->globalConfig['postProduct']}->postProducts($productId);
    }
    public function getProduct(){
        $groupAction     = $this->input->post('customActionType');
        $records         = array();
        $records["data"] = array();
        if ($groupAction == 'group_action') {
            $ids = $this->input->post('id');
            if ($ids) {
                $status = $this->input->post('customActionName');
                if ($status != '') {
                    $this->db->where_in('id', $ids)->update('products', array('status' => $status));
                    $records["customActionStatus"]  = "OK"; // pass custom message(useful for getting status of group actions)
                    $records["customActionMessage"] = "Group action successfully has been completed. Well done!"; // pass custom message(useful for getting status of group actions)
                }
            }
        }

        $where = array();
        $query = $this->db;
        if ($this->input->post('action') == 'filter') {
            if (trim($this->input->post('productId'))) {
                $where['productId'] = trim($this->input->post('productId'));
            }
            if (trim($this->input->post('createdProductId'))) {
                $where['createdProductId'] = trim($this->input->post('createdProductId'));
            }
            if (trim($this->input->post('name'))) {
                $where['name'] = trim($this->input->post('name'));
            }
            if (trim($this->input->post('sku'))) {
                $where['sku'] = trim($this->input->post('sku'));
            }
            if (trim($this->input->post('color'))) {
                $where['color'] = trim($this->input->post('color'));
            }
            if (trim($this->input->post('size'))) {
                $where['size'] = trim($this->input->post('size'));
            }
            if (trim($this->input->post('status')) >= '0') {
                $where['status'] = trim($this->input->post('status'));
            }
        }
        if (trim($this->input->post('updated_from'))) {
            $query->where('date(updated) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim($this->input->post('updated_to'))) {
            $query->where('date(updated) < ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }

        $totalRecord = @$query->select('count("id") as countpro')->get('products')->row_array()['countpro'];
        $limit       = intval($this->input->post('length'));
        $limit       = $limit < 0 ? $totalRecord : $limit;
        $start       = intval($this->input->post('start'));

        $query = $this->db;
        if (trim($this->input->post('updated_from'))) {
            $query->where('date(updated) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim($this->input->post('updated_to'))) {
            $query->where('date(updated) < ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }

        $status              = array('0' => 'Pending', '1' => 'Sent', '2' => 'Updated', '3' => 'Error', '4' => 'Archive');
        $statusColor         = array('0' => 'default', '1' => 'success', '2' => 'info', '3' => 'warning', '4' => 'danger');
        $displayProRowHeader = array('id', 'productId', 'createdProductId', 'name', 'sku', 'color', 'updated', 'status','size');
        if ($this->input->post('order')) {
            foreach ($this->input->post('order') as $ordering) {
                if (@$displayProRowHeader[$ordering['column']]) {
                    $query->order_by($displayProRowHeader[$ordering['column']], $ordering['dir']);
                }
            }
        }
        $datas = $query->select('id,productId,createdProductId,name,sku,color,updated,status,size')->limit($limit, $start)->get('products')->result_array();
        foreach ($datas as $data) {
            $records["data"][] = array(
                '<input type="checkbox" name="id[]" value="' . $data['id'] . '">',
                $data['productId'],
                $data['createdProductId'],
                $data['name'],
                $data['sku'],
                $data['color'],
                $data['size'],
                $data['updated'],
                '<span class="label label-sm label-' . $statusColor[$data['status']] . '">' . $status[$data['status']] . '</span>',
                '<div class="btn-group">
					<a class="btn btn-circle btn-default dropdown-toggle" href="javascript:;" data-toggle="dropdown">
						<i class="fa fa-share"></i>
						<span class="hidden-xs"> Tools </span>
						<i class="fa fa-angle-down"></i>
					</a>
					<div class="dropdown-menu pull-right">
						<li>
							<a class="btnactionsubmit" href="'.base_url('products/products/fetchProducts/'.$data['sku']).'"> Fetch Product </a>
						</li>
						<li>
							<a class="btnactionsubmit" href="'.base_url('products/products/postProducts/'.$data['sku']).'"> Post Product </a>
						</li>
						<li>
							<a href="javascript:;"> Product Info </a>
						</li>						
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
}
