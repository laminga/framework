<?php

namespace minga\framework\settings;

use minga\framework\PhpSession;

class DebugSettings
{
	// Refleja la configuración activa de debug:
	public bool $debug = false;
	public bool $showErrors = false;
	public bool $profiling = false;
	// Guardan registro de la configuración de debug:
	public bool $settingsDebug = false;
	public bool $sessionDebug = false;

	// Settings para reemplazar una url remota por una local de debug:
	public bool $useDebugUrl = false;
	public string $debugUrl = '';

	public function LoadSessionDebugging() : void
	{
		$this->sessionDebug = PhpSession::GetSessionValue("debugging", false);
	}

	public function SetSessionDebugging(bool $value) : void
	{
		$this->sessionDebug = $value;
		PhpSession::SetSessionValue("debugging", $value);
	}
}
