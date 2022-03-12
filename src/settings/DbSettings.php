<?php

namespace minga\framework\settings;

class DbSettings
{
	//Datos DB
	public bool $NoDb = false;
	public $Name = '';
	public $User = '';
	public $Password = '';
	public $Host = '';
	public $Port = 3306;
	public $Engine = 'mysql'; //opciones posibles 'mysql' o 'sphinx'
	public ?string $RemoteUrl = null;

	public int $FullTextMinWordLength = 4;

	public $SpecialWords = [];

	public bool $ForceStrictTables = false;
	public bool $ForceOnlyFullGroupBy = false;

	public bool $SetTimeZone = true;

	public function SetDatabase($host, $dbName, $user, $password, $port = 3306) : void
	{
		$this->Name = $dbName;
		$this->User = $user;
		$this->Password = $password;
		$this->Host = $host;
		$this->Port = $port;
	}
}
