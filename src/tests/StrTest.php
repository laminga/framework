<?php declare(strict_types=1);

namespace minga\framework\tests;

use minga\framework\Str;

class StrTest extends TestCaseBase
{

	public function testStartsWithAlfabetic()
	{
		$this->assertTrue(Str::StartsWithAlfabetic('a'));
		$this->assertFalse(Str::StartsWithAlfabetic('1'));
		$this->assertFalse(Str::StartsWithAlfabetic(''));

		$this->markTestIncomplete('Corregir bugs');
		//TODO: Esta función tiene bugs, no tiene en
		// cuenta los siguientes casos, corregir y
		// descomentar los tests:
		$this->assertTrue(Str::StartsWithAlfabetic('A'));
		$this->assertTrue(Str::StartsWithAlfabetic('á'));
		$this->assertTrue(Str::StartsWithAlfabetic('Á'));
	}

}


