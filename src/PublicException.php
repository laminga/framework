<?php

namespace minga\framework;

class PublicException extends MingaException
{
	public function getPublicMessage()
	{
		return '[PE-E]: ' . $this->getMessage();
	}
}
