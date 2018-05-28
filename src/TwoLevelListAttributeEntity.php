<?php


namespace minga\framework;

class TwoLevelListAttributeEntity extends TwoLevelAttributeEntity
{
	public function AppendItem($section, $item)
	{
		$values = $this->safeGetArray($section, 'items');
		$values[] = json_encode($item);
		$this->safeSetArray($section, 'items', $values);
	}

	public function GetItems($section)
	{
		$values = $this->safeGetArray($section, 'items');

		$ret = array();
		foreach($values as $item)
			$ret[] = json_decode($item, true);
		return $ret;
	}
}
