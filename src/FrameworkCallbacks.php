<?php

namespace minga\framework;

abstract class FrameworkCallbacks
{
	public function RenderTemplate($template, $vals = null)
	{
	}
	public function RenderMessage($template, $vals = null)
	{
	}
	public function EndRequest()
	{
	}
}
