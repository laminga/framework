<?php


namespace minga\framework;

class TwoLevelAttributeEntity
{
	public $path = '';
	public $sections = Array();
	protected $keepSectionCreationDate = false;

	public function SetLocation($path)
	{
		$this->path = $path;
	}

	public function LoadAttributesOnly($path)
	{
		$this->path = $path;
		if ($path != "" && file_exists($path))
			$this->sections = IO::ReadEscapedIniFileWithSections($path);
		else
			$this->sections = Array();
	}

	public function GetCreateDate($section)
	{
		return $this->SafeGet($section, 'created');
	}

	public function SafeGetSection($section, $default = array())
	{
		if (array_key_exists($section, $this->sections))
		{
			return $this->sections[$section];
		}
		return $default;
	}

	public function SafeGet($section, $key, $default = '')
	{
		if (array_key_exists($section, $this->sections))
		{
			$sectionValues = $this->sections[$section];
			if (array_key_exists($key, $sectionValues))
				return $sectionValues[$key];
		}
		return $default;
	}

	public function SaveAttributesOnly()
	{
		if (strlen($this->path) == 0)
			throw new ErrorException("Tried to save to an uninitialized entity.");
		IO::WriteEscapedIniFileWithSections($this->path, $this->sections);
	}

	public function GetItem($section)
	{
		return $this->sections[$section];
	}

	public function GetSectionArray()
	{
		return array_keys($this->sections);
	}

	public function SetItem($section, $value)
	{
		if ($this->keepSectionCreationDate && array_key_exists($section, $this->sections) == false)
		{
			$value['created'] = Date::FormattedArNow();
		}
		$this->sections[$section] = $value;
	}

	public function SetValue($section, $key, $value)
	{
		if (array_key_exists($section, $this->sections) == false)
		{
			$this->sections[$section] = array();
			if ($this->keepSectionCreationDate)
				$this->sections[$section]['created'] = Date::FormattedArNow();
		}
		$this->sections[$section][$key] = $value;
	}

	public function KeyExists($section, $key = null)
	{
		if (array_key_exists($section, $this->sections) == false)
			return false;
		if ($key == null)
			return true;
		else
			return array_key_exists($key, $this->sections[$section]);
	}

	public function Clear()
	{
		$this->sections = array();
	}

	public function RemoveKey($section, $key = null)
	{
		if (array_key_exists($section, $this->sections))
		{
			if ($key == null)
				unset($this->sections[$section]);
			else if (array_key_exists($key, $this->sections[$section]))
				unset($this->sections[$section][$key]);
		}
	}

	public function Count()
	{
		return sizeof($this->sections);
	}

	public function SafeGetArray($section, $key)
	{
		$sectionArray = $this->SafeGetSection($section);
		// Lee los valores...
		$n = 1;
		$current = array();
		while(array_key_exists($key . $n, $sectionArray))
		{
			$value = $sectionArray[$key . $n];
			$current[] = $value;
			$n++;
		}
		return $current;
	}

	public function SafeSetArray($section, $key, $valueArray)
	{
		if ($this->KeyExists($section) == false)
			$this->sections[$section] = array();
		// Lee los valores...
		$n = 1;
		while(array_key_exists($key . $n, $this->sections[$section]))
		{
			unset($this->sections[$section][$key . $n]);
			$n++;
		}
		// Guarda
		for($n = 0; $n < sizeof($valueArray); $n++)
			$this->sections[$section][$key . ($n+1)] = $valueArray[$n];
		// listo
	}
}
