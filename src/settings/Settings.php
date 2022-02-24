<?php

namespace minga\framework\settings;

use minga\framework\Context;

class Settings
{
	// Fields para subclases
	/** @var MailSettings */
	private $mail = null;
	/** @var LogSettings */
	private $log = null;
	/** @var PerformanceSettings */
	private $performance = null;
	/** @var OauthSettings */
	private $oauth = null;
	/** @var MonitorLimits */
	private $limits = null;
	/** @var DbSettings */
	private $db = null;
	/** @var QueueSettings */
	private $queue = null;
	/** @var DebugSettings */
	private $debug = null;
	/** @var CacheSettings */
	private $cache = null;
	/** @var ServersSettings */
	private $servers = null;
	/** @var KeysSettings */
	private $keys = null;
	/** @var NotificationSettings */
	private $notifications = null;

	// UI Settings
	/** @var string */
	public $entorno = '';

	/** @var bool */
	public $updateGoogleBingSitemap = false;
	/** @var bool */
	public $readonlyForMaintenance = false;

	/** @var string */
	public $applicationName = 'AppName';
	/** @var string */
	public $currentCountry = 'Argentina';

	/** @var bool */
	public $useVendor = false;
	/** @var bool */
	public $allowExport = true;
	/** @var bool */
	public $allowExportDoc = false;

	/** @var bool */
	public $showNewUploading = false;
	/** @var bool */
	public $showMoveUrl = false;

	/** @var string */
	public $forceIfModifiedReload = '31/1/1980';
	/** @var bool */
	public $useOldInstitutions = true;
	/** @var bool */
	public $useOldProjects = true;

	/** @var bool */
	public $useProjects = false;
	/** @var bool */
	public $useEvents = true;
	/** @var bool */
	public $useProfiles = true;

	// Deprecated en AA
	/** @var bool */
	public $skipExternalLibraries = false;

	/** @var bool */
	public $useCDN = true;
	/** @var bool */
	public $useOpenId = false;
	/** @var bool */
	public $useOpenIdFacebook = false;
	/** @var bool */
	public $useOpenIdGoogle = false;

	/** @var bool */
	public $normalizeNames = false;
	/** @var bool */
	public $setupInstallOnly = true;

	/** @var bool */
	public $decryptPdfs = true;

	/** @var string */
	public $catalog = '';

	// Caching
	/** @var bool */
	public $useCoverPageCache = true;
	/** @var bool */
	public $useOaiCache = true;
	/** @var string */
	public $converterURL = "";
	/** @var string */
	public $converterKey = "";

	// globales que se setean en startup.php
	/** @var ?string */
	public $rootPath = null;
	/** @var bool */
	public $isTesting = false;
	/** @var bool */
	public $isExporting = false;
	/** @var bool */
	public $isEmbedded = false;
	/** @var bool */
	public $noRobots = false;
	/** @var bool */
	public $isEmbeddedList = false;
	/** @var bool */
	public $isEmbeddedMembers = false;
	/** @var bool */
	public $isBoxed = false;
	/** @var bool */
	public $isBoxValid = false;

	public $boxingContent;
	/** @var bool */
	public $isFramed = false;

	public $timerStart = '';

	/** @var bool */
	public $isAPIEnabled = false;

	/** @var bool */
	public $allowPHPsession = true;
	/** @var bool */
	public $allowCrossSiteSessionCookie = false;

	public $storagePath = '';

	// ARKS
	/** @var ?string */
	public $arkNAAN = null;
	/** @var string */
	public $arkGlobalServer = '';
	/** @var bool */
	public $useArks = true;

	// Mirrors
	/** @var string */
	public $publicCacheURL = '';

	// otras globales
	/** @var string */
	public $section = '';

	public function Initialize(string $rootPath) : void
	{
		$this->catalog = 'mySql';
		$this->rootPath = $rootPath;
		$this->storagePath = $rootPath . '/storage';
	}

	public function HasSSL() : bool
	{
		$scheme = parse_url(Context::Settings()->GetMainServerPublicUrl(), PHP_URL_SCHEME);
		return $scheme == "https";
	}

	public function Keys() : KeysSettings
	{
		if($this->keys == null)
			$this->keys = new KeysSettings();

		return $this->keys;
	}

	public function Mail() : MailSettings
	{
		if ($this->mail == null)
			$this->mail = new MailSettings();

		return $this->mail;
	}

	public function Queue() : QueueSettings
	{
		if ($this->queue == null)
			$this->queue = new QueueSettings();

		return $this->queue;
	}

	public function Db() : DbSettings
	{
		if ($this->db == null)
			$this->db = new DbSettings();

		return $this->db;
	}

	public function Oauth() : OauthSettings
	{
		if ($this->oauth == null)
			$this->oauth = new OauthSettings();

		return $this->oauth;
	}

	public function Cache() : CacheSettings
	{
		if ($this->cache == null)
			$this->cache = new CacheSettings();

		return $this->cache;
	}

	public function Performance() : PerformanceSettings
	{
		if ($this->performance == null)
			$this->performance = new PerformanceSettings();

		return $this->performance;
	}

	public function Log() : LogSettings
	{
		if ($this->log == null)
			$this->log = new LogSettings();

		return $this->log;
	}

	public function Notifications() : NotificationSettings
	{
		if ($this->notifications == null)
			$this->notifications = new NotificationSettings();

		return $this->notifications;
	}

	public function Debug() : DebugSettings
	{
		if ($this->debug == null)
			$this->debug = new DebugSettings();

		return $this->debug;
	}

	public function Servers() : ServersSettings
	{
		if ($this->servers == null)
			$this->servers = new ServersSettings();

		return $this->servers;
	}

	public function Limits() : MonitorLimits
	{
		if ($this->limits == null)
			$this->limits = new MonitorLimits();

		return $this->limits;
	}

	public function GetPublicUrl() : string
	{
		$server = $this->Servers()->Current();
		return $server->publicUrl;
	}

	public function GetMainServerPublicUrl() : string
	{
		$server = $this->Servers()->Main();
		return $server->publicUrl;
	}

	public function GetHomePublicUrl() : string
	{
		$server = $this->Servers()->Home();
		return $server->publicUrl;
	}
}
