<?php

namespace minga\framework;

class Headers
{
	public static function AcceptAnyCOARS()
	{
		// Resuelve el COARS
		if (array_key_exists('HTTP_ORIGIN', $_SERVER))
			$http_origin = $_SERVER['HTTP_ORIGIN'];
		else
			$http_origin = '*';

		header("Access-Control-Allow-Credentials: true");
		header("Access-Control-Allow-Origin: $http_origin");
		header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Full-Url");
	}
}
