<?php 
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
define('APPPATHS',dirname(__FILE__). DIRECTORY_SEPARATOR );
/**
* Code Igniter
*
* An open source application development framework for PHP 4.3.2 or newer
*
* @package     CodeIgniter
* @author      Andy Lyon
* @since       Version 1.0 
* @filesource
*/

// ------------------------------------------------------------------------

/**
* SFTP class using PHPs phpseclib features.
* base on phpseclib http://phpseclib.sourceforge.net/
*
* @package     CodeIgniter
* @subpackage  Libraries
* @category    Sftp
* @author      Andrey Eremin
* @version     0.1
*/

class Sftp {

	var $hostname	 = '';
	var $username	 = '';
	var $password	 = '';
	var $default_dir = '';
	var $sftp        = null;

	public function __construct($sftps) {
		// load config
		//$sftps = $this->CI->config->item('sftp');
		$this->hostname = $sftps['hostname'];
		$this->username = $sftps['username'];
		$this->password = $sftps['password'];
		$this->default_dir = $sftps['default_dir'];

		// Load libraries
		if($this->isSftp){
			foreach (glob("{".APPPATHS."Crypt/*.php}", GLOB_BRACE) as $filename) {
				include_once $filename;
			}
			include(APPPATHS.'Math/BigInteger.php');
			include(APPPATHS.'Net/SFTP.php');
		}
		else{
			include(APPPATHS.'Net/FTP.php');
		}

	}
	
	// Connect to SFTP server
	public function connect() {
		$this->sftp = new Net_SFTP($this->hostname);
		if (!$this->sftp->login($this->username, $this->password)) {
			throw new Exception('Login Failed');
		}
	}

	// return list of files
	public function list_files($path = '') {
		$ret = array();
		$files =  $this->sftp->nlist( (strlen($path) > 0 ? $path : $this->default_dir) );
		if (is_array($files) || is_object($files))
		foreach ($files as $obj) {
			if($obj != "." && $obj != "..") {
				array_push($ret, $obj);
			}
		}
		return $ret; 
	}

	// get content of file
	public function download($filename,$localfile=0) {
		return  $this->sftp->get($filename,$localfile);
	}
	
	public function fileinfo($filename) {  
		return  $this->sftp->stat($filename);
	}
	public function file_modify_time($filename) {
		if($this->isSftp){
			if($this->sftp->stat($filename)){ 
				return $this->sftp->stat($filename)['mtime'];
			}
		}
		else{
			return $this->sftp->file_modify_time($filename);
		}
		return  0;
	}

	public function close() {
		return  $this->sftp->_disconnect();
	}
	
	public function changedir($dirname) {
		return  $this->sftp->chdir($dirname);
	}
	
	public function upload($localfile,$remotefile){
		$data = $localfile;
		if($this->isSftp){
			$data = file_get_contents($localfile);
		}		
		return  $this->sftp->put($remotefile,$data);
	}
	
	public function rename($localfile,$remotefile){
		return  $this->sftp->rename($localfile,$remotefile);
	}
	
	 

}
/* $ftpdetails  = array(
	'hostname' 		=> '108.167.189.55',
	'username' 		=> 'intermailfiles@bsitc-bridge7.com',
	'password'		=> 'bna6Ikdy3oH',
	'default_dir'	=> '/',
	'isSftp' => false,
);
$sftp = new Sftp($ftpdetails);
$connect = $sftp->connect();
$changeDir = $sftp->changedir('intermail');
$files = $sftp->list_files();
$files = $sftp->list_files();


echo "changeDir<pre>";print_r($changeDir);echo "</pre>";
echo "<pre>";print_r($files);echo "</pre>";
die(__FILE__."Line Number".__LINE__); */
// END Sftp Class

/* End of file Sftp.php */