<?php


namespace minga\framework\settings;

class ServerItem
{
	public $name;
	public $type;
	public $publicUrl;
	public $publicSecureUrl;

	public function __construct($name, $type, $publicUrl, $publicSecureUrl)
	{
		$this->name = $name;
		$this->type = $type;
		$this->publicUrl = $publicUrl;
		$this->publicSecureUrl = $publicSecureUrl;
		}
}
