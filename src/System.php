<?php

namespace minga\framework;

class System
{
	public static function GetServerInfo()
	{
		$flags = array();
		$flags[] = array('flag' => 'n', 'name'=>"Servidor");
		$flags[] = array('flag' => 's', 'name'=>"Sistema operativo");
		$flags[] = array('flag' => 'r', 'name'=>"Release");
		$flags[] = array('flag' => 'v', 'name'=>"Versión");
		$flags[] = array('flag' => 'm', 'name'=>"Plataforma");
		$ret = array();
		foreach($flags as $flag)
			$ret[] = array('name' => $flag['name'], 'value' => php_uname($flag['flag']));
		$ret[] = array('name' => 'Arquitectura', 'value' => self::GetArchitecture() . "bits");
		$ret[] = array('name' => 'PHP', 'value' => phpversion());
		$ret[] = array('name' => 'php.ini', 'value' => php_ini_loaded_file());
		return $ret;
	}

	public static function GetDbInfo()
	{

		$settings = array();
		$settings [] = array('name' => 'Host', 'value' => Context::Settings()->Db()->Host);
		if (Context::Settings()->Db()->Schema != '')
			$settings [] = array('name' => 'Schema', 'value' => Context::Settings()->Db()->Schema);
		$settings [] = array('name' => 'Database', 'value' => Context::Settings()->Db()->Name);
		$settings [] = array('name' => 'User', 'value' => Context::Settings()->Db()->User);
		$settings [] = array('name' => 'MySQL Version', 'value' => self::GetMySQLVersion());
		return $settings;
	}
	public static function GetMySQLVersion()
	{
		$db = new Db();
		return $db->fetchScalar("SELECT @@version;");
	}
	public static function GetArchitecture()
	{
		switch(PHP_INT_SIZE)
		{
			case 4:
				return "32"; //32 bit version of PHP
			case 8:
				return "64"; //64 bit version of PHP
			default:
				throw new ErrorException('PHP_INT_SIZE is '.PHP_INT_SIZE);
		}
	}

	public static function IsOnIIS()
	{
		if(isset($_SERVER['SERVER_SOFTWARE']) == false)
			return false;

		$software = Str::ToLower($_SERVER['SERVER_SOFTWARE']);
		if (strpos($software, "microsoft-iis") !== false)
			return true;
		else
			return false;
	}
}
