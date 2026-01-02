<?php

namespace minga\framework;

use minga\framework\enums\DeliveryMode;

class MailDeferredSender
{
	public static function Process(bool $silent = false) : void
	{
		$mails = self::GroupByEmailAndType();
		$cant = self::SendMails($mails);

		if ($silent == false)
			echo 'Emails enviados ' . $cant . "\n";
	}

	private static function GroupByEmailAndType() : array
	{
		$path = Context::Paths()->GetLogLocalPath() . '/' . Log::UnsentMailsPath;
		$destPath = Context::Paths()->GetLogLocalPath() . '/' . Log::MailsPath . '/' . Date::GetLogMonthFolder();

		$files = IO::GetFilesFullPath($path, '.txt');

		$mails = [];
		foreach($files as $file)
		{
			$text = IO::ReadAllText($file);
			$parts = explode("\r\n\r\n", $text, 2);
			$header = self::GetHeader($parts[0]);
			$content = "Para: " . htmlspecialchars($header['OriginalTo']) . "<br>\r\n" . $parts[1];

			if($header['DeliveryMode'] != DeliveryMode::GetName(DeliveryMode::Daily))
			{
				IO::Move($file, $destPath . '/' . basename($file));
				continue;
			}

			if(isset($mails[$header['To']]))
				$mails[$header['To']][$header['Type']][$file] = $content;
			else
				$mails[$header['To']] = [$header['Type'] => [$file => $content]];
		}
		return $mails;
	}

	private static function SendMails(array $mails) : int
	{
		$path = Context::Paths()->GetLogLocalPath() . '/' . Log::MailsPath . '/' . Date::GetLogMonthFolder();
		$ret = 0;
		foreach($mails as $to => $items)
		{
			foreach($items as $type => $texts)
			{

				$text = "\r\n<br>Cantidad de emails: " . count($texts) . "<br>\r\n"
					. "Servidor: " . Context::Settings()->Servers()->Current()->name . "<br><br>\r\n\r\n"
					. implode("<br>\r\n<br>\r\n<hr><br>\r\n<br>\r\n", array_values($texts));
				$mail = new Mail();
				$mail->subject = "Envío diario de " . $type . " " . date('Y-m-d');
				$mail->to = $to;
				$mail->message = $text;
				$mail->skipNotify = true;
				$mail->Send();
				$ret++;
				foreach(array_keys($texts) as $file)
					IO::Move($file, $path . '/' . basename($file));
			}
		}
		return $ret;
	}

	private static function GetHeader(string $header) : array
	{
		$lines = explode("\r\n", $header);
		$ret = [];
		foreach($lines as $line)
		{
			$parts = explode(":", $line, 2);
			$ret[trim($parts[0])] = "";
			if(isset($parts[1]))
				$ret[trim($parts[0])] = trim($parts[1]);
		}
		return $ret;
	}
}
