<?php declare(strict_types=1);

namespace minga\framework\tests;

use Defuse\Crypto\Key;
use minga\framework\settings\KeysSettings;

class KeysSettingsTest extends TestCaseBase
{
	public function testCreateNewRememberKey()
	{
		$ks = new KeysSettings();
		$ret = $ks->CreateNewRememberKey();
		$this->assertEquals(strlen($ret), 184);
	}

	public function testDefuseKey()
	{
		$key = Key::createNewRandomKey();
		$this->assertEquals(strlen($key->saveToAsciiSafeString()), 136);
		$this->assertEquals(strlen($key->getRawBytes()), Key::KEY_BYTE_SIZE);
	}
}


