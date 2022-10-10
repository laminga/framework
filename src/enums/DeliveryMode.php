<?php

namespace minga\framework\enums;

class DeliveryMode
{
	public const Now = 1;
	public const Daily = 2;

	public static function GetName(int $val) : string
	{
		$class = new \ReflectionClass(__CLASS__);
		$constants = array_flip($class->getConstants());
		return $constants[$val];
	}
}
