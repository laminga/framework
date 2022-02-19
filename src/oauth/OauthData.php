<?php

namespace minga\framework\oauth;

use minga\framework\PhpSession;

class OauthData
{
	public $fullname = '';
	public $firstname = '';
	public $lastname = '';
	public $verified = '';
	public $id = '';
	public $email = '';
	public $picture = '';
	public $gender = '';
	public $provider = '';

	public function SetGoogleData($data) : void
	{
		$this->provider = 'google';

		if(isset($data['name']))
			$this->fullname = $data['name'];
		if(isset($data['given_name']))
			$this->firstname = $data['given_name'];
		if(isset($data['family_name']))
			$this->lastname = $data['family_name'];

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

	public function SetFacebookData($data, $picture) : void
	{
		$this->provider = 'facebook';

		if(isset($data['name']))
			$this->fullname = $data['name'];
		if(isset($data['first_name']))
			$this->firstname = $data['first_name'];
		if(isset($data['middle_name']))
			$this->firstname .= trim($this->firstname . ' ' . $data['middle_name']);

		if(isset($data['last_name']))
			$this->lastname = $data['last_name'];

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

	public function SerializeToSession() : void
	{
		$data = [
			'provider' => $this->provider,
			'id' => $this->id,
			'fullname' => $this->fullname,
			'firstname' => $this->firstname,
			'lastname' => $this->lastname,
			'email' => $this->email,
			'gender' => $this->gender,
			'picture' => $this->picture,
			'verified' => $this->verified,
		];
		PhpSession::SetSessionValue('OauthData', json_encode($data));
	}

	public static function SessionHasTerms() : bool
	{
		return PhpSession::GetSessionValue('OauthTerms') == 'on';
	}

	public static function DeserializeFromSession() : ?OauthData
	{
		$session = PhpSession::GetSessionValue('OauthData');
		if($session == '')
			return null;

		$data = json_decode($session, true);

		$ret = new self();
		$ret->provider = $data['provider'];
		$ret->id = $data['id'];
		$ret->fullname = $data['fullname'];
		$ret->firstname = $data['firstname'];
		$ret->lastname = $data['lastname'];
		$ret->email = $data['email'];
		$ret->gender = $data['gender'];
		$ret->picture = $data['picture'];
		$ret->verified = $data['verified'];
		return $ret;
	}

	public static function ClearSession() : void
	{
		PhpSession::SetSessionValue('OauthTerms', '');
		PhpSession::SetSessionValue('OauthData', '');
		PhpSession::SetSessionValue('facebookOauthRedirect', '');
		PhpSession::SetSessionValue('googleOauthRedirect', '');
		PhpSession::SetSessionValue('facebookOauthReturnUrl', '');
		PhpSession::SetSessionValue('googleOauthReturnUrl', '');
	}
}
