<?php

namespace minga\framework;

class MailAsync
{
	public function Call(Mail $mail, bool $log = true, bool $skipNotification = false, bool $throwException = true) : void
	{
		// no hace nada por ahora
		$mail->Send($log, $skipNotification, $throwException);
	}
}
