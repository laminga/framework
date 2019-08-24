<?php

namespace minga\framework\oauth;

use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Storage\Memory;
use minga\framework\Context;
use minga\framework\ErrorException;
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

	public function ResolveRedirectProvider($url, $returnUrl, $terms)
	{
		//Setear en sesión los datos que se quieran tener para después de oauth.
		//porque sale del sitio y no hay forma de mantener estado si no es sesión
		//o cookie.
		PhpSession::SetSessionValue(static::Provider . 'OauthRedirect', $url);
		PhpSession::SetSessionValue(static::Provider . 'OauthReturnUrl', $returnUrl);
		PhpSession::SetSessionValue('OauthTerms', $terms);
		return $this->service->getAuthorizationUri();
	}

	public function RedirectSuccess($data)
	{
		if($data->email == '' || $data->verified == false)
			$this->RedirectErrorNoEmail();

		$data->SerializeToSession(static::Provider);
		$this->RedirectSession();
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

	private function RedirectSession()
	{
		$url = PhpSession::GetSessionValue(static::Provider . 'OauthRedirect');
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
