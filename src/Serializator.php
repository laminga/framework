<?php

namespace minga\framework;

use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Serializator
{
	public static function Serialize($obj) : string
	{
		Profiling::BeginTimer();
		$ret = serialize($obj);
		Profiling::EndTimer();
		return $ret;
	}

	public static function Deserialize(?string $text)
	{
		Profiling::BeginTimer();
		$ret = unserialize($text);
		Profiling::EndTimer();
		return $ret;
	}

	public static function CloneArray(array $arr, bool $resetId = false) : array
	{
		$ret = [];
		foreach($arr as $item)
			$ret[] = self::Clone($item, $resetId);
		return $ret;
	}

	public static function Clone($obj, bool $resetId = false)
	{
		$text = self::Serialize($obj);
		$ret = self::Deserialize($text);
		if ($resetId)
			$ret->Id = null;
		return $ret;
	}

	public static function JsonDeserialize(string $className, $entity)
	{
		$serializer = self::GetSerializer();
		return $serializer->deserialize($entity, $className, 'json');
	}

	private static function GetSerializer() : Serializer
	{
		$encoders = [new JsonEncoder()];
		$normalizer = new ObjectNormalizer(null, null, null, new ReflectionExtractor());
		$dateTimeNormalizer = new DateTimeNormalizer(['datetime_format' => 'd-m-Y H:i']);
		$normalizers = [$dateTimeNormalizer, $normalizer];
		return new Serializer($normalizers, $encoders);
	}

	public static function JsonSerialize($entity) : string
	{
		Profiling::BeginTimer();
		$context = new SerializationContext();
		$context->setSerializeNull(true);
		$serializer = SerializerBuilder::create()->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())->build();
		$value = $serializer->serialize($entity, 'json', $context);
		Profiling::EndTimer();
		return $value;
	}
}

