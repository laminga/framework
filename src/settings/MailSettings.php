<?php

namespace minga\framework\settings;

class MailSettings
{
	public const SMTP = 0;
	public const SendGrid = 2;
	public const File = 4;
	public const Mail = 5;

	/** @var string[]|string */
	public $NotifyAddress = "";
	/** @var string[]|string */
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

	/** @var string[] */
	public array $ExcludedAddresses = [];

	public ?string $From = '';
}
