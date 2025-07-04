<?php

namespace minga\framework;

class AttributeEntity
{
	public string $path = '';
	public array $attributes = [];
	public array $extraAttributes = [];

	public function LoadAttributesOnly(string $path) : void
	{
		Profiling::BeginTimer();
		$this->path = $path;
		$this->attributes = [];
		if ($path != "" && file_exists($path))
			$this->attributes = IO::ReadEscapedIniFile($path);
		Profiling::EndTimer();
	}

	public function SafeGet(string $key, $default = '')
	{
		if (isset($this->attributes[$key]))
			return $this->attributes[$key];
		else if (isset($this->extraAttributes[$key]))
			return $this->extraAttributes[$key];
		return $default;
	}

	public function SafeAppend(string $key, $valueArray) : void
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
			$this->attributes[$key . ($n + 1)] = $current[$n];
		// listo
	}

	public function SafeGetArray(string $key) : array
	{
		// Lee los valores...
		$n = 1;
		$current = [];
		while(isset($this->attributes[$key . $n]))
		{
			$value = $this->attributes[$key . $n];
			$current[] = $value;
			$n++;
		}
		return $current;
	}

	public function SafeSetArray(string $key, array $valueArray) : void
	{
		// Lee los valores...
		$n = 1;
		while(isset($this->attributes[$key . $n]))
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
			$this->attributes[$key . ($n + 1)] = $current[$n];
		// listo
	}

	public function GetAllAttributes() : array
	{
		return array_merge($this->attributes, $this->extraAttributes);
	}

	public function SaveAttributesOnly() : void
	{
		if ($this->path == '')
			throw new ErrorException('Se intentó guardar una entidad no inicializada.');
		Profiling::BeginTimer();
		IO::WriteEscapedIniFile($this->path, $this->attributes);
		Profiling::EndTimer();
	}

	public function SetValue(string $key, $value) : void
	{
		$this->attributes[$key] = $value;
	}

	public function GetValue(string $key)
	{
		return $this->attributes[$key];
	}

	public function RemoveKey(string $key) : void
	{
		if (isset($this->attributes[$key]))
			unset($this->attributes[$key]);
	}

	public function SetDefault(string $key, $default) : void
	{
		if (isset($this->attributes[$key]) == false)
			$this->attributes[$key] = $default;
	}
}
