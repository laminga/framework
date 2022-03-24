<?php

namespace minga\framework;

class WebConnection
{
	protected $ch;
	protected $cherr = null;
	protected $isClosed = true;
	protected $followRedirects = true;
	protected $lastLocation = '';
	protected $maxFileSize = -1;
	protected $httpCode = 0;
	protected $error = '';

	public $throwErrors = true;
	public $logFile = null;
	public $responseFile = null;
	public $bucket = null;
	public $logFile2 = null;
	public $contentType = '';
	public $requestHeaders = [];
	public $accept = 'text/html, application/xhtml+xml, application/xml;q=0.9,*/*;q=0.8';
	private $cookieFile = '';

	public function __construct($throwErrors = false)
	{
		$this->throwErrors = $throwErrors;
	}

	public function Initialize($path = '') : void
	{
		$agent = 'Mozilla/5.0 (Windows NT 6.0; rv:21.0) Gecko/20100101 Firefox/21.0';
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, false);
		if ($path == '')
		{
			$this->bucket = FileBucket::Create();
			$path = $this->bucket->path;
		}
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $path . '/cookie.txt');
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $path . '/cookie.txt');
		$this->logFile = $path . '/log.txt';
		$this->responseFile = $path . '/response.dat';
		$this->isClosed = false;

	}

	public function EnableExtraLog() : void
	{
		if ($this->cherr == null)
		{
			$this->logFile2 = $this->logFile . '.extra.txt';
			$handle = fopen($this->logFile2, 'w');
			curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
			curl_setopt($this->ch, CURLOPT_STDERR, $handle);
			$this->cherr = $handle;
		}
	}

	public function SetFollowRedirects($value) : void
	{
		$this->followRedirects = $value;
	}

	public function SetPort($port) : void
	{
		curl_setopt($this->ch, CURLOPT_PORT, $port);
	}

	public function SetReferer($referer) : void
	{
		$this->lastLocation = $referer;
	}

	public function SetMaxFileSize($size) : void
	{
		$this->maxFileSize = $size;
	}

	public function Get($url, $file = '')
	{
		Profiling::BeginTimer();
		$response = $this->doExecute($url, $file, []);
		$red = 0;

		while ($response->httpCode == 301 || $response->httpCode == 302 || $response->httpCode == 307)
		{
			$red++;

			if($response->HasLocationHeader() == false)
			{
				$this->AppendLog('El header Location no fue encontrado.');
				break;
			}

			$location = $response->GetLocationHeader();
			$this->AppendLog('Redirecting to ' . $location);
			$response = $this->Get($location, $file);
			if ($red > 10)
			{
				$this->AppendLog('Máxima cantidad de redirects alcanzada.');
				break;
			}
		}
		Profiling::EndTimer();

		return $response;
	}

	public function Post($url, $file = '', $args = null)
	{
		Profiling::BeginTimer();
		$response = $this->doExecute($url, $file, $args);
		Profiling::EndTimer();
		if ($response->httpCode == 301 || $response->httpCode == 302 || $response->httpCode == 307)
		{
			$location = $response->GetLocationHeader();
			$this->AppendLog('Redirigiendo a ' . $location);
			return $this->Get($location, $file);
		}
		return $response;
	}

	private function StayHttps($url)
	{
		$partsLast = parse_url($this->lastLocation);
		$parts = parse_url($url);
		if ($parts['host'] != $partsLast['host'])
			return $url;
		$port = Arr::SafeGet($parts, 'port');
		$lastPort = Arr::SafeGet($partsLast, 'port');
		if ($port != $lastPort)
			return $url;
		// perdió el https pero es el mismo server
		return 'https://' . substr($url, 7);
	}

	private function ResolveRelativeUrl($url)
	{
		if (Str::StartsWith($url, '/') == false)
			throw new ErrorException('Dirección url relativa o redirección no soportado');

		if ($this->lastLocation == '')
			throw new ErrorException('Dirección url relativa o redirección requriere referer');

		$parts = parse_url($this->lastLocation);
		$newurl = $parts['scheme'] . '://' . $parts['host'];
		$port = Arr::SafeGet($parts, 'port', '80');
		if ($port != '80')
			$newurl .= ':' . $port;
		return $newurl . $url;
	}

	private function doExecute($url, $file = '', $args = null)
	{
		if ($this->ch == null)
			throw new ErrorException('Debe llamarse el método Initialize() antes.');

		$this->EnableExtraLog();

		if (Str::StartsWith($url, 'http:') && Str::StartsWith($this->lastLocation, 'https:'))
			$url = $this->StayHttps($url);

		if (Str::StartsWith($url, 'http') == false)
			$url = $this->ResolveRelativeUrl($url);

		curl_setopt($this->ch, CURLOPT_URL, $url);

		$this->requestHeaders = [
			'Accept-Language: es-es,en',
			'Accept: ' . $this->accept,
			'Pragma: no-cache',
			'Cache-Control: no-cache',
			'Connection: keep-alive',
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

		curl_setopt($this->ch, CURLOPT_HEADER, false);
		curl_setopt($this->ch, CURLOPT_NOBODY, 0);
		$this->SetReferer($this->lastLocation);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->requestHeaders);

		// indica el archivo
		if ($file == '')
			$file = IO::GetTempFilename();

		$headerFile = $file . '.headers.txt';

		$this->AppendLogData('HeaderFile', $headerFile);

		$fheader = fopen($headerFile, 'w');
		curl_setopt($this->ch, CURLOPT_WRITEHEADER, $fheader);

		$fh = fopen($this->responseFile, 'w');
		curl_setopt($this->ch, CURLOPT_FILE, $fh);
		// Execute the request
		$ret = curl_exec($this->ch);

		fclose($fheader);
		fclose($fh);

		$this->lastLocation = $url;
		// toma headers
		$headers = [];
		$headersSize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
		if ($headersSize == 0)
		{
			$ret = false;
			$this->error = 'El tamaño del header es cero.';
		}
		else
		{
			$headers = $this->HeadersToArray($headerFile);
			copy($this->responseFile, $file);
		}
		// toma error
		$this->ParseErrorCodes($ret, $file);

		if ($this->maxFileSize != -1
			&& $this->HasContentLength($headers))
		{
			$length = $this->GetContentLength($headers);
			if ($length > $this->maxFileSize)
			{
				IO::Delete($file);
				$ret = false;
			}
		}

		// response
		$response = new WebResponse();
		$response->contentType = curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
		$response->uri = $url;
		$response->file = $file;
		$response->httpCode = $this->httpCode;
		$response->error = $this->error;
		$response->headers = $headers;
		$response->success = $ret;
		if ($this->error != '')
			$this->AppendLogData('error retornando: ', $this->error);

		IO::Delete($headerFile);
		if ($ret === false && $this->throwErrors)
		{
			$this->Finalize();
			MessageBox::ThrowMessage('Error: ' . $this->error);
		}

		return $response;
	}

	private function HasContentLength($headers)
	{
		return isset($headers['Content-Length'])
			|| isset($headers['content-length']);
	}

	private function GetContentLength($headers)
	{
		if(isset($headers['Content-Length']))
			return $headers['Content-Length'];

		if(isset($headers['content-length']))
			return $headers['content-length'];

		return null;
	}

	private function HeadersToArray($headerFile)
	{
		$lines = [];
		if(file_exists($headerFile))
		{
			$lines = explode("\r\n",
				file_get_contents($headerFile));
		}

		$headers = [];
		foreach ($lines as $i => $line)
		{
			$i = strpos($line, ': ');
			if ($i)
				$headers[substr($line, 0, $i)] = substr($line, $i + 2);
		}
		return $headers;
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

		$cad = self::PreparePostValues($args, $hasFile);
		if ($hasFile == false)
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $cad);
		else
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $args);
	}

	public static function PreparePostValues(array $args, ?bool &$hasFile = false) : string
	{
		$ret = '';
		ksort($args);
		foreach($args as $key => $value)
		{
			if (is_array($value) == false)
				$value = [$value];
			foreach($value as $subValues)
			{
				if ($ret != '')
					$ret .= '&';
				if (is_a($subValues, 'CURLFile')) // Str::StartsWith($subValues, '@'))
					$hasFile = true;
				else
				{
					if (is_object($subValues) || is_array($subValues))
						$subValues = json_encode($subValues);

					$ret .= $key . '=' . urlencode($subValues);
				}
			}
		}
		return $ret;
	}

	private function ParseErrorCodes($ret, $file) : void
	{
		$this->httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		$this->error = curl_error($this->ch);
		// guarda resultado en el log
		$this->AppendLogData('Status', $this->httpCode);
		if ($ret == false)
			$this->AppendLogData('Error', $this->error);
		else if (file_exists($file))
			$this->AppendLogData('Length', filesize($file));
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
		if ($this->bucket != null)
		{
			$this->bucket->Delete();
			$this->bucket = null;
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

	public function GetCookieFile()
	{
		if($this->cookieFile == '')
			throw new ErrorException('Primero crear la cookie.');

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
			$this->ParseErrorCodes($ret, $path);

			if ($this->throwErrors)
				MessageBox::ThrowMessage('Error: ' . $this->error);

			$ret = '';
		}
		curl_close($ch);
		return $ret;
	}
}

