<?php

namespace minga\framework;

class MingaException extends \Exception
{
	private ?\Exception $innerException;

	public function __construct(string $message = "", ?\Exception $innerException = null)
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

	public function getInnerException() : ?\Exception
	{
		return $this->innerException;
	}
}
