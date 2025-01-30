<?php

namespace minga\framework;

// Links de información de mimetypes, archivos de prueba, etc.
// https://svn.apache.org/viewvc/httpd/httpd/branches/2.2.x/docs/conf/mime.types?view=annotate
// https://stackoverflow.com/questions/1735659/list-of-all-mimetypes-on-the-planet-mapped-to-file-extensions
// https://www.decalage.info/en/download_mso_files
// https://github.com/centic9/CommonCrawlDocumentDownload/tree/master/src/main/java/org/dstadler/commoncrawl
// https://github.com/jafingerhut/osx-spotlight-test-files
class Extensions
{
	public const Pdf = ['pdf'];
	//TODO: implementar rtf
	public const Document = ['pdf', 'docx', 'doc'];
	public const WordDocument = ['docx', 'doc'];
	public const Presentation = ['pdf', 'pptx', 'ppt', 'ppsx', 'pps'];

	public const DocumentAndPresentation = ['pdf', 'docx', 'doc', 'pptx', 'ppt', 'ppsx', 'pps'];

	public const Images = ['jpg', 'png'];

	public static function CheckAndGetFileExtension(string $field, array $validExtensions = self::Document) : string
	{
		if(isset($_FILES[$field]['size']) == false || $_FILES[$field]['size'] <= 0)
			return '';

		$name = $_FILES[$field]['name'];

		$ext = self::GetRealExtension($name, $_FILES[$field]['tmp_name'], $extName);

		if(in_array($ext, $validExtensions) == false)
		{
			$message = Context::Trans('La extensión del archivo debe ser una de las siguientes: {ext}.', ['{ext}' => self::GetExtensionList($validExtensions)]);

			if(in_array($extName, $validExtensions))
				$message .= '<br>' . Context::Trans('Si el archivo tiene una extensión válida, es posible que esté dañado o que se haya cambiado la extensión manualmente. Esto último modifica el nombre, pero no el formato del archivo. Intente abrirlo con la aplicación predeterminada y guardarlo en el formato esperado.');

			MessageBox::ThrowAndLogMessage($message);
		}
		return $ext;
	}

	public static function RemoveExtension(string $filename) : string
	{
		return IO::RemoveExtension($filename);
	}

	public static function GetRealExtension(string $filename, string $filePath, ?string &$extName = '') : string
	{
		$extName = self::GetExtensionFromString($filename);
		return self::GetExtensionFromFile($filePath, $extName, $filename);
	}

	public static function GetExtensionList(array $extensions) : string
	{
		$str = Str::ToUpper(implode(', .', $extensions));
		return '.' . Str::ReplaceLast($str, ', ', ' o ');
	}

	public static function GetExtensionFromString(string $str) : string
	{
		return Str::ToLower(pathinfo($str, PATHINFO_EXTENSION));
	}

	public static function GetExtensionFromFile(string $filePath, string $fileExt, string $original = '') : string
	{
		if($original == '')
			$original = basename($filePath);

		if($fileExt == '')
			$fileExt = self::GetExtensionFromString($original);

		Str::RemoveBegining($fileExt, '.');

		// primero busca por mime type
		// (es más confiable, salvo alguna excepción).
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$fileMime = $finfo->file($filePath);

		$retExt = '';
		// zip pueden ser muchos tipos
		if($fileMime != 'application/zip')
		{
			$validMime = self::ValidMimeTypes();
			foreach($validMime as $mime => $exts)
			{
				if(Str::StartsWith($fileMime, $mime))
				{
					if(in_array($fileExt, $exts))
						$retExt = $fileExt;
					else
						$retExt = $exts[0];
					break;
				}
			}
		}

		$fileType = '';
		//si no lo encontró prueba con el tipo
		if($retExt == '')
		{
			$finfo = new \finfo();
			$fileType = $finfo->file($filePath);

			$validType = self::ValidTypes();
			foreach($validType as $type => $exts)
			{
				if(Str::StartsWith($fileType, $type))
				{
					if(in_array($fileExt, $exts))
						$retExt = $fileExt;
					else
						$retExt = $exts[0];
					break;
				}
			}
		}

		if($retExt == $fileExt)
			return $retExt;

		//si no lo encontró devuelve la original y loguea
		if($retExt == '')
			$retExt = $fileExt;

		//si originalmente no tenía extensión o era tmp no loguea
		if($fileExt == '' || $fileExt == 'tmp')
			return $retExt;

		//No loguea si es lo esperado, o algún tipo conocido.
		// if(
		// 	(Str::StartsWith($fileMime, 'application/octet-stream') && Str::StartsWith($fileType, 'Microsoft OOXML'))
		// 	|| (Str::StartsWith($fileMime, 'application/zip') && Str::StartsWith($fileType, 'Zip archive data'))
		//)
		// {
		// 	return $retExt;
		// }

		$temp = '';
		try
		{
			$temp = IO::GetTempFilename();
			copy($filePath, $temp);
		}
		catch(\Exception $ex)
		{
			//Todo esto es para debug, no tiene que dar error.
			$temp .= ' (error)';
		}

		$e = new \Exception('GetExtensionFromFile: mime: ' . $fileMime . ', type: ' . $fileType . ', fileExt: '
			. $fileExt . ', retExt: ' . $retExt . ', original: ' . $original . '", temp: "' . $temp . '".');

		Log::HandleSilentException($e);

		return $retExt;
	}

	public static function IsWordDocument(string $file) : bool
	{
		//Es una extensión
		if(strlen($file) < 5 && Str::Contains($file, '.') == false)
			$file = '.' . $file;

		return in_array(self::GetExtensionFromString($file), self::WordDocument);
	}

