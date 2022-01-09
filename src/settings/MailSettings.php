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

	public $NoMail = false;

	public $Provider = self::SMTP;

	// File Settings
	public $EmailFilePath = "";

	// SMTP Settings
	public $SMTPSecure = ""; // Opciones: "" | "tls"
	public $SMTPHost = "";
	public $SMTPPort = 25;
	public $SMTPUsername = "";
	public $SMTPPassword = "";
	public $SMTPAuth = false;

	public $ExcludedAddresses = [];
}
