<?php

namespace minga\framework\settings;

use minga\framework\Context;

class Settings
{
	// Fields para subclases
	private MailSettings $mail;
	private LogSettings $log;
	private PerformanceSettings $performance;
	private OauthSettings $oauth;
	private MonitorLimits $limits;
	private DbSettings $db;
	private QueueSettings $queue;
	private DebugSettings $debug;
	private CacheSettings $cache;
	private ServersSettings $servers;
	private KeysSettings $keys;
	private NotificationSettings $notifications;

	// UI Settings
	public string $entorno = 'dev';
	public string $environment = 'dev';

	public bool $updateGoogleBingSitemap = false;
	public bool $readonlyForMaintenance = false;

	public string $applicationName = 'AppName';
	public string $currentCountry = 'Argentina';

	public bool $useVendor = false;
	public bool $allowExport = true;
	public bool $allowExportDoc = false;

	public bool $showNewUploading = false;
	public bool $showMoveUrl = false;

	public string $forceIfModifiedReload = '31/1/1980';
	public bool $useOldInstitutions = true;
	public bool $useOldProjects = true;

	public bool $useProjects = false;
	public bool $useEvents = true;
	public bool $useProfiles = true;

	// Deprecated en AA
	public bool $skipExternalLibraries = false;

	public bool $useCDN = true;
	public bool $useOpenId = false;
	public bool $useOpenIdFacebook = false;
	public bool $useOpenIdGoogle = false;

	public bool $normalizeNames = false;
	public bool $setupInstallOnly = true;

	public bool $decryptPdfs = true;

	public string $catalog = '';

	// Caching
	public bool $useCoverPageCache = true;
	public bool $useOaiCache = true;
	public string $converterURL = "";
	public string $converterKey = "";

	// globales que se setean en startup.php
	public ?string $rootPath = null;
	public bool $isTesting = false;
	public bool $isExporting = false;
	public bool $isEmbedded = false;
	public bool $noRobots = false;
	public bool $isEmbeddedList = false;
	public bool $isEmbeddedMembers = false;
	public bool $isBoxed = false;
	public bool $isBoxValid = false;

	public ?string $boxingContent;
	public bool $isFramed = false;

	public $timerStart = '';

	public bool $isAPIEnabled = false;

	public bool $allowPHPsession = true;
	public bool $allowCrossSiteSessionCookie = false;
	public bool $allowPHPSessionCacheResults = false;

	public $storagePath = '';

	// ARKS
	public ?string $arkNAAN = null;
	public string $arkGlobalServer = '';
	public bool $useArks = true;

	// Proveedores externos
	public bool $useLaReferencia = true;
	public bool $useScielo = true;

	// Mirrors
	public string $publicCacheURL = '';

	// otras globales
	public string $section = '';
	public string $supportMail = '';

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
		if(isset($this->keys) == false)
			$this->keys = new KeysSettings();

		return $this->keys;
	}

	public function Mail() : MailSettings
	{
		if (isset($this->mail) == false)
			$this->mail = new MailSettings();

		return $this->mail;
	}

	public function Queue() : QueueSettings
	{
		if (isset($this->queue) == false)
			$this->queue = new QueueSettings();

		return $this->queue;
	}

	public function Db() : DbSettings
	{
		if (isset($this->db) == false)
			$this->db = new DbSettings();

		return $this->db;
	}

	public function Oauth() : OauthSettings
	{
		if (isset($this->oauth) == false)
			$this->oauth = new OauthSettings();

		return $this->oauth;
	}

	public function Cache() : CacheSettings
	{
		if (isset($this->cache) == false)
			$this->cache = new CacheSettings();

		return $this->cache;
	}

	public function Performance() : PerformanceSettings
	{
		if (isset($this->performance) == false)
			$this->performance = new PerformanceSettings();

		return $this->performance;
	}

	public function Log() : LogSettings
	{
		if (isset($this->log) == false)
			$this->log = new LogSettings();

		return $this->log;
	}

	public function Notifications() : NotificationSettings
	{
		if (isset($this->notifications) == false)
			$this->notifications = new NotificationSettings();

		return $this->notifications;
	}

	public function Debug() : DebugSettings
	{
		if (isset($this->debug) == false)
			$this->debug = new DebugSettings();

		return $this->debug;
	}

	public function Servers() : ServersSettings
	{
		if (isset($this->servers) == false)
			$this->servers = new ServersSettings();

		return $this->servers;
	}

	public function Limits() : MonitorLimits
	{
		if (isset($this->limits) == false)
			$this->limits = new MonitorLimits();

		return $this->limits;
	}

	public function GetSupportMail() : string
	{
		if($this->supportMail != '')
			return $this->supportMail;
		return 'contacto@' . str_replace('www.', '', parse_url($this->GetMainServerPublicUrl(), PHP_URL_HOST));
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
