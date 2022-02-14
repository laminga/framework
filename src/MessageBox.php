<?php

namespace minga\framework;

class MessageBox
{
	public static $IsThrowingMessage = false;

	public static function ThrowInternalError($message) : void
	{
		if(Context::Settings()->isTesting)
			echo 'ERROR. ' . $message . '<br>';
		Log::HandleSilentException(new ErrorException($message));
	}

	public static function ThrowAndLogMessage($message, $action = '') : void
	{
		Log::HandleSilentException(new ErrorException($message));
		self::ThrowMessage($message, $action);
	}

	public static function ThrowMessage($message, $action = '', $title = 'Atención', $caption = 'Continuar') : void
	{
		self::$IsThrowingMessage = true;
		if (Context::Settings()->isFramed)
		{
			echo "<!doctype html><html lang='es'><head><meta charset='utf-8'></head><body onload=\"parent.ThrowMessage('" . Str::EscapeJavascript($message) . "');\"></body></html>";
			exit();
		}

		if ($action == '')
			$action = 'history.back();';
		else if ($action == '2')
			$action = 'history.go(-1);';
		else
			$action = "document.location='" . $action . "';";

		$params = [
			'page' => $title,
			'message' => $message,
			'caption' => $caption,
			'action' => $action,
		];
		self::Render($params);
	}

	public static function ThrowWaitMessage($message, $action = '', $title = 'Atención') : void
	{
		$action = "document.location='" . $action . "';";

		$params = [
			'page' => $title,
			'message' => $message,
			'action' => $action,
			'isWaiting' => true,
		];
		self::Render($params);
	}

	public static function ThrowBackMessage($message) : void
	{
		$params = [
			'page' => 'Oops!',
			'message' => $message,
			'useSearchBar' => 'false',
			'caption' => 'Volver',
			'action' => 'history.back();',
		];
		self::Render($params);
	}

	private static function Render($params) : void
	{
		$params['useSearchBar'] = false;
		if (Context::Settings()->isTesting == false)
		{
			$params['html_title'] = (string)$params['page'];
			Context::Calls()->RenderTemplate('message.html.twig', $params);
		}
		else
		{
			echo 'Test Failed.<br>Error: ' . $params['message'];
			return;
		}
		Context::EndRequest();
	}

	private static function Set500InternalServerErrorHeaders() : void
	{
		header('HTTP/1.1 ');
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		header('Status: 500 Internal Server Error');
	}

	private static function Set403AccessDeniedHeaders() : void
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
		header('Status: 403 Forbidden');
		Context::Settings()->section = 'accessDenied';
	}

	public static function Set404NotFoundHeaders() : void
	{
		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
		header('Status: 404 Not Found');
		Context::Settings()->section = 'notFound';
	}

	public static function ShowMessagePopup($message, $title = 'Enviar mensaje') : void
	{
		$params = [
			'message' => $message,
			'page' => $title,
			'popup' => true,
		];
		Context::Calls()->RenderTemplate('messagePopup.html.twig', $params);
	}

	public static function ShowDialogPopup($message, $title = 'Enviar mensaje', $params = []) : void
	{
		$params = array_merge($params, [
			'message' => $message,
			'page' => $title,
			'popup' => true,
		]);
		Context::Calls()->RenderTemplate('dialogPopup.html.twig', $params);
	}

	public static function ShowDocNotFound($file, $profile) : void
	{
		$profileUrl = $profile->Links()->ContentLink();
		if ($profile->OnlyFiles())
			self::ThrowFileNotFound($file);
		else
		{
			self::Set404NotFoundHeaders();
			Performance::SetController('cErrDocNotFound', 'Show');
			self::ThrowMessage('El documento <b>' . $file . '</b> no está disponible.<p>'
				. "Sin embargo, si así lo desea, lo invitamos a visitar el perfil de <a href='" . $profileUrl
				. "'>" . $profile->GetFullName() . '</a> para consultar otros documentos relacionados.',
				$profileUrl,
				$profile->GetFullName() . ' - ' . $profile->GetLocation()
			);
		}
	}

	public static function ThrowInternalServerError($exception = null) : void
	{
		self::Set500InternalServerErrorHeaders();
		if (Context::Settings()->Debug()->debug)
		{
			$log = '<p>' . Log::FormatTraceLog(debug_backtrace());
			$msg = '';
			if ($exception != null)
				$msg = $exception->getMessage();
			MessageBox::ThrowMessage('Oops. Se ha producido un error... por favor, intente nuevamente en unos instantes. ' . $msg . $log, Context::Settings()->GetMainServerPublicUrl());
		}
		MessageBox::ThrowMessage('Oops. Se ha producido un error... por favor, intente nuevamente en unos instantes.', Context::Settings()->GetMainServerPublicUrl());
	}

	public static function ThrowFileNotFound($extraInfo = '') : void
	{
		self::Set404NotFoundHeaders();
		Performance::SetController('cErrPageNotFound', 'Show');
		if (Context::Settings()->Debug()->debug)
		{
			$log = Log::FormatTraceLog(debug_backtrace());
			MessageBox::ThrowMessage('Página no encontrada. ' . $extraInfo . $log, Context::Settings()->GetMainServerPublicUrl());
		}
		MessageBox::ThrowMessage('Página no encontrada.', Context::Settings()->GetMainServerPublicUrl());
	}

	public static function ThrowAccessDenied($extraInfo = '') : void
	{
		self::Set403AccessDeniedHeaders();
		Performance::SetController('cErrAccessDenied', 'Show');
		if (Context::Settings()->Debug()->debug)
		{
			$log = Log::FormatTraceLog(debug_backtrace());
			MessageBox::ThrowMessage('Acceso denegado. ' . $extraInfo . $log, Context::Settings()->GetMainServerPublicUrl());
		}
		MessageBox::ThrowMessage('Acceso denegado.', Context::Settings()->GetMainServerPublicUrl());
	}
}
