<?php

namespace minga\framework;

use minga\framework\locking\Lock;

class Log
{
	private static $isLoggingMailError = false;

	public static function LogError($errno, $errstr, $errfile, $errline, $context = array(), $trace = null)
	{
		Lock::ReleaseAllStaticLocks();

		if ($trace == null)
		{
			$e = new \Exception();
			$st = explode("\n", $e->getTraceAsString());
			if (sizeof($st) > 2)
			{
				unset($st[0]);
				unset($st[1]);
				unset($st[sizeof($st) - 1]);
			}
			$stack = implode("\r\n", $st);
		}
		else
			$stack = $trace;

		$stack = str_replace("#", "                #", $stack);
		$stack = str_replace("          #1", "#1", $stack);

		//Convierte en links los paths del stack.
		$stack = preg_replace("/(#\d+ )(.*)\((\d+)\)/", "$1<a href='repath://$2@$3'>$2($3)</a>", $stack);

		if (array_key_exists('HTTP_USER_AGENT', $_SERVER))
			$agent = $_SERVER['HTTP_USER_AGENT'];
		else
			$agent = 'null';
		if (array_key_exists('HTTP_REFERER', $_SERVER))
			$referer = $_SERVER['HTTP_REFERER'];
		else
			$referer = 'null';
		if(isset($_SERVER['REMOTE_ADDR']))
			  $remoteAddr = $_SERVER['REMOTE_ADDR'];
		else
			$remoteAddr = 'null';

		$text = "REQUEST\r\n" .
			"=> User:        ". Context::LoggedUser(). "\r\n" .
			"=> Url:         <a href='". Context::Settings()->GetMainServerPublicUrl() . $_SERVER['REQUEST_URI'] . "'>".Context::Settings()->GetMainServerPublicUrl() . $_SERVER['REQUEST_URI']."</a>\r\n" .
			"=> Agent:       ".  $agent . "\r\n" .
			"=> Referer:     <a href='".  $referer . "'>".$referer."</a>\r\n" .
			"=> Method:      ".  $_SERVER['REQUEST_METHOD'] . "\r\n" .
			"=> IP:          ".  $remoteAddr . "\r\n" .
			"===========================================\r\n" .
			"ERROR\r\n" .
			"=> Description: ". $errstr . "\r\n" .
			"=> File:        <a href='repath://" . $errfile . "@" .  $errline . "'>" . $errfile. ":" .  $errline. "</a>\r\n" .
			"=> Level: " . self::getLevel($errno) . "\r\n" .
			"=> Stack: " . $stack . "\r\n";
		if (sizeof($_POST) > 0)
		{
			$text .= "===========================================\r\n" .
				"=> Post:        ". print_r($_POST, true);
		}
		$text .= "===========================================\r\n" .
			"=> Context\r\n" . print_r($context, true);

		// Corrige problemas de new line de las diferentes fuentes.
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\n", "<br>\r\n", $text);

		if (Context::Settings()->Debug()->showErrors)
			$textToShow = $text;
		else
			$textToShow = "Se ha producido un error: " . $errstr . ". <p>Por favor, intente nuevamente. De persistir el error, póngase en contacto con soporte enviando un mensaje a <a href='mailto:soporte@aacademica.org'>soporte@aacademica.org</a> describiendo el inconveniente.";

		self::PutToLog('errors', $text);

		if (self::$isLoggingMailError == false)
		{
			self::$isLoggingMailError = true;
			try
			{
				self::PutToMail($text);
			}
			catch(\Exception $e)
			{
				self::HandleSilentException($e);
			}
			self::$isLoggingMailError = false;
		}

		return $textToShow;
	}


	public static function HandleSilentException($e)
	{
		$textToShow = self::LogException($e, true);

		if(Context::Settings()->Debug()->debug && Str::StartsWith($e->getMessage(), "Error running: \"pdf") == false )
		{
			MessageBox::ThrowBackMessage($textToShow);
			exit();
		}
	}

	public static function LogException($exception, $silent = false)
	{
		$message = $exception->getMessage();
		if ($silent)
			$message .= " (silently processed)";
		return self::LogError($exception->getCode(), $message,
			$exception->getFile(), $exception->getLine(), array(), $exception->getTraceAsString());
	}

	public static function PutToLog($branch, $text)
	{
		// Lo graba en log
		$logPath = Context::Paths()->GetLogLocalPath() . '/' . $branch;
		$path = $logPath . '/' . Date::GetLogMonthFolder();
		IO::EnsureExists($logPath);
		IO::EnsureExists($path);
		//
		$file = Date::FormattedArNow() . '-' . Str::UrlencodeFriendly(Context::LoggedUser()) . '.txt';
		$file = str_replace(':', '.', $file);
		$file = str_replace('+', '-', $file);
		$file = $path . '/' . $file;
		// va
		IO::WriteAllText($file, $text);
	}

	public static function PutToMail($text)
	{
		if (empty(Context::Settings()->Mail()->NotifyAddressErrors))
			return true;
		// Manda email....
		$mail = new Mail();
		$mail->to = Context::Settings()->Mail()->NotifyAddressErrors;
		$mail->subject = 'Error en Acta Académica - ' . Date::FormattedArNow() . '-' . Str::UrlencodeFriendly(Context::LoggedUser());
		$mail->message = $text;
		if (Context::Settings()->isTesting)
			return true;
		$mail->Send(false, true);
	}

	private static function getLevel($errno)
	{
		switch($errno)
		{
			case E_ERROR:
				return 'E_ERROR'; // 1
			case E_WARNING:
				return 'E_WARNING'; // 2
			case E_PARSE:
				return 'E_PARSE'; // 4
			case E_NOTICE:
				return 'E_NOTICE'; // 8
			case E_CORE_ERROR:
				return 'E_CORE_ERROR'; // 16
			case E_CORE_WARNING:
				return 'E_CORE_WARNING'; // 32
			case E_CORE_ERROR:
				return 'E_COMPILE_ERROR'; // 64
			case E_CORE_WARNING:
				return 'E_COMPILE_WARNING'; // 128
			case E_USER_ERROR:
				return 'E_USER_ERROR'; // 256
			case E_USER_WARNING:
				return 'E_USER_WARNING'; // 512
			case E_USER_NOTICE:
				return 'E_USER_NOTICE'; // 1024
			case E_STRICT:
				return 'E_STRICT'; // 2048
			case E_RECOVERABLE_ERROR:
				return 'E_RECOVERABLE_ERROR'; // 4096
			case E_DEPRECATED:
				return 'E_DEPRECATED'; // 8192
			case E_USER_DEPRECATED:
				return 'E_USER_DEPRECATED'; // 16384
			default:
				return $errno;
		}
	}
}
