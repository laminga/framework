<?php

namespace minga\framework;

class FullNameSorter
{
	public $m;
	public function sort($a, $b)
	{
		$aName = $a['fullName'];
		$bName = $b['fullName'];
		$aDescription = Arr::SafeGet($a, 'description');
		$bDescription = Arr::SafeGet($b, 'description');
		if ($aName == $bName)
		{
			return $this->m * strcmp($aDescription, $bDescription);
		}
		// se fija si termina en número...
		$aName = Str::ReformatEndingNumber($aName);
		$bName = Str::ReformatEndingNumber($bName);

		$aFull = $aName . ($aName != "" ? "." : '') . $aDescription;
		$bFull = $bName . ($bName != "" ? "." : '') . $bDescription;
		if (Str::StartsWith($aFull, "[") && !Str::StartsWith($bFull, "["))
			return $this->m * -1;
		else if (!Str::StartsWith($aFull, "[") && Str::StartsWith($bFull, "["))
			return $this->m * 1;

		return $this->m * strcasecmp($aFull, $bFull);
	}
}
