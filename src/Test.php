<?php

namespace minga\framework;

class Test
{
	private static $server = '';
	public static function SetServer($host) : void
	{
		self::$server = $host;
	}
	public static function WriteLine($text) : void
	{
		echo "<b>" . $text . "</b><br>";
	}
	public static function Get($url) : void
	{
		echo "<a href='" . self::$server . $url . "'>" . $url . "</a><br>";
	}
}
