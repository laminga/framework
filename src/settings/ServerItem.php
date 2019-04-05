<?php

namespace minga\framework\settings;

class ServerItem
{
	public $name;
	public $type;
	public $publicUrl;

	public function __construct($name, $type, $publicUrl)
	{
		$this->name = $name;
		$this->type = $type;
		$this->publicUrl = $publicUrl;
	}
}
