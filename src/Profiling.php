<?php

namespace minga\framework;

class Profiling
{
	public static $IsJson = false;

	private static $showQueries = false;
	private static $trimQueries = false;
	private static $progressOnly = false;

	private static $stack = null;
	private static $lockStack = "";
	private static $profileData = null;
	private static $localIsProfiling = null;

	public static function BeginShowQueries($trimQueries = false, $progressOnly = false)
	{
		self::$showQueries = true;
		self::$trimQueries = $trimQueries;
		self::$progressOnly = $progressOnly;
	}

	public static function EndShowQueries()
	{
		self::$showQueries = false;
	}

	public static function ShowQuery($sql, $params, $types)
	{
		if (self::$showQueries === false)
			return;
		if (self::$progressOnly)
		{
			echo '.';
			return;
		}
		//	$e = new ErrorException();
		//	echo $e->getTraceAsString();
		if (self::$trimQueries && strlen($sql) > 50)
			echo 'SQL: ' . substr($sql, 0, 50) . " (..)";
		else
			echo 'SQL: ' . $sql;
		if ($params !== null && count($params) > 0)
		{
			echo " <br>\n";
			print_r($params);
		}
		if ($types !== null && count($types) > 0 && !self::$trimQueries)
		{
			echo " <br>\n";
			print_r($types);
		}
		echo " <br>\n";
	}

	public static function IsProfiling()
	{
		if (self::$localIsProfiling === null)
			self::$localIsProfiling = PhpSession::GetSessionValue("profiling");

		$ses = self::$localIsProfiling;

		if ($ses != "")
			return ($ses == "1");
		if (isset(Context::Settings()->Debug()->profiling) == false)
			return false;

		return Context::Settings()->Debug()->profiling;
	}

	public static function SetProfiling($value)
	{
		if ($value)
			PhpSession::SetSessionValue("profiling", "1");
		else
			PhpSession::SetSessionValue("profiling", "0");
		self::$localIsProfiling = null;
	}

	public static function ShowResults()
	{
		$previous = PhpSession::GetSessionValue("lastProfiling");
		if ($previous != "")
		{
			echo $previous;
			PhpSession::SetSessionValue("lastProfiling", "");
		}

		echo self::GetHtmlResults();
	}

	public static function SaveBeforeRedirect()
	{
		if (self::IsProfiling() == false)
			return;

		PhpSession::SetSessionValue("lastProfiling", self::GetHtmlResults(true));
	}

	public static function GetHtmlResults($saveForLaterFormat = false)
	{
		self::FinishTimers();
		$ret = "";
		if (self::$profileData != null)
		{
			self::$profileData->SumChildren();
			if ($saveForLaterFormat)
				$colorHeader = "green"; // verde
			else
				$colorHeader = "#d445f2"; // rosita
			$ret .= self::EchoTableHeader($colorHeader);
			$ret .= self::RecursiveShow(self::$profileData, 0, self::$profileData->durationMs, self::$profileData->durationMs, true);
			if (self::$IsJson == false)
			{
				$ret .= "</table></div>";
				$ret .= self::$lockStack;
			}
			else
				$ret = explode("\n", $ret);
		}
		return $ret;
	}

	public static function RecursiveShow($profileData, $depth, $totalMs, $parentMs, $isTotal = false, $isUserCode = false)
	{
		if($isUserCode)
		{
			$cellFormat = "<i>";
			$cellFormatClose = "</i>";
		}
		else
		{
			$cellFormat = "";
			$cellFormatClose = "";
		}

		if ($isTotal)
			$tdStyle = "style='background-color: #a0a0a0;'";
		else
		{
			$colorn = min(160 + 32 * $depth, 255);
			$tdStyle = "style='background-color: #" . dechex($colorn) . dechex($colorn) . dechex($colorn) . ";'";
		}
		if ($profileData->hits > 0)
		{
			$duravg = round($profileData->durationMs / $profileData->hits, 0);
			$memavg = round($profileData->memory / $profileData->hits, 0);
			$memPeakavg = round($profileData->memoryPeak / $profileData->hits, 0);
		}
		else
		{
			$duravg = "-";
			$memavg = "-";
			$memPeakavg = "-";
		}

		$ret = self::CreateRow($tdStyle, $cellFormat , $cellFormatClose, $depth,
			$profileData->name,
			$profileData->hits,
			$profileData->dbHits,
			round($profileData->durationMs,0),
			Str::FormatPercentage($profileData->durationMs, $parentMs),
			Str::FormatPercentage($profileData->durationMs, $totalMs),
			$duravg,
			Str::SizeToHumanReadable($profileData->memory, 1) ,
			Str::SizeToHumanReadable($memavg, 1)
		);

		$residual = new ProfilingItem("User code");
		$residual->durationMs = $profileData->durationMs;
		$residual->dbHits = $profileData->dbHits;
		$residual->memory = $profileData->memory;
		$residual->memoryPeak = $profileData->memoryPeak;
		$residual->hits = "-";
		foreach($profileData->children as $child)
		{
			$ret .= self::RecursiveShow($child, $depth + 1, $totalMs, $profileData->durationMs);
			// suma el total para hacer después el residual de user-code
			$residual->durationMs -= $child->durationMs;
			$residual->memory -= $child->memory;
			$residual->dbHits -= $child->dbHits;
			$residual->memoryPeak -= $child->memoryPeak;
		}
		if (count($profileData->children) > 0 && ($residual->durationMs >= 1 || $residual->dbHits >= 1))
		{
			$ret .= self::RecursiveShow($residual, $depth + 1, $totalMs, $profileData->durationMs, false, true);
		}
		return $ret;
	}

