<?php

namespace minga\framework\settings;

class PathSettings
{
	public string $BackupLocalPath = '/backup';
	public string $BinPath = '/cgi-bin';
	public string $BucketsPath = '/buckets';
	public string $CronJobsPath = '/cron';
	public string $CronJobsScriptPath = '/src/automated/cron';
	public string $DumpLocalPath = '/dump';
	public string $FeedbackPath = '/feedback';
	public string $FrameworkTestDataPath = '/data';
	public string $FrameworkTestsPath = '/tests';
	public string $HtmlPurifierCachePath = '/htmlpurifier';
	public string $LogLocalPath = '/log';
	public string $MemoryPeakPath = '/memory';
	public string $MpdfTempPath = '/mpdftemp';
	public string $PerformanceLocalPath = '/performance';
	public string $QueuePath = '/queue';
	public string $SearchLogLocalPath = '/search';
	public string $StorageCaches = '/caches';
	public string $StorageData = '/data';
	public string $TempPath = '/temp';
	public string $TokensPath = '/tokens';
	public string $TrafficLocalPath = '/traffic';
	public string $TranslationsPath = '/translations';
	public string $TwigCache = '/compilation_cache';
}
