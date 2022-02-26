<?php

namespace minga\framework\settings;

class ServerItem
{
	/** @var string */
	public $name;
	/** @var string */
	public $type;
	/** @var string */
	public $publicUrl;

	public function __construct(string $name, string $type, string $publicUrl)
	{
		$this->name = $name;
		$this->type = $type;
		$this->publicUrl = $publicUrl;
	}
}
