<?php declare(strict_types=1);

namespace minga\framework\tests;

use minga\framework\Str;

class StrTest extends TestCaseBase
{
	public function testDecodeEntities() : void
	{
		$this->assertEquals('a', Str::DecodeEntities('a'));
		$this->assertEquals("l'Écriture", Str::DecodeEntities('l&#39;Écriture'));
	}

	public function testStartsWithAlfabetic() : void
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

	public function testIsEmail() : void
	{
		$this->assertTrue(Str::IsEmail('a@b.c'));
		$this->assertTrue(Str::IsEmail('example@example.com'));
		$this->assertTrue(Str::IsEmail('example@met.museum'));

		$this->assertFalse(Str::IsEmail(''));
		$this->assertFalse(Str::IsEmail('a@*.com'));
		$this->assertFalse(Str::IsEmail('sinarroba'));
		$this->assertFalse(Str::IsEmail('sin@punto'));
		$this->assertFalse(Str::IsEmail('sindominio@.com'));
		$this->assertFalse(Str::IsEmail('@sindireccion.com'));
		$this->assertFalse(Str::IsEmail('direccionsuperlarga'
			. 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
			. 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
			. 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
			. 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
			. '@dominio.com'));
	}

	public function testEllipsis()
	{
		$val = '0123456789';
		$this->assertEquals(Str::Ellipsis($val, 10), $val);
		$this->assertEquals(Str::Ellipsis($val, 11), $val);
		$this->assertEquals(Str::Ellipsis($val, 9), '01234567…');
		$this->assertEquals(Str::Ellipsis($val, 1), '…');
		$this->assertEquals(Str::Ellipsis($val, 0), $val);
		$this->assertEquals(Str::Ellipsis('Tábú', 3), 'Tá…');
	}
}

