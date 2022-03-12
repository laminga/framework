<?php

namespace minga\framework;

class Aggregate
{
	public static function BuildTotalsRow(array $list, string $label, array $columns) : array
	{
		$results = [];
		if ($label != "")
		{
			$results[$label] = 'Total';
			$results['isTotal'] = true;
		}
		// inicializa
		foreach($columns as $column)
			$results[$column] = 0;
		// suma
		foreach($list as $item)
		{
			foreach($columns as $column)
			{
				if (isset($item[$column]))
					$results[$column] += $item[$column];
			}
		}
		return $results;
	}
}