	public static function GetMimeContentType(string $ext) : string
	{
		$ext = Str::RemoveBegining($ext, '.');

		switch($ext)
		{
			case "pdf":
				return "application/pdf";
			case "doc":
				return "application/msword";
			case "docx":
				return "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
			case "ppt":
			case "pps":
				return "application/vnd.ms-powerpoint";
			case "pptx":
				return "application/vnd.openxmlformats-officedocument.presentationml.presentation";
			case "ppsx":
				return "application/vnd.openxmlformats-officedocument.presentationml.slideshow";
			case "xls":
				return "application/vnd.ms-excel";
			case "xlsx":
				return "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
			case "png":
				return "image/png";
			case "gif":
				return "image/gif";
			case "jpeg":
			case "jpg":
				return "image/jpeg";
			case "bmp":
				return "image/bmp";
			case "zip":
				return "application/zip";
			case 'sav':
				return 'application/x-vnd.spss-statistics-spd';
			case 'dta':
				return 'application/x-stata';
			case 'rdata':
				return 'application/octet-stream';
			case 'mp3':
				return 'audio/mpeg';
			case 'ogg':
				return 'audio/ogg';
			case 'wav':
				return 'audio/wav';
			default:
				return 'application/octet-stream';
		}
	}

	public static function ValidMimeTypes() : array
	{
		// la primera extensión de cada tipo es la default.
		return [
			//images
			'image/png' => [
				'png',
			],
			'image/x-png' => [
				'png',
			],
			'image/gif' => [
				'gif',
			],
			'image/jpeg' => [
				'jpg',
				'jpeg',
			],
			'image/pjpeg' => [
				'jpg',
				'jpeg',
			],
			'image/x-ms-bmp' => [
				'bmp',
			],
			'image/bmp' => [
				'bmp',
			],

			//pdf
			'application/pdf' => [
				'pdf',
			],
			//rtf
			'text/rtf' => [
				'rtf',
			],
			//word
			'application/msword' => [
				'doc',
				'dot',
			],
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
				'docx',
				'docm',
				'dotx',
				'dotm',
			],
			//excel
			'application/vnd.ms-excel' => [
				'xls',
				'xlt',
			],
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => [
				'xlsx',
				'xlsb',
				'xlsm',
				'xltx',
				'xltm',
			],
			//powerpoint
			'application/vnd.ms-powerpoint' => [
				'ppt',
				'pps',
				'pot',
			],
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => [
				'pptx',
				'ppsx',
				'pptm',
				'ppsm',
				'potx',
				'potm',
			],
			//openoffice
			'application/vnd.oasis.opendocument.text' => [
				'odt',
			],
			'application/vnd.oasis.opendocument.spreadsheet' => [
				'ods',
			],
			'application/vnd.oasis.opendocument.presentation' => [
				'odp',
			],
			// 'application/vnd.oasis.opendocument.text-template' => ['ott'],

			//zip
			'application/zip' => [
				'zip',
				// 'docx',
				// 'pptx',
				// 'ott',
			],
			//tar
			'application/x-tar' => [
				'tar',
				'tar.bz2',
				'tar.gz',
				'tar.xz',
			],

			//binario
			// 'application/octet-stream' => ['docx'],
		];
	}

	public static function ValidTypes() : array
	{
		// la primera extensión de cada tipo es la default.
		return [
			//images
			'PNG image data' => [
				'png',
			],
			'GIF image data' => [
				'gif',
			],
			'JPEG image data' => [
				'jpg',
				'jpeg',
			],
			'PC bitmap' => [
				'bmp',
			],

			'PDF document' => [
				'pdf',
			],

			'Composite Document File V2 Document' => [
				'doc',
				'dot',
				'pot',
				'pps',
				'ppt',
				'xls',
				'xlt',
			],
			'Microsoft Excel 2007+' => [
				'xlsx',
				'xlsb',
				'xltx',
				'xlsm',
				'xltm',
			],
			'Microsoft Excel Worksheet' => [
				'xls',
			],
			'Microsoft OOXML' => [
				'docx',
				'pptx',
			],
			'Microsoft PowerPoint 2007+' => [
				'pptx',
				'ppsx',
				'pptm',
				'ppsm',
				'potx',
				'potm',
			],
			'Microsoft WinWord 2.0 Document' => [
				'doc',
			],
			'Microsoft Word 2007+' => [
				'docx',
				'dotx',
				'docm',
				'dotm',
			],
			'OpenDocument Presentation' => [
				'odp',
			],
			'OpenDocument Spreadsheet' => [
				'ods',
			],
			// 'OpenDocument Text Template' => ['ott'],
			'OpenDocument Text' => [
				'odt',
			],
			'Rich Text Format data' => [
				'rtf',
			],
			'Zip archive data' => [
				'zip',
				'docx',
				'pptx',
				// 'ott',
			],
		];
	}

	public static function GetAllExtensionsFromMimeType(string $mime) : array
	{
		$mime = Str::ToLower($mime);
		$arr = self::ValidMimeTypes();
		if(isset($arr[$mime]))
			return $arr[$mime];
		Log::HandleSilentException(new \Exception('No se encontró extensión para mime: ' . $mime));
		throw new ErrorException('No se encontró extensión para el tipo');
	}

	public static function GetExtensionFromMimeType(string $mime) : string
	{
		$ret = self::GetAllExtensionsFromMimeType($mime);
		return $ret[0];
	}
}
