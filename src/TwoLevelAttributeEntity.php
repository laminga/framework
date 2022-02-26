<?php

namespace minga\framework;

class TwoLevelAttributeEntity
{
	/** @var string */
	public $path = '';
	/** @var array */
	public $sections = [];
	/** @var bool */
	protected $keepSectionCreationDate = false;

	public function SetLocation(string $path) : void
	{
		$this->path = $path;
	}

	public function LoadAttributesOnly(string $path) : void
	{
		$this->path = $path;
		$this->sections = [];
		if ($path != "" && file_exists($path))
			$this->sections = IO::ReadEscapedIniFileWithSections($path);
	}

	public function GetCreateDate(string $section) : string
	{
		return $this->SafeGet($section, 'created');
	}

	public function SafeGetSection(?string $section, $default = [])
	{
		if ($section !== null && isset($this->sections[$section]))
			return $this->sections[$section];
		return $default;
	}

	public function SafeGet(?string $section, string $key, $default = '')
	{
		if ($section !== null && isset($this->sections[$section]))
		{
			$sectionValues = $this->sections[$section];
			if (isset($sectionValues[$key]))
				return $sectionValues[$key];
		}
		return $default;
	}

	public function SaveAttributesOnly() : void
	{
		if ($this->path == '')
			throw new ErrorException("Tried to save to an uninitialized entity.");
		IO::WriteEscapedIniFileWithSections($this->path, $this->sections);
	}

	public function GetItem(string $section)
	{
		return $this->sections[$section];
	}

	public function SafeGetItem(?string $section, $default = null)
	{
		if($section !== null && isset($this->sections[$section]))
			return $this->sections[$section];
		return $default;
	}

	public function GetSectionArray() : array
	{
		return array_keys($this->sections);
	}

	public function SetItem(string $section, $value) : void
	{
		if ($this->keepSectionCreationDate && isset($this->sections[$section]) == false)
			$value['created'] = Date::FormattedArNow();
		$this->sections[$section] = $value;
	}

	public function SetValue(string $section, string $key, $value) : void
	{
		if (isset($this->sections[$section]) == false)
		{
			$this->sections[$section] = [];
			if ($this->keepSectionCreationDate)
				$this->sections[$section]['created'] = Date::FormattedArNow();
		}
		$this->sections[$section][$key] = $value;
	}

	public function KeyExists(string $section, string $key = '') : bool
	{
		if ($key == '')
			return isset($this->sections[$section]);
		return isset($this->sections[$section][$key]);
	}

	public function Clear() : void
	{
		$this->sections = [];
	}

	public function RemoveKey(string $section, string $key = '') : void
	{
		if ($key == '')
			unset($this->sections[$section]);
		else if (isset($this->sections[$section][$key]))
			unset($this->sections[$section][$key]);
	}

	public function Count() : int
	{
		return count($this->sections);
	}

	public function SafeGetArray(?string $section, string $key) : array
	{
		$sectionArray = $this->SafeGetSection($section);
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

	public function SafeSetArray(string $section, string $key, array $valueArray) : void
	{
		if ($this->KeyExists($section) == false)
			$this->sections[$section] = [];
		// Lee los valores...
		$n = 1;
		while(isset($this->sections[$section][$key . $n]))
		{
			unset($this->sections[$section][$key . $n]);
			$n++;
		}
		// Guarda
		for($n = 0; $n < count($valueArray); $n++)
			$this->sections[$section][$key . ($n + 1)] = $valueArray[$n];
	}

	public function RenameSection(string $oldSection, string $newSection) : void
	{
		if(isset($this->sections[$oldSection]))
		{
			$this->sections[$newSection] = $this->sections[$oldSection];
			unset($this->sections[$oldSection]);
			$this->SaveAttributesOnly();
		}
	}
}
