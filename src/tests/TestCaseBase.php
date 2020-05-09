<?php declare(strict_types=1);

namespace minga\framework\tests;

use PHPUnit\Framework\TestCase;
use minga\framework\Context;
use minga\framework\settings\CacheSettings;

class TestCaseBase extends TestCase
{
	public function RemoteProvider()
	{
		return [
			'Sin Remote' => [null],
			'Con Remote' => [Context::Settings()->Servers()->Current()->publicUrl],
		];
	}

	public function CacheSettingProvider()
	{
		return [
			'Sin Cache' => [CacheSettings::Disabled],
			'Con Cache' => [CacheSettings::Enabled],
		];
	}

	public function __construct($name = null, array $data = [], $dataName = '')
	{
		Context::Settings()->isTesting = true;

		Context::Settings()->Cache()->Enabled = CacheSettings::Disabled;

		// Context::Settings()->Debug()->debug = false;
		// Context::Settings()->Debug()->showErrors = false;
		parent::__construct($name, $data, $dataName);
	}

}
