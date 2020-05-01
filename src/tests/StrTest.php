<?php declare(strict_types=1);

namespace minga\framework\tests;

use minga\framework\Str;

class StrTest extends TestCaseBase
{
	public function testStartsWithAlfabetic()
	{
		$this->assertFalse(Str::StartsWithAlfabetic(null));
		$this->assertFalse(Str::StartsWithAlfabetic(''));
		$this->assertFalse(Str::StartsWithAlfabetic('%'));
		$this->assertFalse(Str::StartsWithAlfabetic('1'));

		$this->assertTrue(Str::StartsWithAlfabetic('a'));
		$this->assertTrue(Str::StartsWithAlfabetic('A'));
		$this->assertTrue(Str::StartsWithAlfabetic('á'));
		$this->assertTrue(Str::StartsWithAlfabetic('Á'));
	}

}


