<?php

namespace minga\framework;

use minga\framework\enums\MailType;
use minga\framework\locking\Lock;

class Log
{
	private static bool $isLoggingMailError = false;
	public static $extraErrorTarget = null;
	public static $extraErrorInfo = null;

	public const FatalErrorsPath = 'fatalErrors';
	public const JsErrorsPath = 'jsErrors';
	public const ErrorsPath = 'errors';
	public const MailsPath = 'mails';
	public const UnsentMailsPath = 'mails/unsent';

	public static function LogError($errorNumber, $errorMessage, $errorFile, $errorLine,
		$context = [], $trace = null,
		$innerErrorNumber = null, $innerErrorMessage = null,
		$innerErrorFile = null, $innerErrorLine = null,
		$innerTrace = null)
	{
		Lock::ReleaseAllStaticLocks();

		$errorMessage = self::TrimMessage($errorMessage);
		$innerErrorMessage = self::TrimMessage($innerErrorMessage);

		$error = self::FormatError($errorMessage, $errorNumber, $errorFile,
			$errorLine, $trace);

		$innerError = '';
		if ($innerErrorMessage)
		{
			$innerError = self::FormatError($innerErrorMessage, $innerErrorNumber, $innerErrorFile,
				$innerErrorLine, $innerTrace, "INNER EXCEPTION");
		}

		$text = self::FormatHttpRequest() . $error . $innerError;
		if (count($_POST) > 0)
		{
			$text .= "===========================================\r\n"
				. '=> Post:        ' . print_r($_POST, true);
		}
		$text .= "===========================================\r\n"
			. "=> Context:\r\n" . print_r($context, true);
		if (self::$extraErrorInfo !== null)
		{
			$text .= "===========================================\r\n"
				. '=> Info:        ' . print_r(self::$extraErrorInfo, true);
		}

		$text = self::FixLineEndings($text);

		$filtered = false;
		if (Context::Settings()->Debug()->showErrors)
			$textToShow = $text;
		else
		{
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
				$textToShow = Context::Trans('Se produjo un error');
			}
			else
				$textToShow = Context::Trans('Se produjo un error') . ': ' . $errorMessage;

			$linkMail = '<a href="mailto:' . Context::Settings()->GetSupportMail() . '">' . Context::Settings()->GetSupportMail() . '</a>';
			$textToShow .= '.<p>' . Context::Trans('Por favor, intente nuevamente. De persistir el error, póngase en contacto con soporte enviando un mensaje a {linkMail} describiendo el inconveniente.', ['{linkMail}' => $linkMail]) . '</p>';
		}

		if($filtered)
			$text .= '[texto filtrado al usuario].';

		if (Context::Settings()->Log()->LogErrorsToDisk)
			self::PutToErrorLog($text);

		//Temporalmente no manda mas mails de error de conexión a cdsa.
		if(Str::Contains($text, 'host: https://cdsa.aacademica.org'))
			return $textToShow;
		if(Str::Contains($text, 'TargetUrl: https://cdsa.aacademica.org/services/grid/getFulltextEntries.php'))
			return $textToShow;

		self::LogErrorSendMail($text);

