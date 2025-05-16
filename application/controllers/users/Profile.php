<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Profile extends My_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model('users/profile_model');
    }

    public function index()
    {
        $data = array(
            'user'         => $this->profile_model->getUserDetails(),
            'globalConfig' => $this->profile_model->getGlobalConfig(),
        );
        $this->template->load_template("users/profile", array('data' => $data), $this->session_data);
    }
    public function saveBasic()
    {
        $data = array(
            'firstname' => $this->input->post('firstname'),
            'lastname'  => $this->input->post('lastname'),
            'phone'     => $this->input->post('phone'),
            'email'     => $this->input->post('email'),
        );
        $this->profile_model->saveBasic($data);
        $return = array(
            'status'  => true,
            'message' => 'Data saved successfully',
        );
        echo json_encode($return);
        die();
    }
    public function updatePassword()
    {
        $data = array(
            'password'     => $this->input->post('password'),
            'newpassword'  => $this->input->post('newpassword'),
            'newpassword2' => $this->input->post('newpassword2'),
        );
        $message = 'Password changed successfully';
        $status  = '1';
        if (trim($data['password']) == '') {
            $message = 'Please enter Password';
            $status  = 0;
        } else if (trim($data['newpassword']) == '') {
            $message = 'Please enter New Password';
            $status  = 0;
        } else if (trim($data['newpassword']) != trim($data['newpassword2'])) {
            $message = 'New Password and Re-type New Password not matched';
            $status  = 0;
        } else {
            $res = $this->profile_model->updatePassword($data);
            if ($res) {
                $message = 'Data saved successfully';
                $status  = 1;
            } else {
                $message = 'Problem in saving data';
                $status  = 0;
            }
        }
        $return = array(
            'status'  => $status,
            'message' => $message,
        );
        echo json_encode($return);
        die();
    }
    public function updateProfilePic()
    {
        $img    = $this->input->post('file');
        $return = array(
            'status'  => '0',
            'message' => "Please select file",
        );
        if ($img) {
            $uploadFileName = uniqid() . '.png';
            $img            = str_replace('data:image/png;base64,', '', $img);
            if (substr_count($img, 'image/jpg')) {
                $uploadFileName = uniqid() . '.jpg';
                $img            = str_replace('data:image/jpg;base64,', '', $img);
            }
            $img     = str_replace(' ', '+', $img);
            $data    = base64_decode($img);
            $file    = FCPATH . 'assets/layouts/layout/img/profile/' . $uploadFileName;
            $success = file_put_contents($file, $data);
            $return  = array(
                'status'  => '1',
                'message' => "Profile image successfully uploaded",
            );
            $saveData = array('profileimage' => 'assets/layouts/layout/img/profile/' . $uploadFileName);
            $this->profile_model->uploadedProfiePic($saveData);
        }
        echo json_encode($return);
        die();
    }
    public function saveGlobalConfig()
    {
        $data = array(
            'app_name'                   => $this->input->post('app_name'),
            'account1Name'               => $this->input->post('account1Name'),
            'account1Liberary'           => $this->input->post('account1Liberary'),
            'account2Name'               => $this->input->post('account2Name'),
            'account2Liberary'           => $this->input->post('account2Liberary'),
            'enableProduct'              => $this->input->post('enableProduct'),
            'fetchProduct'               => $this->input->post('fetchProduct'),
            'postProduct'                => $this->input->post('postProduct'),
            'enablePrebook'              => $this->input->post('enablePrebook'),
            'enableCustomer'             => $this->input->post('enableCustomer'),
            'fetchCustomer'              => $this->input->post('fetchCustomer'),
            'postCustomer'               => $this->input->post('postCustomer'),
            'enableSalesOrder'           => $this->input->post('enableSalesOrder'),
            'fetchSalesOrder'            => $this->input->post('fetchSalesOrder'),
            'postSalesOrder'             => $this->input->post('postSalesOrder'),
            'enableReceipt'              => $this->input->post('enableReceipt'),
            'fetchReceipt'               => $this->input->post('fetchReceipt'),
            'postReceipt'                => $this->input->post('postReceipt'),
            'enableDispatchConfirmation' => $this->input->post('enableDispatchConfirmation'),
            'fetchDispatchConfirmation'  => $this->input->post('fetchDispatchConfirmation'),
            'postDispatchConfirmation'   => $this->input->post('postDispatchConfirmation'),
            'fetchPurchaseOrder'         => $this->input->post('fetchPurchaseOrder'),
            'postPurchaseOrder'          => $this->input->post('postPurchaseOrder'),
            'enableStockAdjustment'      => $this->input->post('enableStockAdjustment'),
            'fetchStockAdjustment'       => $this->input->post('fetchStockAdjustment'),
            'postStockAdjustment'        => $this->input->post('postStockAdjustment'),
            'enableStockSync'            => $this->input->post('enableStockSync'),
            'fetchStockSync'             => $this->input->post('fetchStockSync'),
            'enblePreorder'             => $this->input->post('enblePreorder'),
            'postStockSync'              => $this->input->post('postStockSync'),
            'enableMapping'              => $this->input->post('enableMapping'),
            'enableShippingMapping'      => $this->input->post('enableShippingMapping'),
            'enableWarehouseMapping'     => $this->input->post('enableWarehouseMapping'),
            'enablePricelistMapping'     => $this->input->post('enablePricelistMapping'),
            'enablePaymentMapping'       => $this->input->post('enablePaymentMapping'),
            'enableChannelMapping'       => $this->input->post('enableChannelMapping'),
            'enableCategoryMapping'      => $this->input->post('enableCategoryMapping'),
            'enableCategoryMapping'      => $this->input->post('enableCategoryMapping'),
            'enableSalesrepMapping'      => $this->input->post('enableSalesrepMapping'),  
            'enableImportAssembly'      => $this->input->post('enableImportAssembly'),  
        );
        $this->profile_model->saveGlobalConfig($data);
        $return = array(
            'status'  => '1',
            'message' => "Global configuration saved successfully",
        );
        echo json_encode($return);
        die();
    }

}
