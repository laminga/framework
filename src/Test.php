<?php

namespace minga\framework;

class Test
{
	private static $server = '';
	public static function SetServer($host)
	{
		self::$server = $host;
	}
	public static function WriteLine($text)
	{
		echo "<b>" . $text . "</b><br>";
	}
	public static function Get($url)
	{
		echo "<a href='" . self::$server . $url . "'>" . $url . "</a><br>";
	}
}
