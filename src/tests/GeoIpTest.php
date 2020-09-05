<?php declare(strict_types=1);

namespace minga\framework\tests;

use minga\framework\GeoIp;

class GeoIpTest extends TestCaseBase
{
	public function testGetCurrentLatLong()
	{
		$loc = GeoIp::GetCurrentLatLong();
		$this->assertIsNumeric($loc['lat']);
		$this->assertIsNumeric($loc['lon']);
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


