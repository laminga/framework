<?php declare(strict_types=1);

namespace minga\framework\tests;

use minga\framework\Reflection;

final class ReflectionTest extends TestCaseBase
{

	public function testGetMethod()
	{
		$call = [__CLASS__, 'ForTesting'];
		$method = Reflection::GetMethod($call);
		$this->assertEquals($method->class, $call[0]);
		$this->assertEquals($method->name, $call[1]);
	}

	public function testCallArray()
	{
		$this->expectException(\Exception::class);
		$method = [
			[new ForTesting(), 'Method1'],
		];
		Reflection::CallArray($method, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
	}

	public function testGetParams()
	{
		$params = Reflection::GetParams([__CLASS__, 'ForTesting']);
		$this->assertEquals(count($params), self::CANT_PARAMS, 'Count GetParams(ForTesting)');
		$params = Reflection::GetParams([__CLASS__, 'ForTestingNoParams']);
		$this->assertEquals(count($params), 0, 'Count GetParams(ForTestingNoParams)');
	}

	public function testGetParamNames()
	{
		$params = Reflection::GetParamNames([__CLASS__, 'ForTesting']);
		$this->assertEquals(count($params), self::CANT_PARAMS, 'Count GetParamNames(ForTesting)');
		for($i = 0; $i < count($params); $i++)
			$this->assertEquals($params[$i], 'a' . $i, 'Param' . $i);

		$params = Reflection::GetParamNames([__CLASS__, 'ForTestingNoParams']);
		$this->assertEquals(count($params), 0, 'Count GetParamNames(ForTestingNoParams)');
	}

	public function testGetParamType()
	{
		for($i = 0; $i < self::CANT_PARAMS; $i++)
		{
			$param = Reflection::GetParamType([__CLASS__, 'ForTesting'], $i);
			if($i == self::CANT_PARAMS - 1)
				$this->assertEquals($param, __CLASS__, 'Type' . $i);
			else
				$this->assertNull($param, 'Type' . $i);
		}
	}

	public function testInstanciateClass()
	{
		$class = __NAMESPACE__ . '\\ForTesting';
		$instance = Reflection::InstanciateClass($class);
		$this->assertInstanceOf($class, $instance);

		$class = __NAMESPACE__ . '\\ForTestingParams';
		$instance = Reflection::InstanciateClass($class, '', []);
		$this->assertInstanceOf($class, $instance);
	}

	public function testCallMethod()
	{
		$class = __NAMESPACE__ . '\\ForTesting';
		$instance = Reflection::InstanciateClass($class);

		$ret = Reflection::CallMethod([$instance, 'Method1']);
		$this->assertEquals($ret, 'Method1');

		$ret = Reflection::CallMethod([$instance, 'Method2'], 0, 'a');
		$this->assertEquals($ret, 'Method20a');
	}

	public function testCallPrivateStaticMethod()
	{
		$function = 'Method6';
		$class = ForTesting::class;
		$ret = Reflection::CallPrivateStaticMethod($class, $function);
		$this->assertEquals($ret, $function);

		$function = 'Method7';
		$ret = Reflection::CallPrivateStaticMethod($class, $function, 0, 'a');
		$this->assertEquals($ret, $function . '0a');
	}

	public function testCallPrivateMethod()
	{
		$function = 'Method3';
		$instance = new ForTesting();
		$ret = Reflection::CallPrivateMethod($instance, $function);
		$this->assertEquals($ret, $function);

		$function = 'Method4';
		$ret = Reflection::CallPrivateMethod($instance, $function, 0, 'a');
		$this->assertEquals($ret, $function . '0a');
	}

	public function testCallPrivateMethodRef()
	{
		$function = 'Method5';
		$instance = new ForTesting();
		$ref = '';
		$ret = Reflection::CallPrivateMethodRef($instance, $function, 0, $ref);
		$this->assertEquals($ref, 'ref');
		$this->assertEquals($ret, $function . '0');
	}

	const CANT_PARAMS = 6;
	private function ForTesting(string $a0, int $a1, float $a2, bool $a3, array $a4, ReflectionTest $a5) { }

	private function ForTestingNoParams() { }
}

class ForTesting
{
	public function Method1()
	{
		return __FUNCTION__;
	}

	public function Method2(int $a0, string $a1)
	{
		return __FUNCTION__ . $a0 . $a1;
	}

	private function Method3()
	{
		return __FUNCTION__;
	}

	private function Method4(int $a0, string $a1)
	{
		return __FUNCTION__ . $a0 . $a1;
	}

	private function Method5(int $a0, string &$a1)
	{
		$a1 = 'ref';
		return __FUNCTION__ . $a0;
	}

	private static function Method6()
	{
		return __FUNCTION__;
	}

	private static function Method7(int $a0, string $a1)
	{
		return __FUNCTION__ . $a0 . $a1;
	}
}

class ForTestingParams
{
	public function __construct(string $a0, array $a1) { }
}

