<?php

namespace minga\framework\settings;

class OauthSettings
{
	/** @var array */
	public $Credentials = [
		'facebook' => ['key' => '', 'secret' => '', 'callback' => ''],
		'google' => ['key' => '', 'secret' => '', 'callback' => ''],
	];
}
