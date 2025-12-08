<?php

declare(strict_types = 1);

namespace minga\framework\tests;

use minga\framework\ErrorException;
use minga\framework\Str;

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

	public function testFixEncoding() : void
	{
		$this->assertEquals(Str::FixEncoding(""), "");
		$this->assertEquals(Str::FixEncoding("PruebÂµ de niÂ±o"), "Pruebõ de niño");
		$this->assertEquals(Str::FixEncoding("ÃºÂ©"), "úé");
		$this->assertEquals(Str::FixEncoding("Â©"), "é");
		$this->assertEquals(Str::FixEncoding("Â"), "Â");

		$in = 'Â¡Â¢Â£Â¤Â¥Â¦Â§Â¨Â©ÂªÂ«Â­Â®Â¯Â°Â±Â²Â³Â´ÂµÂ·Â¸Â¹ÂºÂ»Â¼Â½Â¾Â¿Ã€ÃÃ‚ÃƒÃ„Ã…Ã†Ã‡ÃˆÃ‰ÃŠÃ‹ÃŒÃŽÃ‘Ã’Ã“Ã”Ã•Ã–Ã—Ã˜Ã™ÃšÃ›ÃœÃžÃŸÃ¡Ã¢Ã£Ã¤Ã¥Ã¦Ã§Ã¨Ã©ÃªÃ«Ã­Ã®Ã¯Ã°Ã±Ã²Ã³Ã´ÃµÃ·Ã¸Ã¹ÃºÃ»Ã¼Ã½Ã¾Ã¿';
		$out = 'áâãäåæçèéêëíîïðñòóôõ÷øùúûüýþÿÀÁÂÃÄÅÆÇÈÉÊËÌÎÑÒÓÔÕÖ×ØÙÚÛÜÞßáâãäåæçèéêëíîïðñòóôõ÷øùúûüýþÿ';
		$this->assertEquals(Str::FixEncoding($in), $out);
	}

	public function testRemoveAccents() : void
	{
		$table = [
			'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Ç' => 'C',
			'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I',
			'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
			'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'à' => 'a', 'á' => 'a',
			'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
			'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n',
			'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ù' => 'u', 'ú' => 'u',
			'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y', 'Ā' => 'A', 'ā' => 'a', 'Ă' => 'A',
			'ă' => 'a', 'Ą' => 'A', 'ą' => 'a', 'Ć' => 'C', 'ć' => 'c', 'Ĉ' => 'C', 'ĉ' => 'c',
			'Ċ' => 'C', 'ċ' => 'c', 'Č' => 'C', 'č' => 'c', 'Ď' => 'D', 'ď' => 'd', 'Ē' => 'E',
			'ē' => 'e', 'Ĕ' => 'E', 'ĕ' => 'e', 'Ė' => 'E', 'ė' => 'e', 'Ę' => 'E', 'ę' => 'e',
			'Ě' => 'E', 'ě' => 'e', 'Ĝ' => 'G', 'ĝ' => 'g', 'Ğ' => 'G', 'ğ' => 'g', 'Ġ' => 'G',
			'ġ' => 'g', 'Ģ' => 'G', 'ģ' => 'g', 'Ĥ' => 'H', 'ĥ' => 'h', 'Ĩ' => 'I', 'ĩ' => 'i',
			'Ī' => 'I', 'ī' => 'i', 'Ĭ' => 'I', 'ĭ' => 'i', 'Į' => 'I', 'į' => 'i', 'Ĵ' => 'J',
			'ĵ' => 'j', 'Ķ' => 'K', 'ķ' => 'k', 'Ĺ' => 'L', 'ĺ' => 'l', 'Ļ' => 'L', 'ļ' => 'l',
			'Ľ' => 'L', 'ľ' => 'l', 'Ń' => 'N', 'ń' => 'n', 'Ņ' => 'N', 'ņ' => 'n', 'Ň' => 'N',
			'ň' => 'n', 'Ō' => 'O', 'ō' => 'o', 'Ŏ' => 'O', 'ŏ' => 'o', 'Ő' => 'O', 'ő' => 'o',
			'Ŕ' => 'R', 'ŕ' => 'r', 'Ŗ' => 'R', 'ŗ' => 'r', 'Ř' => 'R', 'ř' => 'r', 'Ś' => 'S',
			'ś' => 's', 'Ŝ' => 'S', 'ŝ' => 's', 'Ş' => 'S', 'ş' => 's', 'Š' => 'S', 'š' => 's',
			'Ţ' => 'T', 'ţ' => 't', 'Ť' => 'T', 'ť' => 't', 'Ũ' => 'U', 'ũ' => 'u', 'Ū' => 'U',
			'ū' => 'u', 'Ŭ' => 'U', 'ŭ' => 'u', 'Ů' => 'U', 'ů' => 'u', 'Ű' => 'U', 'ű' => 'u',
			'Ų' => 'U', 'ų' => 'u', 'Ŵ' => 'W', 'ŵ' => 'w', 'Ŷ' => 'Y', 'ŷ' => 'y', 'Ÿ' => 'Y',
			'Ź' => 'Z', 'ź' => 'z', 'Ż' => 'Z', 'ż' => 'z', 'Ž' => 'Z', 'ž' => 'z', 'Ơ' => 'O',
			'ơ' => 'o', 'Ǔ' => 'U', 'ǔ' => 'u', 'Ǖ' => 'U', 'ǖ' => 'u', 'Ǘ' => 'U', 'ǘ' => 'u',
			'Ǚ' => 'U', 'ǚ' => 'u', 'Ǜ' => 'U', 'ǜ' => 'u', 'Ǻ' => 'A', 'ǻ' => 'a',

			// reemplazos individuales
			'Æ' => 'AE', 'ß' => 'ss', 'ẞ' => 'SS', 'æ' => 'ae', 'Đ' => 'D', 'đ' => 'd',
			'ħ' => 'h', 'ı' => 'i', 'ĸ' => 'k', 'Ŀ' => 'L', 'ŀ' => 'l', 'Ł' => 'L',
			'ł' => 'l', 'ŉ' => 'N', 'Ŋ' => 'N', 'ŋ' => 'n', 'Œ' => 'OE', 'œ' => 'oe', 'Ŧ' => 'T',
			'ŧ' => 't', 'Ǽ' => 'AE', 'ǽ' => 'ae',
		];
		foreach($table as $k => $v)
			$this->assertEquals(Str::RemoveAccents($k), $v);
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

