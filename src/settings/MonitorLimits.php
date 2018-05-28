<?php


namespace minga\framework\settings;

class MonitorLimits
{
	// Cuando este límite se excede, se deja de responder a la IP durante el día
	// y se avisa por mail del incidente
	public $MaximumDaylyHitsPerIP = 10000;
	public $DefensiveModeThresholdDaylyHits = 30000;
	public $DefensiveModeMaximumDaylyHitsPerIP = 500;

	public $MaximumMobileDaylyHitsPerIP = 10000;
	public $DefensiveModeMaximumMobileDaylyHitsPerIP = 250;

	public $LogAgentThresholdDaylyHits = 100;

	// Cuando estos límites se exceden, se envía un mail de alerta
	public $WarningDaylyHitsPerIP = 5000;
	public $WarningDaylyHits = 20000;
	public $WarningDaylyExecuteMinutes = 150;
	public $WarningDaylyLockMinutes = 10;
}


