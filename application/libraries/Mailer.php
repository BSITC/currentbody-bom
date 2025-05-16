<?php
if (! class_exists('PHPMailer')) {
	include_once(FCPATH . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR . 'PHPMailer.php');
	include_once(FCPATH . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR . 'SMTP.php');
	include_once(FCPATH . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR . 'Exception.php');
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
	public $mail;
	public function send($to = '', $subject = '', $body = '', $from = '', $filePath = '')
	{
		$mail = new PHPMailer;
		$mail->isSMTP();
		$mail->SMTPDebug = 0;
		$mail->Debugoutput = 'html';
		$mail->Host = "smtp.mailgun.org";
		$mail->Port = 587;
		$mail->IsHTML(true);
		$mail->SMTPAuth = true;
		$mail->SMTPSecure  = 'tls';
		$fileDir = __DIR__;
		$temps = explode("/", $fileDir);
		$serverName = '';
		$domainName = '';
		if (substr_count($fileDir, 'bsitc-apps')) {
			foreach ($temps as $temp) {
				if (substr_count($temp, 'bsitc-apps')) {
					$tempServerName = explode(".", $temp);
					$serverName = $tempServerName['0'];
					unset($tempServerName['0']);
					$domainName = implode(".", $tempServerName);
				}
			}
		} else {
			foreach ($temps as $temp) {
				if (substr_count($temp, 'bsitc-bridge')) {
					$tempServerName = explode(".", $temp);
					$serverName = 'info';
					if (count($tempServerName) > 2) {
						unset($tempServerName['0']);
					}
					$domainName = implode(".", $tempServerName);
				}

				if (substr_count($temp, 'bsitc-repo')) {
					$tempServerName = explode(".", $temp);
					$serverName = 'info';
					if (count($tempServerName) > 2) {
						unset($tempServerName['0']);
					}
					$domainName = implode(".", $tempServerName);
				}
			}
		}
		$from = $serverName . '@' . $domainName;
		$mail->Username = $from;
		$mail->Username = '';
		$mail->Password = '';
		if ($filePath) {
			if (is_array($filePath)) {
				foreach ($filePath as $filePa) {
					$mail->addAttachment($filePa);
				}
			} else {
				$mail->addAttachment($filePath);
			}
		}
		$mail->setFrom($mail->Username, 'BSITC Alert');
		if (is_array($to)) {
			foreach ($to as $email => $name) {
				$mail->addAddress($email, $name);
			}
		} else {
			$to = explode(",", $to);
			foreach ($to as $email) {
				$name = '';
				$mail->addAddress($email, $name);
			}
		}
		$mail->Subject = $subject;
		$mail->Body = $body;
		$this->mail = $mail;
		$res  = $mail->send();
		return $res;
	}
}
