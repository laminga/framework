<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\enums\MailTypeError;
use minga\framework\Log;
use minga\framework\tests\TestCaseBase;

class LogTest extends TestCaseBase
{
	public function testLogError() : void
	{
		$text = Log::LogError(1000, "Log error Test", "nofile.php", 25);
		$this->assertTrue($text != '');
	}

	public function testLogPutToMail() : void
	{
		$text = 'Test de Log::PutToMail';
		$this->assertTrue(Log::PutToMail($text, MailTypeError::Error));
	}
}
