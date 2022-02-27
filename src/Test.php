<?php

namespace minga\framework;

//TODO: qué es esta clase?
class Test
{
	private static string $server = '';

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
