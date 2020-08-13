<?php

namespace minga\framework;

class MingaException extends \Exception
{
	private $innerException;

	function __construct($message = "", $innerException = null)
	{
		$this->innerException = $innerException;
		parent::__construct($message);
	}

	// custom string representation of object
	public function __toString()
	{
		return __CLASS__ . ': [' . $this->code . ']: ' . $this->message . "\n";
	}

	public function getPublicMessage()
	{
		return '[ME-E]: ' . $this->getMessage();
	}

	public function getInnerException()
	{
		return $this->innerException;
	}
}
