<?php

namespace minga\framework;

class WebResponse
{
	public $httpCode;
	public $error;
	public $file;
	public $contentType;
	public $uri;
	public $success;
	public $headers = [];

	public function dump()
	{
		$this->echoLine('http_code', $this->httpCode);
		$this->echoLine('content_type', $this->contentType);
		$this->echoLine('file', $this->file);
		$this->echoLine('uri', $this->uri);
		$this->echoLine('error', $this->error);
		echo ('headers:');
		print_r($this->headers);
	}
	public function GetString()
	{
		if (!$this->file) 
		{
			throw new MessageException("No file has been received.");
		}
		return IO::ReadAllText($this->file);
	}
	private function echoLine($key, $value)
	{
		echo($key . ': ' . $value . '<br>');
	}
}

