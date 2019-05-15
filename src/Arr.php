<?php

namespace minga\framework;

class Arr
{
	public static function GetItemByNamedValue($arr, $itemName, $itemValue, $default = null)
	{
		$index = self::IndexOfByNamedValue($arr, $itemName, $itemValue);
		if ($index == -1)
			return $default;
		else
			return $arr[$index];
	}

	public static function Increment(&$arr, $itemName, $n = 1)
	{
		self::CheckSubZero($arr, $itemName);
		$arr[$itemName] += $n;
	}

	public static function CheckSubArray(&$arr, $itemName)
	{
		self::CheckSubItem($arr, $itemName, []);
	}

	public static function CheckSubZero(&$arr, $itemName)
	{
		self::CheckSubItem($arr, $itemName, 0);
	}

	public static function CheckSubItem(&$arr, $itemName, $item)
	{
		if (array_key_exists($itemName, $arr) == false)
			$arr[$itemName] = $item;
	}

	public static function FilterByNamedValue($arr, $itemName, $itemValue, $default = "")
	{
		$ret = [];
		foreach($arr as $item)
		{
			if(self::SafeGet($item, $itemName, $default) == $itemValue)
				$ret[] = $item;
		}
		return $ret;
	}

	public static function IndexOfByNamedValue($arr, $itemName, $itemValue)
	{
		for($n = 0; $n < sizeof($arr); $n++)
		{
			$current = $arr[$n];
			if (array_key_exists($itemName, $current) && $current[$itemName] == $itemValue)
				return $n;
		}
		return -1;
	}

	public static function EatFrom($items, $delimiter)
	{
		$ret = [];
		foreach($items as $item)
			$ret[] = Str::EatFrom($item, $delimiter);
		return $ret;
	}

	public static function ExplodeItems($delimiter, $items)
	{
		$ret = [];
		foreach($items as $item)
			$ret[] = explode($delimiter, $item);
		return $ret;
	}

	public static function TreeFromTwoLevel($items)
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

	public static function SafeGet($arr, $item, $default = "")
	{
		if (array_key_exists($item, $arr))
			return $arr[$item];
		else
			return $default;
	}

	public static function RemoveByField($key, $arrayTotal, $arrayItemsToRemove)
	{
		$ret = [];
		foreach($arrayItemsToRemove as $item)
		{
			self::RemoveItemByNamedKey($arrayTotal, $item[$key], $key);
		}
		return $ret;
	}

	public static function UniqueByField($key, $arrayTotal)
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

	public static function RemoveItem(&$array, $item)
	{
		foreach (array_keys($array, $item) as $key)
		{
			unset($array[$key]);
		}
		return $array;
	}

	public static function ToKeyArr($arr)
	{
		$ret = [];
		foreach($arr as $arrItem)
		{
			$keys = array_keys($arrItem);
			$ret[$arrItem[$keys[0]]] = $arrItem[$keys[1]];
		}
		return $ret;
	}

	public static function RemoveAt(&$itemParam, $pos)
	{
		for($n = $pos; $n < sizeof($itemParam) - 1; $n++)
			$itemParam[$n] = $itemParam[$n+1];
		unset($itemParam[sizeof($itemParam) - 1]);
		return $itemParam;
	}

	public static function RemoveItemByNamedKey($array, $name, $key)
	{
		$pos = self::IndexOfByNamedValue($array, $name, $key);
		if ($pos == -1) return;

		return self::RemoveAt($array, $pos);
	}

	public static function RemoveItemByKeyValue($array, $key, $value)
	{
		$ret = [];
		foreach($array as $item)
		{
			if (array_key_exists($key, $item) == false ||
				$item[$key] != $value)
				$ret [] = $item;
		}
		return $ret;
	}

	public static function AppendKeyArray($arr, $appendArray)
	{
		foreach ($appendArray as $key => $value)
			$arr[$key] = $value;
		return $arr;
	}

	public static function GrowArray($arr, $size)
	{
		if (is_array($arr) == false) $arr = [];
		for ($i = sizeof($arr); $i < $size; $i++)
			$arr[$i] = '';
		return $arr;
	}

	public static function FromSortedToKeyed($arr, $field)
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

	public static function SanitizeIds($arr)
	{
		$ret = [];
		foreach($arr as $a)
		{
			$a = trim($a);
			if ($a !== "")
			{
				$i = intval($a);
				if ($i > 0)
					$ret[] = $i;
			}
		}
		return $ret;
	}

	public static function SortTwoLevelByTopic(&$items)
	{
		self::SortFullNameArray($items);
		foreach($items as &$root)
		{
			if (array_key_exists('groups', $root))
				self::SortFullNameArray($root['groups']);
		}
	}

