<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\Context;
use PHPUnit\Framework\TestCase;

class TestCaseBase extends TestCase
{
	public function RemoteProvider() : array
	{
		return [
			'Sin Remote' => [null],
			'Con Remote' => [Context::Settings()->Servers()->Current()->publicUrl],
		];
	}

	public function __construct(?string $name = null, array $data = [], string $dataName = '')
	{
		Context::Settings()->isTesting = true;
		Context::Settings()->Debug()->debug = false;
		Context::Settings()->Debug()->showErrors = false;

		parent::__construct($name, $data, $dataName);
	}
}
