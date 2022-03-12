<?php

namespace minga\framework\settings;

class LogSettings
{
	public bool $LogErrorsToDisk = true;
	public bool $LogEmailsToDisk = true;

	public bool $LogMemoryPeaks = false;
	public $MemoryPeakMB = 200;
}
