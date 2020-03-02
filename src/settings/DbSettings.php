<?php


namespace minga\framework\settings;

class DbSettings
{
	//Datos DB
	public $NoDb = false;
	public $Name = '';
	public $User = '';
	public $Password = '';
	public $Host = '';
	public $Port = 3306;
	public $Engine = 'mysql'; //opciones posibles 'mysql' o 'sphinx'
	public $RemoteUrl = null;

	public $ForceOnlyFullGroupBy = false;

	public function SetDatabase($host, $dbName, $user, $password, $port = 3306)
	{
		$this->Name = $dbName;
		$this->User = $user;
		$this->Password = $password;
		$this->Host = $host;
		$this->Port = $port;
	}
}
