<?php
class Users_model extends CI_Model{
	
	public function __construct()
    {
        parent::__construct();
    }
	public function getUsers(){
        $groupAction     = $this->input->post('customActionType');
        $records         = array();
        $records["data"] = array();
        //Set order value in session to show selected order on page load
		if($this->input->post('order')){
 			$orderData = array($this->router->directory.$this->router->class => $this->input->post('order'));
 			$this->session->set_userdata($orderData);
        }
        if ($groupAction == 'group_action') {
            $ids = $this->input->post('id');
            if ($ids) {
                $customActionName = $this->input->post('customActionName');
				if($customActionName == "deleteAction"){
					$res = $this->db->where_in('user_id', $ids)->delete('admin_user');
					if($res){
						$records["customActionStatus"]  = "OK"; // pass custom message(useful for getting status of group actions)
						$records["customActionMessage"] = "Group action successfully has been completed. Well done!"; // pass custom 
					}else{
						$records["customActionStatus"]  = "danger"; // pass custom message(useful for getting status of group actions)
						$records["customActionMessage"] = "Some unexpected error occured while deleting Assembly, Please try again!"; // pass custom message(useful for getting status of group actions)
					}
				}
            }
        }
        $where = array();
        $query = $this->db;
        if ($this->input->post('action') == 'filter') {
            if (trim($this->input->post('firstname'))) {
                $where['firstname'] = trim($this->input->post('firstname'));
            }
            if (trim($this->input->post('lastname'))) {
                $where['lastname'] = trim($this->input->post('lastname'));
            }
            if (trim($this->input->post('username'))) {
                $where['username'] = trim($this->input->post('username'));
            }if (trim($this->input->post('email'))) {
                $where['email'] = trim($this->input->post('email'));
            }
            if ($this->input->post('is_active') === '0') {
                $where['is_active'] = trim($this->input->post('is_active'));
            }elseif ($this->input->post('is_active') === '1') {
				$where['is_active'] = trim($this->input->post('is_active'));
			}else{
			}
            if (trim($this->input->post('accessLabel'))) {
                $where['accessLabel'] = trim($this->input->post('accessLabel'));
            }
        }
        if ($where) {
            $query->like($where);
        }

        $totalRecord = @$query->get('admin_user')->num_rows();
        $limit       = intval($this->input->post('length'));
        $limit       = $limit < 0 ? $totalRecord : $limit;
        $start       = intval($this->input->post('start'));
        if ($where) {
            $query->like($where);
        }
        $status              = array('0' => 'No', '1' => 'Yes');
        $accessLabelStatus   = array('1' => 'SuperAdmin','2' => 'Employee', '3' => 'Manager');
        $statusColor         = array('0' => 'danger', '1' => 'success', '2' => 'info', '3' => 'warning', '4' => 'danger');
        $displayProRowHeader = array('user_id','firstname','lastname','email', 'username', 'created', 'is_active', 'accessLabel');
        if ($this->session->userdata($this->router->directory.$this->router->class)) {
            foreach ($this->input->post('order') as $ordering) {
                if (@$displayProRowHeader[$ordering['column']]) {
                    $query->order_by($displayProRowHeader[$ordering['column']], $ordering['dir']);
                }
            }
        }		
        $datas = $query->select('*')->limit($limit, $start)->get('admin_user')->result_array();
        foreach ($datas as $data) { 
		unset($data['created']);
		unset($data['logdate']);
            $records["data"][] = array(
                '<input type="checkbox" name="id[]" value="' . $data['user_id'] . '">',
                $data['firstname'],
                $data['lastname'],
                $data['email'],
                $data['username'],
				'<span class="label label-sm label-' . $statusColor[$data['is_active']] . '">' . $status[$data['is_active']] . '</span>', 
               '<span class="label label-sm label-' . $statusColor[$data['accessLabel']] . '">' . $accessLabelStatus[$data['accessLabel']] . '</span>',
				'<a class="actioneditbtn btn btn-icon-only green" data-user_id='.$data['user_id'].' href="javascript:;" onclick=editAction('.json_encode($data).')><i style="font-size:19px;" class="fa fa-edit" title="Edit User" ></i></a>',
            );
        }
        $draw                       = intval($this->input->post('draw'));
        $records["draw"]            = $draw;
        $records["recordsTotal"]    = $totalRecord;
        $records["recordsFiltered"] = $totalRecord;
        return $records;
    }
	
	public function delete($id){
		$this->db->where(array('user_id' => $id))->delete('admin_user');
	}
	public function save($data){
		if(@$data['user_id']){
			unset($data['password']);
			$data['status'] = $this->db->where(array('user_id' => $data['user_id']))->update('admin_user',$data);
		}
		else{
			$data['status'] =  $this->db->insert('admin_user',$data);
			$data['id'] = $this->db->insert_id();

		}
		return $data;
	}
}
?>