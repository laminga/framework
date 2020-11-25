<?php

namespace minga\framework\settings;

class MonitorLimits
{
	/**
	 * Cuando este límite se excede, se deja de responder a la
	 * IP durante el día y se avisa por mail del incidente.
	 *
	 * @var int */
	public $MaximumDaylyHitsPerIP = 10000;

	/** @var int */
	public $DefensiveModeThresholdDaylyHits = 30000;
	/** @var int */
	public $DefensiveModeMaximumDaylyHitsPerIP = 500;

	/** @var int */
	public $MaximumMobileDaylyHitsPerIP = 10000;
	/** @var int */
	public $DefensiveModeMaximumMobileDaylyHitsPerIP = 250;

	/** @var int */
	public $LogAgentThresholdDaylyHits = 100;

	// Cuando estos límites se exceden, se envía un mail de alerta
	/** @var int */
	public $WarningDaylyHitsPerIP = 7500;
	/** @var int */
	public $WarningDaylyHits = 30000;
	/** @var int */
	public $WarningDaylyExecuteMinutes = 150;
	/** @var int */
	public $WarningDaylyLockMinutes = 10;

	/** @var array */
	public $ExcludeIps = ['127.0.0.1'];
}


