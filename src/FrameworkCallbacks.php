<?php

namespace minga\framework;

abstract class FrameworkCallbacks
{
	public function RenderTemplate(string $template, array $vals = []) : void
	{
	}

	public function RenderMessage(string $template, array $vals = []) : string
	{
		return '';
	}

	public function EndRequest() : void
	{
	}

	public function Db() : Db
	{
		throw new \Exception('Heredar');
	}

	public static function Trans(string $str, array $parameters = [], ?string $domain = null, ?string $locale = null) : string
	{
		foreach($parameters as $key => $value)
			$str = Str::Replace($str, $key, $value);
		return $str;
	}
}
