<?php

namespace minga\framework;

class Sorter
{
	public static function ByAttribute($a, $b, $key)
	{
		return Str::CultureCmp($a->attributes[$key], $b->attributes[$key]);
	}

	public static function ByAttributeDesc($a, $b, $key)
	{
		return -1 * self::ByAttribute($a, $b, $key);
	}

	public static function ByGetter($a, $b, $getter)
	{
		if ($a->$getter() > $b->$getter())
			return 1;
		elseif ($a->$getter() < $b->$getter())
			return -1;

			return 0;
	}

	public static function ByGetterDesc($a, $b, $getter)
	{
		return -1 * self::ByGetter($a, $b, $getter);
	}

	public static function ByKey($a, $b, $key)
	{
		if ($a[$key] > $b[$key])
			return 1;
		elseif ($a[$key] < $b[$key])
			return -1;

			return 0;
	}

	public static function ByField($a, $b, $field)
	{
		if ($a->$field > $b->$field)
			return 1;
		elseif ($a->$field < $b->$field)
			return -1;

			return 0;
	}

	public static function ByKeyDesc($a, $b, $key)
	{
		return -1 * self::ByKey($a, $b, $key);
	}

	public static function StringByKey($a, $b, $key)
	{
		return strcmp($a[$key], $b[$key]);
	}

	public static function StringByKeyDesc($a, $b, $key)
	{
		return -1 * self::StringByKey($a, $b, $key);
	}

	private static function StringBySortKeysArray($a, $b)
	{
		for ($i = 0; $i < count($a->sortKeys); $i++)
		{
			$res = strcmp($b->sortKeys[$i], $a->sortKeys[$i]);
			if ($res != 0)
				return $res;
		}
		return 0;
	}

	public static function StringByTwoKeys($a, $b, $key1, $key2)
	{
		if ($a[$key1] == $b[$key1])
			return self::StringByKey($a, $b, $key2);

			return self::StringByKey($a, $b, $key1);
	}

	public static function ByThreeKeysDesc($a, $b, $key1, $key2, $key3)
	{
		if ($a[$key1] == $b[$key1])
		{
			if ($a[$key2] == $b[$key2])
				return self::ByKeyDesc($a, $b, $key3);

				return self::ByKeyDesc($a, $b, $key2);
		}

			return self::ByKeyDesc($a, $b, $key1);
	}

	public static function ByWordLengthDesc($a, $b)
	{
		return Str::Length($b) - Str::Length($a);
	}

	public static function ByWordCountDesc($a, $b)
	{
		$diffWords = Str::CountWords($b) - Str::CountWords($a);
		if($diffWords != 0)
			return $diffWords;

			return self::ByWordLengthDesc($a, $b);
	}

	private static function CleanString($str)
	{
		return Str::RemoveAccents(Str::ToLower($str));
	}

	public static function ByCleanString($a, $b, $key)
	{
		$c = self::CleanString($a[$key]);
		$d = self::CleanString($b[$key]);
		return Str::CultureCmp($c, $d);
	}

	public static function ByArray($a, $b)
	{
		for ($i = 0; $i < count($a); $i++)
		{
			if (is_numeric($a[$i]) && is_numeric($b[$i]))
			{
				$ret = self::ByKey($a, $b, $i);
				if($ret != 0)
					return $ret;
			}
			else
			{
				$ret = self::ByCleanString($a, $b, $i);
				if($ret != 0)
					return $ret;
			}
		}
		return 0;
	}

	public static function ByKeysArray($a, $b, $key)
	{
		return self::ByArray($a[$key], $b[$key]);
	}

	public static function BySortKeys($a, $b)
	{
		return self::ByArray($a->sortKeys, $b->sortKeys);
	}

	public static function ByFullNameDesc($a, $b)
	{
		return self::ByFullName($a, $b, -1);
	}

	public static function ByFullName($a, $b, $mult = 1)
	{
		if(isset($a['fullName']))
			$aName = $a['fullName'];
		else if(isset($a['fullname']))
			$aName = $a['fullname'];
		else
			throw new \Exception('Fullname a is not set');

		if(isset($b['fullName']))
			$bName = $b['fullName'];
		else if(isset($b['fullname']))
			$bName = $b['fullname'];
		else
			throw new \Exception('Fullname b is not set');

		$aDescription = Arr::SafeGet($a, 'description');
		$bDescription = Arr::SafeGet($b, 'description');
		if ($aName == $bName)
			return $mult * strcmp($aDescription, $bDescription);

		// se fija si termina en número...
		$aName = Str::ReformatEndingNumber($aName);
		$bName = Str::ReformatEndingNumber($bName);

		$aFull = $aName . ($aName != "" ? "." : '') . $aDescription;
		$bFull = $bName . ($bName != "" ? "." : '') . $bDescription;
		if (Str::StartsWith($aFull, "[") && !Str::StartsWith($bFull, "["))
			return $mult * -1;
		else if (!Str::StartsWith($aFull, "[") && Str::StartsWith($bFull, "["))
			return $mult * 1;

		return $mult * Str::CultureCmp($aFull, $bFull);
	}
}
