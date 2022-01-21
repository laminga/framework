<?php

namespace minga\framework;

use minga\framework\settings\MailSettings;
use PHPMailer\PHPMailer\PHPMailerSendGrid;

class MailAsync 
{
	public function Call($mail, $log = true, $skipNotification = false, $throwException = true) : void
	{
		// no hace nada por ahora
		$mail->Send($log = true, $skipNotification = false, $throwException = true);
	}
}
