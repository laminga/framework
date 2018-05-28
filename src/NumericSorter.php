<?php


namespace minga\framework;

class NumericSorter
{
	public $key;
	public $m;
	public function sort($a, $b)
	{
		return $this->m * ($a[$this->key] > $b[$this->key] ? 1 :
			($a[$this->key] < $b[$this->key] ? -1 : 0)
		);
	}

	public function sortAttribute($a, $b)
	{
		return $this->m *
			($a->attributes[$this->key] > $b->attributes[$this->key] ? 1 :
			($a->attributes[$this->key] < $b->attributes[$this->key] ? 1 : 0)
		);
	}
}
