<?php

namespace minga\framework\settings;

class QueueSettings
{
	public bool $Enabled = false;
	/** Máximo default de ítems a ejecutar */
	public int $Max = 50;
}