		return $textToShow;
	}

	public static function LogJsError(string $agent, string $referer, string $errorMessage,
		string $errorUrl, string $errorSource, int $errorLine, int $errorColumn, string $trace) : void
	{
		if(self::ShouldIgnoreJsError($agent, $errorMessage, $errorSource,
			$errorLine, $errorColumn, $trace))
		{
			return;
		}

		$errorMessage = self::TrimMessage($errorMessage);


		$remoteAddr = Params::SafeServer('REMOTE_ADDR', 'null');
		$text = self::FormatRequest($agent, $referer, $remoteAddr,
			$errorUrl, 'JS');

		$text .= "===========================================\r\n"
			. "JAVASCRIPT ERROR\r\n"
			. '=> Description: ' . $errorMessage . "\r\n"
			. '=> Url: ' . $errorUrl . "\r\n"
			. '=> Source: ' . $errorSource . "\r\n"
			. '=> Error line: ' . $errorLine . "\r\n"
			. '=> Error column: ' . $errorColumn . "\r\n"
			. '=> Stack: ' . $trace . "\r\n";

		$text = self::FixLineEndings($text);

		//TODO: en el js
		// if (Context::Settings()->Debug()->showErrors)

		if (Context::Settings()->Log()->LogErrorsToDisk)
			self::PutToJsErrorLog($text);

		self::PutToMailJs($text);
	}

	private static function ShouldIgnoreJsError(string $agent, string $errorMessage,
		string $errorSource, int $errorLine, int $errorColumn, string $trace) : bool
	{
		if(Str::StartsWith($errorSource, 'moz-extension://'))
			return true;
		if(Str::StartsWith($errorSource, 'safari-extension://'))
			return true;
		if(Str::StartsWith($errorSource, 'chrome-extension://'))
			return true;
		if(Str::Contains($errorMessage, 'https://s7.addthis.com'))
			return true;
		if(Str::Contains($errorSource, 'https://s7.addthis.com'))
			return true;
		if(Str::Contains($errorMessage, 'function_bar'))
			return true;

		if(Str::Contains($errorMessage, "setting 'theme'"))
			return true;

		if(Str::Contains($agent, "applebot"))
			return true;

		if(Str::Contains($errorMessage, 'Uncaught TypeError: n.find is not a function')
			&& Str::Contains($errorSource, 'tippy'))
		{
			return true;
		}

		if(Str::Contains($errorMessage, "Uncaught SyntaxError: Unexpected identifier")
			&& Str::Contains($trace, "at XMLHttpRequest.r.onload (<anonymous>:30:38)"))
		{
			return true;
		}

		if(Str::Contains($errorMessage, "Can't find variable: _AutofillCallbackHandler"))
			return true;

		// Es un virus que pueden tener algunos clientes.
		if(Str::ContainsI($errorSource, 'massehight'))
			return true;

		if(Str::ContainsI($errorMessage, "ResizeObserver loop limit"))
			return true;

		if($errorSource == '' && $errorLine == 0 && $errorColumn == 0 && $trace == '')
			return true;

		if(Str::ContainsI($errorMessage, 'loadTippy') && Str::ContainsI($trace, 'Failed to fetch'))
			return true;

		//No funciona...
		// if(Str::ContainsI($errorMessage, 'redefine non-configurable property "userAgent"'))
		//A ver si por separado funciona
		if(Str::ContainsI($errorMessage, 'redefine')
			&& Str::ContainsI($errorMessage, 'property')
			&& Str::ContainsI($errorMessage, 'userAgent'))
		{
			return true;
		}

		if(Str::ContainsI($errorMessage, 'useragent'))
			return true;

		return false;
	}

	private static function FixLineEndings(string $text) : string
	{
		// Corrige problemas de new line de las diferentes fuentes.
		$text = str_replace(["<br>", "<br/>", "<br />"], "", $text);
		$text = str_replace("\r\n", "\n", $text);
		return str_replace("\n", "<br>\r\n", $text);
	}

	public static function TrimMessage(?string $text) : ?string
	{
		if ($text != null && strlen($text) > 15000)
			$text = substr($text, 0, 10240) . " (trimmed at 10240 bytes) " . substr($text, strlen($text) - 1024);
		return $text;
	}

	private static function FormatHttpRequest()
	{
		$agent = Params::SafeServer('HTTP_USER_AGENT', 'null');
		$referer = Params::SafeServer('HTTP_REFERER', 'null');
		$remoteAddr = Params::SafeServer('REMOTE_ADDR', 'null');
		$requestUri = Params::SafeServer('REQUEST_URI', '');
		$requestMethod = Params::SafeServer('REQUEST_METHOD', 'null');

		$fullUrlData = Params::SafeServer('HTTP_FULL_URL', null);
		if ($fullUrlData !== null)
			$fullUrl = '=> Client:      ' . $fullUrlData . "\r\n";
		else
			$fullUrl = '';

		return self::FormatRequest($agent, $referer, $remoteAddr,
			Context::Settings()->GetPublicUrl() . $requestUri,
			$requestMethod, $fullUrl);
	}

	private static function FormatRequest(string $agent, string $referer, string $remoteAddr,
		string $requestUri, string $requestMethod, string $fullUrl = '') : string
	{
		return "REQUEST\r\n"
			. '=> DateTime: ' . Date::FormattedArNow() . "\r\n"
			. '=> User:        ' . Context::LoggedUser() . "\r\n"
			. "=> Url:         <a href='" . $requestUri . "'>" . $requestUri . "</a>\r\n" . $fullUrl
			. '=> Agent:       ' . $agent . "\r\n"
			. "=> Referer:     <a href='" . $referer . "'>" . $referer . "</a>\r\n"
			. '=> Method:      ' . $requestMethod . "\r\n"
			. '=> IP:          ' . $remoteAddr . "\r\n";
	}

	private static function FormatError($errorMessage, $errorNumber, $errorFile,
		$errorLine, $trace = null, $errorType = "ERROR")
	{
		$level = $errorNumber;
		if(is_numeric($errorNumber))
			$level = self::GetLevel($errorNumber);
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
		return "===========================================\r\n"
			. $errorType . "\r\n"
			. '=> Description: ' . $errorMessage . "\r\n"
			. "=> File:        <a href='repath://" . $errorFile . '@' . $errorLine . "'>" . $errorFile . ':' . $errorLine . "</a>\r\n"
			. '=> Level: ' . $level . "\r\n"
			. '=> Stack: ' . $stack . "\r\n";
	}

	public static function InternalExceptionToText($exception) : string
	{
		$message = $exception->getMessage();
		if (is_a($exception, MingaException::class) && $exception->getInnerException() != null)
		{
			$inner = $exception->getInnerException();
			if (is_a($inner, \Exception::class))
			{
				return self::InternalErrorToText($exception->getCode(), $message, $exception->getFile(),
					$exception->getLine(), [], $exception->getTraceAsString(),
					$inner->getCode(), $inner->getMessage(), $inner->getFile(),
					$inner->getLine(), $inner->getTraceAsString());
			}
			return self::InternalErrorToText($exception->getCode(), $message, $exception->getFile(),
				$exception->getLine(), [], $exception->getTraceAsString(), $inner);
		}
		return self::InternalErrorToText($exception->getCode(), $message, $exception->getFile(),
			$exception->getLine(), [], $exception->getTraceAsString());
	}

	private static function InternalErrorToText($errorNumber, $errorMessage, $errorFile, $errorLine,
		$context = [], $trace = null,
		$innerErrorNumber = null, $innerErrorMessage = null,
		$innerErrorFile = null, $innerErrorLine = null,
		$innerTrace = null)
	{
		$errorMessage = self::TrimMessage($errorMessage);
		$innerErrorMessage = self::TrimMessage($innerErrorMessage);
		$error = self::FormatError($errorMessage, $errorNumber, $errorFile,
			$errorLine, $trace);
		$innerError = '';
		if ($innerErrorMessage)
		{
			$innerError = self::FormatError($innerErrorMessage, $innerErrorNumber, $innerErrorFile,
				$innerErrorLine, $innerTrace, "INNER EXCEPTION");
		}
		$text = $error . $innerError;
		$text .= "===========================================\r\n"
			. "=> Context:\r\n" . print_r($context, true);
		if (self::$extraErrorInfo !== null)
		{
			$text .= "===========================================\r\n"
				. '=> Info:        ' . print_r(self::$extraErrorInfo, true);
		}
		return self::FixLineEndings($text);
	}

	public static function AppendExtraInfo($info) : void
	{
		if (self::$extraErrorInfo === null)
			self::$extraErrorInfo = [];
		self::$extraErrorInfo[] = $info;
	}

	private static function LogErrorSendMail($text) : void
	{
		if (self::$isLoggingMailError)
			return;

		try
		{
			self::$isLoggingMailError = true;
			// Manda el error por mail
			self::PutToMail(self::RemovePassword($text), MailType::Error);

			// Si lo envió sin errores, procesa fatales pendientes
			FatalErrorSender::SendFatalErrors(true);
			FatalErrorSender::SendErrorLog(true);
		}
		catch(\Exception $e)
		{
			self::PutToFatalErrorLog("ERROR: " . $e->getMessage() . "<br>\r\n" . $text);
		}
		finally
		{
			self::$isLoggingMailError = false;
		}
	}

	private static function RemovePassword($text)
	{
		$words = ['password', 'passwordi', 'pppassword', 'reg_password', 'reg_verification'];
		foreach($words as $word)
		{
			if(Str::Contains($text, '[' . $word . ']'))
			{
				$text = preg_replace('/(\[' . $word . '\] => ).*/',
					'$1[removido]<br>', $text);
			}
		}
		return $text;
	}

	public static function HandleSilentException(\Exception $e) : void
	{
		$textToShow = self::LogException($e, true);

		if(Context::Settings()->Debug()->debug && Str::StartsWith($e->getMessage(), 'Error running: "pdf') == false)
		{
			if(System::IsCli())
			{
				echo strip_tags($textToShow);
				exit();
			}
			MessageBox::ThrowBackMessage($textToShow);
			exit();
		}
	}

	public static function FormatTraceLog(array $trace) : string
	{
		$log = '<p>';
		foreach ($trace as $i => $t)
		{
			$log .= $i . ' => <a href="repath://' . $t['file'] . '@' . $t['line'] . '">'
				. $t['file'] . ' (' . $t['line'] . ')</a>: ' . Arr::SafeGet($t, 'class') . '::' . $t['function'] . '().<br>';
		}
		return $log . '</p>';
	}

	public static function LogException($exception, $silent = false)
	{
		$message = $exception->getMessage();
		if ($silent)
			$message .= ' (silently processed)';

		if (is_a($exception, MingaException::class) && $exception->getInnerException())
		{
			$inner = $exception->getInnerException();
			if (is_a($inner, \Exception::class))
			{
				return self::LogError($exception->getCode(), $message, $exception->getFile(),
					$exception->getLine(), [], $exception->getTraceAsString(),
					$inner->getCode(), $inner->getMessage(), $inner->getFile(),
					$inner->getLine(), $inner->getTraceAsString());
			}
			return self::LogError($exception->getCode(), $message, $exception->getFile(),
				$exception->getLine(), [], $exception->getTraceAsString(), $inner);
		}
		return self::LogError($exception->getCode(), $message, $exception->getFile(),
			$exception->getLine(), [], $exception->getTraceAsString());
	}

	public static function PutToFatalErrorLog(string $text) : void
	{
		// Guarda en una carpeta de errores que no pudieron ser notificados.
		self::PutToLog(self::FatalErrorsPath, $text, true);
	}

	public static function PutToJsErrorLog(string $text) : void
	{
		// Guarda en una carpeta de errores de javascript.
		self::PutToLog(self::JsErrorsPath, $text);
	}

	public static function PutToErrorLog(string $text) : void
	{
		// Guarda en la carpeta estandar de errores.
		self::PutToLog(self::ErrorsPath, $text);
	}

	public static function PutToLog(string $branch, string $text, bool $doNotSaveMonthly = false) : void
	{
		// Lo graba en log
		$logPath = Context::Paths()->GetLogLocalPath() . '/' . $branch;
		$path = $logPath;
		if ($doNotSaveMonthly == false)
			$path .= '/' . Date::GetLogMonthFolder();

		IO::EnsureExists($logPath);
		IO::EnsureExists($path);
		$file = Date::FormattedArNowMs() . '-' . Str::UrlencodeFriendly(Context::LoggedUser()) . '.txt';
		$file = str_replace(':', '.', $file);
		$file = str_replace('+', '-', $file);
		$file = $path . '/' . $file;
		// va
		IO::WriteAllText($file, $text);
		if (self::$extraErrorTarget !== null)
		{
			$directory = dirname(self::$extraErrorTarget);
			if (IO::Exists($directory))
				IO::WriteAllText(self::$extraErrorTarget, $text);
		}
	}

	public static function PutToMailJs(string $text) : bool
	{
		return self::PutToMail($text, MailType::JavascriptError, 'Javascript ');
	}

	public static function PutToMailFatal(string $text) : bool
	{
		return self::PutToMail($text, MailType::FatalError, 'Fatal ');
	}

	public static function PutToMail(string $text, int $type, string $prefix = '') : bool
	{
		if (empty(Context::Settings()->Mail()->NotifyAddressErrors))
			return true;
		// Manda email...
		$mail = new MailError();
		$mail->to = Context::Settings()->Mail()->NotifyAddressErrors;
		$mail->subject = $prefix . 'Error ' . Context::Settings()->applicationName . ' - ' . Date::FormattedArNow() . '-' . Str::UrlencodeFriendly(Context::LoggedUser());
		$mail->message = $text;
		if (Context::Settings()->isTesting)
			return true;
		$mail->SendByType($type);
		return true;
	}

	private static function GetLevel(int $errorNumber) : string
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
			case E_COMPILE_ERROR:
				return 'E_COMPILE_ERROR'; // 64
			case E_COMPILE_WARNING:
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
				return (string)$errorNumber;
		}
	}
}
