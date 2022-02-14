<?php

namespace minga\framework;

class MailAsync
{
	public function Call($mail, $log = true, $skipNotification = false, $throwException = true) : void
	{
		// no hace nada por ahora
		$mail->Send($log = true, $skipNotification = false, $throwException = true);
	}
}
