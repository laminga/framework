<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\Extensions;

class ExtensionsTest extends TestCaseBase
{
	public function testRemoveInvalidChars() : void
	{
		$this->assertEquals("", Extensions::RemoveInvalidChars(""));
		$this->assertEquals(".ext", Extensions::RemoveInvalidChars(".ext"));
		$this->assertEquals("ext", Extensions::RemoveInvalidChars("ext"));
		$this->assertEquals("ext1", Extensions::RemoveInvalidChars("ext1"));
		$this->assertEquals(".extrar", Extensions::RemoveInvalidChars(".ext.rar"));
		$this->assertEquals("ext", Extensions::RemoveInvalidChars("ext."));
		$this->assertEquals("ext", Extensions::RemoveInvalidChars("ex.t."));
		$this->assertEquals("ext", Extensions::RemoveInvalidChars("ext\0"));
		$this->assertEquals("ext", Extensions::RemoveInvalidChars("e-xát/."));
		$this->assertEquals("ext", Extensions::RemoveInvalidChars("!@#$%%^&*(-+ext<?>{}:"));
	}

	public function testRemoveExtension() : void
	{
		$this->assertEquals("", Extensions::RemoveExtension(""));
		$this->assertEquals("file", Extensions::RemoveExtension("file"));
		$this->assertEquals("file", Extensions::RemoveExtension("file.txt"));
		$this->assertEquals("file.tar", Extensions::RemoveExtension("file.tar.gz"));
		$this->assertEquals("/var/a.b/file", Extensions::RemoveExtension("/var/a.b/file.txt"));
	}

	public function testChangeExtension() : void
	{
		$this->assertEquals("", Extensions::ChangeExtension("", ""));
		$this->assertEquals("file", Extensions::ChangeExtension("file.txt", ""));
		$this->assertEquals("file.txt", Extensions::ChangeExtension("file.pdf", "txt"));
		$this->assertEquals("file.txt", Extensions::ChangeExtension("file.pdf", ".txt"));
		$this->assertEquals("file.txt", Extensions::ChangeExtension("file", ".txt"));
		$this->assertEquals("/var/a.b/file.txt", Extensions::ChangeExtension("/var/a.b/file.pdf", "txt"));
	}
}
