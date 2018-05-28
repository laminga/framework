<?php

namespace minga\framework;

use minga\framework\settings\MailSettings;
use PHPMailer\PHPMailer\PHPMailerSendGrid;

class Mail
{
	public $to;
	public $toCaption = "";
	public $from = "";
	public $fromCaption = '';
	public $subject;
	public $message;
	public $skipNotify = false;
	public static $MailsSent = 0;

	function __construct()
	{
		$this->fromCaption = Context::Settings()->applicationName;
		$this->from = Context::Settings()->Mail()->From;
	}

	//TODO: Si va a logear todos los emails quitar el parámetro, o agregar uno a Context::Settings
	public function Send($log = true, $isNotification = false, $throwException = true)
	{
		// if ($log)
		$this->PutToLog();

		$mail = new PHPMailerSendGrid($throwException);
		$this->SetProvider($mail);
		// $this->SetSSL($mail);

		$mail->CharSet="UTF-8";

		$this->SetAddress($mail, $this->to, $this->toCaption);

		if (! empty(Context::Settings()->Mail()->NotifyAddress) && ! $isNotification && ! $this->skipNotify)
			$this->SetBCC($mail, Context::Settings()->Mail()->NotifyAddress);

		$mail->setFrom($this->from, $this->fromCaption);
		$mail->Subject = $this->subject;
		$mail->AltBody = 'Para ver el mensaje, por favor use un lector de correo electrónico compatible con HTML';
		$mail->msgHTML($this->message);

		$mail->isHTML(true);
		$mail->send();
		self::$MailsSent += 1;
	}

	// function SetSSL($mail)
	// {
	// 	if (Context::Settings()->Debug()->debug)
	// 		$mail->SMTPOptions = array(
	// 			'ssl' => array(
	// 				'verify_peer' => false,
	// 				'verify_peer_name' => false,
	// 				'allow_self_signed' => true
	// 			));
	// }

	function SetProvider($mail)
	{
		switch(Context::Settings()->Mail()->Provider)
		{
			case MailSettings::SendGrid:
				$mail->isSendGrid();
				$mail->SendGridApiKey = Context::Settings()->Mail()->SendGridApiKey;
				break;
			case MailSettings::SparkPost:
				$mail->isSparkPost();
				$mail->SparkPostApiKey = Context::Settings()->Mail()->SparkPostApiKey;
				break;
			case MailSettings::File:
				$mail->isFile();
				$mail->EmailFilePath = Context::Settings()->Mail()->EmailFilePath;
				break;
			default:
				$mail->isSMTP();
				$mail->SMTPSecure = Context::Settings()->Mail()->SMTPSecure;
				$mail->Host = Context::Settings()->Mail()->SMTPHost;
				$mail->Port = Context::Settings()->Mail()->SMTPPort;
				$mail->Username = Context::Settings()->Mail()->SMTPUsername;
				$mail->Password = Context::Settings()->Mail()->SMTPPassword;
				$mail->SMTPAuth = Context::Settings()->Mail()->SMTPAuth;
		}
	}

	function SetAddress($mail, $to, $caption)
	{
		if(is_array($to))
		{
			foreach($to as $address)
				$mail->addAddress($address);
		}
		else
			$mail->addAddress($to, $caption);
	}

	function SetBCC($mail, $bcc)
	{
		if(is_array($bcc))
		{
			foreach($bcc as $address)
				$mail->addBCC($address);
		}
		else
			$mail->addBCC($bcc);
	}

	function PutToLog()
	{
		$text = "From: " . $this->from . "\r\n" .
			"To: " . $this->to . "\r\n" .
			"Subject: " . $this->subject . "\r\n".
			$this->message;
		Log::PutToLog('mails', $text);
	}
}
