<?php

namespace minga\framework\oauth;

use minga\framework\PhpSession;

class OauthData
{
	public $fullName = '';
	public $firstName = '';
	public $lastName = '';
	public $verified = '';
	public $id = '';
	public $email = '';
	public $picture = '';
	public $gender = '';
	public $provider = '';

	public function SetGoogleData($data)
	{
		$this->provider = 'google';

		if(isset($data['name']))
			$this->fullName = $data['name'];
		if(isset($data['given_name']))
			$this->firstName = $data['given_name'];
		if(isset($data['family_name']))
			$this->lastName = $data['family_name'];

		if(isset($data['verified_email']))
			$this->verified = $data['verified_email'];

		if(isset($data['email']))
			$this->email = $data['email'];
		if(isset($data['id']))
			$this->id = $data['id'];

		if(isset($data['picture']))
			$this->picture = $data['picture'];
		if(isset($data['gender']))
			$this->gender = $data['gender'];
	}

	public function SetFacebookData($data, $picture)
	{
		$this->provider = 'facebook';

		if(isset($data['name']))
			$this->fullName = $data['name'];
		if(isset($data['first_name']))
			$this->firstName = $data['first_name'];
		if(isset($data['middle_name']))
			$this->firstName .=  trim($this->firstName.' '.$data['middle_name']);

		if(isset($data['last_name']))
			$this->lastName = $data['last_name'];

		//Si trae el email de facebook está verificado.
		if(isset($data['email']))
		{
			$this->email = $data['email'];
			$this->verified = true;
		}

		if(isset($data['id']))
			$this->id = $data['id'];
		if(isset($data['gender']))
			$this->gender = $data['gender'];

		if(isset($picture['data']['is_silhouette'])
			&& $picture['data']['is_silhouette'] == false
			&& isset($picture['data']['url']))
			$this->picture = $picture['data']['url'];
	}

	public function SerializeToSession()
	{
		$data = [
			'provider' => $this->provider,
			'id' => $this->id,
			'fullName' => $this->fullName,
			'firstName' => $this->firstName,
			'lastName' => $this->lastName,
			'email' => $this->email,
			'gender' => $this->gender,
			'picture' => $this->picture,
			'verified' => $this->verified,
		];
		PhpSession::SetSessionValue('OauthData', json_encode($data));
	}

	public static function SessionHasTerms()
	{
		return PhpSession::GetSessionValue('OauthTerms') == 'on';
	}

	public static function DeserializeFromSession()
	{
		$session = PhpSession::GetSessionValue('OauthData');
		if($session == '')
			return null;

		$data = json_decode($session, true);

		$ret = new self();
		$ret->provider = $data['provider'];
		$ret->id = $data['id'];
		$ret->fullName = $data['fullName'];
		$ret->firstName = $data['firstName'];
		$ret->lastName = $data['lastName'];
		$ret->email = $data['email'];
		$ret->gender = $data['gender'];
		$ret->picture = $data['picture'];
		$ret->verified = $data['verified'];
		return $ret;
	}
	public static function ClearSession()
	{
		PhpSession::SetSessionValue('OauthTerms', '');
		PhpSession::SetSessionValue('OauthData', '');
		PhpSession::SetSessionValue('facebookOauthRedirect', '');
		PhpSession::SetSessionValue('googleOauthRedirect', '');
		PhpSession::SetSessionValue('facebookOauthReturnUrl', '');
		PhpSession::SetSessionValue('googleOauthReturnUrl', '');
	}
}
