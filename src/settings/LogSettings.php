<?php

namespace minga\framework\settings;

class LogSettings
{
	public $LogErrorsToDisk = true;
	public $LogEmailsToDisk = true;

	public $LogMemoryPeaks = false;
	public $MemoryPeakMB = 200;
}
