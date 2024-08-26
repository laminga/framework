<?php

namespace minga\framework;

class Arr
{
	public static function Clone(array $arr) : array
	{
		$ret = [];
		foreach($arr as $k => $v)
			$ret[$k] = clone $v;
		return $ret;
	}

	public static function KeysToLower(array $arr) : array
	{
		$ret = [];
		foreach($arr as $k => $v)
			$ret[Str::ToLower($k)] = $v;
		return $ret;
	}

	public static function GetItemByNamedValue(array $arr, $itemName, $itemValue, $default = null)
	{
		$index = self::IndexOfByNamedValue($arr, $itemName, $itemValue);
		if ($index == -1)
			return $default;
		return $arr[$index];
	}

	public static function GetItemByProperty(array $arr, $itemProperty, $itemValue, $default = null)
	{
		$index = self::IndexOfByProperty($arr, $itemProperty, $itemValue);
		if ($index == -1)
			return $default;
		return $arr[$index];
	}

	public static function InArrayCount(array $arr, $element) : int
	{
		return count(array_keys($arr, $element));
	}

	public static function CastColumnAsFloat(array &$arr, $column) : void
	{
		for($n = 0; $n < count($arr); $n++)
		{
			$value = $arr[$n][$column];
			if ($value !== null)
				$arr[$n][$column] = (float)$value;
		}
	}

	public static function IntToBoolean(array &$arr, $fields) : array
	{
		foreach($arr as &$item)
		{
			foreach($fields as $field)
				$item[$field] = ($item[$field] == true);
		}
		return $arr;
	}

	/**
	 * array_search case insensitve
	 */
	public static function ArraySearchI(string $needle, array $haystack) : string
	{
		$ret = array_search(mb_strtolower($needle), array_map('mb_strtolower', $haystack));
		if($ret === false)
			return '';
		return (string)$ret;
	}

	public static function IndexOf(array $array, $element)
	{
		$ret = array_search($element, $array);
		if ($ret === false)
			$ret = -1;
		return $ret;
	}

	public static function TwoElementsToKeyValue(array $array) : array
	{
		$ret = [];
		foreach($array as $val)
			$ret[reset($val)] = end($val);
		return $ret;
	}

	public static function AddRange(array &$arr1, array $arr2) : array
	{
		$arr1 = array_merge($arr1, $arr2);
		return $arr1;
	}

	public static function InsertAt(array &$arr1, array $element, int $pos) : array
	{
		array_splice($arr1, $pos, 0, [$element]);
		return $arr1;
	}

	public static function AssocToString(array $arr, bool $includeKeys = true, bool $omitEmpty = false) : string
	{
		$ret = '';
		foreach($arr as $key => $value)
		{
			if ($omitEmpty == false || $value)
			{
				if ($ret !== '')
					$ret .= ",";
				if ($includeKeys)
					$ret .= $key . "=";
				$ret .= $value;
			}
		}
		return $ret;
	}

	public static function ToString(array $arr, bool $omitEmpty = false) : string
	{
		$ret = '';
		foreach($arr as $key => $value)
		{
			if ($omitEmpty == false || $value)
			{
				if ($ret !== '')
					$ret .= ",";
				$ret .= $value;
			}
		}
		return $ret;
	}

	public static function Increment(array &$arr, string $itemName, int $n = 1) : void
	{
		self::EnsureSetted($arr, $itemName, 0);
		$arr[$itemName] += $n;
	}

	public static function EnsureSettedArray(array &$arr, string $itemName) : void
	{
		self::EnsureSetted($arr, $itemName, []);
	}

	public static function EnsureSetted(array &$arr, string $itemName, $item) : void
	{
		if (isset($arr[$itemName]) == false)
			$arr[$itemName] = $item;
	}

	public static function FilterByNamedValue(array $arr, string $itemName, string $itemValue, string $default = "") : array
	{
		$ret = [];
		foreach($arr as $item)
		{
			if(self::SafeGet($item, $itemName, $default) == $itemValue)
				$ret[] = $item;
		}
		return $ret;
	}

	public static function RemoveDuplicatesByNamedKey(array $arr, $itemName) : array
	{
		$ret = [];

		for($n = 0; $n < count($arr); $n++)
		{
			$current = $arr[$n];
			if(self::IndexOfByNamedValue($arr, $itemName, $current[$itemName]) === $n)
				$ret[] = $current;
		}
		return $ret;
	}