	public static function SortNativeKeyedArray(&$arr)
	{
		ksort($arr);
	}

	public static function SortNativeKeyedArrayDesc(&$arr)
	{
		krsort($arr);
	}

	/*** Sorter Wrappers ***/

	public static function SortByField(&$arr, $field)
	{
		usort($arr, function($a, $b) use ($field) { return Sorter::ByField($a, $b, $field); });
	}

	public static function SortAssocByKey(&$arr, $key)
	{
		uasort($arr, function($a, $b) use ($key) { return Sorter::ByKey($a, $b, $key); });
	}

	public static function SortAssocByKeyDesc(&$arr, $key)
	{
		uasort($arr, function($a, $b) use ($key) { return Sorter::ByKeyDesc($a, $b, $key); });
	}

	public static function SortAssocBySortKeys(&$arr)
	{
		uasort($arr, function($a, $b) { return Sorter::BySortKeys($a, $b); });
	}

	public static function SortAssocByThreeKeysDesc(&$arr, $key1, $key2, $key3)
	{
		uasort($arr, function($a, $b) use ($key1, $key2, $key3) { return Sorter::ByThreeKeysDesc($a, $b, $key1, $key2, $key3); });
	}

	public static function SortStringByKey(&$arr, $key)
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::StringByKey($a, $b, $key); });
	}

	public static function SortStringByKeyDesc(&$arr, $key)
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::StringByKeyDesc($a, $b, $key); });
	}

	public static function SortStringByTwoKeys(&$arr, $key1, $key2)
	{
		usort($arr, function($a, $b) use ($key1, $key2) { return Sorter::StringByTwoKeys($a, $b, $key1, $key2); });
	}

	public static function SortFullNameArray(&$arr)
	{
		usort($arr, function($a, $b) { return Sorter::ByFullName($a, $b); });
	}

	public static function SortAttributeEntityArray(&$arr, $key)
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::ByAttribute($a, $b, $key); });
	}

	public static function SortByKeyDesc(&$arr, $key)
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::ByKeyDesc($a, $b, $key); });
	}

	public static function SortByCleanString(&$arr, $key)
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::ByCleanString($a, $b, $key); });
	}

	public static function SortByKeysArray(&$arr, $key)
	{
		usort($arr, function($a, $b) use ($key) { return Sorter::ByKeysArray($a, $b, $key); });
	}

	public static function SortByWordLengthDesc(&$arr)
	{
		usort($arr, function($a, $b) { return Sorter::ByWordLengthDesc($a, $b); });
	}

	public static function SortWordCountDesc(&$arr)
	{
		usort($arr, function($a, $b) { return Sorter::ByWordCountDesc($a, $b); });
	}

	public static function ShrinkArray($arr, $size)
	{
		$ret = [];
		for ($i = 0; $i < $size; $i++)
			$ret[] = $arr[$i] ;
		return $ret;
	}

	public static function ConvertToTwoLevelByTopic($items)
	{
		$ret = [];
		$target = null;
		foreach($items as $group)
		{
			if (self::SafeGet($group, 'isTopic'))
			{
				if ($target != null)
					$ret[] =$target;
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
			$ret[] =$target;
		return $ret;
	}

	public static  function ConvertToFlatByTopic($items)
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

	public static function CutArrayAndSummarize($arr, $newSize)
	{
		$total = 0;
		if (sizeof($arr) <= $newSize) return $arr;
		$keys = array_keys($arr);
		$ret = [];
		for($i = 0; $i < $newSize; $i++)
			$ret[$keys[$i]] = $arr[$keys[$i]];
		for($i = $newSize; $i < sizeof($arr); $i++)
			$total = intval($total + $arr[$keys[$i]]);
		$ret['Otros'] = $total;
		return $ret;
	}

	public static function AddShare($arr, $unit = "")
	{
		$total = 0;
		$keys = array_keys($arr);
		for($i = 0; $i < sizeof($arr); $i++)
			$total = intval(($total + $arr[$keys[$i]]));
		$ret = [];
		for($i = 0; $i < sizeof($arr); $i++)
		{
			if ($total != 0)
			{
				$pc = round($arr[$keys[$i]] / $total * 100);
				if ($pc==0) $pc = "<1";
			}
			else
				$pc = 0;
			//$ret[$keys[$i] . " (" . $pc . "%)"] = $arr[$keys[$i]];
			$ret[$keys[$i] ] = $arr[$keys[$i]] . $unit . " (" . $pc . "%)";
		}
		return $ret;
	}

}
