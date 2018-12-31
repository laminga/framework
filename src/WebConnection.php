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
	protected $http_code = 0;
	protected $error = '';

	public $throwErrors = true;
	public $logFile = null;
	public $responseFile = null;
	public $bucket = null;
	public $logFile2 = null;
	public $content_type = "";
	public $request_headers = array();

	private $cookie_file = "";

	public function __construct($throwErrors = false)
	{
		$this->throwErrors = $throwErrors;
	}

	public function Initialize($path = '')
	{
		$agent = "Mozilla/5.0 (Windows NT 6.0; rv:21.0) Gecko/20100101 Firefox/21.0";
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
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
	public function EnableExtraLog()
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

	public function SetFollowRedirects($value)
	{
		$this->followRedirects = $value;
	}
	public function SetPort($port)
	{
		curl_setopt($this->ch, CURLOPT_PORT, $port);
	}

	public function SetReferer($referer)
	{
		$this->lastLocation = $referer;
	}
	public function SetMaxFileSize($size)
	{
		$this->maxFileSize = $size;
	}

	public function Get($url, $file = '')
	{
		Profiling::BeginTimer();
		$response = $this->doExecute($url, $file, array());
		$red = 0;

		while ($response->http_code == 301 || $response->http_code == 302 || $response->http_code == 307)
		{
			$red++;
			$location = $response->headers['Location'];
			$this->AppendLog('Redirecting to ' . $location);
			$response = $this->Get($location, $file);
			if ($red > 10)
			{
				$this->AppendLog('Max redirects reached.');
				break;
			}
		}
		Profiling::EndTimer();

		return $response;
	}

	public function Post($url, $file = '', $args)
	{
		Profiling::BeginTimer();
		$response = $this->doExecute($url, $file, $args);
		Profiling::EndTimer();
		if ($response->http_code == 301 || $response->http_code == 302 || $response->http_code == 307)
		{
			$location = $response->headers['Location'];
			$this->AppendLog('Redirecting to ' . $location);
			return $this->Get($location, $file);
		}
		else
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
		$ret = 'https://' . substr($url, 7);
		return $ret;
	}

	private function ResolveRelativeUrl($url)
	{
		if (Str::StartsWith($url, '/') == false)
		{
			throw new \Exception('Relative URL or redirect not supported');
		}
		if ($this->lastLocation == '')
		{
			throw new \Exception('Relative URL or redirect require referer');
		}
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
			throw new \Exception("Initialize() method should be called first.");

		$this->EnableExtraLog();

		if (Str::StartsWith($url, 'http:') && Str::StartsWith($this->lastLocation, 'https:'))
		{
			$url = $this->StayHttps($url);
		}

		if (Str::StartsWith($url, 'http') == false)
		{
			$url = $this->ResolveRelativeUrl($url);
		}

		curl_setopt($this->ch, CURLOPT_URL, $url);

		$this->request_headers = array();
		$this->request_headers[] = "Accept-Language: es-es,en";
		$this->request_headers[] = "Accept: text/html, application/xhtml+xml, application/xml;q=0.9,*/*;q=0.8";
		$this->request_headers[] = "Pragma: no-cache";
		$this->request_headers[] = "Cache-Control: no-cache";
		$this->request_headers[] = "Connection: keep-alive";

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
		$this->SetReferer($this->lastLocation);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->request_headers);

		// indica el archivo
		$fh = null;
		if ($file == '')
			$file = IO::GetTempFilename();

		$fh = fopen($this->responseFile, 'w');
		curl_setopt($this->ch, CURLOPT_FILE, $fh);
		// Execute the request
		$ret = curl_exec($this->ch);
		fclose($fh);
		$this->lastLocation = $url;
		// toma headers
		$headers = array();
		$headersSize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
		if ($headersSize == 0)
		{
			$ret = false;
			$this->error = "HeaderSize is zero.";
		}
		else
		{
			$fh = fopen($this->responseFile, "r");
			// Lee headers
			$header_text = fread($fh, $headersSize);
			$skipFirst = true;
			foreach (explode("\r\n", $header_text) as $i => $line)
			{
				$i = strpos($line, ': ');
				if ($i)
					$headers[substr($line, 0, $i)] = substr($line, $i + 2);
			}
			fclose($fh);
			// saca headers
			$this->TruncateBeggining($this->responseFile, $headersSize);
			copy($this->responseFile, $file);
		}
		// toma error
		$this->ParseErrorCodes($ret, $file);

		if ($this->maxFileSize != -1)
		{
			if (array_key_exists("Content-Length", $headers))
			{
				$length = $headers["Content-Length"];
				if ($length > $this->maxFileSize)
				{
					IO::Delete($file);
				$ret = false;
				}
			}
		}
		// response
		$response = new WebResponse();
		$response->content_type = curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE);
		$response->uri = $url;
		$response->file = $file;
		$response->http_code = $this->http_code;
		$response->error = $this->error;
		$response->headers = $headers;
		$response->success = $ret;
		if ($this->error != "")
			$this->AppendLogData('Returning error: ', $this->error);

		if ($ret === false && $this->throwErrors)
		{
			$this->Finalize();
			MessageBox::ThrowMessage("Error: " . $this->error);
		}
		return $response;
	}

	private function AddPostFields($args)
	{
		curl_setopt($this->ch, CURLOPT_POST, 1);
		if (is_array($args) == false)
		{
			// json
			$this->request_headers[] = 'Content-Type: application/json';
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $args);
			return;
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
				if (is_a($subValues, 'CURLFile')) // Str::StartsWith($subValues, "@"))

					$hasFile = true;
				else
					$cad = $cad . $key . '=' . urlencode($subValues);
			}
		}
		if (!$hasFile)
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $cad);
		else
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $args);
	}

	private function TruncateBeggining($file, $read_position)
	{
		$size = filesize($file);
		$handle = fopen($file, "c+");
		fseek($handle, $read_position);  // return to actual position
		$write_position = 0;
		$chunkSize = 	40960;
    while (($chunk = fread($handle, $chunkSize)) !== FALSE) {
			$read_position = ftell($handle); // get actual line
			fseek($handle, $write_position); // move to previous position
			fwrite($handle, $chunk);           // put actual line in previous position
			fseek($handle, $read_position);  // return to actual position
			$write_position += $chunkSize;    // set write position to the next loop
			if ($read_position >= $size)
				break;
    }
    fflush($handle);                         // write any pending change to file
    ftruncate($handle, $write_position);     // drop the repeated last line
    fclose($handle);
	}

	private function ParseErrorCodes($ret, $file)
	{
		$this->http_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		$this->error = curl_error($this->ch);
		// guarda resultado en el log
		$this->AppendLogData('Status', $this->http_code);
		if (!$ret)
			$this->AppendLogData('Error', $this->error);
		else if (file_exists($file))
			$this->AppendLogData('Length', filesize($file));
	}

	public function Finalize()
	{
		if (!$this->isClosed)
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

	public function AppendLog($value)
	{
		if ($this->logFile == null) return;
		IO::AppendLine($this->logFile, "\r\n" . $value . " [" . Date::FormattedArNow() . "]");
	}

	private function AppendLogData($key, $value)
	{
		if ($this->logFile == null) return;
		IO::AppendLine($this->logFile, "=> " . $key . ": " . $value);
	}

	public function ClearCookieFile()
	{
		if($this->cookie_file != "")
			IO::Delete($this->cookie_file);

		$this->cookie_file = "";
	}

	public function GetCookieFile()
	{
		if($this->cookie_file == "")
			throw new \Exception("Create cookie first.");

		return $this->cookie_file;
	}

	public function CreateCookieFile()
	{
		if($this->cookie_file == "")
			$this->cookie_file = IO::GetTempFilename();

		return $this->cookie_file;
	}

	public function Upload($url, $path, array $postData = array())
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
		$agent = "Mozilla/5.0 (Windows NT 6.0; rv:21.0) Gecko/20100101 Firefox/21.0";
		curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
		$ret = curl_exec($ch);
		if (curl_errno($ch))
		{
			$this->ParseErrorCodes($ret, $path);

			if ($this->throwErrors)
				MessageBox::ThrowMessage("Error: " . $this->error);

			$ret = "";
		}
		curl_close($ch);
		return $ret;
	}
}