	public static function InArrayByNamedValue(array $arr, $itemName, $itemValue): bool
	{
		$i = self::IndexOfByNamedValue($arr, $itemName, $itemValue);
		return ($i !== -1);
	}

	public static function IndexOfByNamedValue(array $arr, $itemName, $itemValue) : int
	{
		for($n = 0; $n < count($arr); $n++)
		{
			$current = $arr[$n];
			if (isset($current[$itemName]) && $current[$itemName] == $itemValue)
				return $n;
		}
		return -1;
	}

	public static function IndexOfByProperty(array $arr, $itemProperty, $itemValue) : int
	{
		for($n = 0; $n < count($arr); $n++)
		{
			$current = $arr[$n];
			if ($current->$itemProperty == $itemValue)
				return $n;
		}
		return -1;
	}

	public static function SystematicSample(array $items, $size) : array
	{
		$ret = [];
		$interval = count($items) / $size;
		$first = rand(0, (int)$interval - 1);
		$pos = $first;
		$count = 0;
		while($count < $size)
		{
			$ret[] = $items[(int)$pos];
			$pos += $interval;
			$count++;
		}
		return $ret;
	}

	public static function EatFrom(array $items, string $delimiter) : array
	{
		$ret = [];
		foreach($items as $item)
			$ret[] = Str::EatFrom($item, $delimiter);
		return $ret;
	}

	public static function ExplodeItems(string $delimiter, array $items) : array
	{
		$ret = [];
		foreach($items as $item)
			$ret[] = explode($delimiter, $item);
		return $ret;
	}

	public static function TreeFromTwoLevel(array $items) : array
	{
		$roots = [];
		foreach($items as $item)
		{
			$key = $item[0];
			if (isset($roots[$key]) == false)
				$roots[$key] = [];

			$roots[$key][] = $item[1];
		}
		return $roots;
	}

	public static function SafeGet(array $arr, $item, $default = "")
	{
		if (isset($arr[$item]))
			return $arr[$item];
		return $default;
	}

	public static function RemoveByField($key, $arrayTotal, array $arrayItemsToRemove) : array
	{
		$ret = [];
		foreach($arrayItemsToRemove as $item)
			self::RemoveItemByNamedKey($arrayTotal, $item[$key], $key);
		return $ret;
	}

	public static function SummarizeField(array $array, $field) : int
	{
		$ret = 0;
		foreach($array as $item)
		{
			if (isset($item[$field]))
			{
				$value = $item[$field];
				if ($value)
					$ret += $value;
			}
		}
		return $ret;
	}

	public static function SummarizeValues(array $array) : int
	{
		$ret = 0;
		foreach($array as $value)
		{
			if ($value && $value != '-')
				$ret += $value;
		}
		return $ret;
	}

	public static function MeanValues(array $array, $weights = null)
	{
		$sum = 0;
		$count = 0;
		if (!$weights)
			foreach($array as $value)
			{
				if ($value && $value !== '-')
				{
					$sum += $value;
					$count++;
				}
			}
		else
			for($n = 0; $n < count($array); $n++)
			{
				$value = $array[$n];
				$weight = $weights[$n];
				if ($value && $value !== '-' && $weight)
				{
					$sum += $value * $weight;
					$count += $weight;
				}
			}
		if ($count > 0)
			$ret = $sum / $count;
		else
			$ret = 0;
		return $ret;
	}

	public static function UniqueByField($key, array $arrayTotal) : array
	{
		$ret = [];
		$keys = [];
		foreach($arrayTotal as $item)
		{
			if (in_array($item[$key], $keys) == false)
			{
				$ret[] = $item;
				$keys[] = $item[$key];
			}
		}
		return $ret;
	}

	public static function RemoveItem(array &$array, $item) : array
	{
		foreach (array_keys($array, $item) as $key)
			unset($array[$key]);
		return $array;
	}

	public static function ToKeyArr(array $arr) : array
	{
		$ret = [];
		foreach($arr as $arrItem)
		{
			$keys = array_keys($arrItem);
			$ret[$arrItem[$keys[0]]] = $arrItem[$keys[1]];
		}
		return $ret;
	}

	public static function ToKeyByNamedValue(array $arr, $namedKey, $namedValue) : array
	{
		$ret = [];
		foreach($arr as $arrItem)
			$ret[$arrItem[$namedKey]] = $arrItem[$namedValue];
		return $ret;
	}

