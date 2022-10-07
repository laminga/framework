<?php

namespace minga\framework;

use minga\framework\enums\DeliveryMode;
use minga\framework\enums\MailType;
use PHPMailer\PHPMailer\PHPMailerSendGrid;

class MailError extends Mail
{
	public function SendByType(int $type) : void
	{
		//TODO: quitar esto cuando se actualicen los settings de todas las implementaciones [2022/10/05]
		//TODO: cambiar tipo de MailSettings::NotifyAddressErrors a arrray (string[])
		if(is_array($this->to) == false || count($this->to) == 0 || is_string(key($this->to)) == false)
		{
			$this->Send();
			return;
		}

		//Excepciones: envía el mail directo si está cercano al release,
		//si es desa...
		if(System::IsNearRelease() || System::IsDesa())
		{
			$this->Send();
			return;
		}

		foreach($this->to as $address => $mode)
		{
			if (Context::Settings()->Log()->LogEmailsToDisk || $mode == DeliveryMode::Daily)
				$this->LogMail($type, $address, $mode);

			if (Context::Settings()->Mail()->NoMail)
				continue;

			//Ya logueó, puede salir, los manda un cron.
			if($mode == DeliveryMode::Daily)
				continue;

			$mail = new PHPMailerSendGrid(true);

			$this->SetProvider($mail, $address);
			$this->SetAddress($mail, $address, '');

			$mail->CharSet = PHPMailerSendGrid::CHARSET_UTF8;
			$mail->setFrom($this->from, $this->fromCaption);
			$mail->Subject = $this->subject;
			$mail->AltBody = Context::Trans('Para ver el mensaje, por favor use un lector de correo electrónico compatible con HTML');
			$mail->msgHTML($this->message);

			$mail->isHTML(true);
			$mail->send();
			Performance::$mailsSent += $this->GetSentEmailsCount($mail);
		}
	}

	protected function LogMail(int $type, string $to, int $mode) : void
	{
		$text = "Type: " . MailType::GetName($type) . "\r\n"
			. "DeliveryMode: " . DeliveryMode::GetName($mode) . "\r\n"
			. "To: " . $to . "\r\n"
			. "From: " . $this->from . "\r\n"
			. "Subject: " . $this->subject . "\r\n"
			. "\r\n"
			. $this->message;

		Log::PutToLog($this->GetLogPath($mode),
			$text, $this->GetDoNotSaveMonthly($mode));
	}

	private function GetLogPath(int $mode) : string
	{
		if($mode == DeliveryMode::Daily)
			return Log::UnsentMailsPath;
		return Log::MailsPath;
	}

	private function GetDoNotSaveMonthly(int $mode) : bool
	{
		return $mode == DeliveryMode::Daily;
	}
}
