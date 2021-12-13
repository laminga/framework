<?php

namespace minga\framework\settings;

use minga\framework\ErrorException;

class ServersSettings
{
	/** @var ServerItem[] */
	private $servers = [];
	private $currentServer = null;

	private $currentServerObj = null;
	private $mainServerObj = null;

	public $RemoteLoginWhiteList = [];

	public $Python27 = null;
	public $Python3 = null;
	public $PhpCli = 'php';

	public $LoopLocalPort = null;
	public $LoopLocalHost = 'localhost';
	public $LoopLocalScheme = 'http';

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
		if ($this->currentServerObj == null)
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
		if ($this->currentServer == null)
		{
			if (count($this->servers) > 1)
				throw new ErrorException('Many servers are set in configuration but no current server is specificied. Call Context::Settings()->Servers()->SetCurrentServer(name) to set one.');
			if (count($this->servers) == 0)
				throw new ErrorException('No servers are set in configuration file.');
			$keys = array_keys($this->servers);
			return $this->servers[$keys[0]];
		}

		if (array_key_exists($this->currentServer, $this->servers) == false)
		{
			throw new ErrorException('"'. $this->currentServer
				. '" is specified as current server but no server with such name is registered in the configuration settings .');
		}

		return $this->servers[$this->currentServer];
	}

	public function OnlyCDNs() : bool
	{
		foreach($this->servers as $key => $value)
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
		foreach($cdns as $key => $value)
			$servers[] = $value->publicUrl;
		if (count($servers) == 0)
			$servers = $this->Current()->publicUrl;
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
		foreach($this->servers as $key => $value)
		{
			if ($value->name == 'home')
				return $value;
		}
		return $this->Main();
	}

	public function Main() : ServerItem
	{
		if ($this->mainServerObj == null)
			return $this->Current();
			//throw new ErrorException('No main server is set in configuration settings.');
		return $this->mainServerObj;
	}
}
