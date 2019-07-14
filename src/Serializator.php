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

	public static function CloneArray($arr, $resetId = false)
	{
		$ret = array();
		foreach($arr as $item)
			$ret[] = self::Clone($item, $resetId);
		return $ret;
	}

	public static function Clone($obj, $resetId = false)
	{
		$text = self::Serialize($obj);
		$ret = self::Deserialize($text);
		if ($resetId)
			$ret->Id = null;
		return $ret;
	}
}

