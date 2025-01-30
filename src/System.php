<?php

namespace minga\framework;

class System
{
	public static function GetServerInfo()
	{
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
		$ret[] = ['name' => 'PHP', 'value' => PHP_VERSION];
		$ret[] = ['name' => 'php.ini', 'value' => php_ini_loaded_file()];

		return $ret;
	}

	public static function GetVersion() : array
	{
		$file = Context::Paths()->GetRoot() . '/version';
		if (file_exists($file))
		{
			$time = IO::FileMTime($file);
			$value = trim(file_get_contents($file)) . ' (' . date('Y-m-d H:i:s', $time - 60 * 60 * 3) . ')';
		}
		else
			$value = 'Archivo de versión no encontrado.';

		return [
			'name' => 'Versión',
			'value' => $value,
		];
	}

	public static function GetHost() : array
	{
		$host = gethostname();
		$ip = gethostbyname($host);

		return [
			'name' => 'Host',
			'value' => $host . ' (' . $ip . ')',
		];
	}

	public static function GetDiskInfoBytes() : array
	{
		$root = Context::Paths()->GetStorageRoot();
		$storage = disk_free_space($root);
		$tmp = sys_get_temp_dir();
		$system = disk_free_space($tmp);
		return ['Storage' => $storage, 'System' => $system];
	}

	public static function GetDiskInfo() : array
	{
		$root = Context::Paths()->GetStorageRoot();
		$storage = disk_free_space($root);
		$storageFormatted = round($storage / 1024 / 1024, 1) . "MB";
		$tmp = sys_get_temp_dir();
		$system = disk_free_space($tmp);
		$systemFormatted = round($system / 1024 / 1024, 1) . "MB";

		return [
			['name' => 'Storage location', 'value' => $root],
			['name' => 'Storage free space', 'value' => $storageFormatted, 'valueNumeric' => $storage],
			['name' => 'System location', 'value' => $tmp],
			['name' => 'System free space', 'value' => $systemFormatted, 'valueNumeric' => $system],
		];
	}

	public static function GetDbInfo() : array
	{
		return [
			['name' => 'Host', 'value' => Context::Settings()->Db()->Host],
			['name' => 'Database', 'value' => Context::Settings()->Db()->Name],
			['name' => 'User', 'value' => Context::Settings()->Db()->User],
			['name' => 'MySQL Version', 'value' => self::GetMySQLVersion()],
			['name' => 'Ping', 'value' => self::GetPingTimeMs() . " ms"],
		];
	}

	public static function GetPingTimeMs() : float
	{
		if (Context::Settings()->Db()->NoDbConnection())
			return -1;

		$start = microtime(true);
		$db = new Db();
		$db->fetchScalar('SELECT @@version;');
		$time_elapsed_secs = microtime(true) - $start;
		return round($time_elapsed_secs * 1000, 2);
	}

	public static function GetMySQLVersion() : string
	{
		if (Context::Settings()->Db()->NoDbConnection())
			return '-';
		$db = new Db();
		return $db->fetchScalar('SELECT @@version;');
	}

	public static function GetArchitecture() : string
	{
		switch(PHP_INT_SIZE)
		{
			case 4:
				return '32'; //32 bit version of PHP
			case 8:
				return '64'; //64 bit version of PHP
			default:
				throw new ErrorException('PHP_INT_SIZE es ' . PHP_INT_SIZE);
		}
	}

	public static function IsWindows() : bool
	{
		return Str::StartsWithI(PHP_OS, 'win');
	}

	public static function IsCli() : bool
	{
		return PHP_SAPI == 'cli';
	}

	public static function IsTestingInWindows() : bool
	{
		return Context::Settings()->isTesting
			&& self::IsWindows();
	}

	public static function IsOnIIS() : bool
	{
		if(isset($_SERVER['SERVER_SOFTWARE']) == false)
			return PHP_OS === "WINNT";
		$software = Str::ToLower($_SERVER['SERVER_SOFTWARE']);
		return strpos($software, 'microsoft-iis') !== false;
	}

	/**
	 * Devuelve true si está dentro de la cantidad
	 * de $days desde el último release.
	 */
	public static function IsNearRelease(int $days = 3, string $file = 'version') : bool
	{
		$file = Context::Paths()->GetRoot() . '/' . $file;
		if (file_exists($file) == false)
			return true;

		$time = IO::FileMTime($file);
		return $time + $days * 60 * 60 * 24 > time();
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
	public static function Execute($command, array $args = [], array &$lines = [], $redirectStdErr = true) : int
	{
		$stdErr = '';
		if($redirectStdErr)
			$stdErr = ' 2>&1';

		$str = '';
		foreach($args as $arg)
			$str .= escapeshellarg($arg) . ' ';

		$val = 0;
		$command = IO::EscapeLongFilename($command);
		exec($command . ' ' . trim($str) . $stdErr, $lines, $val);
		return $val;
	}

	public static function RunCommandGS(string $command, string $args, ?int &$returnCode = null, bool $returnFirstLineOnly = false, bool $checkFile = true) : string
	{
		if ($checkFile && file_exists($command) == false)
			throw new ErrorException('No se encontró el binario: "' . $command . '".');

		if (Str::StartsWith($args, ' ') == false)
			$args = ' ' . $args;

		exec($command . $args, $out, $returnCode);

		if ($returnCode == 126)
			throw new ErrorException('Error de permisos: "' . $command . '".');

		if (count($out) == 0)
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
			'return' => $return,
		];
	}
}
