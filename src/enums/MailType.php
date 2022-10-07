<?php

namespace minga\framework\enums;

class MailType
{
	public const Unclassified = 0;

	public const Error = 1;
	public const JavascriptError = 2;
	public const FatalError = 3;
	public const AdministrativeAlert = 4;

	public static function GetName(int $val) : string
	{
		$class = new \ReflectionClass(__CLASS__);
		$constants = array_flip($class->getConstants());
		return $constants[$val];
	}
}
