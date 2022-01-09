<?php

namespace minga\framework;

class TwoLevelListAttributeEntity extends TwoLevelAttributeEntity
{
	protected $useInternalId = false;

	public function AppendItem($section, $item)
	{
		$values = $this->SafeGetArray($section, 'items');
		$id = null;
		if ($this->useInternalId)
			$id = $this->getNextId();
		$item['id'] = $id;
		$values[] = json_encode($item);
		$this->SafeSetArray($section, 'items', $values);
	}
	public function DeleteItem($section, $itemId)
	{
		// Lo busca entre los decodificados
		$items = $this->GetItems($section);
		if ($this->useInternalId == false)
			throw new ErrorException('TwoLevelListAttributeEntity must have InternalId to be deleted');
		$n = Arr::IndexOfByNamedValue($items, 'id', $itemId);
		// Lo saca de los codificados
		$values = $this->SafeGetArray($section, 'items');
		if ($n != -1)
			Arr::RemoveAt($values, $n);
		$this->SafeSetArray($section, 'items', $values);
	}
	public function getNextId()
	{
		$id = (int)($this->SafeGet('__id_numbers__', 'id', 0))+1;
		$this->SetValue('__id_numbers__', 'id', $id);
		return $id;
	}
	public function GetItems($section)
	{
		$values = $this->SafeGetArray($section, 'items');

		$ret = [];
		foreach($values as $item)
			$ret[] = json_decode($item, true);
		return $ret;
	}
}
