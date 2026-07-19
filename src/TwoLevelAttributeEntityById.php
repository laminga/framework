<?php

namespace minga\framework;

class TwoLevelAttributeEntityById extends TwoLevelAttributeEntity
{
	public function GetCreateDateById(int $id) : string
	{
		return $this->SafeGetById($id, 'created');
	}

	public function SafeGetSectionById(int $id, array $default = []) : array
	{
		if (isset($this->sections[$id]))
			return $this->sections[$id];
		return $default;
	}

	public function SafeGetIntById(int $id, string $key, int $default = 0) : int
	{
		return (int)$this->SafeGetById($id, $key, (string)$default);
	}

	public function SafeGetBoolById(int $id, string $key, bool $default = false) : bool
	{
		return (bool)$this->SafeGetById($id, $key, (string)$default);
	}

	public function SafeGetById(int $id, string $key, string $default = '') : string
	{
		if (isset($this->sections[$id]))
		{
			$sectionValues = $this->sections[$id];
			if (isset($sectionValues[$key]))
				return $sectionValues[$key];
		}
		return $default;
	}

	public function GetItemById(int $id) : array
	{
		return $this->sections[$id];
	}

	public function SafeGetItemById(int $id, array $default = [])
	{
		if(isset($this->sections[$id]))
			return $this->sections[$id];
		return $default;
	}

	public function SetItemById(int $id, array $value) : void
	{
		if ($this->keepSectionCreationDate && isset($this->sections[$id]) == false)
			$value['created'] = Date::FormattedArNow();
		$this->sections[$id] = $value;
	}

	public function SetValueById(int $id, string $key, $value) : void
	{
		if (isset($this->sections[$id]) == false)
		{
			$this->sections[$id] = [];
			if ($this->keepSectionCreationDate)
				$this->sections[$id]['created'] = Date::FormattedArNow();
		}
		$this->sections[$id][$key] = $value;
	}

	public function KeyExistsById(int $id, string $key = '') : bool
	{
		if ($key == '')
			return isset($this->sections[$id]);
		return isset($this->sections[$id][$key]);
	}

	public function RemoveKeyById(int $id, string $key = '') : void
	{
		if ($key == '')
			unset($this->sections[$id]);
		else if (isset($this->sections[$id][$key]))
			unset($this->sections[$id][$key]);
	}

	public function SafeGetArrayById(int $id, string $key) : array
	{
		$sectionArray = $this->SafeGetSectionById($id);
		// Lee los valores...
		$n = 1;
		$current = [];
		while(isset($sectionArray[$key . $n]))
		{
			$value = $sectionArray[$key . $n];
			$current[] = $value;
			$n++;
		}
		return $current;
	}

	public function SafeSetArrayById(int $id, string $key, array $valueArray) : void
	{
		if ($this->KeyExistsById($id) == false)
			$this->sections[$id] = [];
		// Lee los valores...
		$n = 1;
		while(isset($this->sections[$id][$key . $n]))
		{
			unset($this->sections[$id][$key . $n]);
			$n++;
		}
		// Guarda
		for($n = 0; $n < count($valueArray); $n++)
			$this->sections[$id][$key . ($n + 1)] = $valueArray[$n];
	}

	public function RenameSectionById(int $oldSection, int $newSection) : void
	{
		if(isset($this->sections[$oldSection]))
		{
			$this->sections[$newSection] = $this->sections[$oldSection];
			unset($this->sections[$oldSection]);
			$this->SaveAttributesOnly();
		}
	}
}
