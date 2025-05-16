<?php
class Net_SFTP{
	public $hostname,$username,$password,$login,$ftp;
	public function __construct($hostname){
		$this->hostname = $hostname;
		$this->ftp = ftp_connect($this->hostname) or die("Could not connect to ".$this->hostname);		
	}
	public function login($username,$password){
		$this->username = $username;
		$this->password = $password;
		$this->ftp = ftp_connect($this->hostname) or die("Could not connect to ".$this->hostname);
		$this->login = ftp_login($this->ftp, $this->username, $this->password);
		return $this->login;
	}
	public function nlist($path){
		if(!$path){
			$path = ".";
		}
		$files = ftp_nlist($this->ftp, $path);
		return $files;
	}
	public function get($filename,$localfile){
		if($localfile){
			$tramsfer = ftp_get($this->ftp, $localfile, $filename, FTP_BINARY);
			return $tramsfer;
		}
		return false;
	}
	public function file_modify_time($filename){
		return  ftp_mdtm($this->ftp, $filename);
	}
	public function _disconnect(){
		ftp_close($this->ftp);
	}
	public function put($remotefile,$localfile){
		return ftp_put($this->ftp, $remotefile, $localfile, FTP_BINARY);
	}
	public function rename($oldFile,$newFIle){
		return ftp_rename($this->ftp, $oldFile, $newFIle);
	}
	public function chdir($dirname){
		return ftp_chdir($this->ftp, $dirname);
	}
	
}
?>