<?php

namespace minga\framework;

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

class Serializator
{
	public static function Serialize($obj)
	{
		Profiling::BeginTimer();
		$ret = serialize($obj);
		Profiling::EndTimer();
		return $ret;
	}

	public static function Deserialize($text)
	{
		Profiling::BeginTimer();
		$ret = unserialize($text);
		Profiling::EndTimer();
		return $ret;
	}

	public static function CloneArray($arr, $resetId = false)
	{
		$ret = [];
		foreach($arr as $item)
			$ret[] = self::Clone($item, $resetId);
		return $ret;
	}

	public static function Clone($obj, $resetId = false)
	{
		$text = self::Serialize($obj);
		$ret = self::Deserialize($text);
		if ($resetId)
			$ret->Id = null;
		return $ret;
	}

	
	public static function JsonDeserialize($className, $entity){
		$serializer = self::GetSerializer();
		return $serializer->deserialize($entity, $className, 'json');
	}

	private static function GetSerializer()
	{
		$encoders = array(new JsonEncoder());
		$normalizer = new ObjectNormalizer(null, null, null, new ReflectionExtractor());
		$dateTimeNormalizer = new DateTimeNormalizer(array('datetime_format' => 'd-m-Y H:i'));
		$normalizers = array($dateTimeNormalizer, $normalizer);
		$serializer = new Serializer($normalizers, $encoders);
		return $serializer;
	}

	public static function JsonSerialize($entity)
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

