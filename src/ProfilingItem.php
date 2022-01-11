<?php

namespace minga\framework;

class ProfilingItem
{
	public $name;

	private $startTime;
	private $startMemory;

	public $memory;
	public $memoryPeak;
	public $durationMs;
	public $hits;
	public $dbHits;
	public $isInternal;
	private $startPause;

	public $children = [];

	public function __construct($name)
	{
		$this->name = $name;
		$this->startTime = microtime(true);
		$this->startMemory = memory_get_usage();
		$this->durationMs = 0;
		$this->hits = 0;
		$this->dbHits = 0;
		$this->memory = 0;
		$this->memoryPeak = 0;
		$this->startPause = Performance::$pauseEllapsedSecs;
	}

	private function ftime()
	{
		$a = gettimeofday();
		return $a['sec'] + ($a['usec'] * 0.000001);
	}

	public function CompleteTimer() : void
	{
		$t2 = microtime(true);
		$endPause = Performance::$pauseEllapsedSecs;
		$m2 = memory_get_usage();
		$this->memory = $m2 - $this->startMemory;
		$span = $t2 - $this->startTime - ($endPause - $this->startPause);
		$this->durationMs = $span * 1000;
		$this->hits = 1;
	}

	public function SumChildren() : void
	{
		$this->memory = 0;
		$this->memoryPeak = 0;
		$this->durationMs = 0;
		$this->hits = 0;
		$this->dbHits = 0;
		foreach($this->children as $child)
		{
			$this->durationMs += $child->durationMs;
			$this->hits += $child->hits;
			$this->dbHits += $child->dbHits;
			$this->memory += $child->memory;
			$this->memoryPeak += $child->memoryPeak;
		}
	}

	public function GetChildrenOrCreate($name)
	{
		foreach($this->children as $child)
			if ($child->name == $name)
				return $child;
		// lo crea
		$ret = new ProfilingItem($name);
		$this->children[] = $ret;
		return $ret;
	}
}
