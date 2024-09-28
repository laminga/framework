<?php

namespace minga\framework\settings;

use minga\framework\PhpSession;

class DebugSettings
{
	// Refleja la configuraci�n activa de debug:
	public bool $debug = false;
	public bool $showErrors = false;
	public bool $profiling = false;
	// Guardan registro de la configuraci�n de debug:
	public bool $settingsDebug = false;
	public bool $sessionDebug = false;

	public function LoadSessionDebugging() : void
	{
		$this->sessionDebug = PhpSession::GetSessionValue("debugging", false);
	}
	public function SetSessionDebugging(bool $value): void
	{
		$this->sessionDebug = $value;
		PhpSession::SetSessionValue("debugging", $value);
	}
}
