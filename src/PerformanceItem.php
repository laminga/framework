<?php

namespace minga\framework;

/**
 * Encapsula un registro de métricas de Performance (hits, duración, locking, etc.)
 * y resuelve su serialización/deserialización en los tres formatos usados en disco:
 *  - corto:  hits;duration;locked                                  (locks.txt)
 *  - medio:  hits;duration;locked;dbMs;dbHits                      (controller/user usage)
 *  - largo:  hits;duration;locked;google;mails;dbMs;dbHits;extraHits (dayly usage)
 */
class PerformanceItem
{
	public int $hits;
	public int $duration;
	public int $locked;
	public int $google;
	public int $mails;
	public int $dbMs;
	public int $dbHits;
	public array $extraHits;

	public function __construct(int $hits = 0, int $duration = 0, int $locked = 0, int $google = 0,
		int $mails = 0, int $dbMs = 0, int $dbHits = 0, array $extraHits = [])
	{
		$this->hits = $hits;
		$this->duration = $duration;
		$this->locked = $locked;
		$this->google = $google;
		$this->mails = $mails;
		$this->dbMs = $dbMs;
		$this->dbHits = $dbHits;
		$this->extraHits = $extraHits;
	}

	public static function Parse(string $value) : self
	{
		$parts = explode(';', $value);
		$hits = isset($parts[0]) ? (int)$parts[0] : 0;
		$duration = isset($parts[1]) ? (int)$parts[1] : 0;
		$locked = isset($parts[2]) ? (int)$parts[2] : 0;
		$google = isset($parts[3]) ? (int)$parts[3] : 0;
		$mails = isset($parts[4]) ? (int)$parts[4] : 0;
		$dbMs = isset($parts[5]) ? (int)$parts[5] : 0;
		$dbHits = isset($parts[6]) ? (int)$parts[6] : 0;
		$extraHits = isset($parts[7]) ? explode(',', $parts[7]) : [];

		return new self($hits, $duration, $locked, $google, $mails, $dbMs, $dbHits, $extraHits);
	}

	public function ToStringShort() : string
	{
		return $this->hits . ';' . $this->duration . ';' . $this->locked;
	}

	public function ToStringMedium() : string
	{
		return $this->hits . ';' . $this->duration . ';' . $this->locked . ';' . $this->dbMs . ';' . $this->dbHits;
	}

	public function ToStringLong() : string
	{
		return $this->hits . ';' . $this->duration . ';' . $this->locked . ';' . $this->google . ';' . $this->mails
			. ';' . $this->dbMs . ';' . $this->dbHits . ';' . implode(',', $this->extraHits);
	}

	public function Add(self $other) : void
	{
		$this->hits += $other->hits;
		$this->duration += $other->duration;
		$this->locked += $other->locked;
		$this->google += $other->google;
		$this->mails += $other->mails;
		$this->dbMs += $other->dbMs;
		$this->dbHits += $other->dbHits;
	}

	/**
	 * Replica la lógica original de IncrementLargeKey: acumula los valores de este
	 * registro (los "viejos") dentro del array de extraHits entrante (los "nuevos"),
	 * posición a posición, y devuelve el resultado.
	 */
	public function MergeExtraHitsInto(array $incomingExtraHits) : array
	{
		for ($n = 0; $n < count($incomingExtraHits); $n++)
		{
			if ($n < count($this->extraHits) && $this->extraHits[$n])
				$incomingExtraHits[$n] += $this->extraHits[$n];
		}
		return $incomingExtraHits;
	}
}