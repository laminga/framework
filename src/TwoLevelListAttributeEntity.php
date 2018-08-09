<?php


namespace minga\framework;

class TwoLevelListAttributeEntity extends TwoLevelAttributeEntity
{
	protected $useInternalId = false;

	public function AppendItem($section, $item)
	{
		$values = $this->safeGetArray($section, 'items');
		if ($this->useInternalId)
			$id = $this->getNextId();
		$item['id'] = $id;
		$values[] = json_encode($item);
		$this->safeSetArray($section, 'items', $values);
	}
	public function DeleteItem($section, $itemId)
	{
		// Lo busca entre los decodificados
		$items = $this->GetItems($section, 'items');
		if ($this->useInternalId == false)
			throw new Exception('TwoLevelListAttributeEntity must have InternalId to be deleted');
		$n = Arr::IndexOfByNamedValue($items, 'id', $itemId);
		// Lo saca de los codificados
		$values = $this->safeGetArray($section, 'items');
		if ($n != -1)
			Arr::RemoveAt($values, $n);
		$this->safeSetArray($section, 'items', $values);
	}
	public function getNextId()
	{
		$id = intval($this->safeGet('__id_numbers__', 'id', 0))+1;
		$this->SetValue('__id_numbers__', 'id', $id);
		return $id;
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
