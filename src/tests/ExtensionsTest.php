<?php

declare(strict_types = 1);

namespace minga\tests;

use minga\framework\tests\TestCaseBase;

class ExtensionsTest extends TestCaseBase
{
	public function testExtensions() : void
	{
		$ext = get_loaded_extensions();
		$exts = array_map('strtolower', $ext);
		sort($exts);
		// Quizás no todas son necesarias
		$required = [
			"bcmath", "calendar", "core", "ctype", "curl",
			"date", "dom", "fileinfo", "filter", "gd",
			"gettext", "gmp", "hash", "iconv", "intl",
			"json", "libxml", "mbstring", "pcre", "pdo",
			"pdo_mysql", "pdo_sqlite", "reflection",
			"session", "simplexml", "sqlite3", "standard",
			"xml", "xmlreader", "xmlwriter", "xsl", "zip",
		];

		$ret = array_diff($required, $exts);
		$this->assertCount(0, $ret, 'Debe habilitar las siguientes extensiones para que el sitio funcione correctamente: ' . implode(', ', $ret));


		$recommended = [
			// "opcache",
		];
		$ret = array_diff($recommended, $exts);
		$this->assertCount(0, $ret, 'Es recomendado habilitar las siguientes extensiones: ' . implode(', ', $ret));
	}
}
