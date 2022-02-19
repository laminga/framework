<?php

namespace minga\framework;

class PublicException extends MingaException
{
	public function getPublicMessage() : string
	{
		return '[PE-E]: ' . $this->getMessage();
	}
}
