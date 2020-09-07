<?php declare(strict_types=1);

namespace minga\framework\tests;

use minga\framework\Reflection;
use minga\framework\Traffic;

class TrafficTest extends TestCaseBase
{
	public function testGetDevice()
	{
		$instance = new Traffic();
		$ret = Reflection::CallPrivateMethod($instance, 'GetDevice');
		$this->assertEquals('Computadora', $ret);
	}
}


