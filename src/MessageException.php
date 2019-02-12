<?php


namespace minga\framework;

class MessageException extends \Exception
{
	// custom string representation of object
	public function __toString()
	{
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}
