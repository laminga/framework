<?php

namespace minga\framework;

class WebResponse
{
	public int $httpCode;
	public $error;
	public $file;
	public $contentType;
	public $uri;
	public bool $success;
	public array $headers = [];

	public function dump() : void
	{
		$this->echoLine('http_code', $this->httpCode);
		$this->echoLine('content_type', $this->contentType);
		$this->echoLine('file', $this->file);
		$this->echoLine('uri', $this->uri);
		$this->echoLine('error', $this->error);
		echo 'headers:';
		print_r($this->headers);
	}

	public function GetString()
	{
		if ($this->file == false)
			throw new MessageException(Context::Trans('No se recibió el archivo.'));

		return IO::ReadAllText($this->file);
	}

	private function echoLine($key, $value) : void
	{
		echo $key . ': ' . $value . '<br>';
	}

	public function HasLocationHeader() : bool
	{
		return isset($this->headers['Location'])
			|| isset($this->headers['location']);
	}

	public function GetLocationHeader()
	{
		if(isset($this->headers['Location']))
			return $this->headers['Location'];

		if(isset($this->headers['location']))
			return $this->headers['location'];

		return null;
	}
}

