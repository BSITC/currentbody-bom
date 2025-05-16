<?php
class Sales_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    public function fetchSales($orderId = ''){
        $salesDatas = $this->{$this->globalConfig['fetchSalesOrder']}->fetchSales($orderId);
        $saveDatasTemps = $this->db->select('id,orderId,account1Id,account2Id')->get('sales_order')->result_array();
        $saveDatas = array();
        foreach ($saveDatasTemps as $saveDatasTemp) {
            $key = @trim(preg_replace("/[^a-zA-Z0-9\s_]/", "", strtolower($saveDatasTemp['account1Id'].'_'.$saveDatasTemp['account2Id'].'_'.$saveDatasTemp['orderId'])));
            $saveDatas[$key] = $saveDatasTemp;
        }
        $batchInsert = array(); $batchInsertItems = array();$batchInsertAddresss = array();
        foreach ($salesDatas as $account1Id => $salesData) {
            foreach ($salesData as $orderId => $row) {
                $key =  @trim(preg_replace("/[^a-zA-Z_0-9\s_]/", "", strtolower($account1Id.'_'.$row['orders']['account2Id'].'_'.$orderId)));
                if(@!$saveDatas[$key]){
                    $batchInsert[] = $row['orders'];  
                    $batchInsertAddresss[] = $row['address'];  
                    foreach ($row['items'] as $items) {
                    	$batchInsertItems[] = $items;
                    }
                }                
            }
        }
        if($batchInsert){
            $this->db->insert_batch('sales_order', $batchInsert);
            $this->db->insert_batch('sales_address', $batchInsertAddresss);
        }
        if($batchInsertItems){
            $this->db->insert_batch('sales_item', $batchInsertItems);
        }      
    }
    public function postSales($orderId = ''){
       $this->{$this->globalConfig['postSalesOrder']}->postSales($orderId);
    }
    public function getSales()
    {
		$warehouseList = $this->assembly_model->getWarehouseMaster();
        $groupAction     = $this->input->post('customActionType');
        $records         = array();
        $records["data"] = array();
        if ($groupAction == 'group_action') {
            $ids = $this->input->post('id');
            if ($ids) {
                $status = $this->input->post('customActionName');
                if ($status != '') {
                    $this->db->where_in('id', $ids)->update('sales_order', array('status' => $status));
                    $records["customActionStatus"]  = "OK"; // pass custom message(useful for getting status of group actions)
                    $records["customActionMessage"] = "Group action successfully has been completed. Well done!"; // pass custom message(useful for getting status of group actions)
                }
            }
        }

        $where = array();
        $query = $this->db;
        if ($this->input->post('action') == 'filter') {
            if (trim($this->input->post('orderId'))) {
                $where['orderId'] = trim($this->input->post('orderId'));
            }
            if (trim($this->input->post('reference'))) {
                $where['reference'] = trim($this->input->post('reference'));
            }
			if (trim($this->input->post('customerEmail'))) {
                $where['customerEmail'] = trim($this->input->post('customerEmail'));
            }
			if (trim($this->input->post('delAddressName'))) {
                $where['delAddressName'] = trim($this->input->post('delAddressName'));
            }
			if (trim($this->input->post('warehouse'))) {
                $where['warehouse'] = trim($this->input->post('warehouse'));
            }
            if (trim($this->input->post('status')) >= '0') {
                $where['status'] = trim($this->input->post('status'));
            }
        }
        if (trim($this->input->post('updated_from'))) {
            $query->where('date(created) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim($this->input->post('updated_to'))) {
            $query->where('date(created) <= ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }
        $totalRecord = @$query->select('count("id") as countsales')->get('sales_order')->row_array()['countsales'];
        $limit       = intval($this->input->post('length'));
        $limit       = $limit < 0 ? $totalRecord : $limit;
        $start       = intval($this->input->post('start'));

        $query = $this->db;
        if (trim($this->input->post('updated_from'))) {
            $query->where('date(created) >= ', "date('" . $this->input->post('updated_from') . "')", false);
        }
        if (trim($this->input->post('updated_to'))) {
            $query->where('date(created) <= ', "date('" . $this->input->post('updated_to') . "')", false);
        }
        if ($where) {
            $query->like($where);
        }
        $status              = array('0' => 'Pending', '1' => 'Allocation All Allocated', '2' => 'Allocation Not Required', '3' => 'Allocation None Allocated', '4' => 'Allocation Part Allocated');
        $statusColor         = array('0' => 'default', '1' => 'success', '2' => 'info', '3' => 'warning', '4' => 'danger');
        $displayProRowHeader = array('id', 'orderId','reference', 'warehouse','created', 'status');
        if ($this->input->post('order')) {
            foreach ($this->input->post('order') as $ordering) {
                if (@$displayProRowHeader[$ordering['column']]) {
                    $query->order_by($displayProRowHeader[$ordering['column']], $ordering['dir']);
                }
            }
        }
        $datas = $query->select('id,orderId,warehouse,updated,reference,status,created')->limit($limit, $start)->get('sales_order')->result_array();
        foreach ($datas as $data) {
            $records["data"][] = array(
                '<input type="checkbox" name="id[]" value="' . $data['id'] . '">',
                $data['orderId'],
                $data['reference'],
                $warehouseList[$data['warehouse']]['warehouseName'],
                date('M d,Y h:i:s a',strtotime($data['created'])),
                '<span class="label label-sm label-' . $statusColor[$data['status']] . '">' . $status[$data['status']] . '</span>',
                '<div class="btn-group">
					<a class="btn btn-circle btn-default dropdown-toggle" href="javascript:;" data-toggle="dropdown">
						<i class="fa fa-share"></i>
						<span class="hidden-xs"> Tools </span>
						<i class="fa fa-angle-down"></i>
					</a>
					<div class="dropdown-menu pull-right">
						<li>
							<a class="newInfoBtn" target="_blank" href="'.base_url('sales/sales/salesItem/'.$data['orderId']).'"> Sales Item</a>
						</li>
						<li>
							<a class="newInfoBtn" target="_blank" href="'.base_url('sales/sales/salesInfo/'.$data['orderId']).'"> Sales info</a>
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
	public function getSalesItem($orderId){
		$datas = $this->db->get_where('sales_item',array('orderId' => $orderId))->result_array();
		return $datas;
	}
}
