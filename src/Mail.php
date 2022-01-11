<?php

namespace minga\framework;

use minga\framework\settings\MailSettings;
use PHPMailer\PHPMailer\PHPMailerSendGrid;

class Mail
{
	public $to;
	public $bcc = null;
	public $toCaption = "";
	public $from = "";
	public $fromCaption = '';
	public $subject;
	public $message;
	public $skipNotify = false;
	public static $MailsSent = 0;

	public function __construct()
	{
		$this->fromCaption = Context::Settings()->applicationName;
		$this->from = Context::Settings()->Mail()->From;
	}

	public function Send($log = true, $skipNotification = false, $throwException = true) : void
	{
		if (Context::Settings()->Log()->LogEmailsToDisk)
			$this->PutToLog();

		if (Context::Settings()->Mail()->NoMail)
			return;

		$mail = new PHPMailerSendGrid($throwException);

		$this->SetProvider($mail, $this->to);

		$mail->CharSet = "UTF-8";

		$this->SetAddress($mail, $this->to, $this->toCaption);

		if (!empty(Context::Settings()->Mail()->NotifyAddress) && !$skipNotification && !$this->skipNotify)
			$this->SetBCC($mail, Context::Settings()->Mail()->NotifyAddress);

		if(empty($this->bcc) == false)
			$this->SetBCC($mail, $this->bcc);

		$mail->setFrom($this->from, $this->fromCaption);
		$mail->Subject = $this->subject;
		$mail->AltBody = 'Para ver el mensaje, por favor use un lector de correo electrónico compatible con HTML';
		$mail->msgHTML($this->message);

		$mail->isHTML(true);
		$mail->send();
		self::$MailsSent += 1;
	}

	private function IsForcedMailProviderDomain($recipient)
	{
		return Str::ContainsI($recipient, '@hotmail.')
			|| Str::ContainsI($recipient, '@live.')
			|| Str::ContainsI($recipient, '@outlook.');
	}

	private function ResolveProvider($recipient)
	{
		if(Context::Settings()->entorno == 'desa')
			return Context::Settings()->Mail()->Provider;

		if (is_array($recipient) == false)
			$recipient = [$recipient];

		foreach($recipient as $to)
		{
			if ($this->IsForcedMailProviderDomain($to))
				return MailSettings::Mail;
		}

		return Context::Settings()->Mail()->Provider;
	}

	public function SetProvider($mail, $recipient) : void
	{
		$provider = $this->ResolveProvider($recipient);

		switch($provider)
		{
			case MailSettings::SendGrid:
				$mail->isSendGrid();
				$mail->SendGridApiKey = Context::Settings()->Keys()->SendGridApiKey;
				break;
			case MailSettings::File:
				$mail->isFile();
				$mail->EmailFilePath = Context::Settings()->Mail()->EmailFilePath;
				break;
			case MailSettings::Mail:
				$mail->isMail();
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

	private function SetAddress($mail, $to, $caption) : void
	{
		if(is_array($to))
		{
			foreach($to as $address)
				$mail->addAddress($address);
		}
		else
			$mail->addAddress($to, $caption);
	}

	private function SetBCC($mail, $bcc) : void
	{
		if(is_array($bcc))
		{
			foreach($bcc as $address)
				$mail->addBCC($address);
		}
		else
			$mail->addBCC($bcc);
	}

	public function PutToLog() : void
	{
		$to = '';
		if(is_array($this->to))
		{
			foreach($this->to as $address)
				$to .= $address . ', ';
			$to = Str::RemoveEnding($to, ', ');
		}
		else
			$to = $this->to;

		$text = "From: " . $this->from . "\r\n"
			. "To: " . $to . "\r\n"
			. "Subject: " . $this->subject . "\r\n"
			. $this->message;
		Log::PutToLog(Log::MailsPath, $text);
	}
}