	public static function ToArrFromKeyArr(array $arr) : array
	{
		$ret = [];
		foreach($arr as $key => $values)
			$ret[] = array_merge(['Id' => $key], $values);
		return $ret;
	}

	public static function RemoveAt(array &$arr, $pos) : array
	{
		array_splice($arr, $pos, 1);
		return $arr;
	}

	public static function RemoveItemByNamedKey(array $array, $name, $key)
	{
		$pos = self::IndexOfByNamedValue($array, $name, $key);
		if ($pos == -1)
			return false;

		return self::RemoveAt($array, $pos);
	}

	public static function Remove(array &$array, $value) : array
	{
		$n = self::IndexOf($array, $value);
		if($n == -1)
			return $array;
		return self::RemoveAt($array, $n);
	}

	public static function RemoveByValue(array $array, $value) : array
	{
		if (isset($array[$value]))
			unset($array[$value]);
		return $array;
	}

	public static function RemoveItemByKeyValue(array $array, $key, $value) : array
	{
		$ret = [];
		foreach($array as $item)
		{
			if (isset($item[$key]) == false || $item[$key] != $value)
				$ret[] = $item;
		}
		return $ret;
	}

	public static function AppendKeyArray(array $arr, array $appendArray) : array
	{
		foreach ($appendArray as $key => $value)
			$arr[$key] = $value;
		return $arr;
	}

	public static function GrowArray(array $arr, int $size) : array
	{
		for ($i = count($arr); $i < $size; $i++)
			$arr[$i] = '';
		return $arr;
	}

	public static function AddArrayKeys(array $arr1, array $arr2) : array
	{
		$ret = [];
		foreach([$arr1, $arr2] as $arr)
		{
			foreach($arr as $key => $value)
			{
				if (isset($ret[$key]))
					$ret[$key] += $value;
				else
					$ret[$key] = $value;
			}
		}

		return $ret;
	}

	public static function RemoveMissingKeys(array $arr, array $dictionary) : array
	{
		$ret = [];
		foreach($arr as $key => $item)
		{
			if (isset($dictionary[$key]))
				$ret[$key] = $item;
		}
		return $ret;
	}

	public static function ReplaceKeys(array $arr, array $dictionary) : array
	{
		$ret = [];
		foreach($arr as $key => $item)
		{
			$newKey = $dictionary[$key];
			$ret[$newKey] = $item;
		}
		return $ret;
	}

	public static function FromSortedToKeyed(array $arr, $field) : array
	{
		$ret = [];
		$group = [];
		$last = null;
		foreach($arr as $a)
		{
			$id = $a[$field];
			if ($id != $last)
			{
				if ($last !== null)
					$ret[$last] = $group;
				$group = [];
				$last = $id;
			}
			$group[] = $a;
		}
		if ($last !== null)
			$ret[$last] = $group;
		return $ret;
	}

	public static function SanitizeIds(array $arr, bool $omitZeros = true) : array
	{
		$ret = [];
		foreach($arr as $a)
		{
			$a = trim($a);
			if ($a !== "")
			{
				$i = (int)$a;
				if ($i > 0 || $omitZeros == false)
					$ret[] = $i;
			}
		}
		return $ret;
	}

	public static function SortTwoLevelByTopic(array &$items) : void
	{
		self::SortFullNameArray($items);
		foreach($items as &$root)
		{
			if (isset($root['groups']))
				self::SortFullNameArray($root['groups']);
		}
	}

	public static function SortNativeKeyedArray(array &$arr) : void
	{
		ksort($arr);
	}

	public static function SortNativeKeyedArrayDesc(array &$arr) : void
	{
		krsort($arr);
	}

	// Sorter Wrappers

	public static function SortByField(array &$arr, $field) : void
	{
		usort($arr, function($a, $b) use ($field) : int { return Sorter::ByField($a, $b, $field); });
	}

	public static function SortByNamedValue(array &$arr, $name) : void
	{
		usort($arr, function($a, $b) use ($name) : int { return Sorter::ByNamedValue($a, $b, $name); });
	}

	public static function SortByGetter(array &$arr, $getter) : void
	{
		usort($arr, function($a, $b) use ($getter) : int { return Sorter::ByGetter($a, $b, $getter); });
	}

