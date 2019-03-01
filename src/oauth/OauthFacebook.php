<?php

namespace minga\framework\oauth;

class OauthFacebook extends OauthConnector
{
	const Provider = 'facebook';

	// http://demos.idiotminds.com/social/
	// &display=popup

	protected function GetFields()
	{
		return array('email', 'public_profile');
	}

	//No se está usando
	protected function DataGranted()
	{
		$result = json_decode($this->service->request('/me/permissions'), true);
		// array ('data' =>
		// 	array (
		// 		array ('permission' => 'email', 'status' => 'granted'),
		// 		array ('permission' => 'public_profile', 'status' => 'granted'),
		// 	)
		// );
		if(isset($result['data']) && is_array($result['data']))
		{
			foreach($result['data'][0] as $data)
			{
				if(isset($data['permission']) && $data['permission'] == 'email')
					return (isset($data['status']) && $data['status'] == 'granted');
			}
		}
		return false;
	}

	protected function GetData()
	{
		$data = json_decode($this->service->request('/me?fields=name,first_name,middle_name,last_name,birthday,gender,email'), true);
		$picture = json_decode($this->service->request('/me/picture?type=large&redirect=false'), true);

		$oauthData = new OauthData();
		$oauthData->SetFacebookData($data, $picture);
		return $oauthData;
	}
}
