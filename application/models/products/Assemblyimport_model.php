<?php
class Assemblyimport_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    public function getProduct(){
        $groupAction     = $this->input->post('customActionType');
        $records         = array();
        $records["data"] = array();
        if ($groupAction == 'group_action'){
            $ids = $this->input->post('id');
			$status = $this->input->post('customActionName'); 
			if ($status != '' && $status =='3') {
				$this->db->where_in('id', $ids)->where('isProcessedWithError','1')->update('import_assembly', array('isProcessed' => $status)); 
				$records["customActionStatus"]	= "OK"; // pass custom message(useful for getting status of group actions)
				$records["customActionMessage"]	= "Assembly(ies) status is successfully updated!"; // pass custom message(useful for getting status of group actions)
			}
            /* if ($ids) {
				$res = $this->db->where_in('uniqueFileId',$ids)->delete('import_assembly');
				if($res)
					$this->db->where_in('uniqueFileId',$ids)->delete('import_assembly_bom');
            } */
        }
        $query = $this->db;
        if ($this->input->post('action') == 'filter') {
            if (trim($this->input->post('isProcessed')) >= '0') {
				if(trim($this->input->post('isProcessed')) == "processedwitherrors" ){
					$where['isProcessedWithError'] = '1';
				}elseif( trim($this->input->post('isProcessed')) == "3"){
					$where['isProcessed'] = trim($this->input->post('isProcessed'));
					$where['isProcessedWithError'] = '1';
				}else{
					$where['isProcessed'] = trim($this->input->post('isProcessed'));
					$where['isProcessedWithError'] = '0';
				}
            }
			if (trim($this->input->post('totalBom')) >= '0') {
                $where['totalBom'] = trim($this->input->post('totalBom'));
            }
			if (trim($this->input->post('totalBom'))) {
                $where['totalBom'] = trim($this->input->post('totalBom'));
            }
			if (trim($this->input->post('toShowFilename'))) {
                $where['toShowFilename'] = trim($this->input->post('toShowFilename'));
            }
        }
        if (trim((string)$this->input->post('updated_from')) ?? '') {
            $query->where('date(uploadedTime) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim((string)$this->input->post('updated_to')) ?? '') {
            $query->where('date(uploadedTime) <= ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if (isset($where)) {
            $query->like($where);
        }

        $totalRecord = @$query->select('count("id") as countpro')->get('import_assembly')->row_array()['countpro'];
        $limit       = intval($this->input->post('length'));
        $limit       = $limit < 0 ? $totalRecord : $limit;
        $start       = intval($this->input->post('start'));

        $query = $this->db;
        if (trim((string)$this->input->post('updated_from')) ?? '') {
            $query->where('date(uploadedTime) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim((string)$this->input->post('updated_to')) ?? '') {
            $query->where('date(uploadedTime) <= ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if (isset($where)) {
            $query->like($where);
        }

        $status              = array('0' => 'Pending', '1' => 'Processed', '2' => 'Updated', '3' => 'Reviewed', '4' => 'Archive');
		$statuserror		 = array('0' => 'Pending', '1' => 'Processed with errors', '2' => 'Updated', '3' => 'Error', '4' => 'Archive');
        $statusColor         = array('0' => 'default', '1' => 'success', '2' => 'info', '3' => 'warning', '4' => 'danger');
        $statusColorerr    = array('0' => 'default', '1' => 'danger', '2' => 'info', '3' => 'warning', '4' => 'danger');
        $displayProRowHeader = array('id', 'toShowFilename', 'totalBom', 'isProcessed', 'uploadedTime');
        if ($this->input->post('order')) {
            foreach ($this->input->post('order') as $ordering) {
                if (@$displayProRowHeader[$ordering['column']]) {
                    $query->order_by($displayProRowHeader[$ordering['column']], $ordering['dir']);
                }
            }
        }
        $datas = $query->select('*')->limit($limit, $start)->get('import_assembly')->result_array();
        foreach ($datas as $data) {
			$statusMessage = '<span class="label label-sm label-' . $statusColor[$data['isProcessed']] . '">' . $status[$data['isProcessed']] . '</span>';
			if($data['isProcessedWithError'] && $data['isProcessed']<3){
				$statusMessage = '<span class="label label-sm label-' . $statusColorerr[$data['isProcessedWithError']] . '">' . $statuserror[$data['isProcessedWithError']] . '</span>';
			}
            $records["data"][] = array(
                '<input type="checkbox" name="id[]" value="' . $data['id'] . '">',
                $data['toShowFilename'],
                $data['totalBom'],
				$statusMessage,
                date('M d,Y h:i:s a',strtotime($data['uploadedTime'])),
				 '<div class="btn-group">
                    <a class="btn btn-circle btn-default dropdown-toggle" href="javascript:;" data-toggle="dropdown">
                        <i class="fa fa-share"></i>
                        <span class="hidden-xs"> Tools </span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <div class="dropdown-menu pull-right"> 
						<li>
                            <a class="btnactionsubmit" href="'.base_url('products/assemblyimport/processImportedAssemblies/'.$data['uniqueFileId']).'"> Process Import </a>
                        </li>
                        <li>
                            <a class="newInfoBtn" target="_blank" href="'.base_url('products/assemblyimport/importInfo/'.$data['uniqueFileId']).'"> Import Info </a>
                        </li>
						 <li>
                            <a class="newInfoBtn" target="_blank" href="'.base_url('assemblyImport/'.$data['importFileName']).'" download="'.$data['importFileName'].'"> Download File </a>
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
