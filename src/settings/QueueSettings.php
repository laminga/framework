<?php

namespace minga\framework\settings;

class QueueSettings
{
	public $Enabled = false;
	public $MaxRetries = 3;
	//Revisar si hace falta bajar el número...
	public $MaxToProcess = 1000;
}
