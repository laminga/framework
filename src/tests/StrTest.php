<?php declare(strict_types=1);

namespace minga\framework\tests;

use minga\framework\Str;

class StrTest extends TestCaseBase
{
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

}


