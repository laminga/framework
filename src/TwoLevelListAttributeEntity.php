<?php

namespace minga\framework;

class TwoLevelListAttributeEntity extends TwoLevelAttributeEntity
{
	protected bool $useInternalId = false;

	public function AppendItem(string $section, $item) : void
	{
		$values = $this->SafeGetArray($section, 'items');
		$id = null;
		if ($this->useInternalId)
			$id = $this->GetNextId();
		$item['id'] = $id;
		$values[] = json_encode($item);
		$this->SafeSetArray($section, 'items', $values);
	}

	public function DeleteItem(string $section, $itemId) : void
	{
		// Lo busca entre los decodificados
		$items = $this->GetItems($section);
		if ($this->useInternalId == false)
			throw new ErrorException('TwoLevelListAttributeEntity debe tener un InternalId para ser eliminado');
		$n = Arr::IndexOfByNamedValue($items, 'id', $itemId);
		// Lo saca de los codificados
		$values = $this->SafeGetArray($section, 'items');
		if ($n != -1)
			Arr::RemoveAt($values, $n);
		$this->SafeSetArray($section, 'items', $values);
	}

	private function GetNextId() : int
	{
		$id = (int)($this->SafeGet('__id_numbers__', 'id')) + 1;
		$this->SetValue('__id_numbers__', 'id', $id);
		return $id;
	}

	public function GetItems(string $section) : array
	{
		$values = $this->SafeGetArray($section, 'items');

		$ret = [];
		foreach($values as $item)
			$ret[] = json_decode($item, true);
		return $ret;
	}
}
