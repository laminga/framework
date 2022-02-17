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

	public static function InsertAt(array &$arr1, $element, $pos) : array
	{
		array_splice($arr1, $pos, 0, [$element]);
		return $arr1;
	}

	public static function AssocToString(array $arr, $includeKeys = true, $ommitEmpty = false) : string
	{
		$ret = '';
		foreach($arr as $key => $value)
		{
			if ($ommitEmpty == false || $value)
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

	public static function ToString(array $arr, bool $ommitEmpty = false) : string
	{
		$ret = '';
		foreach($arr as $key => $value)
		{
			if ($ommitEmpty == false || $value)
			{
				if ($ret !== '')
					$ret .= ",";
				$ret .= $value;
			}
		}
		return $ret;
	}

	public static function Increment(&$arr, $itemName, $n = 1) : void
	{
		self::CheckSubZero($arr, $itemName);
		$arr[$itemName] += $n;
	}

	public static function CheckSubArray(array &$arr, $itemName) : void
	{
		self::CheckSubItem($arr, $itemName, []);
	}

	public static function CheckSubZero(array &$arr, $itemName) : void
	{
		self::CheckSubItem($arr, $itemName, 0);
	}

	public static function CheckSubItem(array &$arr, $itemName, $item) : void
	{
		if (array_key_exists($itemName, $arr) == false)
			$arr[$itemName] = $item;
	}

	public static function FilterByNamedValue(array $arr, $itemName, $itemValue, $default = "") : array
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

	public static function IndexOfByNamedValue(array $arr, $itemName, $itemValue)
	{
		for($n = 0; $n < count($arr); $n++)
		{
			$current = $arr[$n];
			if (array_key_exists($itemName, $current) && $current[$itemName] == $itemValue)
				return $n;
		}
		return -1;
	}

	public static function IndexOfByProperty(array $arr, $itemProperty, $itemValue)
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

	public static function EatFrom(array $items, $delimiter) : array
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
			if (array_key_exists($key, $roots) == false)
				$roots[$key] = [];

			$roots[$key][] = $item[1];
		}
		return $roots;
	}

	public static function SafeGet(array $arr, $item, $default = "")
	{
		if (array_key_exists($item, $arr))
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
			if (array_key_exists($field, $item))
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

	public static function Remove(array &$array, $value)
	{
		$n = self::IndexOf($array, $value);
		return self::RemoveAt($array, $n);
	}

	public static function RemoveByValue(array $array, $value) : array
	{
		if (array_key_exists($value, $array))
			unset($array[$value]);
		return $array;
	}

	public static function RemoveItemByKeyValue(array $array, $key, $value) : array
	{
		$ret = [];
		foreach($array as $item)
		{
			if (array_key_exists($key, $item) == false
				|| $item[$key] != $value)
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

	public static function GrowArray($arr, $size) : array
	{
		if (is_array($arr) == false)
			$arr = [];
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
				if (array_key_exists($key, $ret))
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
			if (array_key_exists($key, $dictionary))
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

	public static function SanitizeIds(array $arr, bool $ommitZeros = true) : array
	{
		$ret = [];
		foreach($arr as $a)
		{
			$a = trim($a);
			if ($a !== "")
			{
				$i = (int)$a;
				if ($i > 0 || !$ommitZeros)
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
			if (array_key_exists('groups', $root))
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
		usort($arr, function($a, $b) use ($field) { return Sorter::ByField($a, $b, $field); });
	}

	public static function SortByGetter(array &$arr, $getter) : void
	{
		usort($arr, function($a, $b) use ($getter) { return Sorter::ByGetter($a, $b, $getter); });
	}

	public static function SortByGetterDesc(array &$arr, $getter) : void
	{
		usort($arr, function($a, $b) use ($getter) { return Sorter::ByGetterDesc($a, $b, $getter); });
	}

	public static function SortAssocByKey(array &$arr, $key) : void
	{
		uasort($arr, function($a, $b) use ($key) { return Sorter::ByKey($a, $b, $key); });
	}

	public static function SortAssocByKeyDesc(array &$arr, $key) : void
	{
		uasort($arr, function($a, $b) use ($key) { return Sorter::ByKeyDesc($a, $b, $key); });
	}

	public static function SortAssocBySortKeys(array &$arr) : void
	{
		uasort($arr, function($a, $b) { return Sorter::BySortKeys($a, $b); });
	}

	public static function SortAssocByThreeKeysDesc(array &$arr, $key1, $key2, $key3) : void
	{
		uasort($arr, function($a, $b) use ($key1, $key2, $key3) { return Sorter::ByThreeKeysDesc($a, $b, $key1, $key2, $key3); });
	}

	public static function SortStringByKey(array &$arr, $key) : void
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::StringByKey($a, $b, $key); });
	}

	public static function SortStringByKeyDesc(array &$arr, $key) : void
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::StringByKeyDesc($a, $b, $key); });
	}

	public static function SortStringByTwoKeys(array &$arr, $key1, $key2) : void
	{
		usort($arr, function($a, $b) use ($key1, $key2) { return Sorter::StringByTwoKeys($a, $b, $key1, $key2); });
	}

	public static function SortFullNameArray(array &$arr) : void
	{
		usort($arr, function($a, $b) { return Sorter::ByFullName($a, $b); });
	}

	public static function SortAttributeEntityArray(array &$arr, $key) : void
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::ByAttribute($a, $b, $key); });
	}

	public static function SortByKey(array &$arr, $key) : void
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::ByKey($a, $b, $key); });
	}

	public static function SortByKeyDesc(array &$arr, $key) : void
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::ByKeyDesc($a, $b, $key); });
	}

	public static function SortByCleanString(array &$arr, $key) : void
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::ByCleanString($a, $b, $key); });
	}

	public static function SortByKeysArray(array &$arr, $key) : void
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::ByKeysArray($a, $b, $key); });
	}

	public static function SortByWordLengthDesc(array &$arr) : void
	{
		usort($arr, function($a, $b) { return Sorter::ByWordLengthDesc($a, $b); });
	}

	public static function SortWordCountDesc(array &$arr) : void
	{
		usort($arr, function($a, $b) { return Sorter::ByWordCountDesc($a, $b); });
	}

	public static function ShrinkArray(array $arr, $size) : array
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
			if (array_key_exists('groups', $group))
			{
				foreach($group['groups'] as $subgroup)
					$ret[] = $subgroup;
			}
		}
		return $ret;
	}

	public static function CutArrayAndSummarize(array $arr, $newSize) : array
	{
		$total = 0;
		if (count($arr) <= $newSize)
			return $arr;
		$keys = array_keys($arr);
		$ret = [];
		for($i = 0; $i < $newSize; $i++)
			$ret[$keys[$i]] = $arr[$keys[$i]];
		for($i = $newSize; $i < count($arr); $i++)
			$total = (int)($total + $arr[$keys[$i]]);
		$ret['Otros'] = $total;
		return $ret;
	}

	public static function AddAt(array $arr, $n, $element) : array
	{
		array_splice($arr, $n, 0, [$element]);
		return $arr;
	}

	public static function AddShare(array $arr, $unit = "") : array
	{
		$total = 0;
		$keys = array_keys($arr);
		for($i = 0; $i < count($arr); $i++)
			$total = (int)(($total + $arr[$keys[$i]]));
		$ret = [];
		for($i = 0; $i < count($arr); $i++)
		{
			if ($total != 0)
			{
				$pc = round($arr[$keys[$i]] / $total * 100);
				if ($pc == 0)
					$pc = "<1";
			}
			else
				$pc = 0;
			//$ret[$keys[$i] . " (" . $pc . "%)"] = $arr[$keys[$i]];
			$ret[$keys[$i]] = $arr[$keys[$i]] . $unit . " (" . $pc . "%)";
		}
		return $ret;
	}
}
