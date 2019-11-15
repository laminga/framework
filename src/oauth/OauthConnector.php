<?php

namespace minga\framework\oauth;

use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Storage\Memory;
use minga\framework\Context;
use minga\framework\ErrorException;
use minga\framework\IO;
use minga\framework\Log;
use minga\framework\MessageBox;
use minga\framework\MessageException;
use minga\framework\PhpSession;
use minga\framework\Profiling;
use minga\framework\Str;

abstract class OauthConnector
{
	//los hijos deben declarar el provider de este modo.
	const Provider = '';

	protected $storage;
	protected $service;

	public function __construct()
	{
		$this->Setup();
	}

	public function Setup()
	{
		$this->storage = new Memory();

		$credentials = new Credentials(
			Context::Settings()->Oauth()->Credentials[static::Provider]['key'],
			Context::Settings()->Oauth()->Credentials[static::Provider]['secret'],
			Context::Settings()->Oauth()->Credentials[static::Provider]['callback']
		);
		$serviceFactory = new \OAuth\ServiceFactory();
		$this->service = $serviceFactory->createService(static::Provider, $credentials, $this->storage, $this->GetFields());
	}

	/**
	 * Con los datos de $code y $state que devuelve el provider
	 * obtiene el token de acceso para solicitar los datos
	 * del usuario. Si vuelve todo bien es que el usuario autorizó
	 * y las credenciales de oauth son válidas.
	 */
	public function RequestData($code, $state)
	{
		try
		{
			//Sólo acá el provider es con mayúscula.
			$provider = Str::Capitalize(static::Provider);

			$this->storage->storeAuthorizationState($provider, $state);
			$this->service->requestAccessToken($code, $state);

			return $this->GetData();
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
			return null;
		}
	}

	private function SetSession($url, $returnUrl, $terms) : array
	{
		//Setear en sesión los datos que se quieran tener para después de oauth.
		//porque sale del sitio y no hay forma de mantener estado si no es sesión
		//o cookie.
		$sess = [
			static::Provider . 'OauthRedirect' => $url,
			static::Provider . 'OauthReturnUrl' => $returnUrl,
			'OauthTerms' => $terms,
		];

		foreach($sess as $k => $v)
			PhpSession::SetSessionValue($k, $v);

		return $sess;
	}

	public function ResolveRedirectProvider($url, $returnUrl, $terms)
	{
		$sess = $this->SetSession($url, $returnUrl, $terms);

		$uri = $this->service->getAuthorizationUri();

		$this->PutSessionToFile($uri, $sess);

		return $uri;
	}

	private function PutSessionToFile($uri, $sess)
	{
		$query = [];
		parse_str($uri->getQuery(), $query);
		if(isset($query['state']))
		{
			$path = Context::Paths()->GetTempPath() . '/oauth_' . $query['state'] . '.json';
			IO::WriteJson($path, $sess);
		}
	}

	private function CleanOldFiles() : void
	{
		$files = glob(Context::Paths()->GetTempPath() . '/oauth_*.json');
		foreach($files as $file)
		{
			//Una hora
			if(time() - filectime($file) > 3600)
				IO::Delete($file);
		}
	}

	private function GetSessionFromFile(string $state) : string
	{
		$this->CleanOldFiles();

		$file = Context::Paths()->GetTempPath() . '/oauth_' . $state . '.json';
		if(file_exists($file))
		{
			$data = IO::ReadJson($file);
			$this->SetSession($data[static::Provider . 'OauthRedirect'], $data[static::Provider . 'OauthReturnUrl'], $data['OauthTerms']);
			IO::Delete($file);
			return $data[static::Provider . 'OauthRedirect'];
		}
		return '';
	}

	public function RedirectSuccess($data, string $state) : void
	{
		if($data->email == '' || $data->verified == false)
			$this->RedirectErrorNoEmail();

		$data->SerializeToSession(static::Provider);
		$this->RedirectSession($state);
	}

	public function RedirectErrorNoEmail()
	{
		Log::HandleSilentException(new MessageException('No email from ' . $this->ProviderName()));

		MessageBox::ShowDialogPopup('No se ha podido obtener una dirección de correo electrónico a través de ' . $this->ProviderName() . '. Intente otro método de registro para la identificación.', 'Atención');
	}

	public function RedirectError($error = null)
	{
		if($error != null)
			Log::HandleSilentException(new ErrorException($error));

		MessageBox::ShowDialogPopup('No se ha podido realizar la interacción con ' . $this->ProviderName() . ' para la identificación.', 'Atención');
	}

	private function RedirectSession(string $state)
	{
		$this->CleanOldFiles();

		$url = PhpSession::GetSessionValue(static::Provider . 'OauthRedirect');

		if($url == '')
			$url = self::GetSessionFromFile($state);

		$this->CloseAndRedirect($url);
	}

	public function ProviderName()
	{
		$c = get_called_class();
		return Str::Capitalize($c::Provider);
	}

	private function CloseAndRedirect($target)
	{
		//TODO: validar el target.
		//-Que sea de este dominio (que no redirija a otro sitio).
		//-Que no tenga funciones inválidas (deleteUser, etc.)
		//-No tenga código javascript (xss).
		if($target == '')
		{
			Log::HandleSilentException(new ErrorException('Undefined target.'));
			$target = Context::Settings()->GetMainServerPublicUrl();
		}

		$js = "window.opener.location='" . $target . "';";
		$js .= 'window.close();';
		echo '<!doctype html><html lang="es"><head><meta charset="utf-8"></head><body onload="' . $js . '"></body></html>';

		// Guarda info de profiling
		Profiling::SaveBeforeRedirect();
		Context::EndRequest();
	}

	/**
	 * Obtiene los fields que se le piden al provider
	 * cada provider maneja los suyos.
	 */
	abstract protected function GetFields();

	/**
	 * Obtiene los datos del provider normalizados
	 * en formato de OauthData.
	 */
	abstract protected function GetData();

	/**
	 * Valida para cada provider si los datos necesarios
	 * fueron autorizados por el usuario.
	 */
	abstract protected function DataGranted();
}
