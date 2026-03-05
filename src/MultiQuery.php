<?php

namespace minga\framework;

class MultiQuery
{
	public $params;
	public $sql;

	public function __construct($query1, $query2 = null, $query3 = null, $query4 = null, $query5 = null,
		$query6 = null, $query7 = null, $query8 = null, $query9 = null, $query10 = null)
	{
		$queries = [];
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

		$select = "";
		$from = "";
		$where = "";
		$groupBy = "";
		$orderBy = "";
		$params = [];

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
			if ($query->MaxRows !== null)
				$this->setMaxRows($query->MaxRows);
			if ($query->Params != null)
				$params = array_merge($params, $query->Params);
		}
		if (Str::StartsWith($where, " AND "))
			$where = (string)substr($where, 5);

		$this->params = $params;
		$this->sql = "SELECT " . $select . " FROM " . $from
			. ($where != "" ? " WHERE " . $where : "")
			. ($groupBy != "" ? " GROUP BY " . $groupBy : "")
			. ($orderBy != "" ? " ORDER BY " . $orderBy : "");
	}

	public function setMaxRows($max) : void
	{
		$this->sql .= " LIMIT 0, " . $max;
	}

	public function dump() : void
	{
		echo 'Template: <br>' . $this->sql;
		echo '<br>&nbsp;<br>Params: <br>';
		print_r($this->params);
		echo '<br>&nbsp;<br>Query: <br>' . $this->includeParams($this->sql, $this->params);

		exit();
	}

	public function fetchAll()
	{
		return Context::Calls()->Db()->fetchAll($this->sql, $this->params);
	}

	public function fetchAllByPos()
	{
		return Context::Calls()->Db()->fetchAllByPos($this->sql, $this->params);
	}

	private function includeParams($str, $params)
	{
		$n = strpos($str, '?');
		$i = 0;
		while($n !== false)
		{
			$str = substr($str, 0, $n) . Str::CheapSqlEscape($params[$i++]) . substr($str, $n + 1);
			$n = strpos($str, '?');
		}
		return $str;
	}
}

