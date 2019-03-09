<?php

namespace minga\framework;

class ModifiedSince
{
	public static function AddCacheHeadersByFile($filename)
	{
		/*if (Request::IsGoogle())
		{
			$text = "HTTP_IF_MODIFIED_SINCE=";
			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) == false)
				$text .= "NOTSET;";
			else
			{
				$text .= $_SERVER['HTTP_IF_MODIFIED_SINCE'] . ";";
				$c = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
				$text .= "strtotime=" . $c . ";";
			}
			$timeStamp = Zipping::FileMTime($filename);
			$text .= "TIMESTAMP=" . $timeStamp . ";";
			$text .= "_SERVER=". print_r($_SERVER, true);
			Log::HandleSilentException(new ErrorException("Google crawled download: " . $text));
		}*/
		return self::AddCacheHeaders(Zipping::FileMTime($filename));
	}
	public static function AddCacheHeaders($timeStamp)
	{
		if($timeStamp === false)
			$timeStamp = time();

		$tsHeader = gmdate('D, d M Y H:i:s ', $timeStamp) . 'GMT';

		header("Last-Modified: " . $tsHeader);
		header("Cache-Control: private");

		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
			&& ($timeStamp <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])
				|| $tsHeader == $_SERVER['HTTP_IF_MODIFIED_SINCE']))
		{
			header('HTTP/1.0 304 Not Modified');
			return false;
		}
		return true;
	}

	public static function ProcessIfModified($file, $articleFile = null)
	{
		$date = self::CalculateIfModifiedDate($file, $articleFile);
		if ($date != null)
		{
			if (self::AddCacheHeaders($date) == false)
			{
				Performance::AddControllerSuffix("Headers");
				Context::EndRequest();
			}
		}
	}

	private static function CalculateIfModifiedDate($file1, $file2 = null)
	{
		$timeStamp2 = null;
		$timeStamp1 = null;

		if (file_exists($file1))
		{
			$timeStamp1 = IO::FileMTime($file1);
			if($timeStamp1 === false)
				$timeStamp1 = null;
		}
		if ($file2 != null && Zipping::FileExists($file2))
		{
			$timeStamp2 = Zipping::FileMTime($file2);
			if($timeStamp2 === false)
				$timeStamp2 = null;
		}
		if ($timeStamp1 === null || ($timeStamp2 !== null && $timeStamp2 > $timeStamp1))
			$timeStamp1 = $timeStamp2;

		if ($timeStamp1 != null)
		{
			// Tiene una fecha válida...
			$generalTimeObj = \DateTime::createFromFormat('d/m/Y', Context::Settings()->forceIfModifiedReload);
			$generalTime = $generalTimeObj->getTimeStamp();
			if ($generalTime > $timeStamp1)
				$timeStamp1 = $generalTime;

			return $timeStamp1;
		}
		return null;
	}
}

