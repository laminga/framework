<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\Context;
use minga\framework\IO;
use minga\framework\WebConnection;

class WebConnectionTest extends TestCaseBase
{
	public function setUp() : void
	{
		$this->Clean();
	}

	public function tearDown() : void
	{
		$this->Clean();
	}

	private function Clean() : void
	{
		IO::Delete(Context::Paths()->GetTempPath() . '/README.md');
		IO::Delete(Context::Paths()->GetTempPath() . '/cookie.txt');
		IO::Delete(Context::Paths()->GetTempPath() . '/log.txt');
		IO::Delete(Context::Paths()->GetTempPath() . '/log.txt.extra.txt');
		IO::Delete(Context::Paths()->GetTempPath() . '/response.dat');
	}

	public function testGet() : void
	{
		$base = Context::Paths()->GetTempPath();
		$url = "https://raw.githubusercontent.com/laminga/framework/refs/heads/master/README.md";
		$size = filesize(realpath(Context::Paths()->GetFrameworkPath() . '/../README.md'));

		$wc = new WebConnection();
		$wc->Initialize($base);
		$wc->Get($url, $base . "/README.md");
		$wc->Finalize();
		$this->assertTrue(file_exists($base . '/README.md'));
		$this->assertEquals(filesize($base . '/README.md'), $size);
		$this->assertTrue(file_exists($base . '/log.txt'), 'log file');
		$this->assertTrue(file_exists($base . '/log.txt.extra.txt'), 'log extra file');
		$this->assertTrue(file_exists($base . '/response.dat'), 'response dat file');
		$this->assertTrue(file_exists($base . '/cookie.txt'), 'cookie file');
	}
}

