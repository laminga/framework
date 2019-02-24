<?php

namespace minga\framework;

class ProfilingItem
{
	public $name;

	private $startTime;
	private $startMemory;
	private $startMemoryPeak;

	public $memory;
	public $memoryPeak;
	public $durationMs;
	public $hits;
	public $isInternal;
	private $start_pause;

	public $children = Array();

	public function __construct($name)
	{
		$this->name = $name;
		$this->startTime = 	microtime(true);
		$this->startMemory = memory_get_usage();
		$this->startMemoryPeak = memory_get_peak_usage();
		$this->durationMs = 0;
		$this->hits = 0;
		$this->memory = 0;
		$this->memoryPeak = 0;
		$this->start_pause = Performance::$pause_ellapsed_secs;
	}

	private function ftime()
	{
		$a = gettimeofday();
		return $a['sec'] + ($a['usec']*0.000001);
	}

	public function CompleteTimer()
	{
		$t2 = microtime(true);
		$end_pause = Performance::$pause_ellapsed_secs;
		$m2 = memory_get_usage();
		/* $mp2 = memory_get_peak_usage(); */

		$this->memory = $m2 - $this->startMemory;
		/* $this->memoryPeak = $mp2 - $this->startMemoryPeak; */

		$span = $t2 - $this->startTime - ($end_pause - $this->start_pause);
		$this->durationMs = $span * 1000;
		$this->hits = 1;
	}
	public function SumChildren()
	{
		$this->memory = 0;
		$this->memoryPeak = 0;
		$this->durationMs = 0;
		$this->hits = 0;
		foreach($this->children as $child)
		{
			$this->durationMs += $child->durationMs;
			$this->hits += $child->hits;
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
