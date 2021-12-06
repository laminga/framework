<?php

namespace minga\framework;

use minga\framework\ErrorException;

class AttributeEntity
{
	public $path = '';
	public $attributes = [];
	public $extraAttributes = [];

	public function LoadAttributesOnly($path)
	{
		$this->path = $path;
		$this->attributes = [];
		if ($path != "" && file_exists($path))
			$this->attributes = IO::ReadEscapedIniFile($path);
	}

	public function SafeGet($key, $default = '')
	{
		if (array_key_exists($key, $this->attributes))
			return $this->attributes[$key];
		else if (array_key_exists($key, $this->extraAttributes))
			return $this->extraAttributes[$key];
		return $default;
	}

	public function SafeAppend($key, $valueArray)
	{
		// Lee los valores...
		$current = $this->SafeGetArray($key);
		// Agrega lo propio
		if (is_array($valueArray) == false)
			$valueArray = [$valueArray];
		foreach($valueArray as $value)
		{
			$processed = trim($value);
			if ($processed != "" && in_array($processed, $current) == false)
				$current[] = $processed;
		}
		// Guarda
		for($n = 0; $n < count($current); $n++)
			$this->attributes[$key . ($n+1)] = $current[$n];
		// listo
	}

	public function SafeGetArray($key)
	{
		// Lee los valores...
		$n = 1;
		$current = [];
		while(array_key_exists($key . $n, $this->attributes))
		{
			$value = $this->attributes[$key . $n];
			$current[] = $value;
			$n++;
		}
		return $current;
	}

	public function SafeSetArray($key, $valueArray)
	{
		// Lee los valores...
		$n = 1;
		while(array_key_exists($key . $n, $this->attributes))
		{
			unset($this->attributes[$key . $n]);
			$n++;
		}
		// Agrega lo propio
		$current = [];
		foreach($valueArray as $value)
		{
			$processed = trim($value);
			if ($processed != "" && in_array($processed, $current) == false)
				$current[] = $processed;
		}
		// Guarda
		for($n = 0; $n < count($current); $n++)
			$this->attributes[$key . ($n+1)] = $current[$n];
		// listo
	}

	public function GetAllAttributes()
	{
		return array_merge($this->attributes, $this->extraAttributes);
	}

	public function SaveAttributesOnly()
	{
		if ($this->path == '')
			throw new ErrorException("Tried to save to an uninitialized entity.");
		IO::WriteEscapedIniFile($this->path, $this->attributes);
	}

	public function SetValue($key, $value)
	{
		$this->attributes[$key] = $value;
	}

	public function GetValue($key)
	{
		return $this->attributes[$key];
	}

	public function RemoveKey($key)
	{
		if (array_key_exists($key, $this->attributes))
			unset($this->attributes[$key]);
	}

	public function SetDefault($key, $default)
	{
		if (!array_key_exists($key, $this->attributes))
			$this->attributes[$key] = $default;
	}
}
