<?php

namespace minga\framework;

class ConditionalDebug
{
	public string $user = '';

	public function __construct(string $user)
	{
		$this->user = $user;
        if (!$this->user)
            throw new SilentException("Debe indicarse un usuario para realizar la depuración condicional");
	}

	private function ConditionsMet() : bool
    {
        return (Context::LoggedUser() == $this->user && $this->user != '');
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
            echo 'done';
			set_exception_handler([$this, 'handle']);

            // set the error handler
            set_error_handler(function ($code, $message, $file, $line) {
                $this->handleError($code, $message, $file, $line);
            }, -1);
        }
    }

    public function HandleException($ex)
    {
        if ($this->ConditionsMet()) {
            $text = Log::InternalExceptionToText($ex);
            echo ($text);
            exit;
        } else
            throw $ex;
    }
    public function handleError($code, $message, $file, $line)
    {
        echo 'done32' . $code . $message;
        // code to handle the exception
        $text = Log::InternalErrorToText($code, $message, $file, $line);
        echo ($text);
        exit;

    }
    public function handle(Exception $ex)
	{
        echo 'done22';
        // code to handle the exception
		$text = Log::InternalExceptionToText($ex);
        echo($text);
		exit;
	}
}
