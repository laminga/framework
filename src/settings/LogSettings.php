<?php

namespace minga\framework\settings;

class LogSettings
{
	/** @var bool */
	public $LogErrorsToDisk = true;
	/** @var bool */
	public $LogEmailsToDisk = true;

	/** @var bool */
	public $LogMemoryPeaks = false;
	public $MemoryPeakMB = 200;
}
