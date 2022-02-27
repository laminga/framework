<?php

namespace minga\framework\settings;

class OauthSettings
{
	public array $Credentials = [
		'facebook' => ['key' => '', 'secret' => '', 'callback' => ''],
		'google' => ['key' => '', 'secret' => '', 'callback' => ''],
	];
}
