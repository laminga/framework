<?php

namespace minga\framework;

class Headers
{
	//TODO: renombrar a CORS
	public static function AcceptAnyCOARS() : void
	{
		// Resuelve el CORS
		$origin = Params::SafeServer('HTTP_ORIGIN', '*');
		header("Access-Control-Allow-Credentials: true");
		header("Access-Control-Allow-Origin: $origin");
		header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
		header("Access-Control-Allow-Headers: Origin,X-Requested-With,Content-Type,Accept,Full-Url,Access-Link");
	}
}
