<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');
#[\AllowDynamicProperties]
class Common
{
	public function __construct()
	{
		$this->ci = &get_instance();
		if (!@$this->ci->session->userdata['global_config']['app_name']) {
			$data = $this->ci->db->get('global_config')->row_array();
			$this->ci->session->set_userdata('global_config', $data);
		}
		if (!@$this->ci->globalConfig) {
			$this->ci->globalConfig = $this->ci->session->userdata('global_config');
			if (!$this->ci->globalConfig) {
				$this->ci->load->model('users/profile_model');
				$data = array(
					'user'         => $this->ci->profile_model->getUserDetails(),
					'globalConfig' => $this->ci->profile_model->getGlobalConfig(),
				);
				$this->ci->session->set_userdata('global_config', $data);
				$this->ci->globalConfig = $this->ci->profile_model->getGlobalConfig();
			}
		}
		$ignoreLoadConfig = array('profile', 'dashboard', 'login');
		$controllerName = @$this->ci->router->class;
		$account1 = '';
		$account2 = '';
		if (!in_array(strtolower($controllerName), $ignoreLoadConfig)) {
			if ($this->ci->globalConfig) {
				$account1 = $this->ci->globalConfig['account1Liberary'];
				$account2 = $this->ci->globalConfig['account2Liberary'];
				if ($account1) {
					if (!@$this->ci->{$account1}) {
						$this->ci->load->library($account1);
						$this->ci->load->library($account2);
					}
					if (!@$this->ci->account1Config) {
						$account1Account = array();
						$account1Config = array();
						$temps = $this->ci->db->get_where('account_' . $account1 . '_account')->result_array();
						foreach ($temps as $temp) {
							$account1Account[$temp['id']] = $temp;
						}
						$temps = $this->ci->db->get_where('account_' . $account1 . '_config')->result_array();
						foreach ($temps as $temp) {
							$account1Config[$temp['id']] = $temp;
						}
						$this->ci->account1Config = $account1Config;
						$this->ci->account1Account = $account1Account;
					}
					if (!@$this->ci->account2Config) {
						$account2Account = array();
						$account2Config = array();
						$temps = $this->ci->db->get_where('account_' . $account2 . '_account')->result_array();
						foreach ($temps as $temp) {
							$account2Account[$temp['id']] = $temp;
						}
						$temps = $this->ci->db->get_where('account_' . $account2 . '_config')->result_array();
						foreach ($temps as $temp) {
							$account2Config[$temp['id']] = $temp;
						}

						$this->ci->account2Config = $account2Config;
						$this->ci->account2Account = $account2Account;
					}
				}
			}
			//Setting default timezone
			if (strtolower($account1) == 'brightpearl') {
				$bpConfig = reset($this->ci->account1Config);
				if ($bpConfig['timezone'])
					date_default_timezone_set($bpConfig['timezone']);
			}
		}
	}

	public function getCurrencyRate($from, $to)
	{
		$return = '';
		if ($from && $to) {
			$dateJson = json_decode(file_get_contents('https://currency-api.appspot.com/api/' . $from . '/' . $to . '.json?amount=1'), true);
			if (!$dateJson['success']) {
				$dateJson = $this->ci->db->get_where('global_rate', array('fromCurrency' 	=> $from, 'toCurrency' 	=> $to))->row_array();
			}
			if ($dateJson['rate']) {
				$return = array(
					'from' 			=> $from,
					'to' 			=> $to,
					'rate' 			=> $dateJson['rate'],
				);
			}
		}
		return $return;
	}
}
