<?php

namespace minga\framework\settings;

class ServersSettings
{
	private $servers = array();
	private $currentServer = null;

	private $currentServerObj = null;
	private $mainServerObj = null;

	public function RegisterServer($name, $url, $sslUrl = null, $isCDN = false)
	{
		if ($sslUrl == null) $sslUrl = $url;
		$type = ($isCDN ? 'cdns' : 'main');
		$server = new ServerItem($name, $type, $url, $sslUrl);
		if ($type == 'main')
			$this->mainServerObj = $server;
		$this->servers[$name] = $server;
	}
	public function RegisterCDNServer($name, $url, $sslUrl = null)
	{
		$this->RegisterServer($name, $url, $sslUrl, true);
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

	private function ResolveCurrentServer()
	{
		if ($this->currentServer == null)
		{
			if (sizeof($this->servers) > 1)
				throw new \Exception("Many servers are set in configuration but no current server is specificied. Call Context::Settings()->Servers()->SetCurrentServer(name) to set one.");
			if (sizeof($this->servers) == 0)
				throw new \Exception("No servers are set in configuration file.");
			$keys = array_keys($this->servers);
			return $this->servers[$keys[0]];
		}

		if (array_key_exists($this->currentServer, $this->servers) == false)
		{
			throw new \Exception("'" . $this->currentServer . "' is specified as current server but no server with such name is registered in the configuration settings .");
		}

		return $this->servers[$this->currentServer];
	}

	public function GetServers()
	{
		return $this->servers;
	}

	public function GetServer($name)
	{
		if(isset($this->servers[$name]))
			return $this->servers[$name];
		else
			return $this->servers['core'];
	}

	public function Main()
	{
		if ($this->mainServerObj == null)
			throw new \Exception('No main server is set in configuration settings.');
		else
			return $this->mainServerObj;
	}
}
