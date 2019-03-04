<?php

namespace minga\framework;

class System
{
	public static function GetServerInfo()
	{
		// vd(php_uname('n'));
				// $host = gethostname();
				// $ip = gethostbyname($host);

		$flags = [];
		// $flags[] = ['flag' => 'n', 'name' => 'Servidor'];
		$flags[] = ['flag' => 's', 'name' => 'Sistema operativo'];
		$flags[] = ['flag' => 'r', 'name' => 'Release'];
		$flags[] = ['flag' => 'v', 'name' => 'Versión'];
		$flags[] = ['flag' => 'm', 'name' => 'Plataforma'];

		$ret = [
			self::GetVersion(),
			self::GetHost(),
		];

		foreach($flags as $flag)
			$ret[] = ['name' => $flag['name'], 'value' => php_uname($flag['flag'])];

		$ret[] = ['name' => 'Arquitectura', 'value' => self::GetArchitecture() . 'bits'];
		$ret[] = ['name' => 'PHP', 'value' => phpversion()];
		$ret[] = ['name' => 'php.ini', 'value' => php_ini_loaded_file()];

		return $ret;
	}

	public static function GetVersion()
	{
		$file = Context::Paths()->GetRoot() . '/version';
		if (file_exists($file))
			$value = trim(file_get_contents($file)) . ' (' . date('Y-m-d H:i:s', IO::FileMTime($file) - 60 * 60 * 3) . ')';
		else
			$value = 'Version file not found.';

		return [
			'name' => 'Versión',
			'value' => $value,
		];
	}

	public static function GetHost()
	{
		$host = gethostname();
		$ip = gethostbyname($host);

		return [
			'name' => 'Host',
			'value' => $host . ' (' . $ip . ')',
		];
	}

	public static function GetDbInfo()
	{
		$settings = [];
		$settings[] = ['name' => 'Host', 'value' => Context::Settings()->Db()->Host];
		if (Context::Settings()->Db()->Schema != '')
			$settings[] = ['name' => 'Schema', 'value' => Context::Settings()->Db()->Schema];
		$settings[] = ['name' => 'Database', 'value' => Context::Settings()->Db()->Name];
		$settings[] = ['name' => 'User', 'value' => Context::Settings()->Db()->User];
		$settings[] = ['name' => 'MySQL Version', 'value' => self::GetMySQLVersion()];
		return $settings;
	}

	public static function GetMySQLVersion()
	{
		$db = new Db();
		return $db->fetchScalar('SELECT @@version;');
	}

	public static function GetArchitecture()
	{
		switch(PHP_INT_SIZE)
		{
			case 4:
				return '32'; //32 bit version of PHP
			case 8:
				return '64'; //64 bit version of PHP
			default:
				throw new ErrorException('PHP_INT_SIZE is '.PHP_INT_SIZE);
		}
	}

	public static function IsOnIIS()
	{
		if(isset($_SERVER['SERVER_SOFTWARE']) == false)
			return false;

		$software = Str::ToLower($_SERVER['SERVER_SOFTWARE']);
		return (strpos($software, 'microsoft-iis') !== false);
	}
}
