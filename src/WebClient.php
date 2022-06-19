<?php

namespace minga\framework;

class WebClient
{
	protected $ch;
	protected $cherr = null;
	protected bool $isClosed = true;

	public $httpCode;
	public $error;
	public bool $throwErrors = true;
	public $logFile = null;
	public $logFile2 = null;
	public $contentType = '';
	public $requestHeaders = [];

	private string $cookieFile = '';

	public function __construct(bool $throwErrors = true)
	{
		$this->throwErrors = $throwErrors;
	}

	public function Initialize(string $path = '') : void
	{
		$agent = 'Mozilla/5.0 (Windows NT 6.0; rv:21.0) Gecko/20100101 Firefox/21.0';
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);

		curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		if ($path != '')
		{
			curl_setopt($this->ch, CURLOPT_COOKIEJAR, $path . '/cookie.txt');
			curl_setopt($this->ch, CURLOPT_COOKIEFILE, $path . '/cookie.txt');
			$this->logFile = $path . '/log.txt';
		}
		$this->isClosed = false;

	}

	public function ExtraLog() : void
	{
		$this->logFile2 = $this->logFile . '.extra.txt';

		$handle = fopen($this->logFile2, 'w');
		curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
		curl_setopt($this->ch, CURLOPT_STDERR, $handle);
		$this->cherr = $handle;
	}

	public function SetFollowRedirects(bool $value) : void
	{
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $value);
	}

	public function SetPort(int $port) : void
	{
		curl_setopt($this->ch, CURLOPT_PORT, $port);
	}

	public function SetReferer(string $referer) : void
	{
		curl_setopt($this->ch, CURLOPT_REFERER, $referer);
	}

	public function ExecuteWithSizeLimit($url, $maxFileSize, $file = '', $args = [])
	{
		return $this->Execute($url, $file, $args, true, $maxFileSize);
	}

	public function Execute($url, $file = '', $args = [], $saveHeaders = false, $maxFileSize = -1)
	{
		Profiling::BeginTimer();
		try
		{
			if ($saveHeaders && $file != '')
			{
				$this->doExecute($url, $file . '.headers.res', $args, true);
				$contents = IO::ReadAllText($file . '.headers.res');
				$headers = $this->get_headers_from_curl_response2($contents);
				IO::WriteEscapedIniFile($file . '.headers.txt', $headers);
				IO::Delete($file . '.headers.res');
				if ($maxFileSize != -1)
				{
					if ($this->HasContentLength($headers))
					{
						$length = $this->GetContentLength($headers);
						if ($length > $maxFileSize)
						{
							IO::Delete($file . '.headers.txt');
							return false;
						}
					}
				}
			}
			return $this->doExecute($url, $file, $args, false);
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	private function HasContentLength(array $headers) : bool
	{
		return isset($headers['Content-Length'])
			|| isset($headers['content-length']);
	}

	private function GetContentLength(array $headers) : int
	{
		if(isset($headers['Content-Length']))
			return (int)$headers['Content-Length'];

		if(isset($headers['content-length']))
			return (int)$headers['content-length'];

		return 0;
	}

	public function ExecuteForRedirect($url, $file = '', $args = [])
	{
		Profiling::BeginTimer();

		$ret = $this->doExecuteForRedirect($url, $file . '.headers.res', $args);
		$contents = IO::ReadAllText($file . '.headers.res');
		$headers = $this->get_headers_from_curl_response2($contents);
		IO::WriteEscapedIniFile($file . '.headers.txt', $headers);
		$body = $this->get_content_from_curl_response2($contents);
		IO::WriteAllText($file, $body);

		Profiling::EndTimer();
		return $ret;
	}

	private function doExecute($url, $file = '', $args = null, $saveHeaders = false)
	{
		curl_setopt($this->ch, CURLOPT_URL, $url);
		$this->requestHeaders = [
			'Accept-Language: es-es,en',
			'Accept: text/html, application/xhtml+xml, application/xml;q=0.9,*/*;q=0.8',
			// 'Pragma: no-cache',
			// 'Cache-Control: no-cache',
			// 'Connection: keep-alive',
			// 'Accept-Encoding: gzip, deflate',
		];

		if ($args != null)
		{
			$method = 'POST ';
			$this->AddPostFields($args);
		}
		else
		{
			$method = 'GET ';
			curl_setopt($this->ch, CURLOPT_POST, 0);
		}
		$this->AppendLog($method . $url);
		$this->AppendLogData('File', $file);

		if ($saveHeaders)
		{
			curl_setopt($this->ch, CURLOPT_HEADER, 1);
			curl_setopt($this->ch, CURLOPT_NOBODY, 1);
		}
		else
		{
			curl_setopt($this->ch, CURLOPT_HEADER, 0);
			curl_setopt($this->ch, CURLOPT_NOBODY, 0);
		}
		// indica el archivo
		$fh = null;
		if ($file != '')
		{
			$fh = fopen($file, 'w');
			curl_setopt($this->ch, CURLOPT_FILE, $fh);
		}
		else
		{
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		}
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->requestHeaders);



		// Execute the request
		$ret = curl_exec($this->ch);
		if ($fh != null && $file != '')
			fclose($fh);

		$this->contentType = curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
		// toma error
		$this->ParseErrorCodes($ret);

		if ($ret === false)
		{
			if ($this->throwErrors)
			{
				MessageBox::ThrowMessage('Error: ' . $this->error);
				$this->Finalize();
			}
			return '';
		}

			return $ret;
	}

	private function doExecuteForRedirect($url, $file, $args = null)
	{
		curl_setopt($this->ch, CURLOPT_URL, $url);

		if ($args != null)
		{
			$method = 'POST ';
			$this->AddPostFields($args);
		}
		else
		{
			$method = 'GET ';
			curl_setopt($this->ch, CURLOPT_POST, 0);
		}
		$this->AppendLog($method . $url);
		$this->AppendLogData('File', $file);

		curl_setopt($this->ch, CURLOPT_HEADER, 1);
		curl_setopt($this->ch, CURLOPT_NOBODY, 0);

		// indica el archivo
		$fh = null;
		if ($file != '')
		{
			$fh = fopen($file, 'w');
			curl_setopt($this->ch, CURLOPT_FILE, $fh);
		}
		else
		{
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		}
		// Execute the request
		$ret = curl_exec($this->ch);
		if ($fh != null && $file != '')
			fclose($fh);

		$this->contentType = curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
		// toma error
		$this->ParseErrorCodes($ret);

		if ($ret === false)
		{
			if ($this->throwErrors)
			{
				MessageBox::ThrowMessage('Error: ' . $this->error);
				$this->Finalize();
			}
			return '';
		}

			return $ret;
	}

	private function AddPostFields($args) : void
	{
		curl_setopt($this->ch, CURLOPT_POST, 1);
		if (is_array($args) == false)
		{
			// json
			$this->requestHeaders[] = 'Content-Type: application/json';
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $args);
			return;
		}
		//curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
		//'Content-Type: application/x-www-form-urlencoded']);

		$cad = '';
		$hasFile = false;
		foreach($args as $key => $value)
		{
			if (is_array($value) == false)
				$value = [$value];
			foreach($value as $subValues)
			{
				if ($cad != '') $cad = $cad . '&';
				if (is_a($subValues, 'CURLFile')) // Str::StartsWith($subValues, '@'))
					$hasFile = true;
				else
					$cad = $cad . $key . '=' . urlencode($subValues);
			}
		}
		if ($hasFile == false)
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $cad);
		else
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $args);
	}

	private function ParseErrorCodes($ret) : void
	{
		$this->httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		$this->error = curl_error($this->ch);
		// guarda resultado en el log
		$this->AppendLogData('Status', $this->httpCode);
		if ($ret == false)
			$this->AppendLogData('Error', $this->error);
		else
			$this->AppendLogData('Content-Length', curl_getinfo($this->ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD));
	}

	public function printInfo() : void
	{
		echo '<p>';
		$info = curl_getinfo($this->ch);
		print_r($info);
		echo '</p>';
	}

	private function get_headers_from_curl_response2(string $headerText) : array
	{
		$headers = [];
		foreach (explode("\r\n", $headerText) as $line)
			if (Str::StartsWith($line, 'HTTP/'))
				$headers['http_code'] = $line;
			else
			{
				if ($line != '' && Str::Contains($line, ': '))
				{
					 [$key, $value] = explode(': ', $line);
					$headers[$key] = $value;
				}
			}

		return $headers;
	}

	private function get_content_from_curl_response2(string $headerText) : string
	{
		return Str::EatUntil($headerText, "\r\n\r\n");
	}

	public function Finalize() : void
	{
		if ($this->isClosed == false)
		{
			curl_close($this->ch);
			$this->isClosed = true;
			if ($this->cherr != null)
				fclose($this->cherr);
		}
	}

	public function AppendLog($value) : void
	{
		if ($this->logFile == null)
			return;
		IO::AppendLine($this->logFile, "\r\n" . $value . ' [' . Date::FormattedArNow() . ']');
	}

	private function AppendLogData($key, $value) : void
	{
		if ($this->logFile == null)
			return;
		IO::AppendLine($this->logFile, '=> ' . $key . ': ' . $value);
	}

	public function ClearCookieFile() : void
	{
		if($this->cookieFile != '')
			IO::Delete($this->cookieFile);

		$this->cookieFile = '';
	}

	public function GetCookieFile() : string
	{
		if($this->cookieFile == '')
			throw new ErrorException('Primero crear la cookie.');

		return $this->cookieFile;
	}

	public function CreateCookieFile() : string
	{
		if($this->cookieFile == '')
			$this->cookieFile = IO::GetTempFilename();

		return $this->cookieFile;
	}
}

