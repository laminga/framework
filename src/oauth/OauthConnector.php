<?php

namespace minga\framework\oauth;

use minga\framework\Context;
use minga\framework\ErrorException;
use minga\framework\IO;
use minga\framework\Log;
use minga\framework\MessageBox;
use minga\framework\PhpSession;
use minga\framework\Profiling;
use minga\framework\PublicException;
use minga\framework\Str;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Storage\Memory;

abstract class OauthConnector
{
	//los hijos deben declarar el provider de este modo.
	public const Provider = '';

	protected Memory $storage;
	protected $service;

	public function __construct()
	{
		$this->Setup();
	}

	public function Setup() : void
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
	public function RequestData(string $code, ?string $state) : ?OauthData
	{
		try
		{
			//Solo acá el provider es con mayúscula.
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

	private function SetSession(string $url, string $returnUrl, bool $terms) : array
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

	public function ResolveRedirectProvider(string $url, string $returnUrl, bool $terms) : UriInterface
	{
		$sess = $this->SetSession($url, $returnUrl, $terms);

		$uri = $this->service->getAuthorizationUri();

		$this->PutSessionToFile($uri, $sess);

		return $uri;
	}

	private function PutSessionToFile(UriInterface $uri, array $sess) : void
	{
		$query = [];
		parse_str($uri->getQuery(), $query);
		if(isset($query['state']))
		{
			IO::WriteJson($this->GetStateFilePath($query['state']), $sess);
		}
	}

	private function GetStateFilePath(string $state) : string
	{
		return Context::Paths()->GetTempPath() . '/oauth_' . $state . '.json';
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

	/**
	 * Cada $state se emite al iniciar el ingreso y se puede usar una sola vez.
	 * El provider entrega un $code de un solo uso, por lo que un segundo pedido
	 * con el mismo $state (recarga de la ventana, restauración de pestañas,
	 * previsualización de enlaces) debe descartarse antes de consultar al provider.
	 * Al reclamarlo se restauran los datos de sesión guardados al iniciar.
	 */
	public function ClaimState(string $state) : bool
	{
		$this->CleanOldFiles();

		if(preg_match('/^[a-f0-9]{32}$/', $state) !== 1)
			return false;

		$file = $this->GetStateFilePath($state);
		if(file_exists($file) == false)
			return false;

		$data = IO::ReadJson($file);
		IO::Delete($file);
		$this->SetSession($data[static::Provider . 'OauthRedirect'], $data[static::Provider . 'OauthReturnUrl'], $data['OauthTerms']);
		return true;
	}

	public function RedirectStateUsed() : void
	{
		MessageBox::ShowDialogPopup(Context::Trans('El ingreso con {provider} ya fue procesado o venció. Cierre esta ventana y, si no pudo ingresar, vuelva a intentarlo.', [
			'{provider}' => $this->ProviderName(),
		]), Context::Trans('Atención'));
	}

	public function RedirectSuccess(OauthData $data, string $rememberToken = '') : void
	{
		if($data->email == '' || $data->verified == false)
			$this->RedirectErrorNoEmail();

		$data->SerializeToSession();
		$this->RedirectSession($rememberToken);
	}

	public function RedirectErrorNoEmail() : void
	{
		Log::HandleSilentException(new PublicException('No email from ' . $this->ProviderName()));
		MessageBox::ShowDialogPopup(Context::Trans('No se ha podido obtener una dirección de correo electrónico a través de {provider}. Intente otro método de registro para la identificación.', [
			'{provider}' => $this->ProviderName(),
		]), Context::Trans('Atención'));
	}

	public function RedirectError(?string $error = null) : void
	{
		if($error != null)
			Log::HandleSilentException(new ErrorException($error));

		MessageBox::ShowDialogPopup(Context::Trans('No se ha podido realizar la interacción con {provider} para la identificación.', ['{provider}' => $this->ProviderName()]), Context::Trans('Atención'));
	}

	private function RedirectSession(string $rememberToken) : void
	{
		$target = PhpSession::GetSessionValue(static::Provider . 'OauthRedirect');

		$this->NotifyOpener($rememberToken, $this->ResolveTarget($target));
	}

	/**
	 * El target proviene de un parámetro enviado por el cliente al iniciar el ingreso:
	 * solo se admiten rutas del sitio para no redirigir a otro dominio ni ejecutar código.
	 */
	private function ResolveTarget(string $target) : string
	{
		$isRelative = Str::StartsWith($target, '/')
			&& Str::StartsWith($target, '//') == false
			&& Str::StartsWith($target, '/\\') == false;
		$isPublicUrl = Str::StartsWithI($target, Context::Settings()->GetPublicUrl() . '/');
		$isMainUrl = Str::StartsWithI($target, Context::Settings()->GetMainServerPublicUrl() . '/');

		if($isRelative || $isPublicUrl || $isMainUrl)
			return $target;

		Log::HandleSilentException(new ErrorException('Parámetro "target" no definido o inválido: ' . $target));
		return Context::Settings()->GetMainServerPublicUrl();
	}

	public function ProviderName() : string
	{
		$c = static::class;
		return Str::Capitalize($c::Provider);
	}

	// La aplicación corre en otro dominio: recibe la sesión por mensaje porque no comparte cookies con este popup.
	// Si el popup perdió su opener (navegadores embebidos, pestañas restauradas) continúa en esta misma ventana,
	// y si no puede cerrarse queda un texto en lugar de una página en blanco que el usuario recargaría.
	private function NotifyOpener(string $rememberToken, string $target) : void
	{
		$message = [
			'type' => 'oauthLogin',
			'sessionId' => PhpSession::SessionId(),
			'rememberToken' => $rememberToken,
			'redirect' => $target,
		];
		$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		$js = 'var message = ' . json_encode($message, $flags) . ';';
		$js .= 'if (window.opener && !window.opener.closed) {';
		$js .= 'window.opener.postMessage(message, ' . json_encode($this->GetOpenerOrigin(), $flags) . ');';
		$js .= 'window.close();';
		$js .= '} else {';
		$js .= 'window.location.replace(message.redirect);';
		$js .= '}';
		$text = htmlspecialchars(Context::Trans('Ingreso completado. Puede cerrar esta ventana.'), ENT_QUOTES, 'UTF-8');
		echo '<!doctype html><html><head><meta charset="utf-8"></head><body><p>' . $text . '</p><script>' . $js . '</script></body></html>';

		// Guarda info de profiling
		Profiling::SaveBeforeRedirect();
		Context::EndRequest();
	}

	private function GetOpenerOrigin() : string
	{
		$parts = parse_url(Context::Settings()->GetMainServerPublicUrl());
		$origin = $parts['scheme'] . '://' . $parts['host'];
		if(isset($parts['port']))
			$origin .= ':' . $parts['port'];
		return $origin;
	}

	/**
	 * Obtiene los fields que se le piden al provider
	 * cada provider maneja los suyos.
	 */
	abstract protected function GetFields() : array;

	/**
	 * Obtiene los datos del provider normalizados
	 * en formato de OauthData.
	 */
	abstract protected function GetData() : OauthData;

	/**
	 * Valida para cada provider si los datos necesarios
	 * fueron autorizados por el usuario.
	 */
	abstract protected function DataGranted() : bool;
}
