<?php

namespace minga\framework;

class Headers
{
	//CORS: Cross-Origin Resource Sharing
	public static function AcceptAnyCORS() : void
	{
		$origin = Params::SafeServer('HTTP_ORIGIN', '*');
		header("Access-Control-Allow-Credentials: true");
		header("Access-Control-Allow-Origin: $origin");
		header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
		header("Access-Control-Allow-Headers: Origin,X-Requested-With,Content-Type,Accept,Full-Url,Access-Link");
	}
}
