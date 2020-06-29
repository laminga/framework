<?php

namespace minga\framework\settings;

use minga\framework\Context;
use minga\framework\Str;

class Settings
{
	// Fields para subclases
	private $mail = null;
	private $log = null;
	private $performance = null;
	private $oauth = null;
	private $limits = null;
	private $db = null;
	private $queue = null;
	private $debug = null;
	private $cache = null;
	private $servers = null;
	private $keys = null;

	// UI Settings
	public $entorno = '';

	public $updateGoogleBingSitemap = false;
	public $readonlyForMaintenance = false;

	public $applicationName = 'AppName';

	public $useVendor = false;
	public $allowExport = true;
	public $allowExportDoc = false;

	public $showNewUploading = false;
	public $showMoveUrl = false;

	public $forceIfModifiedReload = '31/1/1980';
	public $useOldInstitutions = true;
	public $useOldProjects = true;

	public $useProjects = false;
	public $useGeographies = false;
	public $useEvents = true;
	public $useProfiles = true;

	public $skipExternalLibraries = false;
	public $useOpenId = false;
	public $useOpenIdFacebook = false;
	public $useOpenIdGoogle = false;

	public $normalizeNames = false;
	public $setupInstallOnly = true;

	public $decryptPdfs = true;

	public $catalog = '';

	// Caching
	public $useCoverPageCache = true;
	public $useOaiCache = true;
	public $converterURL = "";
	public $converterKey = "";

	// globales que se setean en startup.php
	public $rootPath = null;
	public $isTesting = false;
	public $isExporting = false;
	public $isEmbedded = false;
	public $noRobots = false;
	public $isEmbeddedList = false;
	public $isEmbeddedMembers = false;
	public $isBoxed = false;
	public $isBoxValid = false;
	public $boxingContent;
	public $isFramed = false;
	public $timerStart = '';

	public $allowPHPsession = true;

	public $storagePath = '';

	// Mirrors
	public $publicCacheURL = '';

	// otras globales
	public $section = '';

	public function Initialize($rootPath)
	{
		$this->catalog = 'mySql';
		$this->rootPath = $rootPath;
		$this->storagePath = $rootPath . '/storage';
	}

	public function HasSSL()
	{
		$scheme = parse_url(Context::Settings()->GetMainServerPublicUrl(), PHP_URL_SCHEME);
		return $scheme == "https";
	}

	public function Keys()
	{
		if($this->keys == null)
			$this->keys = new KeysSettings();

		return $this->keys;
	}

	public function Mail()
	{
		if ($this->mail == null)
			$this->mail = new MailSettings();

		return $this->mail;
	}

	public function Queue()
	{
		if ($this->queue == null)
			$this->queue = new QueueSettings();

		return $this->queue;
	}

	public function Db()
	{
		if ($this->db == null)
			$this->db = new DbSettings();

		return $this->db;
	}

	public function Oauth()
	{
		if ($this->oauth == null)
			$this->oauth = new OauthSettings();

		return $this->oauth;
	}

	public function Cache()
	{
		if ($this->cache == null)
			$this->cache = new CacheSettings();

		return $this->cache;
	}

	public function Performance()
	{
		if ($this->performance == null)
			$this->performance = new PerformanceSettings();

		return $this->performance;
	}

	public function Log()
	{
		if ($this->log == null)
			$this->log = new LogSettings();

		return $this->log;
	}

	public function Debug()
	{
		if ($this->debug == null)
			$this->debug = new DebugSettings();

		return $this->debug;
	}

	public function Servers()
	{
		if ($this->servers == null)
			$this->servers = new ServersSettings();

		return $this->servers;
	}

	public function Limits()
	{
		if ($this->limits == null)
			$this->limits = new MonitorLimits();

		return $this->limits;
	}

	public function GetPublicUrl()
	{
		$server = $this->Servers()->Current();
		return $server->publicUrl;
	}

	public function GetMainServerPublicUrl()
	{
		$server = $this->Servers()->Main();
		return $server->publicUrl;
	}

	public function GetHomePublicUrl()
	{
		$server = $this->Servers()->Home();
		return $server->publicUrl;
	}
}
