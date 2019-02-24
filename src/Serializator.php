<?php

namespace minga\framework;

class Serializator
{
	public static function Serialize($obj)
	{
		Profiling::BeginTimer();
		$ret = serialize($obj);
		Profiling::EndTimer();
		return $ret;
	}

	public static function Deserialize($text)
	{
		Profiling::BeginTimer();
		$ret = unserialize($text);
		Profiling::EndTimer();
		return $ret;
	}
}

