<?php
namespace minga\framework;

class WebResponse
{
	public $http_code;
	public $error;
	public $file;
	public $content_type;
	public $uri;
	public $success;
	public $headers = array();
	
	public function dump()
	{
		$this->echoLine('http_code', $this->http_code);
		$this->echoLine('content_type', $this->content_type);
		$this->echoLine('file', $this->file);
		$this->echoLine('uri', $this->uri);
		$this->echoLine('error', $this->error);	
		echo ('headers:');	
		print_r($this->headers);
	}

	private function echoLine($key, $value)
	{
		echo($key . ': ' . $value . '<br>');
	}
}

