<?php

namespace minga\framework\settings;

use minga\framework\IO;

class MailSettings
{
	public const SMTP = 0;
	public const SendGrid = 2;
	public const File = 4;
	public const Mail = 5;

	/** @var string[]|string */
	public $NotifyAddress = "";

	/** @var array<string,int>|string[]|string */
	//TODO: cambiar a tipo array, inicializar [], cuando se actualicen la configuración de las implementaciones
	//ver: MailError.php@13 [2022/10/05]
	public $NotifyAddressErrors = "";

	public bool $NoMail = false;

	public int $Provider = self::SMTP;

	// File Settings
	public $EmailFilePath = "";

	// SMTP Settings
	public string $SMTPSecure = ""; // Opciones: "" | "tls"
	public string $SMTPHost = "";
	public int $SMTPPort = 25;
	public string $SMTPUsername = "";
	public string $SMTPPassword = "";
	public bool $SMTPAuth = false;

	public ?string $From = '';

	/** @var string[] */
	private array $ExcludedAddresses;

	public function GetExcludedAddresses(string $file) : array
	{
		if(isset($this->ExcludedAddresses) == false)
			$this->ExcludedAddresses = IO::ReadAllLines($file);
		return $this->ExcludedAddresses;
	}
}
