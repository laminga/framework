<?php

namespace minga\framework\oauth;

class OauthGoogle extends OauthConnector
{
	public const Provider = 'google';

	protected function GetFields()
	{
		return ['userinfo_email', 'userinfo_profile'];
	}

	protected function GetData()
	{
		$data = json_decode($this->service->request('userinfo'), true);

		$oauthData = new OauthData();
		$oauthData->SetGoogleData($data);
		return $oauthData;
	}

	protected function DataGranted()
	{
		return true;
	}
}
