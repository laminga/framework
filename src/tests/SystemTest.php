<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\Context;
use minga\framework\System;
use minga\framework\IO;

class SystemTest extends TestCaseBase
{
	public function tearDown() : void
	{
		IO::Delete(Context::Paths()->GetRoot() . '/testSystem');
	}

	public function testIsNearRelease() : void
	{
		$file = Context::Paths()->GetRoot() . '/testSystem';
		\touch($file);
		$this->assertTrue(System::IsNearRelease(2, basename($file)));
		IO::Delete($file);

		\touch($file, strtotime("now -1 day"));
		$this->assertTrue(System::IsNearRelease(2, basename($file)));
		IO::Delete($file);

		\touch($file, strtotime("now -2 day"));
		usleep(10000);
		$this->assertFalse(System::IsNearRelease(2, basename($file)));
		IO::Delete($file);

		\touch($file, strtotime("now -3 day"));
		$this->assertFalse(System::IsNearRelease(2, basename($file)));
		IO::Delete($file);
	}

}

