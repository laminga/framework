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

	/** @deprecated No se usa, quitar de settings */
	public int $MaximumMobileDaylyHitsPerIP = 10000;
	/** @deprecated No se usa, quitar de settings */
	public int $DefensiveModeMaximumMobileDaylyHitsPerIP = 500;

	public int $LogAgentThresholdDaylyHits = 100;

	// Cuando se excede, devuelve página con mensaje
	public int $LimitMonthlyMapsPerKey = 27500;

	// Cuando estos límites se exceden, se envía un mail de alerta
	public int $WarningDaylyHitsPerIP = 7500;
	public int $WarningDaylyHits = 30000;
	public int $WarningDaylyExecuteMinutes = 150;
	public int $WarningDaylyLockMinutes = 10;
	public int $WarningDaylyErrors = 50;
	public int $WarningMonthlyEmails = 11000;
	public int $LimitMonthlyEmails = 11997;


	public int $WarningMonthlyMapsPerKey = 20000;

	public $WarningRequestSeconds = 30;
	public array $ExcludeIps = ['127.0.0.1'];

	// Verifica diariamente el espacio libre
	public int $WarningMinimumFreeStorageSpaceMB = 1000;
	public int $WarningMinimumFreeSystemSpaceMB = 1000;
}
