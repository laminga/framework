<?php

namespace minga\framework;

use minga\framework\settings\MailSettings;
use PHPMailer\PHPMailer\PHPMailerSendGrid;

class Mail
{
	/** @var array<string,int>|string[]|string */
	public $to;
	/** @var string[]|string */
	public $bcc;
	public string $toCaption = "";
	public string $replyTo = "";
	public string $replyToCaption = "";
	public ?string $from = "";
	public string $fromCaption = "";
	public string $subject;
	public string $message;
	public bool $skipNotify = false;

	public function __construct()
	{
		$this->fromCaption = Context::Settings()->applicationName;
		$this->from = Context::Settings()->Mail()->From;
	}

	public function Send(bool $log = true, bool $skipNotification = false, bool $throwException = true) : void
	{
		if (Context::Settings()->Log()->LogEmailsToDisk)
			$this->PutToLog();

		if (Context::Settings()->Mail()->NoMail)
			return;

		$mail = new PHPMailerSendGrid($throwException);

		$this->SetProvider($mail, $this->to);

		$mail->CharSet = PHPMailerSendGrid::CHARSET_UTF8;

		$this->SetAddress($mail, $this->to, $this->toCaption);
		$this->SetReplyTo($mail, $this->replyTo, $this->replyToCaption);

		if (empty(Context::Settings()->Mail()->NotifyAddress) == false
			&& $skipNotification == false
			&& $this->skipNotify == false)
		{
			$this->SetBCC($mail, Context::Settings()->Mail()->NotifyAddress);
		}

		if(empty($this->bcc) == false)
			$this->SetBCC($mail, $this->bcc);

		$mail->setFrom($this->from, $this->fromCaption);
		$mail->Subject = $this->subject;
		$mail->AltBody = Context::Trans('Para ver el mensaje, por favor use un lector de correo electrónico compatible con HTML');
		$mail->msgHTML($this->message);

		$mail->isHTML(true);
		$mail->send();
		Performance::$mailsSent += $this->GetSentEmailsCount($mail);
	}

	protected function GetSentEmailsCount(PHPMailerSendGrid $mail) : int
	{
		return count($mail->getToAddresses()) + count($mail->getBccAddresses());
	}

	protected function IsForcedMailProviderDomain(string $recipient) : bool
	{
		return Str::ContainsI($recipient, '@hotmail.')
			|| Str::ContainsI($recipient, '@live.')
			|| Str::ContainsI($recipient, '@outlook.');
	}

	/**
	 * @param string[]|string $recipient
	 */
	protected function ResolveProvider($recipient) : int
	{
		if(Context::Settings()->Mail()->Provider == MailSettings::File)
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

	/**
	 * @param string[]|string $recipient
	 */
	protected function SetProvider(PHPMailerSendGrid $mail, $recipient) : void
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

	/**
	 * @param string[]|string $to
	 */
	protected function SetAddress(PHPMailerSendGrid $mail, $to, string $caption) : void
	{
		if(is_array($to))
		{
			foreach($to as $address)
				$mail->addAddress($address);
		}
		else
			$mail->addAddress($to, $caption);
	}

	protected function SetReplyTo(PHPMailerSendGrid $mail, string $replyTo, string $caption) : void
	{
		if($replyTo != '')
			$mail->addReplyTo($replyTo, $caption);
	}

	/**
	 * @param string[]|string $bcc
	 */
	protected function SetBCC(PHPMailerSendGrid $mail, $bcc) : void
	{
		if(is_array($bcc))
		{
			foreach($bcc as $address)
				$mail->addBCC($address);
		}
		else
			$mail->addBCC($bcc);
	}

	protected function PutToLog() : void
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
