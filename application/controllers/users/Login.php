<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Login extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('users/login_model', '', true);
        $this->load->library('form_validation');
        $this->session->set_userdata('global_config', $this->db->get('global_config')->row_array());
    }

    public function index()
    {   
        $user_session_data = $this->session->userdata('login_user_data');
        if (isset($user_session_data['username']) && $user_session_data['username'] != "") {
            redirect('dashboard', 'refresh'); //Go to private area
        } else {
            $this->load->helper(array('form'));
            $this->load->view('users/login');
        }
    }

    public function checkLogin()
    {
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        $this->form_validation->set_rules('username', 'Username', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|require');
        $result = $this->login_model->login($username, $password);
        if ($result) {
            echo "1";
            $this->check_database($password);
        } else {
            echo "0";
        }
        die();
    }
    public function submit()
    {

        $this->form_validation->set_rules('username', 'Username', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|callback_check_database');

        if ($this->form_validation->run() == false) {
            $this->load->view('users/login'); //Field validation failed.  User redirected to login page
        } else {
            redirect('dashboard', 'refresh'); //Go to private area
        }

    }

    public function check_database($password)
    {
        $username = $this->input->post('username'); //Field validation succeeded.  Validate against database
        $result   = $this->login_model->login($username, $password); //query the database
        if ($result) {
            $sess_array = array();
            foreach ($result as $row) {
                if ($row->is_active == 1) {
                    $sess_array = array(
                        'user_id'      => $row->user_id,
                        'firstname'    => $row->firstname,
                        'lastname'     => $row->lastname,
                        'email'        => $row->email,
                        'username'     => $row->username,
                        'accessLabel'  => $row->accessLabel,
                        'profileimage' => ($row->profileimage) ? (base_url($row->profileimage)) : $this->config->item('script_url').'/assets/layouts/layout/img/profile.png',
                    );

                    $data = array(
                        'user_id' 		=> $row->user_id,
                        'logdate' 		=> date('Y-m-d H:i:s'),
                        'lastLoginIp' 	=> $_SERVER['REMOTE_ADDR'],
                        'lognum'  		=> $row->lognum + 1,
                    );
                    $this->login_model->update($data);

                    $this->session->set_userdata('login_user_data', $sess_array);
                    return true;
                } else {
                    $this->form_validation->set_message('check_database', 'Your account is inactive,contact to administrator');
                    return false;
                }
            }
        } else {
            $this->form_validation->set_message('check_database', 'Invalid username or password');
            return false;
        }
    }

    public function logout()
    {
        $this->session->sess_destroy();
        redirect('', '');
    }
	
	public function forgotPassword(){
		$response = array();
		$email    = $this->input->post('email'); //Field validation succeeded.  Validate against database
        $result   = $this->login_model->getUserByEmail($email); //query the database
		if($result){
			$varificationCode = strtoupper(uniqid());
			$updateResult   = $this->login_model->updateUserDataByEmail($email, $varificationCode); //query the database
			if($updateResult){
				$this->load->library('mailer');
				$from = array('alert@businesssolutionsinthecloud.com' => 'Info');
				$body = 'Hi '.ucfirst($result['0']->firstname).',
				<p>We received your request to reset your password.</p>
				<p>Your verification code is below.</p>
				<p><b>'.$varificationCode.'</b></p>
				<p>If you did not make this request, please ignore this email.</p>
				<p>If you require further assistance, please send an email to <a href="mailto:support@businesssolutionsinthecloud.com">support@businesssolutionsinthecloud.com</a></p>
				<br><br>	Thanks,<br>BSITC Support Team' ;
				$subject = 'Forgot password';
				$res = $this->mailer->send($email,$subject,$body,$from);
				if($res){
					$response['type'] = "success";
					$response['message'] = "Verification code send to your email address, Enter verification code below to reset your password.";
				}else{
					$response['type'] = "error";
					$response['message'] = "Some unexpected error occurred, Please try again.";
				}
			}else{
				$response['type'] = "error";
				$response['message'] = "Some unexpected error occurred, Please try again.";
			}
		}else{
			$response['type'] = "error";
			$response['message'] = "Please enter valid email address.";
		}
		echo json_encode($response);
		exit;
	}
	public function verifyCode(){
		$response = array();
		$verificationCode    = $this->input->post('verificationCode'); //Field validation succeeded.  Validate against database
		if($verificationCode){
			$result   = $this->login_model->getverifyCode($verificationCode); //query the database
			if($result){
				$response['type'] = "success";
				$response['message'] = '<div class="tab-pane" id="tab_1_3"><form role="form" action="javascript:;"><h1>Update Password ?</h1><p class="successmessage"> Verification code has been match successfully, Please reset you password.</p><div class="form-group"><label class="control-label">Password</label><input type="password" id="newpassword" name="newpassword" class="form-control" /> </div><div class="form-group"><label class="control-label">Re-type Password</label><input type="password" id="newpassword2" name="newpassword2" class="form-control" /> </div><div class="margin-top-10"><a href="javascript:;" class="btn green updatePasswordSave">Update Password</a><a href="javascript:;" class="btn default cancelUpdatePassword">Cancel</a></div> <input class="hide" type="text" id="verificationCodeforUpdate" name="verificationCodeforUpdate" value ="'.base64_encode($verificationCode).'" class="form-control" /></form></div>';
			}else{
				$response['type'] = "error";
				$response['message'] = 'Please enter valid verification code.';
			}
		}else{
			$response['type'] = "error";
			$response['message'] = "Verification code is required field.";
		}
		echo json_encode($response);
		exit;
	}
	public function updateForgotPassword(){
		$response = array();
		$verificationCode    = $this->input->post('verificationCode');
		$newpassword    = $this->input->post('newpassword');
		$newpassword2    = $this->input->post('newpassword2');
		if($newpassword != $newpassword2){
			$response['type'] = "error";
			$response['message'] = 'Password and Re-type Password field does not matched.';
		}else if($newpassword && $newpassword2){
			$updateResult   = $this->login_model->updateForgetPassword(md5($newpassword), base64_decode($verificationCode)); //query the database
			if($updateResult){
				$this->login_model->updateVerification(base64_decode($verificationCode));
				$response['type'] = "success";
				$response['message'] = 'Password has been updated successfully, Please login with updated password.';
			}else{
				$response['type'] = "error";
				$response['message'] = "Some unexpected error occurred while updating password, Please try again.";
			}
		}else{
			$response['type'] = "error";
			$response['message'] = 'Please enter Password and Re-type Password field to update password.';
		}
		echo json_encode($response);
		exit;
	}
	
}
