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
		return $ret;
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
				throw new \Exception('PHP_INT_SIZE is '.PHP_INT_SIZE);
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