	private static function CreateRow($tdStyle, $cellFormat , $cellFormatClose, $depth,
		$v1, $v2, $v3, $v4, $v5, $v6, $v7, $v8, $v9)
	{
		$v1Parts = explode('\\', $v1);
		$v1 = $v1Parts[count($v1Parts) - 1];
		if (self::$IsJson == false)
		{
			return "<tr><td " . $tdStyle . "><div style='padding-left: " . ($depth * 12) . "px'>" . $cellFormat . $v1 . $cellFormatClose . "</div></td>"
				. "<td " . $tdStyle . " align='center'>" . $cellFormat . $v2 . $cellFormatClose . "</td>"
				. "<td " . $tdStyle . " align='center'>" . $cellFormat . $v3 . $cellFormatClose . "</td>"
				. "<td " . $tdStyle . " align='center'>" . $cellFormat . $v4 . $cellFormatClose . "</td>"
				. "<td " . $tdStyle . " align='center'>" . $cellFormat . $v5 . $cellFormatClose . "</td>"
				. "<td " . $tdStyle . " align='center'>" . $cellFormat . $v6 . $cellFormatClose . "</td>"
				. "<td " . $tdStyle . " align='center'>" . $cellFormat . $v7 . $cellFormatClose . "</td>"
				. "<td " . $tdStyle . " align='right'>" . $cellFormat . $v8 . $cellFormatClose . "</td>"
				. "<td " . $tdStyle . " align='right'>" . $cellFormat . $v9 . $cellFormatClose . "</td>"
				. "</tr>";
		}
		else
		{
			return self::FixColWidth(str_repeat("_", $depth * 2) . $v1, 71, true)
				. self::FixColWidth($v2, 7)
				. self::FixColWidth($v3, 7)
				. self::FixColWidth($v4, 7)
				. self::FixColWidth($v5, 8)
				. self::FixColWidth($v6, 8)
				. self::FixColWidth($v7, 11)
				. self::FixColWidth($v8, 11)
				. self::FixColWidth($v9, 11)
				. " \n";
		}
	}

	private static function FixColWidth($val, $width, $textAlignLeft = false)
	{
		$val = trim($val);
		if (strlen($val) >= $width)
			$val = substr($val, 0, $width - 1);
		return str_pad($val, $width, "_", ($textAlignLeft ? STR_PAD_RIGHT : STR_PAD_BOTH));
	}

	private static function EchoTableHeader($colorHeader)
	{
		// headers
		$ret = "";
		if (self::$IsJson == false)
			$ret = "<div class='dProfiling'><table cellpadding='2' class='cellspacing0' style='width:650px; border: 1px solid grey;'>";
		$tdStyle = "style='background-color: " . $colorHeader . "'";
		$cellFormat = "<b>";
		$cellFormatClose = "</b>";

		$ret .= self::CreateRow($tdStyle, $cellFormat , $cellFormatClose, 0,
			'Profiling items',
			'Hits',
			'DbHits',
			'Ms',
			'% share',
			'% total',
			'Avg. Ms',
			'Memory',
			'Avg. Mem.'
		);
		return $ret;
	}

	private static function GetMethodName($isInternalFunction)
	{
		try
		{
			if($isInternalFunction)
				$i = 3;
			else
				$i = 2;
			$bt = debug_backtrace();
			if (!array_key_exists('class', $bt[$i]))
			{
				$bt[$i]['class'] = basename($bt[$i]['file']);
				$bt[$i]['type'] = '->';
			}
			return $bt[$i]['class'] . $bt[$i]['type'] . $bt[$i]['function'];
		}
		catch(\Exception $e)
		{
			return '(error trace)';
		}
	}

	public static function BeginTimer($name = '', $isInternalFunction = false)
	{
		if (self::IsProfiling() == false)
			return;
		if($name === '')
			$name = self::GetMethodName($isInternalFunction);
		if (self::$stack == null)
		{
			self::$stack = [];
			self::$profileData = new ProfilingItem("Total");
		}
		$newItem = new ProfilingItem($name);
		$newItem->isInternal = $isInternalFunction;
		self::$stack [] = $newItem;

		// integridad
		if (count(self::$stack) > 128)
		{
			echo "El stack de Rendimiento ha superado los 128 niveles. Es posible que haya código erróneo iniciando Timers sin finalizarlos.<p>";

			self::ShowResults();
			Context::EndRequest();
		}
	}

	public static function EndTimer()
	{
		if (self::IsProfiling() == false)
			return;

		$index = count(self::$stack) - 1;
		if ($index == -1)
			return;
		$item = self::$stack[$index];
		$item->CompleteTimer();

		self::MergeLastBrachValues();
		self::$stack = Arr::ShrinkArray(self::$stack, $index);
	}

	public static function RegisterDbHit()
	{
		foreach(self::$stack as $item)
			$item->dbHits++;
	}

	public static function AppendLockInfo($info)
	{
		if (self::IsProfiling() == false)
			return;
		self::$lockStack .= $info . " <br>";
	}

	public static function FinishTimers()
	{
		while(count(self::$stack) > 0)
			self::EndTimer();
	}

	private static function MergeLastBrachValues()
	{
		// lo suma en la rama correspondiente
		self::RecursiveMerge(self::$profileData, 0);
	}

	private static function RecursiveMerge($profileData, $depth)
	{
		$targetItem = $profileData->GetChildrenOrCreate(self::$stack[$depth]->name);
		$item = self::$stack[$depth];

		if (count(self::$stack) == $depth + 1)
		{
			// termina
			$targetItem->durationMs += $item->durationMs;
			$targetItem->memory += $item->memory;
			$targetItem->memoryPeak += $item->memoryPeak;
			$targetItem->hits += $item->hits;
			$targetItem->dbHits += $item->dbHits;
		}
		else
			self::RecursiveMerge($targetItem, $depth + 1);
	}

}
