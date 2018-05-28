<?php

namespace minga\framework;

class Sorter
{
	public $key;
	public $m;
	public function sort($a, $b)
	{
		return $this->m * strcmp($a[$this->key], $b[$this->key]);
	}

	public function sortAttribute($a, $b)
	{
		return $this->m * strcasecmp($a->attributes[$this->key], $b->attributes[$this->key]);
	}
}
