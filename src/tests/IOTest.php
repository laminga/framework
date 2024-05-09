<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\IO;
use minga\framework\System;

class IOTest extends TestCaseBase
{
	public function testRemoveExtension() : void
	{
		$this->assertEquals("", IO::RemoveExtension(""));
		$this->assertEquals("/", IO::RemoveExtension("/"));
		$this->assertEquals(".", IO::RemoveExtension("."));
		$this->assertEquals("a", IO::RemoveExtension("a"));
		$this->assertEquals("a", IO::RemoveExtension("a."));
		$this->assertEquals("a", IO::RemoveExtension("a.txt"));
		$this->assertEquals("a.txt", IO::RemoveExtension("a.txt.exe"));
		$this->assertEquals("/a", IO::RemoveExtension("/a.txt"));
		$this->assertEquals("a/a", IO::RemoveExtension("a/a.txt"));
		$this->assertEquals("/a/a", IO::RemoveExtension("/a/a.txt"));
		$this->assertEquals("/a/a", IO::RemoveExtension("/a/a"));
		$this->assertEquals("/a.org/a", IO::RemoveExtension("/a.org/a"));
		$this->assertEquals("/a.org/a", IO::RemoveExtension("/a.org/a.txt"));
		$this->assertEquals("/a.org/a.txt", IO::RemoveExtension("/a.org/a.txt.exe"));
		$this->assertEquals("/a/a/a/a/a", IO::RemoveExtension("/a/a/a/a/a"));
		$this->assertEquals(".htaccess", IO::RemoveExtension(".htaccess"));
		$this->assertEquals(".htaccess", IO::RemoveExtension(".htaccess.txt"));
		$this->assertEquals("a/.htaccess", IO::RemoveExtension("a/.htaccess"));

		if(System::IsWindows())
			$this->assertEquals("C:/a/a", IO::RemoveExtension("C:\\a\\a.txt"));
		else
			$this->assertEquals("C:\\a\\a", IO::RemoveExtension("C:\\a\\a.txt"));
	}
}
