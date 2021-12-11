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
		{
			$time = IO::FileMTime($file);
			if($time === false)
				$value = trim(file_get_contents($file)) . ' (no date)';
			else
				$value = trim(file_get_contents($file)) . ' (' . date('Y-m-d H:i:s', $time - 60 * 60 * 3) . ')';
		}
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

	public static function IsWindows()
	{
		return Str::StartsWithI(PHP_OS, 'win');
	}

	public static function IsTestingInWindows()
	{
		return Context::Settings()->isTesting
			&& self::IsWindows();
	}

	public static function IsOnIIS()
	{
		if(isset($_SERVER['SERVER_SOFTWARE']) == false)
			return (PHP_OS === "WINNT");
		$software = Str::ToLower($_SERVER['SERVER_SOFTWARE']);
		return (strpos($software, 'microsoft-iis') !== false);
	}

	//-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	//-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	//TODO: unificar estos cuatro métodos de Execute o RunCommand en uno.
	//-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
	//-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

	/**
	 * Ejecuta un comando en el directorio del
	 * binario, para ello guarda el directorio
	 * inicial, cambia al del ejecutable y
	 * vuelve al directorio inicial.
	 */
	public static function RunCommandOnPath($command, $path = null, $throwOnError = true)
	{
		if($path === null)
			$path = Context::Paths()->GetBinPath();

		$prevDir = getcwd();
		chdir($path);

		if(System::IsWindows())
			$command = Str::RemoveBegining($command, './');

		$lastLine = exec($command, $output, $return);

		if($return !== 0 && $throwOnError)
		{
			if ($return == 126)
				throw new ErrorException('Error de permisos: "' . $command . '".');
			else
				throw new ErrorException('Error RunCommandOnPath: "' . $command
				. '", retval: ' . $return . ', last line: "' . $lastLine . '"');
		}
		chdir($prevDir);
		return $output;
	}

	/**
	 * Execute usado por mapas.
	 */
	public static function Execute($command, array $args = [], array &$lines = [], $redirectStdErr = true)
	{
		$stdErr = '';
		if($redirectStdErr)
			$stdErr = ' 2>&1';

		$str = '';
		foreach($args as $arg)
			$str .= escapeshellarg($arg) . ' ';

		$val = 0;
		exec($command . ' ' . trim($str) . $stdErr, $lines, $val);
		return $val;
	}

	public static function RunCommandGS(string $command, string $args, &$returnCode = null, $returnFirstLineOnly = false, $checkFile = true)
	{
		if ($checkFile && file_exists($command) == false)
			throw new ErrorException('No se encontró el binario: "' . $command. '".');

		if (Str::StartsWith($args, ' ') == false)
			$args = ' ' . $args;

		exec($command . $args, $out, $returnCode);

		if ($returnCode == 126)
			throw new ErrorException('Error de permisos: "' . $command . '".');

		if (is_array($out) == false || count($out) == 0)
			return '';
		else if ($returnFirstLineOnly)
			return $out[0];
		else
			return implode("\n", $out);
	}

	public static function RunCommandRaw(string $command) : array
	{
		$output = [];
		$return = 0;
		$lastLine = exec($command, $output, $return);

		if ($return == 126)
			throw new ErrorException('Error de permisos: "' . $command . '".');

		return [
			'command' => $command,
			'output' => implode("\n", $output),
			'lastLine' => $lastLine,
			'return' => $return
		];
	}

}
