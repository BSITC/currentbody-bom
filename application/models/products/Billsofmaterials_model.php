<?php
class Billsofmaterials_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    public function fetchProducts($productId = '')
    {
        $saveDatasTemps = $this->db->select('id,productId,account1Id,account2Id,sku,size,color,size')->get('products')->result_array();
        $saveDatas      = array();
        foreach ($saveDatasTemps as $saveDatasTemp) {
            $key             = @trim(preg_replace("/[^a-zA-Z0-9\s_]/", "", strtolower($saveDatasTemp['account1Id'] . '_' . $saveDatasTemp['account2Id'] . '_' . $saveDatasTemp['sku'] . '_' . $saveDatasTemp['productId'] . '_' . $saveDatasTemp['color'] . '_' . $saveDatasTemp['size'])));
            $saveDatas[$key] = $saveDatasTemp;
        }
        $batchInsert  = array();
        $batchUpdate  = array();
        $productDatas = $this->{$this->globalConfig['fetchProduct']}->fetchProducts($productId);
        foreach ($productDatas as $account1Id => $productData) {
            foreach ($productData as $row) {
                $key = @trim(preg_replace("/[^a-zA-Z_0-9\s_]/", "", strtolower($account1Id . '_' . $row['account2Id'] . '_' . $row['sku'] . '_' . $row['productId'] . '_' . $row['color'] . '_' . $row['size'])));
                if (@$saveDatas[$key]) {
                    $row['id']     = $saveDatas[$key]['id'];
                    $row['status'] = '2';
                    $batchUpdate[] = $row;
                } else {
                    $batchInsert[] = $row;
                }
            }
        }
        if ($batchInsert) {
            $this->db->insert_batch('products', $batchInsert);
        }
        if ($batchUpdate) {
            $this->db->update_batch('products', $batchUpdate, 'id');
        }
    }
    public function getProduct(){
        $groupAction     = $this->input->post('customActionType');
        $records         = array();
        $records["data"] = array();
        if ($groupAction == 'group_action') {
            $ids = $this->input->post('id');
            if ($ids) {
				$this->db->where_in('productId',$ids)->delete('products');
				$this->db->where_in('productId',$ids)->delete('product_bom');
            }
        }

        $where = array('isBOM' => '1');
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
        if (trim((string)$this->input->post('updated_from') ?? '')) {
            $query->where('date(updated) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim((string)$this->input->post('updated_to')) ?? '') {
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
        if (trim((string)$this->input->post('updated_from')) ?? '') {
            $query->where('date(updated) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim((string)$this->input->post('updated_to')) ?? '') {
            $query->where('date(updated) < ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }

        $status              = array('0' => 'Pending', '1' => 'Sent', '2' => 'Updated', '3' => 'Error', '4' => 'Archive');
        $statusColor         = array('0' => 'default', '1' => 'success', '2' => 'info', '3' => 'warning', '4' => 'danger');
        $displayProRowHeader = array('id', 'productId', 'createdProductId', 'name', 'sku', 'color', 'updated', 'status', 'size');
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
                '<input type="checkbox" name="id[]" value="' . $data['productId'] . '">',
                $data['productId'],
                $data['sku'],
                $data['name'],
                '<a class="actioneditbtn btn btn-icon-only blue" href="' . base_url('products/billsofmaterials/editbom/' . $data['productId']) . '" title="Edit settings"><i class="fa fa-edit" title="Edit settings" ></i></a>                
                ',
            );
        }
        $draw                       = intval($this->input->post('draw'));
        $records["draw"]            = $draw;
        $records["recordsTotal"]    = $totalRecord;
        $records["recordsFiltered"] = $totalRecord;
        return $records;
    }
    public function saveReceipe($productId, $datas)
    {
        if ((is_array($datas)) && ($productId)) {
			$user_session_data = $this->session->userdata('login_user_data');
			$productMapping = array();
			$productTemps = $this->db->select('productId,sku,name')->get('products')->result_array();
			foreach($productTemps as $productTemp){
				$productMapping[strtolower($productTemp['sku'])] = $productTemp;
			}
			$this->db->where(array('productId' => $productId))->delete('product_bom');
			$batchInsert = array();
			$primaryRecipeId 	= @(int) $this->input->post('isPrimary');
			foreach($datas as $recipeId => $data){
				$bomQty 	 = $data['bomQty'];
				$recipename  = $data['recipename'];
				$recipeOrder = $data['recipeOrder'];
				$isPrimary   = ($recipeId == $primaryRecipeId)?('1'):('0');
				foreach($data['sku'] as $key => $sku){
					if($sku){
						$qty       = @$data['qty'][$key];
						$name      = @$data['name'][$key];
						$batchInsert[] = array(
							'recipename' 			=> $recipename,
							'receipeId' 			=> $recipeId,
							'isPrimary' 			=> $isPrimary,
							'bomQty' 				=> $bomQty,
							'productId' 			=> $productId,
							'qty' 					=> $qty,
							'name' 					=> $name,
							'componentProductId' 	=> @$productMapping[strtolower($sku)]['productId'],
							'sku' 					=> $sku,
							'created' 				=> date('Y-m-d H:i:s'),
							'updatedBy' 			=> $user_session_data['username'],
							'ip' 					=> $_SERVER['REMOTE_ADDR'],
							'recipeOrder' 			=> $recipeOrder,
						);						
					}
				}
			}
			if($batchInsert){
				$this->db->insert_batch('product_bom',$batchInsert);
				$productLogs = $this->db->get_where('product_bom',array('productId' => $productId))->result_array();
				if($productLogs){
					$path = FCPATH.'logs'.DIRECTORY_SEPARATOR.'bom'.DIRECTORY_SEPARATOR . $productId. DIRECTORY_SEPARATOR. date("Ymd-His-").strtoupper(uniqid()).'.logs';
					if(!is_dir(dirname($path))) { mkdir(dirname($path),0777,true);chmod(dirname($path), 0777); }
					$logs = json_encode($productLogs);
					file_put_contents($path,$logs,FILE_APPEND);
				}
			}
        }
    }
}
