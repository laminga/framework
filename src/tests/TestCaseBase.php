<?php declare(strict_types=1);

namespace minga\framework\tests;

use PHPUnit\Framework\TestCase;
use minga\framework\Context;

class TestCaseBase extends TestCase
{
	public function RemoteProvider()
	{
		return [
			'Sin Remote' => [null],
			'Con Remote' => [Context::Settings()->Servers()->Current()->publicUrl],
		];
	}

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		Context::Settings()->isTesting = true;

		// Context::Settings()->Debug()->debug = false;
		// Context::Settings()->Debug()->showErrors = false;
		parent::__construct($name, $data, $dataName);
	}

}
