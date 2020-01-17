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
	public $Schema = '';

	public $ForceOnlyFullGroupBy = false;

	public function SetDatabase($host, $dbName, $user, $password)
	{
		$this->Name = $dbName;
		$this->User = $user;
		$this->Password = $password;
		$this->Host = $host;
	}
}
