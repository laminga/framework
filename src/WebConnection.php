<?php

namespace minga\framework;

class WebConnection
{
	protected $ch;
	protected $cherr = null;
	protected bool $isClosed = true;
	protected bool $followRedirects = true;
	protected string $lastLocation = '';
	protected int $maxFileSize = -1;
	private int $httpCode = 0;
	private string $error = '';

	private const Get = 'GET';
	private const Post = 'POST';
	private const Delete = 'DELETE';
	private const Put = 'PUT';
	private const Patch = 'PATCH';

	public bool $throwErrors = true;
	public ?string $logFile = null;
	public ?string $responseFile = null;
	public ?FileBucket $bucket = null;
	public ?string $logFile2 = null;
	public string $contentType = '';
	public array $requestHeaders = [];
	public string $accept = 'text/html, application/xhtml+xml, application/xml;q=0.9,*/*;q=0.8';
	private string $cookieFile = '';
	public bool $keepHeaders = false;

	public function __construct(bool $throwErrors = false)
	{
		$this->throwErrors = $throwErrors;
	}

	public function Initialize(string $path = '') : void
	{
		$userAgent = 'Mozilla/5.0 (Windows NT 6.0; rv:21.0) Gecko/20100101 Firefox/21.0';
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($this->ch, CURLOPT_USERAGENT, $userAgent);
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

	public function SetFollowRedirects(bool $value) : void
	{
		$this->followRedirects = $value;
	}

	public function SetPort(int $port) : void
	{
		curl_setopt($this->ch, CURLOPT_PORT, $port);
	}

	public function SetReferer(string $referer) : void
	{
		$this->lastLocation = $referer;
	}

	public function SetMaxFileSize(int $size) : void
	{
		$this->maxFileSize = $size;
	}

	public function Get(string $url, string $file = '', int $redirectCount = 0) : WebResponse
	{
		Profiling::BeginTimer();
		$response = $this->doExecute(self::Get, $url, $file);
		$red = $redirectCount;

		while ($response->httpCode == 301 || $response->httpCode == 302 || $response->httpCode == 307)
		{
			$red++;

			if($response->HasLocationHeader() == false)
			{
				$this->AppendLog('El header Location no fue encontrado.');
				break;
			}

			$location = $response->GetLocationHeader();
			$this->PrepareForRedirect($location, $file, $red);
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

	/**
	 * @param string|array|null $args
	 */
	public function Post(string $url, string $file = '', $args = null) : WebResponse
	{
		Profiling::BeginTimer();
		$response = $this->doExecute(self::Post, $url, $file, $args);
		Profiling::EndTimer();
		if ($response->httpCode == 301 || $response->httpCode == 302 || $response->httpCode == 307)
		{
			$location = $response->GetLocationHeader();
			$this->PrepareForRedirect($location, $file);
			return $this->Get($location, $file, 1);
		}
		return $response;
	}

	/**
	 * @param string|array|null $args
	 */
	public function Put(string $url, string $file = '', $args = null) : WebResponse
	{
		Profiling::BeginTimer();
		$response = $this->doExecute(self::Put, $url, $file, $args);
		Profiling::EndTimer();
		if ($response->httpCode == 301 || $response->httpCode == 302 || $response->httpCode == 307)
		{
			$location = $response->GetLocationHeader();
			$this->AppendLog('Redirigiendo PUT a ' . $location);
			return $this->Put($location, $file, $args);
		}
		return $response;
	}

	/**
	 * @param string|array|null $args
	 */
	public function Patch(string $url, string $file = '', $args = null) : WebResponse
	{
		Profiling::BeginTimer();
		$response = $this->doExecute(self::Patch, $url, $file, $args);
		Profiling::EndTimer();
		if ($response->httpCode == 301 || $response->httpCode == 302 || $response->httpCode == 307)
		{
			$location = $response->GetLocationHeader();
			$this->AppendLog('Redirigiendo PATCH a ' . $location);
			return $this->Patch($location, $file, $args);
		}
		return $response;
	}

	public function Delete(string $url, string $file = '') : WebResponse
	{
		Profiling::BeginTimer();
		$response = $this->doExecute(self::Delete, $url, $file);
		Profiling::EndTimer();
		if ($response->httpCode == 301 || $response->httpCode == 302 || $response->httpCode == 307)
		{
			$location = $response->GetLocationHeader();
			$this->AppendLog('Redirigiendo DELETE a ' . $location);
			return $this->Delete($location, $file);
		}
		return $response;
	}

	private function PrepareForRedirect(string $location, string $file, int $red = 1) : void
	{
		$this->AppendLog('Redirigiendo a ' . $location);
		// renombra los archivos de log
		$fileHeaders = $file . '.headers.txt';
		$reqHeaders = $fileHeaders . ".req.txt";

		$n = strrpos($file, ".");
		$oldFileName = substr($file, 0, $n) . "@" . $red . substr($file, $n);
		$n = strrpos($file, ".");
		$oldHeadersFile = substr($fileHeaders, 0, $n) . "@" . $red . substr($fileHeaders, $n);
		$n = strrpos($file, ".");
		$oldReqHeaders = substr($reqHeaders, 0, $n) . "@" . $red . substr($reqHeaders, $n);

		IO::Move($file, $oldFileName);
		IO::Move($fileHeaders, $oldHeadersFile);
		IO::Move($reqHeaders, $oldReqHeaders);
	}

	private function StayHttps(string $url) : string
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

	private function ResolveRelativeUrl(string $url) : string
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

	/**
	 * @param string|array|null $args
	 */
	private function doExecute(string $method, string $url, string $file = '', $args = null) : WebResponse
	{
		if ($this->ch == null)
			throw new ErrorException('Debe llamarse el método Initialize() antes.');

		$this->EnableExtraLog();

		if (Str::StartsWith($url, 'http:') && Str::StartsWith($this->lastLocation, 'https:'))
			$url = $this->StayHttps($url);

		if (Str::StartsWith($url, 'http') == false)
			$url = $this->ResolveRelativeUrl($url);

		curl_setopt($this->ch, CURLOPT_URL, $url);

		$this->SetHeader('Accept-Language', 'es-AR,es,en');
		$this->SetHeader('Accept', $this->accept);
		$this->SetHeader('Pragma', 'no-cache');
		$this->SetHeader('Cache-Control', 'no-cache');
		$this->SetHeader('Connection', 'keep-alive');

		if ($args != null)
		{
			$this->AddPostFields($args);
		}
		else
		{
			curl_setopt($this->ch, CURLOPT_POST, 0);
		}

		if ($method == self::Post)
			curl_setopt($this->ch, CURLOPT_POST, 1);
		else if ($method == self::Delete || $method == self::Patch || $method == self::Put)
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);

		$this->AppendLog($method . ' ' . $url);
		$this->AppendLogData('File', $file);

		curl_setopt($this->ch, CURLOPT_HEADER, false);
		curl_setopt($this->ch, CURLOPT_NOBODY, 0);
		$this->SetReferer($this->lastLocation);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->requestHeaders);

		// indica el archivo
		if ($file == '')
			$file = IO::GetTempFilename();

		$headerFile = $file . '.headers.txt';
		$requestHeaderFile = $file . '.headers.txt.req.txt';

		$this->AppendLogData('HeaderFile', $headerFile);
		$this->AppendLogData('HeaderResponseFile', $headerFile);

		IO::WriteAllText($requestHeaderFile, Str::Replace(print_r($this->requestHeaders, true), "[", "\r\n["));

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
		$this->ParseErrorCodes((bool)$ret, $file);

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

		if (!$this->keepHeaders)
		{
			IO::Delete($headerFile);
			IO::Delete($requestHeaderFile);
		}

		if ($ret == false && $this->throwErrors)
		{
			$this->Finalize();
			MessageBox::ThrowMessage('Error: ' . $this->error);
		}

		return $response;
	}

	private function HasContentLength(array $headers) : bool
	{
		return isset($headers['Content-Length'])
			|| isset($headers['content-length']);
	}

	private function SetHeader(string $key, string $value) : void
	{
		$valueItem = $key . ": " . $value;
		for($n = 0; $n < count($this->requestHeaders); $n++)
		{
			if (Str::StartsWith($this->requestHeaders[$n], $key . ":"))
			{
				$this->requestHeaders[$n] = $valueItem;
				return;
			}
		}
		$this->requestHeaders[] = $valueItem;
	}

	private function GetContentLength(array $headers) : int
	{
		if(isset($headers['Content-Length']))
			return (int)$headers['Content-Length'];

		if(isset($headers['content-length']))
			return (int)$headers['content-length'];

		return 0;
	}

	private function HeadersToArray(string $headerFile) : array
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

	/**
	 * @param string|array $args
	 */
	private function AddPostFields($args) : void
	{
		if (is_array($args) == false)
		{
			// json
			$this->SetHeader('Content-Type', 'application/json');
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

	private function ParseErrorCodes(bool $ret, string $file) : void
	{
		$this->httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		$this->error = curl_error($this->ch);
		// guarda resultado en el log
		$this->AppendLogData('Status', (string)$this->httpCode);
		if ($ret == false)
			$this->AppendLogData('Error', $this->error);
		else if (file_exists($file))
			$this->AppendLogData('Length', (string)filesize($file));
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

	public function AppendLog(string $value) : void
	{
		if ($this->logFile == null)
			return;
		IO::AppendLine($this->logFile, "\r\n" . $value . ' [' . Date::FormattedArNow() . ']');
	}

	private function AppendLogData(string $key, string $value) : void
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

	public function Upload(string $url, string $path, string $userAgent = '') : string
	{
		$ch = curl_init();
		$this->ch = $ch;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		$data = file_get_contents($path);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->CreateCookieFile());

		// $userAgent = 'Mozilla/5.0 (Windows NT 6.0; rv:21.0) Gecko/20100101 Firefox/21.0';
		if($userAgent != '')
			curl_setopt($this->ch, CURLOPT_USERAGENT, $userAgent);

		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->requestHeaders);


		$fh = fopen($this->responseFile, 'w');
		curl_setopt($this->ch, CURLOPT_FILE, $fh);

		$ret = curl_exec($this->ch);

		fclose($fh);

		$this->httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		if (curl_errno($ch))
		{
			$this->ParseErrorCodes((bool)$ret, $path);

			if ($this->throwErrors)
				throw new PublicException($this->httpCode . ' ' . $this->error);
		}
		curl_close($ch);
		if(is_bool($ret))
			return '';
		return $ret;
	}
}

