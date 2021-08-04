<?php declare(strict_types=1);

namespace minga\framework\tests;

use minga\framework\Context;
use minga\framework\ExifTool;
use minga\framework\IO;

class ExifToolTest extends TestCaseBase
{

	protected $testFile = '';

	public function setUp() : void
	{
		if(file_exists(ExifTool::GetBinary()) == false)
			$this->markTestSkipped('No está instalado ExifTool.');

		$this->testFile = IO::GetTempFilename() . '.pdf';
		IO::Copy(Context::Paths()->GetFrameworkTestDataPath() . '/test_file.pdf', $this->testFile);
	}

	public function tearDown() : void
	{
		IO::Delete($this->testFile);
	}

	public function testUpdateMetadata() : void
	{
		$ret = ExifTool::UpdateMetadata($this->testFile, '"a"' . "\n" . "\\", 'a');
		$this->assertTrue($ret);
	}
}


