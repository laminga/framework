<?php

namespace minga\framework;

class QueryPart
{
	public $From;
	public $Where;
	public $Params;
	public $Select;
	public $GroupBy;
	public $OrderBy;

	public function __construct($from, $where, $params = array(), $select = null, $groupBy = null, $orderBy = null)
	{
		$this->From = $from;
		$this->Where = $where;
		$this->Params = $params;
		$this->GroupBy = $groupBy;
		$this->OrderBy = $orderBy;
		$this->Select = $select;
	}

}

