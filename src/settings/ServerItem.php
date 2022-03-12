<?php

namespace minga\framework\settings;

class ServerItem
{
	public string $name;
	public string $type;
	public string $publicUrl;

	public function __construct(string $name, string $type, string $publicUrl)
	{
		$this->name = $name;
		$this->type = $type;
		$this->publicUrl = $publicUrl;
	}
}
