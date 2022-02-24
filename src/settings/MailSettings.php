<?php

namespace minga\framework\settings;

class MailSettings
{
	public const SMTP = 0;
	public const SendGrid = 2;
	public const File = 4;
	public const Mail = 5;

	public $NotifyAddress = "";
	public $NotifyAddressErrors = "";

	/** @var bool */
	public $NoMail = false;

	/** @var int */
	public $Provider = self::SMTP;

	// File Settings
	public $EmailFilePath = "";

	// SMTP Settings
	/** @var string */
	public $SMTPSecure = ""; // Opciones: "" | "tls"
	/** @var string */
	public $SMTPHost = "";
	/** @var int */
	public $SMTPPort = 25;
	/** @var string */
	public $SMTPUsername = "";
	/** @var string */
	public $SMTPPassword = "";
	/** @var bool */
	public $SMTPAuth = false;

	/** @var string[] */
	public $ExcludedAddresses = [];

	/** @var ?string */
	public $From = '';
}
