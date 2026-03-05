<?php

namespace minga\framework\enums;

class DeliveryMode
{
	public const None = 0;
	public const Now = 1;
	public const Daily = 2;

	public static function GetName(int $val) : string
	{
		$class = new \ReflectionClass(self::class);
		$constants = array_flip($class->getConstants());
		return $constants[$val];
	}
}
