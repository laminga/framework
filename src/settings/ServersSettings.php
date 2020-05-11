<?php

namespace minga\framework\settings;

use minga\framework\ErrorException;

class ServersSettings
{
	private $servers = [];
	private $currentServer = null;

	private $currentServerObj = null;
	private $mainServerObj = null;

	public $RemoteLoginWhiteList = [];

	public $Python27 = null;
	public $PhpCli = 'php';

	public function RegisterServer($name, $url, $isCDN = false)
	{
		$type = ($isCDN ? 'cdns' : 'main');

		$server = new ServerItem($name, $type, $url);
		if ($type == 'main')
			$this->mainServerObj = $server;
		$this->servers[$name] = $server;
	}

	public function RegisterCDNServer($name, $url)
	{
		$this->RegisterServer($name, $url, true);
	}

	public function SetCurrentServer($name)
	{
		$this->currentServer = $name;
	}

	public function CurrentIsMain()
	{
		$server = $this->Current();
		return $server->type == 'main';
	}

	public function Current()
	{
		if ($this->currentServerObj == null)
			$this->currentServerObj = $this->ResolveCurrentServer();

		return $this->currentServerObj;
	}
	public function RegisterServers($appUrl, $homeUrl = null)
	{
		if (!$homeUrl) $homeUrl = $appUrl;
		$this->RegisterServer('home', $homeUrl);
		$this->RegisterServer('app', $appUrl);
		$this->SetCurrentServer('app');
	}

	private function ResolveCurrentServer()
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

	
	public function OnlyCDNs()
	{
		foreach($this->servers as $key => $value)
		{
			if ($value->type != 'cdns')
				return false;
		}
		return true;
	}

	public function GetCDNServers()
	{
		$ret = [];
		foreach($this->servers as $key => $value)
		{
			if ($value->type == 'cdns')
				$ret[$key] = $value;
		}
		return $ret;
	}

	public function GetServers()
	{
		return $this->servers;
	}

	public function GetContentServerUris()
	{
		// Trae el
		$cdns = $this->GetCDNServers();
		$svrs = [];
		foreach($cdns as $key => $value)
			$svrs[] = $value->publicUrl;
		if (sizeof($svrs) == 0)
			$svrs = $this->Current()->publicUrl;
		return $svrs;
	}

	public function GetServer($name)
	{
		if(isset($this->servers[$name]))
			return $this->servers[$name];
		else
			return $this->Current();
	}

	public function Main()
	{
		if ($this->mainServerObj == null)
			throw new ErrorException('No main server is set in configuration settings.');
		else
			return $this->mainServerObj;
	}
}
