<?php

namespace minga\framework\settings;

use minga\framework\Context;
use minga\framework\ErrorException;

class ServersSettings
{
	/** @var ServerItem[] */
	private array $servers = [];
	private ?string $currentServer = null;

	private ServerItem $currentServerObj;
	private ServerItem $mainServerObj;

	public $RemoteLoginWhiteList = [];

	public ?string $Python27 = null;
	public ?string $Python3 = null;
	public string $PhpCli = 'php';

	public ?int $LoopLocalPort = null;
	public string $LoopLocalHost = 'localhost';
	public string $LoopLocalScheme = 'http';

	public function RegisterServer(string $name, string $url, bool $isCDN = false) : void
	{
		$type = ($isCDN ? 'cdns' : 'main');

		$server = new ServerItem($name, $type, $url);
		if ($type == 'main')
			$this->mainServerObj = $server;
		$this->servers[$name] = $server;
	}

	public function RegisterCDNServer(string $name, string $url) : void
	{
		$this->RegisterServer($name, $url, true);
	}

	public function SetCurrentServer(string $name) : void
	{
		$this->currentServer = $name;
	}

	public function CurrentIsMain() : bool
	{
		$server = $this->Current();
		return $server->type == 'main';
	}

	public function Current() : ServerItem
	{
		if (isset($this->currentServerObj) == false)
			$this->currentServerObj = $this->ResolveCurrentServer();

		return $this->currentServerObj;
	}

	public function RegisterServers($appUrl, $homeUrl = null) : void
	{
		if ($homeUrl == false)
			$homeUrl = $appUrl;
		$this->RegisterServer('home', $homeUrl);
		$this->RegisterServer('app', $appUrl);
		$this->SetCurrentServer('app');
	}

	private function ResolveCurrentServer() : ServerItem
	{
		if (isset($this->currentServer) == false)
		{
			if (count($this->servers) > 1)
				throw new ErrorException(Context::Trans('Hay varios servidores en el archivo de configuración pero ninguno especificado como el actual. Llamando Context::Settings()->Servers()->SetCurrentServer(name) se configura uno.'));
			if (count($this->servers) == 0)
				throw new ErrorException(Context::Trans('No hay servidores en el archivo de configuración.'));
			$keys = array_keys($this->servers);
			return $this->servers[$keys[0]];
		}

		if (isset($this->servers[$this->currentServer]) == false)
		{
			throw new ErrorException(Context::Trans('"{server}" está especificado como servidor actual pero no hay un servidor registrado con ese nombre en la configuración.'), ['{server}' => $this->currentServer]);
		}

		return $this->servers[$this->currentServer];
	}

	public function OnlyCDNs() : bool
	{
		foreach($this->servers as $value)
		{
			if ($value->type != 'cdns')
				return false;
		}
		return true;
	}

	public function GetCDNServers() : array
	{
		$ret = [];
		foreach($this->servers as $key => $value)
		{
			if ($value->type == 'cdns')
				$ret[$key] = $value;
		}
		return $ret;
	}

	public function GetServers() : array
	{
		return $this->servers;
	}

	public function GetContentServerUris() : array
	{
		// Trae el
		$cdns = $this->GetCDNServers();
		$servers = [];
		foreach($cdns as $value)
			$servers[] = $value->publicUrl;
		if (count($servers) == 0)
			$servers = [$this->Current()->publicUrl];
		return $servers;
	}

	public function GetServer(string $name) : ServerItem
	{
		if(isset($this->servers[$name]))
			return $this->servers[$name];
		return $this->Current();
	}

	public function Home() : ServerItem
	{
		foreach($this->servers as $value)
		{
			if ($value->name == 'home')
				return $value;
		}
		return $this->Main();
	}

	public function Main() : ServerItem
	{
		if (isset($this->mainServerObj) == false)
			return $this->Current();
			//throw new ErrorException('No main server is set in configuration settings.');
		return $this->mainServerObj;
	}
}
