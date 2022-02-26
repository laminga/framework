<?php

namespace minga\framework;

//El nombre de esta clase no es el mejor y no es recomendable usarla en general
//pero era peor que estas funciones estén en clases que si tienen un nombre y
//funciones coherentes con él.
class Misc
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
