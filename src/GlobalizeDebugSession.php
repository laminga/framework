<?php

namespace minga\framework;

use minga\framework\locking\GlobalDebugLock;

class GlobalizeDebugSession
{
	private static $currentFileValue;
	private static $currentFileDate;
	private static $currentCookie;
	private static $currentCookieTime;
	public const FILE = 'XDEBUG_SESSION.txt';

	public static function GlobalizeDebug()
	{
		$ip = Params::SafeServer('REMOTE_ADDR') . '@' . Params::SafeServer('HTTP_X_FORWARDED_FOR');
		// Lee archivo
		self::readGlobalizedFileValue($ip);
		// Lee Cookie
		self::readCookie();
		// Compara
		if (self::$currentCookie !== '' &&
				(self::$currentFileValue == '' ||
				self::$currentCookieTime > self::$currentFileDate))
		{
			// Respeta cookie
			if (self::$currentCookie !== self::$currentFileValue)
				self::writeGlobalizedFile($ip, self::$currentCookie, self::$currentCookieTime);
		}
		else if (self::$currentFileValue != '')
		{
			Cookies::SetCookie('XDEBUG_SESSION', self::$currentFileValue);
			Cookies::SetCookie('XDEBUG_SESSION_TIME', self::$currentFileDate);
		}
	}

	private static function readCookie()
	{
		self::$currentCookie = Cookies::GetCookie('XDEBUG_SESSION');
		self::$currentCookieTime = intval(Cookies::GetCookie('XDEBUG_SESSION_TIME'));
		if (self::$currentCookie != '' && self::$currentCookieTime == 0)
		{
			self::$currentCookieTime = time();
			Cookies::SetCookie('XDEBUG_SESSION_TIME', self::$currentCookieTime);
		}
	}
	private static function resolveGlobalizedPath()
	{
		return Context::Paths()->GetTempPath() . '/' . self::FILE;
	}
	private static function readGlobalizedFile()
	{
		$path = self::resolveGlobalizedPath();
		if (file_exists($path))
		{
			return IO::ReadJson($path);
		}
		else
			return [];
	}
	private static function readGlobalizedFileValue($ip)
	{
		GlobalDebugLock::BeginRead();
		$arr = self::readGlobalizedFile();
		$val = Arr::SafeGet($arr, $ip, null);
		if ($val === null)
		{
			$val = ['value' => '', 'date' => null];
		}
		self::$currentFileValue = $val['value'];
		self::$currentFileDate = intval($val['date']);
		GlobalDebugLock::EndRead();
	}
	private static function writeGlobalizedFile($ip, $value, $date)
	{
		GlobalDebugLock::BeginWrite();
		$arr = self::readGlobalizedFile();
		$arr[$ip] = ['value' => $value, 'date' => $date];
		$path = self::resolveGlobalizedPath();
		IO::WriteJson($path, $arr);
		GlobalDebugLock::EndWrite();
	}
}
