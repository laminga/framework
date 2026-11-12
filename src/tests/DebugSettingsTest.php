<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\settings\DebugSettings;
use PHPUnit\Framework\TestCase;

/**
 * La variable de entorno `AA_DEBUG` puede apagar el debug de la instancia, pero nunca encenderlo.
 *
 * @group aislada
 */
class DebugSettingsTest extends TestCase
{
	private string|false $original;

	protected function setUp() : void
	{
		$this->original = getenv('AA_DEBUG');
	}

	protected function tearDown() : void
	{
		if ($this->original === false)
		{
			putenv('AA_DEBUG');
		}
		else
		{
			putenv('AA_DEBUG=' . $this->original);
		}
	}

	private function ActiveSettings() : DebugSettings
	{
		$settings = new DebugSettings();
		$settings->debug = true;
		$settings->showErrors = true;
		return $settings;
	}

	public static function OffValues() : array
	{
		return [
			'cero' => ['0'],
			'off' => ['off'],
			'false en mayúsculas' => ['FALSE'],
			'con espacios' => [' 0 '],
		];
	}

	/**
	 * @dataProvider OffValues
	 */
	public function testTheVariableTurnsTheDebugOff(string $value) : void
	{
		putenv('AA_DEBUG=' . $value);
		$settings = $this->ActiveSettings();

		$settings->ApplyEnvironmentOverride();

		$this->assertFalse($settings->debug);
		$this->assertFalse($settings->showErrors);
	}

	public function testWithoutTheVariableTheSettingsAreKept() : void
	{
		putenv('AA_DEBUG');
		$settings = $this->ActiveSettings();

		$settings->ApplyEnvironmentOverride();

		$this->assertTrue($settings->debug);
		$this->assertTrue($settings->showErrors);
	}

	public function testTheVariableNeverTurnsTheDebugOn() : void
	{
		putenv('AA_DEBUG=1');
		$settings = new DebugSettings();

		$settings->ApplyEnvironmentOverride();

		$this->assertFalse($settings->debug);
	}
}
