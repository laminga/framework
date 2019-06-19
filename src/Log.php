<?php

namespace minga\framework;

use minga\framework\locking\Lock;

class Log
{
	private static $isLoggingMailError = false;
	public static $extraErrorTarget = null;
	public static $extraErrorInfo = null;

	public static function LogError($errorNumber, $errorMessage, $errorFile, $errorLine, $context = [], $trace = null)
	{
		Lock::ReleaseAllStaticLocks();

		if ($trace == null)
		{
			$e = new ErrorException();
			$st = explode("\n", $e->getTraceAsString());
			if (count($st) > 2)
			{
				unset($st[0]);
				unset($st[1]);
				unset($st[count($st) - 1]);
			}
			$stack = implode("\r\n", $st);
		}
		else
			$stack = $trace;

		$stack = str_replace('#', '                #', $stack);
		$stack = str_replace('          #1', '#1', $stack);

		//Convierte en links los paths del stack.
		$stack = preg_replace('/(#\d+ )(.*)\((\d+)\)/', "$1<a href='repath://$2@$3'>$2($3)</a>", $stack);

		$agent = Params::SafeServer('HTTP_USER_AGENT', 'null');
		$referer = Params::SafeServer('HTTP_REFERER', 'null');
		$remoteAddr = Params::SafeServer('REMOTE_ADDR', 'null');
		$requestUri = Params::SafeServer('REQUEST_URI', 'null');
		$requestMethod = Params::SafeServer('REQUEST_METHOD', 'null');

		$fullUrlData = Params::SafeServer('HTTP_FULL_URL', null);
		if ($fullUrlData !== null)
			$fullUrl = '=> Client:      '. $fullUrlData . "\r\n";
		else
			$fullUrl = '';

		$text = "REQUEST\r\n" .
			'=> User:        '. Context::LoggedUser(). "\r\n" .
			"=> Url:         <a href='". Context::Settings()->GetPublicUrl() . $requestUri . "'>" . Context::Settings()->GetPublicUrl() . $requestUri . "</a>\r\n" .
			$fullUrl .
			'=> Agent:       '.  $agent . "\r\n" .
			"=> Referer:     <a href='".  $referer . "'>".$referer."</a>\r\n" .
			'=> Method:      '.  $requestMethod . "\r\n" .
			'=> IP:          '.  $remoteAddr . "\r\n" .
			"===========================================\r\n" .
			"ERROR\r\n" .
			'=> Description: '. $errorMessage . "\r\n" .
			"=> File:        <a href='repath://" . $errorFile . '@' .  $errorLine . "'>" . $errorFile. ':' .  $errorLine. "</a>\r\n" .
			'=> Level: ' . self::GetLevel($errorNumber) . "\r\n" .
			'=> Stack: ' . $stack . "\r\n";
		if (count($_POST) > 0)
		{
			$text .= "===========================================\r\n" .
				'=> Post:        ' . print_r($_POST, true);
		}
		$text .= "===========================================\r\n" .
			"=> Context:\r\n" . print_r($context, true);
		if (self::$extraErrorInfo !== null)
		{
			$text .= "===========================================\r\n" .
				'=> Info:        ' . print_r(self::$extraErrorInfo, true);
		}
		// Corrige problemas de new line de las diferentes fuentes.
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\n", "<br>\r\n", $text);

		$filtered = false;
		if (Context::Settings()->Debug()->showErrors)
			$textToShow = $text;
		else
		{
			//Filtro temporal (esperemos) para que no muestre
			//mensajes de error con código de excepciones no
			//capturadas. La solución correcta sería usar
			//tipos de excepciones para errores propios y
			//filtrar las que son tipo \Exception.
			if(Str::Contains($errorMessage, '/')
				|| Str::Contains($errorMessage, "\\")
				|| Str::Contains($errorMessage, '(')
				|| Str::Contains($errorMessage, ')')
				|| Str::Contains($errorMessage, '[')
				|| Str::Contains($errorMessage, ']')
				|| Str::Contains($errorMessage, ':')
				|| Str::Contains($errorMessage, ';')
				|| Str::Contains($errorMessage, '>'))
			{
				$filtered = true;
				$textToShow = 'Se produjo un error';
			}
			else
				$textToShow = 'Se produjo un error: ' . $errorMessage;

			$textToShow .= '.<p>Por favor, intente nuevamente. De persistir el error, póngase en contacto con soporte enviando un mensaje a <a href="mailto:soporte@aacademica.org">soporte@aacademica.org</a> describiendo el inconveniente.';
		}

		if($filtered)
			$text .= '[texto filtrado al usuario].';

		if (Context::Settings()->Log()->LogErrorsToDisk)
			self::PutToLog('errors', $text);

		self::LogErrorSendMail($text);

		return $textToShow;
	}
	public static function AppendExtraInfo($info)
	{
		if (self::$extraErrorInfo === null)
			self::$extraErrorInfo = array();
		self::$extraErrorInfo[] = $info;
	}
	private static function LogErrorSendMail($text)
	{
		if (self::$isLoggingMailError)
			return;

		try
		{
			self::$isLoggingMailError = true;

			if(Str::Contains($text, '[passwordi]'))
			{
				$text = preg_replace('/(\[passwordi\] => ).*/',
					'$1[removido]<br>', $text);
			}
			if(Str::Contains($text, '[password]'))
			{
				$text = preg_replace('/(\[password\] => ).*/',
					'$1[removido]<br>', $text);
			}
			self::PutToMail($text);
		}
		catch(\Exception $e)
		{
			self::HandleSilentException($e);
		}
		finally
		{
			self::$isLoggingMailError = false;
		}
	}

	public static function HandleSilentException($e)
	{
		$textToShow = self::LogException($e, true);

		if(Context::Settings()->Debug()->debug && Str::StartsWith($e->getMessage(), 'Error running: "pdf') == false)
		{
			MessageBox::ThrowBackMessage($textToShow);
			exit();
		}
	}

	public static function LogException($exception, $silent = false)
	{
		$message = $exception->getMessage();
		if ($silent)
			$message .= ' (silently processed)';
		return self::LogError($exception->getCode(), $message,
			$exception->getFile(), $exception->getLine(), [], $exception->getTraceAsString());
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
		if (self::$extraErrorTarget !== null)
			IO::WriteAllText(self::$extraErrorTarget, $text);
	}

	public static function PutToMail($text)
	{
		if (empty(Context::Settings()->Mail()->NotifyAddressErrors))
			return true;
		// Manda email....
		$mail = new Mail();
		$mail->to = Context::Settings()->Mail()->NotifyAddressErrors;
		$mail->subject = 'Error ' . Context::Settings()->applicationName . ' - ' . Date::FormattedArNow() . '-' . Str::UrlencodeFriendly(Context::LoggedUser());
		$mail->message = $text;
		if (Context::Settings()->isTesting)
			return true;
		$mail->Send(false, true);
		return true;

	}

	private static function GetLevel($errorNumber)
	{
		switch($errorNumber)
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
				return $errorNumber;
		}
	}
}
