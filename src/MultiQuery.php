<?php

namespace minga\framework;

class MultiQuery
{
	public $params;
	public $sql;

	public function __construct($query1, $query2 = null, $query3 = null, $query4 = null, $query5 = null,
		$query6 = null, $query7 = null, $query8 = null, $query9 = null, $query10 = null)
	{
		$queries = array();
		if ($query1 != null)
			$queries[] = $query1;
		if ($query2 != null)
			$queries[] = $query2;
		if ($query3 != null)
			$queries[] = $query3;
		if ($query4 != null)
			$queries[] = $query4;
		if ($query5 != null)
			$queries[] = $query5;
		if ($query6 != null)
			$queries[] = $query6;
		if ($query7 != null)
			$queries[] = $query7;
		if ($query8 != null)
			$queries[] = $query8;
		if ($query9 != null)
			$queries[] = $query9;
		if ($query10 != null)
			$queries[] = $query10;

		$select =  "";
		$from = "";
		$where = "";
		$groupBy = "";
		$orderBy = "";
		$params = array();

		foreach($queries as $query)
		{
			if ($query->Select != null)
				$select .= ($select != "" ? ", " : "") . $query->Select;
			if ($query->From != null)
				$from .= ($from != "" ? ", " : "") . $query->From;
			if ($query->Where != null)
				$where .= ($where != "" ? " AND " : "") . $query->Where;
			if ($query->GroupBy != null)
				$groupBy .= ($groupBy != "" ? ", " : "") . $query->GroupBy;
			if ($query->OrderBy != null)
				$orderBy .= ($orderBy != "" ? ", " : "") . $query->OrderBy;

			if ($query->Params != null)
				$params = array_merge($params, $query->Params);
		}

		$this->params = $params;
		$this->sql = "SELECT " . $select . " FROM " . $from .
			($where != "" ? " WHERE " . $where : "") .
			($groupBy != "" ? " GROUP BY " . $groupBy : "").
			($orderBy != "" ? " ORDER BY " . $orderBy : "");
	}

	public function dump()
	{
		echo $this->sql;
		print_r($this->params);
		exit();
	}

}

