<?php declare(strict_types=1);

namespace minga\framework\tests;

use minga\framework\Date;
use minga\framework\ErrorException;

class DateTest extends TestCaseBase
{
	public function testFormattedDateToDateTime()
	{
		$this->assertEquals(Date::FormattedDateToDateTime(null), false);
		$this->assertEquals(Date::FormattedDateToDateTime('aaa'), false);
		$this->assertInstanceOf(\DateTime::class, Date::FormattedDateToDateTime('2010-01-01@00.00.01'));
	}

	public function testDateNotPast()
	{
		$this->assertEquals(Date::DateNotPast('', 1), false);
		$this->assertEquals(Date::DateNotPast(null, 1), false);
		$this->assertEquals(Date::DateNotPast('2010-01-01@00.00.01', 1), false);
		$this->assertEquals(Date::DateNotPast('2010-01-01@00.00.01', 100000), true);
		$this->assertEquals(Date::DateNotPast('2222-01-01@00.00.01', 1), true);
	}

	public function testDateNotPastException()
	{
		$this->expectException(ErrorException::class);
		Date::DateNotPast('aaaaaa', 9);
	}

	public function testDateNotPastException1()
	{
		$this->expectException(ErrorException::class);
		Date::DateNotPast('2222-01-01@00.00.01', -1);
	}

	public function testDateNotPastException2()
	{
		$this->expectException(ErrorException::class);
		Date::DateNotPast('2222-01-01@00.00.01', 0);
	}

}

