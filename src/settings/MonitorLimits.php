<?php

namespace minga\framework\settings;

class MonitorLimits
{
	/**
	 * Cuando este límite se excede, se deja de responder a la
	 * IP durante el día y se avisa por mail del incidente.
	 */
	public int $MaximumDaylyHitsPerIP = 10000;

	public int $DefensiveModeThresholdDaylyHits = 30000;
	public int $DefensiveModeMaximumDaylyHitsPerIP = 500;

	public int $MaximumMobileDaylyHitsPerIP = 10000;
	public int $DefensiveModeMaximumMobileDaylyHitsPerIP = 250;

	public int $LogAgentThresholdDaylyHits = 100;

	// Cuando estos límites se exceden, se envía un mail de alerta
	public int $WarningDaylyHitsPerIP = 7500;
	public int $WarningDaylyHits = 30000;
	public int $WarningDaylyExecuteMinutes = 150;
	public int $WarningDaylyLockMinutes = 10;

	public $WarningRequestSeconds = 30;
	public array $ExcludeIps = ['127.0.0.1'];
}


