<?php

namespace minga\framework;

class Obj
{
	public static function SafeGet(object $obj, string $item, $default = "")
	{
		if (isset($obj->$item))
			return $obj->$item;
		return $default;
	}
}
