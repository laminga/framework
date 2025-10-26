<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\Date;
use minga\framework\GeoIp;
use minga\framework\System;

class GeoIpTest extends TestCaseBase
{
	public function testGetCurrentLatLong() : void
	{
		$loc = GeoIp::GetCurrentLatLong();
		if(System::IsCli() && GeoIp::GetCurrentIp() == "")
			$this->assertNull($loc);
		else
		{
			$this->assertIsNumeric($loc['lat']);
			$this->assertIsNumeric($loc['lon']);
		}
	}

	public function testDatabaseUpdated() : void
	{
		$now = Date::DateTimeNow();

		$date = GeoIp::GetCountryDatabaseDatetime();
		$this->assertLessThan(360, Date::DaysDiff($now, $date), "La base de datos de países de GeoIP tiene más de un año de antigüedad. Debe ser actualizada para proveer de resultados confiables.");

		$date = GeoIp::GetCityDatabaseDatetime();
		$this->assertLessThan(360, Date::DaysDiff($now, $date), "La base de datos de ciudades de GeoIP tiene más de un año de antigüedad. Debe ser actualizada para proveer de resultados confiables.");
	}

	public function testGetCountryName() : void
	{
		$loc = GeoIp::GetCountryName('123.123.123.123');
		$this->assertEquals('China', $loc);
	}

	public function testGetClientCountryCode() : void
	{
		$_SERVER['REMOTE_ADDR'] = '';
		$loc = GeoIp::GetClientCountryCode();
		$this->assertEquals('--', $loc, $_SERVER['REMOTE_ADDR']);

		$_SERVER['REMOTE_ADDR'] = '77.111.247.71';
		$loc = GeoIp::GetClientCountryCode();
		$this->assertEquals('NO', $loc, $_SERVER['REMOTE_ADDR']);

		$_SERVER['REMOTE_ADDR'] = '100.64.1.2';
		$loc = GeoIp::GetClientCountryCode();
		$this->assertEquals('--', $loc, $_SERVER['REMOTE_ADDR']);

		$_SERVER['REMOTE_ADDR'] = '152.170.72.21';
		$loc = GeoIp::GetClientCountryCode();
		$this->assertEquals('AR', $loc, $_SERVER['REMOTE_ADDR']);

		$_SERVER['REMOTE_ADDR'] = '23.12.155.1';
		$loc = GeoIp::GetClientCountryCode();
		$this->assertEquals('AR', $loc, $_SERVER['REMOTE_ADDR']); }

		public function testGetNameFromCode() : void
		{
			$loc = GeoIp::GetNameFromCode('AR');
			$this->assertEquals('Argentina', $loc);
	}
}


