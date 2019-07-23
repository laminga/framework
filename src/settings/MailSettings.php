<?php

namespace minga\framework\settings;

class MailSettings
{
	const SMTP = 0;
	const Mandrill = 1;
	const SendGrid = 2;
	const SparkPost = 3;
	const File = 4;

	public $NotifyAddress = "";
	public $NotifyAddressErrors = "";

	public $Provider = self::SMTP;

	// Mandrill Settings
	public $MandrillApiKey = "";

	// SparkPost Settings
	public $SparkPostApiKey = "";

	// File Settings
	public $EmailFilePath  = "";

	// SMTP Settings
	public $SMTPSecure = ""; // Opciones: "" | "tls"
	public $SMTPHost = "";
	public $SMTPPort = 25;
	public $SMTPUsername = "";
	public $SMTPPassword = "";
	public $SMTPAuth = false;

	public $ExcludedAddresses = array();
}
