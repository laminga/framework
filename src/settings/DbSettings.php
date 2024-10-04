<?php

namespace minga\framework\settings;

class DbSettings
{
	//Datos DB
	public bool $NoDb = false;
	public string $Name = '';
	public string $User = '';
	public string $Password = '';
	public string $Host = '';
	public int $Port = 3306;
	public string $Engine = 'mysql'; //opciones posibles 'mysql' o 'sphinx'
	public ?string $RemoteUrl = null;

	public string $Charset = 'utf8';

	public int $FullTextMinWordLength = 4;

	public $SpecialWords = [];

	public bool $LogTableUpdateTime = false;

	public bool $ForceStrictTables = false;
	public bool $ForceOnlyFullGroupBy = false;

	public bool $SetTimeZone = true;

	public function NoDbConnection(): bool
	{
		return ($this->NoDb || $this->Host == '' ||
			$this->Name == '' || $this->User == '');
	}

	public function SetDatabase(string $host, string $dbName, string $user, string $password, string $charset = 'utf8', int $port = 3306) : void
	{
		$this->Name = $dbName;
		$this->User = $user;
		$this->Password = $password;
		$this->Host = $host;
		$this->Charset = $charset;
		$this->Port = $port;
	}

	public function GetDriver() : string
	{
		if($this->Engine == 'mysql')
			return 'pdo_mysql';
		return '';
	}
}
