<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\ErrorException;
use minga\framework\Reflection;
use minga\framework\Traffic;

class TrafficTest extends TestCaseBase
{
	public function testGetIpLastPart() : void
	{
		$instance = new Traffic();
		$ret = $this->doTestGetIpLastPart('123.123.123.123');
		$this->assertEquals(123, $ret);
		$ret = $this->doTestGetIpLastPart('123.123.123.0');
		$this->assertEquals(0, $ret);
		$ret = $this->doTestGetIpLastPart('2001:db8:3333:4444:5555:6666:7777:8888');
		$this->assertEquals(136, $ret);
		$ret = $this->doTestGetIpLastPart('2001:db8:3333::');
		$this->assertEquals(0, $ret);
		$ret = $this->doTestGetIpLastPart('2001:db8:3333::FFFF');
		$this->assertEquals(255, $ret);
	}

	public function testGetIpLastPartException() : void
	{
		$instance = new Traffic();
		$this->expectException(ErrorException::class);
		Reflection::CallPrivateMethod($instance, 'GetIpLastPart', '');
	}

	public function doTestGetIpLastPart(string $ip) : int
	{
		$instance = new Traffic();
		return (int)Reflection::CallPrivateMethod($instance, 'GetIpLastPart', $ip);
	}
}


