<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\Str;
use minga\framework\ErrorException;

class StrTest extends TestCaseBase
{
	public function testDecodeEntities() : void
	{
		$this->assertEquals('a', Str::DecodeEntities('a'));
		$this->assertEquals("l'Écriture", Str::DecodeEntities('l&#39;Écriture'));
		$this->assertEquals("'", Str::DecodeEntities("&amp;#39;"));
	}

	public function testRemoveResumenWord() : void
	{
		$this->assertEquals(Str::RemoveResumenWord('resumen:'), '');
		$this->assertEquals(Str::RemoveResumenWord('RESUMEN:'), '');
		$this->assertEquals(Str::RemoveResumenWord('Resumen:'), '');
		$this->assertEquals(Str::RemoveResumenWord('abstract:'), '');
		$this->assertEquals(Str::RemoveResumenWord('ABSTRACT:'), '');
		$this->assertEquals(Str::RemoveResumenWord('Abstract:'), '');
		$this->assertEquals(Str::RemoveResumenWord('resumen.'), '');
		$this->assertEquals(Str::RemoveResumenWord('RESUMEN.'), '');
		$this->assertEquals(Str::RemoveResumenWord('Resumen.'), '');
		$this->assertEquals(Str::RemoveResumenWord('abstract.'), '');
		$this->assertEquals(Str::RemoveResumenWord('ABSTRACT.'), '');
		$this->assertEquals(Str::RemoveResumenWord('Abstract.'), '');
		$this->assertEquals(Str::RemoveResumenWord('.'), '.');
		$this->assertEquals(Str::RemoveResumenWord(':'), ':');
		$this->assertEquals(Str::RemoveResumenWord(''), '');
		$this->assertEquals(Str::RemoveResumenWord('xxx Abstract.'), 'xxx Abstract.');
		$this->assertEquals(Str::RemoveResumenWord('xxx Resumen.'), 'xxx Resumen.');
		$this->assertEquals(Str::RemoveResumenWord('Abstract. '), ' ');
		$this->assertEquals(Str::RemoveResumenWord('Resumen'), 'Resumen');
		$this->assertEquals(Str::RemoveResumenWord('Abstract'), 'Abstract');
	}

	public function testRemoveBegining() : void
	{
		$this->assertEquals(Str::RemoveBegining('', ''), '');
		$this->assertEquals(Str::RemoveBegining('a', 'a'), '');
		$this->assertEquals(Str::RemoveBegining('aaabc', 'aaa'), 'bc');
		$this->assertEquals(Str::RemoveBegining('a', 'a'), '');
	}

	public function testStartsWithAlfabetic() : void
	{
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

	public function testEllipsis() : void
	{
		$val = '0123456789';
		$this->assertEquals(Str::Ellipsis($val, 10), $val);
		$this->assertEquals(Str::Ellipsis($val, 11), $val);
		$this->assertEquals(Str::Ellipsis($val, 9), '01234567…');
		$this->assertEquals(Str::Ellipsis($val, 1), '…');
		$this->assertEquals(Str::Ellipsis('Tábú', 3), 'Tá…');

		$this->expectException(ErrorException::class);
		$this->assertEquals(Str::Ellipsis($val, 0), $val);
	}
}

