<?php

namespace minga\framework;

class MessageBox
{
	public static bool $IsThrowingMessage = false;

	public static function ThrowInternalError(string $message) : void
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

	public static function ThrowMessage($message, $action = '', $title = '__Atención', $caption = '__Continuar') : void
	{
		if($title == '__Atención')
			$title = Context::Trans('Atención');

		if($caption == '__Continuar')
			$caption = Context::Trans('Continuar');


		self::$IsThrowingMessage = true;
		if (Context::Settings()->isFramed)
		{
			echo "<!doctype html><html><head><meta charset='utf-8'></head><body onload=\"parent.ThrowMessage('" . Str::EscapeJavascript($message) . "');\"></body></html>";
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

	public static function ThrowWaitMessage(string $message, string $action = '', string $title = '__Atención') : void
	{
		if($title == '__Atención')
			$title = Context::Trans('Atención');

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
			'caption' => Context::Trans('Volver'),
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

	public static function ShowMessagePopup(string $message, string $title = '__Enviar mensaje') : void
	{
		if($title == '__Enviar mensaje')
			$title = Context::Trans('Enviar mensaje');

		$params = [
			'message' => $message,
			'page' => $title,
			'popup' => true,
		];
		Context::Calls()->RenderTemplate('messagePopup.html.twig', $params);
	}

	public static function ShowDialogPopup(string $message, string $title = '__Enviar mensaje', array $params = []) : void
	{
		if($title == '__Enviar mensaje')
			$title = Context::Trans('Enviar mensaje');
		$params = array_merge($params, [
			'message' => $message,
			'page' => $title,
			'popup' => true,
		]);
		Context::Calls()->RenderTemplate('dialogPopup.html.twig', $params);
	}

	public static function ShowDocNotFound($file, $content) : void
	{
		$contentUrl = $content->Links()->ContentLink();
		if ($content->OnlyFiles())
			self::ThrowFileNotFound($file);
		else
		{
			self::Set404NotFoundHeaders();
			Performance::SetController('cErrDocNotFound', 'Show');
			$link = '<a href="' . $contentUrl . '">' . $content->GetFullName() . '</a>';
			self::ThrowMessage(Context::Trans('El documento <b>{file}</b> no está disponible.<p>Sin embargo, si así lo desea, lo invitamos a visitar el perfil de {link} para consultar otros documentos relacionados.</p>', ['{file}' => $file, '{link}' => $link]), $contentUrl, $content->GetFullName() . ' - ' . $content->GetLocation());
		}
	}

	public static function ThrowInternalServerError(?\Exception $exception = null) : void
	{
		self::Set500InternalServerErrorHeaders();
		if (Context::Settings()->Debug()->debug)
		{
			$log = '<p>' . Log::FormatTraceLog(debug_backtrace());
			$msg = '';
			if ($exception != null)
				$msg = $exception->getMessage();
			MessageBox::ThrowMessage(Context::Trans('Oops. Se produjo un error… por favor, intente nuevamente en unos instantes.') . ' ' . $msg . $log, Context::Settings()->GetMainServerPublicUrl());
		}
		MessageBox::ThrowMessage(Context::Trans('Oops. Se produjo un error… por favor, intente nuevamente en unos instantes.'), Context::Settings()->GetMainServerPublicUrl());
	}

	public static function ThrowFileNotFound($extraInfo = '') : void
	{
		self::Set404NotFoundHeaders();
		Performance::SetController('cErrPageNotFound', 'Show');
		if (Context::Settings()->Debug()->debug)
		{
			$log = Log::FormatTraceLog(debug_backtrace());
			MessageBox::ThrowMessage(Context::Trans('Página no encontrada.') . ' ' . $extraInfo . $log, Context::Settings()->GetMainServerPublicUrl());
		}
		MessageBox::ThrowMessage(Context::Trans('Página no encontrada.'), Context::Settings()->GetMainServerPublicUrl());
	}

	public static function ThrowAccessDenied(string $extraInfo = '') : void
	{
		self::Set403AccessDeniedHeaders();
		Performance::SetController('cErrAccessDenied', 'Show');
		if (Context::Settings()->Debug()->debug)
		{
			$log = Log::FormatTraceLog(debug_backtrace());
			MessageBox::ThrowMessage(Context::Trans('Acceso denegado.') . ' ' . $extraInfo . $log, Context::Settings()->GetMainServerPublicUrl());
		}
		MessageBox::ThrowMessage(Context::Trans('Acceso denegado.'), Context::Settings()->GetMainServerPublicUrl());
	}
}
