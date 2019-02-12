<?php

namespace minga\framework;

class MingaException extends \Exception
{
	// custom string representation of object
	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
	public function getPublicMessage()
	{
		return '[ME-E]: ' . $this->getMessage();
	}
}
