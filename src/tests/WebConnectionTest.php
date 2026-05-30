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
		IO::Delete(Context::Paths()->GetTempPath() . '/phpunit.xml');
		IO::Delete(Context::Paths()->GetTempPath() . '/cookie.txt');
		IO::Delete(Context::Paths()->GetTempPath() . '/log.txt');
		IO::Delete(Context::Paths()->GetTempPath() . '/log.txt.extra.txt');
		$files = glob(Context::Paths()->GetTempPath() . '/response*.dat');
		foreach($files as $file)
			IO::Delete($file);
	}

	public function testGet() : void
	{
		$base = Context::Paths()->GetTempPath();
		$url = "https://raw.githubusercontent.com/laminga/framework/refs/heads/master/phpunit.xml";
		$size = filesize(realpath(Context::Paths()->GetFrameworkPath() . '/../phpunit.xml'));

		$wc = new WebConnection();
		$wc->Initialize($base);
		$wc->Get($url, $base . "/phpunit.xml");
		$wc->Finalize();
		$this->assertTrue(file_exists($base . '/phpunit.xml'));
		$this->assertEquals(filesize($base . '/phpunit.xml'), $size);
		$this->assertTrue(file_exists($base . '/log.txt'), 'log file');
		$this->assertTrue(file_exists($base . '/log.txt.extra.txt'), 'log extra file');
		$this->assertTrue(file_exists($base . '/cookie.txt'), 'cookie file');
		$response = glob($base . '/response*.dat');
		$this->assertNotEmpty($response, "response dat file");
	}
}

