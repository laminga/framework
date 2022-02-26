<?php

namespace minga\framework;

class MingaException extends \Exception
{
	private $innerException;

	public function __construct(string $message = "", $innerException = null)
	{
		$this->innerException = $innerException;
		parent::__construct($message);
	}

	public function __toString() : string
	{
		return __CLASS__ . ': [' . $this->code . ']: ' . $this->message . "\n";
	}

	public function getPublicMessage() : string
	{
		return '[ME-E]: ' . $this->getMessage();
	}

	public function getInnerException()
	{
		return $this->innerException;
	}
}
