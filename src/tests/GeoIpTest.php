<?php declare(strict_types=1);

namespace minga\framework\tests;

use minga\framework\GeoIp;
use minga\framework\Date;

class GeoIpTest extends TestCaseBase
{
	public function testGetCurrentLatLong()
	{
		$loc = GeoIp::GetCurrentLatLong();
		$this->assertIsNumeric($loc['lat']);
		$this->assertIsNumeric($loc['lon']);
	}

	public function testDatabaseUpdated()
	{
		$now = Date::DateTimeNow();

		$date = GeoIp::GetCountryDatabaseDatetime();		
		$this->assertLessThan(360, Date::DaysDiff($now, $date), "La base de datos de países de GeoIP tiene más de un año de antigüedad. Debe ser actualizada para proveer de resultados confiables.");

		$date = GeoIp::GetCityDatabaseDatetime();		
		$this->assertLessThan(360, Date::DaysDiff($now, $date), "La base de datos de ciudades de GeoIP tiene más de un año de antigüedad. Debe ser actualizada para proveer de resultados confiables.");
	}

	public function testGetCountryName()
	{
		$loc = GeoIp::GetCountryName('123.123.123.123');
		$this->assertEquals('China', $loc);
	}

	public function testGetClientCountryCode()
	{
		$loc = GeoIp::GetClientCountryCode();
		if(isset($_SERVER['REMOTE_ADDR']))
			$this->assertNotEquals('--', $loc);
		else
			$this->assertEquals('--', $loc);
	}

	public function testGetNameFromCode()
	{
		$loc = GeoIp::GetNameFromCode('AR');
		$this->assertEquals('Argentina', $loc);
	}
}