	public static function SortByGetterDesc(array &$arr, $getter) : void
	{
		usort($arr, function($a, $b) use ($getter) : int { return Sorter::ByGetterDesc($a, $b, $getter); });
	}

	public static function SortAssocByKey(array &$arr, string $key) : void
	{
		uasort($arr, function($a, $b) use ($key) : int { return Sorter::ByKey($a, $b, $key); });
	}

	public static function SortAssocByKeyDesc(array &$arr, string $key) : void
	{
		uasort($arr, function($a, $b) use ($key) : int { return Sorter::ByKeyDesc($a, $b, $key); });
	}

	public static function SortAssocBySortKeys(array &$arr) : void
	{
		uasort($arr, function($a, $b) : int { return Sorter::BySortKeys($a, $b); });
	}

	public static function SortAssocByThreeKeysDesc(array &$arr, int $key1, int $key2, int $key3) : void
	{
		uasort($arr, function($a, $b) use ($key1, $key2, $key3) : int { return Sorter::ByThreeKeysDesc($a, $b, $key1, $key2, $key3); });
	}

	public static function SortStringByKey(array &$arr, string $key) : void
	{
		usort($arr, function($a, $b) use ($key) : int { return Sorter::StringByKey($a, $b, $key); });
	}

	public static function SortStringByKeyDesc(array &$arr, string $key) : void
	{
		usort($arr, function($a, $b) use ($key) : int { return Sorter::StringByKeyDesc($a, $b, $key); });
	}

	public static function SortStringByTwoKeys(array &$arr, string $key1, string $key2) : void
	{
		usort($arr, function($a, $b) use ($key1, $key2) : int { return Sorter::StringByTwoKeys($a, $b, $key1, $key2); });
	}

	public static function SortFullNameArray(array &$arr) : void
	{
		usort($arr, function($a, $b) : int { return Sorter::ByFullName($a, $b); });
	}

	public static function SortAttributeEntityArray(array &$arr, string $key) : void
	{
		usort($arr, function($a, $b) use ($key) : int { return Sorter::ByAttribute($a, $b, $key); });
	}

	public static function SortByKey(array &$arr, $key) : void
	{
		usort($arr, function($a, $b) use ($key) : int { return Sorter::ByKey($a, $b, $key); });
	}

	public static function SortByKeyDesc(array &$arr, $key) : void
	{
		usort($arr, function($a, $b) use ($key) : int { return Sorter::ByKeyDesc($a, $b, $key); });
	}

	public static function SortByCleanString(array &$arr, string $key) : void
	{
		usort($arr, function($a, $b) use ($key) : int { return Sorter::ByCleanString($a, $b, $key); });
	}

	public static function SortByKeysArray(array &$arr, string $key) : void
	{
		usort($arr, function($a, $b) use ($key) : int { return Sorter::ByKeysArray($a, $b, $key); });
	}

	public static function SortByWordLengthDesc(array &$arr) : void
	{
		usort($arr, function(string $a, string $b) : int { return Sorter::ByWordLengthDesc($a, $b); });
	}

	public static function SortWordCountDesc(array &$arr) : void
	{
		usort($arr, function(string $a, string $b) : int { return Sorter::ByWordCountDesc($a, $b); });
	}

	public static function ShrinkArray(array $arr, int $size) : array
	{
		$ret = [];
		for ($i = 0; $i < $size; $i++)
			$ret[] = $arr[$i];
		return $ret;
	}

	public static function ConvertToTwoLevelByTopic(array $items) : array
	{
		$ret = [];
		$target = null;
		foreach($items as $group)
		{
			if (self::SafeGet($group, 'isTopic'))
			{
				if ($target != null)
					$ret[] = $target;
				$target = $group;
				$target['groups'] = [];
			}
			else
			{
				if ($target != null)
					$target['groups'][] = $group;
				else
					$ret[] = $group;
			}
		}
		if ($target != null)
			$ret[] = $target;
		return $ret;
	}

	public static function ConvertToFlatByTopic(array $items) : array
	{
		$ret = [];
		foreach($items as $group)
		{
			$ret[] = $group;
			if (isset($group['groups']))
			{
				foreach($group['groups'] as $subgroup)
					$ret[] = $subgroup;
			}
		}
		return $ret;
	}

	public static function AddAt(array $arr, $n, $element) : array
	{
		array_splice($arr, $n, 0, [$element]);
		return $arr;
	}
}
