<?php

namespace minga\framework;

class WebClient
{
	protected $ch;
	protected $cherr = null;
	protected $isClosed = true;

	public $httpCode;
	public $error;
	public $throwErrors = true;
	public $logFile = null;
	public $logFile2 = null;
	public $contentType = '';
	public $requestHeaders = [];

	private $cookieFile = '';

	public function __construct($throwErrors = true)
	{
		$this->throwErrors = $throwErrors;
	}

	public function Initialize($path = '')
	{
		$agent = 'Mozilla/5.0 (Windows NT 6.0; rv:21.0) Gecko/20100101 Firefox/21.0';
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);

		curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		if ($path != '')
		{
			curl_setopt($this->ch, CURLOPT_COOKIEJAR, $path. '/cookie.txt');
			curl_setopt($this->ch, CURLOPT_COOKIEFILE, $path. '/cookie.txt');
			$this->logFile = $path. '/log.txt';
		}
		$this->isClosed = false;

	}

	public function ExtraLog()
	{
		$this->logFile2 = $this->logFile . '.extra.txt';

		$handle = fopen($this->logFile2, 'w');
		curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
		curl_setopt($this->ch, CURLOPT_STDERR, $handle);
		$this->cherr = $handle;
	}

	public function SetFollowRedirects($value)
	{
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $value);
	}

	public function SetPort($port)
	{
		curl_setopt($this->ch, CURLOPT_PORT, $port);
	}

	public function SetReferer($referer)
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
		if ($saveHeaders && $file != '')
		{
			$this->doExecute($url, $file . '.headers.res', $args, true);
			$contents = IO::ReadAllText($file . '.headers.res');
			$headers = $this->get_headers_from_curl_response2($contents);
			IO::WriteEscapedIniFile($file . '.headers.txt', $headers);
			IO::Delete($file . '.headers.res');
			if ($maxFileSize != -1)
			{
				if (array_key_exists('Content-Length', $headers))
				{
					$length = $headers['Content-Length'];
					if ($length > $maxFileSize)
					{
						IO::Delete($file . '.headers.txt');
						Profiling::EndTimer();
						return false;
					}
				}
			}
		}
		$ret = $this->doExecute($url, $file, $args, false);
		Profiling::EndTimer();
		return $ret;
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
				MessageBox::ThrowMessage("Error: " . $this->error);
				$this->Finalize();
			}
			return '';
		}
		else
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
		$fh = null;		if ($file != '')
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
				MessageBox::ThrowMessage("Error: " . $this->error);
				$this->Finalize();
			}
			return '';
		}
		else
			return $ret;
	}

	private function AddPostFields($args)
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
		else
		{
			;
				/*curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/x-www-form-urlencoded'));*/
		}

		$cad = '';
		$hasFile = false;
		foreach($args as $key => $value)
		{
			if (is_array($value) == false)
				$value = array($value);
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

	private function ParseErrorCodes($ret)
	{
		$this->httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		$this->error = curl_error($this->ch);
		// guarda resultado en el log
		$this->AppendLogData('Status', $this->httpCode);
		if ($ret == false)
			$this->AppendLogData('Error', $this->error);
		else
			$this->AppendLogData('Content-Length', curl_getinfo($this->ch,CURLINFO_CONTENT_LENGTH_DOWNLOAD));
	}

	public function printInfo()
	{
		echo('<p>');
		$info = curl_getinfo($this->ch);
		print_r($info);
		echo('</p>');
	}

	private function get_headers_from_curl_response(&$response)
	{
		$headers = [];
		$sep = strpos($response, "\r\n\r\n");
		$header_text = substr($response, 0, $sep);
		$response = substr($response, $sep + 4);

		foreach (explode("\r\n", $header_text) as $i => $line)
			if ($i === 0)
				$headers['http_code'] = $line;
			else
			{
				list ($key, $value) = explode(': ', $line);

				$headers[$key] = $value;
			}

		return $headers;
	}

	private function get_headers_from_curl_response2($header_text)
	{
		$headers = [];
		foreach (explode("\r\n", $header_text) as $line)
			if (Str::StartsWith($line, 'HTTP/'))
				$headers['http_code'] = $line;
			else
			{
				if ($line != '' && Str::Contains($line, ': '))
				{
					list ($key, $value) = explode(': ', $line);
					$headers[$key] = $value;
				}
			}

		return $headers;
	}

	private function get_content_from_curl_response2($header_text)
	{
		return Str::EatUntil($header_text, "\r\n\r\n");
	}

	public function Finalize()
	{
		if ($this->isClosed == false)
		{
			curl_close($this->ch);
			$this->isClosed = true;
			if ($this->cherr != null)
				fclose($this->cherr);
		}
	}

	public function AppendLog($value)
	{
		if ($this->logFile == null)
			return;
		IO::AppendLine($this->logFile, "\r\n" . $value . ' [' . Date::FormattedArNow() . ']');
	}

	private function AppendLogData($key, $value)
	{
		if ($this->logFile == null)
			return;
		IO::AppendLine($this->logFile, '=> ' . $key . ': ' . $value);
	}

	public function ClearCookieFile()
	{
		if($this->cookieFile != '')
			IO::Delete($this->cookieFile);

		$this->cookieFile = '';
	}

	public function GetCookieFile()
	{
		if($this->cookieFile == '')
			throw new ErrorException('Create cookie first.');

		return $this->cookieFile;
	}

	public function CreateCookieFile()
	{
		if($this->cookieFile == '')
			$this->cookieFile = IO::GetTempFilename();

		return $this->cookieFile;
	}

	public function Upload($url, $path, array $postData = [])
	{
		$finfo = new \finfo(FILEINFO_MIME);

		$mime = $finfo->file($path);
		$postData['file'] = new \CURLFile($path, $mime);


		$ch = curl_init();
		$this->ch = $ch;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->CreateCookieFile());
		$agent = 'Mozilla/5.0 (Windows NT 6.0; rv:21.0) Gecko/20100101 Firefox/21.0';
		curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
		$ret = curl_exec($ch);
		if (curl_errno($ch))
		{
			$this->ParseErrorCodes($ret);

			if ($this->throwErrors)
				MessageBox::ThrowMessage('Error: ' . $this->error);

			$ret = '';
		}

		curl_close($ch);

		return $ret;
	}

}

