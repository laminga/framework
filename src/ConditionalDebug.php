<?php

namespace minga\framework;

class ConditionalDebug
{
	public string $user = '';

	public function __construct(string $user)
	{
		$this->user = $user;
		if (!$this->user)
			Log::HandleSilentException(new \Exception("Debe indicarse un usuario para realizar la depuración condicional"));
	}

	private function ConditionsMet() : bool
	{
		return Context::LoggedUser() == $this->user && $this->user != '';
	}

	public function EchoAndExit(string $text) : void
	{
		if ($this->ConditionsMet())
		{
			echo $text;
			exit;
		}
	}

	public function ManageExceptions() : void
	{
		if ($this->ConditionsMet())
		{
			// no funciona;
			set_exception_handler(function($exception) : void {
				$this->HandleException($exception);
			});

			// set the error handler
			set_error_handler(function($code, $message, $file, $line) {
				return $this->handleError($code, $message, $file, $line);
			});
		}
	}

	public function HandleException($ex) : void
	{
		if ($this->ConditionsMet()) {
			$text = Log::InternalExceptionToText($ex);
			echo $text;
			Log::HandleSilentException(new \Exception($text));
			exit;
		}
			throw $ex;
	}

	public function handleError($code, $message, $file, $line) : void
	{
		echo 'done32' . $code . $message;
		// code to handle the exception
		$text = Log::InternalErrorToText($code, $message, $file, $line);
		echo $text;
		exit;

	}
}
