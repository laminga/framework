<?php

namespace minga\framework\settings;

class DbSettings
{
	//Datos DB
	/** @var bool */
	public $NoDb = false;
	public $Name = '';
	public $User = '';
	public $Password = '';
	public $Host = '';
	public $Port = 3306;
	public $Engine = 'mysql'; //opciones posibles 'mysql' o 'sphinx'
	/** @var ?string */
	public $RemoteUrl = null;

	/** @var int */
	public $FullTextMinWordLength = 4;

	public $SpecialWords = [];

	/** @var bool */
	public $ForceStrictTables = false;
	/** @var bool */
	public $ForceOnlyFullGroupBy = false;

	/** @var bool */
	public $SetTimeZone = true;

	public function SetDatabase($host, $dbName, $user, $password, $port = 3306) : void
	{
		$this->Name = $dbName;
		$this->User = $user;
		$this->Password = $password;
		$this->Host = $host;
		$this->Port = $port;
	}
}
