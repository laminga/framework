<?php

namespace minga\framework;

class Reflection
{
	public static function InstanciateClass($class, ...$constructorParams)
	{
		if(count($constructorParams) == 0)
			return new $class;

		$rc = new \ReflectionClass($class);
		return $rc->newInstanceArgs($constructorParams);
	}

	public static function GetParamNames($method) : array
	{
		$params = self::GetParams($method);
		$res = [];
		foreach ($params as $param)
			$res[] = $param->name;
		return $res;
	}

	public static function GetParamType($method, int $index)
	{
		$params = self::GetParams($method);
		$class = $params[$index]->getClass();
		if($class === null)
			return null;
		return $class->name;
	}

	public static function GetParams($method)
	{
		return self::GetMethod($method)->getParameters();
	}

	public static function GetMethod($method)
	{
		if(is_array($method))
		{
			if(is_object($method[0]))
				$method[0] = get_class($method[0]);
			$method = $method[0] . '::' . $method[1];
		}

		return new \ReflectionMethod($method);
	}

	public static function CallMethod($method, ...$params)
	{
		return call_user_func($method, ...$params);
	}

	public static function CallPrivateMethod($instance, $function, ...$params)
	{
		$method = self::GetMethod([$instance, $function]);
		$method->setAccessible(true);
		return $method->invoke($instance, ...$params);
	}

	public static function CallPrivateMethodRef($instance, $function, $param, &$refParam)
	{
		$makePublic = function($param) use ($function, &$refParam) {
			return self::$function($param, $refParam);
		};
		return $makePublic->call($instance, $param);
	}
}
