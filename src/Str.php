<?php

namespace minga\framework;

class Str
{

	public static function Convert($str, $to = 'Windows-1252', $from = 'UTF-8')
	{
		return iconv($from, $to, $str);
	}

	public static function DetectEncoding($str)
	{
		$encodings = [
			'UTF-8',
			'macintosh',
			'Windows-1252',
			'SJIS',
			'ISO-8859-1'
		];

		$encoding = 'UTF-8';
		foreach ($encodings as $encoding)
		{
			if ($encoding === "macintosh")
			{
				if (self::macCheckEncoding($str))
					return $encoding;
			}
			else if (mb_check_encoding($str, $encoding))
				return $encoding;
		}
		return null;
	}

	private static function macCheckEncoding($str) {
		// Estos caracteres son infrecuentes y representan caracteres extendidos castellanos
		// en el encoding MACROMAN (macintosh)
		$tokens = [ chr(0x87) // á -> ‡
			, chr(0x8e) // é -> Ž
			//, chr(0x92) // í -> ’
			//, chr(0x97) // ó -> —
			//, chr(0x9c) // ú -> œ
			//, chr(0xe7) // Á -> ç  (en portugués es frecuente ç; en castellano, no tanto Á)
			, chr(0x83) // É -> ƒ
			//, chr(0xea) // Í -> ê
			, chr(0xee) // Ó -> î
			//, chr(0xf2) // Ú -> ò

			, chr(0x9f) // ü -> Ÿ
			, chr(0x86) // Ü -> †
			//, chr(0x96) // ñ -> –
			, chr(0x84) // Ñ -> „
		];
		foreach($tokens as $token)
		{
			if (strpos($str, $token) !== false)
			{
				return true;
			}
		}
		return false;
	}

	public static function PolygonToCoordinates($polygon)
	{
		$ret = [];
		$cad = self::EatUntil($polygon, "((");
		$cad = self::Replace($cad, "))", "");
		$parts = explode(',', $cad);
		foreach($parts as $p)
		{
			$coords = explode(' ', $p);
			$ret[] = $coords;
		}
		return $ret;
	}

	/**
	 * Igual que explode de php pero no devuelve elementos vacíos.
	 */
	public static function ExplodeNoEmpty($delimiter, $str)
	{
		return array_values(array_filter(explode($delimiter, $str)));
	}

	//TODO: mover a una clase mejor.
	public static function BuildTotalsRow($list, $label, $columns)
	{
		$results = [];
		if ($label != "")
		{
			$results[$label] = 'Total';
			$results['isTotal'] = true;
		}
		// inicializa
		foreach($columns as $column)
			$results[$column] = 0;
		// suma
		foreach($list as $item)
		{
			foreach($columns as $column)
			{
				if (array_key_exists($column, $item))
					$results[$column] += $item[$column];
			}
		}
		// listo
		return $results;
	}

	public static function CultureCmp($a, $b)
	{
		$a2 = self::RemoveAccents($a);
		$b2 = self::RemoveAccents($b);
		return strcasecmp($a2, $b2);
	}

	public static function IntCmp($a, $b)
	{
		if ($a === null && $b === null)
			return 0;
		else if ($b === null)
			return 1;
		else if ($a === null)
			return -1;
		else
			return $a - $b;
	}

	public static function UrlencodeFriendly($cad)
	{
		return str_replace('%40', '@', urlencode($cad));
	}

	public static function FixEncoding($cad)
	{
		$cad = self::Replace($cad, 'Â¡', 'á');
		$cad = self::Replace($cad, 'Â¢', 'â');
		$cad = self::Replace($cad, 'Â£', 'ã');
		$cad = self::Replace($cad, 'Â¤', 'ä');
		$cad = self::Replace($cad, 'Â¥', 'å');
		$cad = self::Replace($cad, 'Â¦', 'æ');
		$cad = self::Replace($cad, 'Â§', 'ç');
		$cad = self::Replace($cad, 'Â¨', 'è');
		$cad = self::Replace($cad, 'Â©', 'é');
		$cad = self::Replace($cad, 'Âª', 'ê');
		$cad = self::Replace($cad, 'Â«', 'ë');
		$cad = self::Replace($cad, 'Â­', 'í');
		$cad = self::Replace($cad, 'Â®', 'î');
		$cad = self::Replace($cad, 'Â¯', 'ï');
		$cad = self::Replace($cad, 'Â°', 'ð');
		$cad = self::Replace($cad, 'Â±', 'ñ');
		$cad = self::Replace($cad, 'Â²', 'ò');
		$cad = self::Replace($cad, 'Â³', 'ó');
		$cad = self::Replace($cad, 'Â´', 'ô');
		$cad = self::Replace($cad, 'Âµ', 'õ');
		$cad = self::Replace($cad, 'Â·', '÷');
		$cad = self::Replace($cad, 'Â¸', 'ø');
		$cad = self::Replace($cad, 'Â¹', 'ù');
		$cad = self::Replace($cad, 'Âº', 'ú');
		$cad = self::Replace($cad, 'Â»', 'û');
		$cad = self::Replace($cad, 'Â¼', 'ü');
		$cad = self::Replace($cad, 'Â½', 'ý');
		$cad = self::Replace($cad, 'Â¾', 'þ');
		$cad = self::Replace($cad, 'Â¿', 'ÿ');
		$cad = self::Replace($cad, 'Ã€', 'À');
		$cad = self::Replace($cad, 'Ã', 'Á');
		$cad = self::Replace($cad, 'Ã‚', 'Â');
		$cad = self::Replace($cad, 'Ãƒ', 'Ã');
		$cad = self::Replace($cad, 'Ã„', 'Ä');
		$cad = self::Replace($cad, 'Ã…', 'Å');
		$cad = self::Replace($cad, 'Ã†', 'Æ');
		$cad = self::Replace($cad, 'Ã‡', 'Ç');
		$cad = self::Replace($cad, 'Ãˆ', 'È');
		$cad = self::Replace($cad, 'Ã‰', 'É');
		$cad = self::Replace($cad, 'ÃŠ', 'Ê');
		$cad = self::Replace($cad, 'Ã‹', 'Ë');
		$cad = self::Replace($cad, 'ÃŒ', 'Ì');
		$cad = self::Replace($cad, 'ÃŽ', 'Î');
		$cad = self::Replace($cad, 'Ã‘', 'Ñ');
		$cad = self::Replace($cad, 'Ã’', 'Ò');
		$cad = self::Replace($cad, 'Ã“', 'Ó');
		$cad = self::Replace($cad, 'Ã”', 'Ô');
		$cad = self::Replace($cad, 'Ã•', 'Õ');
		$cad = self::Replace($cad, 'Ã–', 'Ö');
		$cad = self::Replace($cad, 'Ã—', '×');
		$cad = self::Replace($cad, 'Ã˜', 'Ø');
		$cad = self::Replace($cad, 'Ã™', 'Ù');
		$cad = self::Replace($cad, 'Ãš', 'Ú');
		$cad = self::Replace($cad, 'Ã›', 'Û');
		$cad = self::Replace($cad, 'Ãœ', 'Ü');
		$cad = self::Replace($cad, 'Ãž', 'Þ');
		$cad = self::Replace($cad, 'ÃŸ', 'ß');
		$cad = self::Replace($cad, 'Ã¡', 'á');
		$cad = self::Replace($cad, 'Ã¢', 'â');
		$cad = self::Replace($cad, 'Ã£', 'ã');
		$cad = self::Replace($cad, 'Ã¤', 'ä');
		$cad = self::Replace($cad, 'Ã¥', 'å');
		$cad = self::Replace($cad, 'Ã¦', 'æ');
		$cad = self::Replace($cad, 'Ã§', 'ç');
		$cad = self::Replace($cad, 'Ã¨', 'è');
		$cad = self::Replace($cad, 'Ã©', 'é');
		$cad = self::Replace($cad, 'Ãª', 'ê');
		$cad = self::Replace($cad, 'Ã«', 'ë');
		$cad = self::Replace($cad, 'Ã­', 'í');
		$cad = self::Replace($cad, 'Ã®', 'î');
		$cad = self::Replace($cad, 'Ã¯', 'ï');
		$cad = self::Replace($cad, 'Ã°', 'ð');
		$cad = self::Replace($cad, 'Ã±', 'ñ');
		$cad = self::Replace($cad, 'Ã²', 'ò');
		$cad = self::Replace($cad, 'Ã³', 'ó');
		$cad = self::Replace($cad, 'Ã´', 'ô');
		$cad = self::Replace($cad, 'Ãµ', 'õ');
		$cad = self::Replace($cad, 'Ã·', '÷');
		$cad = self::Replace($cad, 'Ã¸', 'ø');
		$cad = self::Replace($cad, 'Ã¹', 'ù');
		$cad = self::Replace($cad, 'Ãº', 'ú');
		$cad = self::Replace($cad, 'Ã»', 'û');
		$cad = self::Replace($cad, 'Ã¼', 'ü');
		$cad = self::Replace($cad, 'Ã½', 'ý');
		$cad = self::Replace($cad, 'Ã¾', 'þ');
		$cad = self::Replace($cad, 'Ã¿', 'ÿ');
		return $cad;
	}

	public static function UrldecodeFriendly($cad)
	{
		return urldecode(str_replace('@', '%40', $cad));
	}

	public static function CrawlerUrlEncode($name)
	{
		$name = self::RemoveAccents(self::ToLower($name));
		$name = self::Replace($name, " ", "_");
		$name = self::Replace($name, ",", "_");
		$name = self::Replace($name, "/", "_");
		$name = self::Replace($name, "\\", "_");
		$name = self::Replace($name, "(", "_");
		$name = self::Replace($name, ")", "_");
		$name = self::Replace($name, ".", "_");
		$name = self::Replace($name, "__", "_");
		$name = self::Replace($name, "__", "_");
		$name = self::Replace($name, "__", "_");
		if (self::EndsWith($name, "_")) $name = substr($name, 0, strlen($name) - 1);
		return urlencode($name);
	}

	public static function SizeToHumanReadable($bytes, $precision = 2)
	{
		if ($bytes == "-")
			return;
		$units = ['b', 'KB', 'MB', 'GB', 'TB'];
		$bytes = max($bytes, 0);
		$pow = (int)floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		return number_format($bytes, $precision, ".", ",").' '.$units[$pow];
	}

	public static function StartsWith($haystack, $needle)
	{
		if ($needle === null) return false;
		return !strncmp($haystack, $needle, strlen($needle));
	}

	public static function StartsWithI($haystack, $needle)
	{
		if ($needle === null) return false;
		return !strncasecmp($haystack, $needle, strlen($needle));
	}

	public static function ReformatEndingNumber($text)
	{
		return $text;
	}

	//Contains case insensitve
	public static function ContainsI($haystack, $needle)
	{
		$pos = stripos($haystack , $needle);
		return ($pos !== false);
	}

	public static function Contains($haystack, $needle)
	{
		$pos = strpos($haystack , $needle);
		return ($pos !== false);
	}

	public static function EscapeJavascript($string)
	{
		return str_replace("'", '\'', str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', (string)$string), "\0..\37'\\"))));
	}

	public static function BooleanToText($value)
	{
		if ($value == "")
			return "";
		if ($value === "1" || $value === 1 || $value === true)
			return "Sí";

		return "No";
	}

	public static function SpanishSingle($value)
	{
		if (self::EndsWith($value, "les"))
			$value = self::RemoveEnding($value, "es");
		else if (self::EndsWith($value, "s"))
			$value = self::RemoveEnding($value, "s");
		return $value;
	}

	public static function AppendFulltextEndsWithAndRequiredSigns($originalQuery)
	{
		return self::ProcessQuotedBlock($originalQuery, function($keywords) {
			$keywords_filtered = array_filter($keywords, function($word) {
				return strlen($word) >= Context::Settings()->Db()->FullTextMinWordLength;
			});
			$subQuery = join("* +", $keywords_filtered);
			if ($subQuery != '')
				$subQuery = '+' . $subQuery . '*';
			return $subQuery;
		});
	}

	public static function ProcessQuotedBlock($originalQuery, $replacer)
	{
		// Agrega + al inicio de todas las palabras para que el query funcione como 'todas las palabras'
		$query = self::Replace($originalQuery, "'", '"');
		$quoteParts = explode('"', trim($query));
		$even = true;
		$ret = '';
		foreach($quoteParts as $part)
		{
			if ($part !== '' && in_array($part, ['+', '-', '*'], true) === false)
			{
				if ($even)
				{
					$keywords = explode(" ", trim($part));
					$ret .= $replacer($keywords) . " ";
				}
				else
					$ret .= $replacer(['"' . $part . '"']) . ' ';
			}
			$even = !$even;
		}
		// Listo
		return trim($ret);
	}

	public static function EatUntil($haystack, $needle)
	{
		$pos = mb_strpos($haystack, $needle);
		if ($pos === false)
			return $haystack;

		return substr($haystack, $pos + strlen($needle));
	}

	public static function CheapSqlEscape($cad)
	{
		if ($cad === null)
			return 'null';
		return "'" . Str::Replace($cad, "'", "\'") . "'";
	}

	public static function TwoSplit($text, $separator, &$first, &$last)
	{
		$pos = strpos($text, $separator);
		if ($pos === false)
		{
			$first = $text;
			$last = '';
		}
		else
		{
			$first = substr($text, 0, $pos);
			$last = substr($text, $pos + strlen($separator));
		}
	}

	public static function TwoSplitReverse($text, $separator, &$first, &$last)
	{
		$pos = strrpos($text, $separator);
		if ($pos === false)
		{
			$first = $text;
			$last = '';
		}
		else
		{
			$first = substr($text, 0, $pos);
			$last = substr($text, $pos + strlen($separator));
		}
	}

	public static function AppendParam($url, $param, $value = "")
	{
		$n = strpos($url, "#");
		$suffix = "";
		if ($n !== false)
		{
			$suffix = substr($url, $n);
			$url = substr($url, 0, $n);
		}

		$ret = $url;
		if (Str::Contains($ret, "?") == false)
			$ret .= "?";
		else
			$ret .= "&";
		$ret .= $param;
		if ($value != "")
			$ret .= "=" . urlencode($value);
		return $ret . $suffix;
	}

	public static function EatFrom($haystack, $needle)
	{
		$pos = strpos($haystack, $needle);
		if ($pos === false)
			return $haystack;

		return substr($haystack, 0, $pos);
	}

	public static function EnsureEndsWith($haystack, $needle)
	{
		if (self::EndsWith($haystack, $needle))
			return $haystack;
		return $haystack . $needle;
	}

	public static function EndsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0)
			return true;
		return (substr($haystack, -$length) === $needle);
	}

	private static function InsecureHash($cad)
	{
		return crypt($cad, 'universo');
	}

	public static function SecurePasswordHash($password)
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}

	public static function ValidatePassword($password, $hash, &$needRehash)
	{
		if ($hash === self::InsecureHash($password))
		{
			$needRehash = true;
			return true;
		}
		else if (password_verify($password, $hash))
		{
			$needRehash = password_needs_rehash($hash, PASSWORD_DEFAULT);
			return true;
		}
		$needRehash = false;
		return false;
	}


	public static function TextContainsWordList($list, $cad)
	{
		$ret = [];
		$cadSpaced = ' ' . $cad . ' ';
		foreach($list as $word)
			if (self::ContainsI($cadSpaced, ' ' . $word . ' '))
				$ret[] = $word;
		return $ret;
	}


	public static function ReplaceGroup($cad, $str, $s2)
	{
		for ($i = 0; $i < strlen($str); $i++)
			$cad = str_replace($str[$i], $s2, $cad);

		return $cad;
	}

	public static function Replace($subject, $search, $replace)
	{
		return str_replace($search, $replace, $subject);
	}

	public static function ReplaceOnce($cad, $str, $s2)
	{
		$pos = strpos($cad, $str);
		if ($pos !== false)
			return substr_replace($cad,$s2,$pos,strlen($str));

		return $cad;
	}

	public static function ReplaceLast($subject, $search, $replace)
	{
		$pos = strrpos($subject, $search);
		if($pos !== false)
			$subject = substr_replace($subject, $replace, $pos, strlen($search));

		return $subject;
	}

	public static function RemoveNonAlphanumeric($cad)
	{
		return preg_replace("/[^A-Za-z0-9 ]/", '', $cad);
	}

	public static function RemoveAccents($cad)
	{
		return strtr(utf8_decode($cad),
			utf8_decode(
				'µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØŎŒÙÚÛÜŠÝŸŽàáâãäåæçèéêëìíîïñòóôõöøōŏœùúûüšýÿž'),
			'uAAAAAAACEEEEIIIIDNOOOOOOOOUUUUSYYZaaaaaaaceeeeiiiinooooooooouuuusyyz');
	}

	public static function RemoveDot($cad)
	{
		if (self::EndsWith($cad, "."))
			return substr($cad, 0, strlen($cad) -1);
		return $cad;
	}

	public static function RemoveWordHiddenFormat($cad)
	{
		$cad = self::RemoveBlock($cad, "<xml>", "</xml>");
		$cad = self::RemoveBlock($cad, "<!--[if ", "<![endif]-->");
		return $cad;
	}

	public static function RemoveHtmlFormat($cad)
	{
		return self::RemoveBlock($cad, "<", ">");
	}

	public static function RemoveBlock($cad, $startTag, $endTag)
	{
		// le saca los tags de html
		while(false !== ($n = strpos($cad, $startTag)))
		{
			$end = strpos($cad, $endTag, $n);
			if ($end > 0)
			{
				$end += strlen($endTag);
				// remueve el pedazo
				$cad = substr($cad, 0, $n).
					substr($cad, $end);
			}
			else
				$cad = substr($cad, 0, $n);
		}
		return $cad;
	}

	public static function RemoveBegining($cad, $part)
	{
		if (self::StartsWith($cad, $part))
			$cad = substr($cad, strlen($part));
		return $cad;
	}

	public static function RemoveEnding($cad, $part)
	{
		if (self::EndsWith($cad, $part))
			$cad = substr($cad, 0, strlen($cad) - strlen($part));
		return $cad;
	}

	public static function RemoveParenthesis($cad)
	{
		$cad = self::RemoveBegining($cad, "(");
		$cad = self::RemoveEnding($cad, ")");
		return $cad;
	}

	public static function IsNullOrEmpty($cad)
	{
		return ($cad === '' || $cad === null);
	}

	public static function GetEndingPart($name, $separator)
	{
		$parts = explode($separator, $name);
		return $parts[count($parts) - 1];
	}

	public static function Ellipsis($cad, $maxSize = 50)
	{
		//TODO: se puede reemplazar por
		//return mb_strimwidth($cad, 0, $maxSize, '…', 'UTF-8');
		//hay que probarlo.

		if (self::Length($cad) > $maxSize)
			$cad = mb_substr($cad, 0, $maxSize - 2, "UTF-8") . "…";
		return $cad;
	}

	public static function EllipsisAnsi($cad, $maxSize = 40, $signal = '..')
	{
		if (strlen($cad) > $maxSize)
		{
			$cad = substr($cad, 0, $maxSize - strlen($signal) + 1);
			if (self::EndsWith($cad, ' ') == false)
				$cad .= ' ' . $signal;
			else
				$cad .= $signal;
		}
		return $cad;
	}

	public static function Capitalize($cad)
	{
		return mb_strtoupper(mb_substr($cad, 0, 1)) . mb_substr($cad, 1);
	}

	public static function StartsWithAlfabetic($cad)
	{
		return ctype_alpha(self::RemoveAccents(mb_substr($cad, 0, 1)));
	}

	public static function TextAreaTextToHtml($cad)
	{
		$cad = htmlspecialchars($cad);
		return nl2br($cad);
	}

	public static function Length($str, $encoding = 'UTF-8')
	{
		return mb_strlen($str, $encoding);
	}

	public static function Substr($str, $start, $length = null, $encoding = 'UTF-8')
	{
		return mb_substr($str, $start, $length, $encoding);
	}

	public static function Concat($a, $b, $separator)
	{
		if (trim($a) == "" || trim($b) == "")
			$separator = "";
		return trim($a) . $separator . trim($b);
	}

	public static function ToLower($str)
	{
		return mb_convert_case($str, MB_CASE_LOWER);
	}

	public static function ToUpper($str)
	{
		return mb_convert_case($str, MB_CASE_UPPER);
	}

	public static function CountWords($str)
	{
		$unicode = '';
		if(self::IsUtf8($str))
			$unicode = 'u';
		return count(preg_split('/\s+/i' . $unicode, $str,
			null, PREG_SPLIT_NO_EMPTY));
	}

	public static function ToUnicode($cad)
	{
		$cad2 = $cad;
		try
		{
			$cad2 = @iconv('Windows-1252', 'UTF-8', $cad);
		}
		catch (\Exception $e)
		{
		}
		return $cad2;
	}

	public static function IsUtf8($str)
	{
		return mb_check_encoding($str, 'UTF-8');
	}

	public static function GetNWords($str, $n)
	{
		$unicode = '';
		if(self::IsUtf8($str))
			$unicode = 'u';
		$words = preg_split('/\s+/i' . $unicode, $str,
			null, PREG_SPLIT_NO_EMPTY);
		return implode(' ', array_slice($words, 0, $n));
	}

	public static function RemoveResumenWord($newAbstract)
	{
		$newAbstract = self::RemoveBegining($newAbstract, 'resumen:');
		$newAbstract = self::RemoveBegining($newAbstract, 'RESUMEN:');
		$newAbstract = self::RemoveBegining($newAbstract, 'Resumen:');
		$newAbstract = self::RemoveBegining($newAbstract, 'abstract:');
		$newAbstract = self::RemoveBegining($newAbstract, 'ABSTRACT:');
		$newAbstract = self::RemoveBegining($newAbstract, 'Abstract:');

		$newAbstract = self::RemoveBegining($newAbstract, 'resumen.');
		$newAbstract = self::RemoveBegining($newAbstract, 'RESUMEN.');
		$newAbstract = self::RemoveBegining($newAbstract, 'Resumen.');
		$newAbstract = self::RemoveBegining($newAbstract, 'abstract.');
		$newAbstract = self::RemoveBegining($newAbstract, 'ABSTRACT.');
		$newAbstract = self::RemoveBegining($newAbstract, 'Abstract.');
		return $newAbstract;
	}

	public static function RemoveWordFormats($str)
	{
		$str = trim($str);
		$l = 0;
		while($l != strlen($str))
		{
			$l = strlen($str);

			$str = self::RemoveDelimited($str, "&lt;!--", "--&gt;", " ");
			$str = self::RemoveDelimited($str, "&amp;lt;!--", "--&amp;gt;", " ");
			$str = self::RemoveDelimited($str, "&amp;amp;lt;!--", "--&amp;amp;gt;", " ");
			$str = self::RemoveDelimited($str, "&amp;amp;amp;lt;!--", "--&amp;amp;amp;gt;", " ");
			$str = self::RemoveDelimited($str, "&amp;amp;amp;amp;lt;!--", "--&amp;amp;amp;amp;gt;", " ");
			$str = self::RemoveDelimited($str, "&amp;amp;amp;amp;amp;lt;!--", '--&amp;amp;amp;amp;amp;gt;', " ");
			$str = self::RemoveDelimited($str, "&amp;amp;amp;amp;amp;amp;lt;!--", '--&amp;amp;amp;amp;amp;amp;gt;', " ");
			$str = self::RemoveDelimited($str, "&amp;amp;amp;amp;amp;amp;amp;lt;!--", '--&amp;amp;amp;amp;amp;amp;amp;gt;', " ");
			$str = self::RemoveDelimited($str, "&amp;amp;amp;amp;amp;amp;amp;amp;lt;!--", '--&amp;amp;amp;amp;amp;amp;amp;amp;gt;', " ");
			$str = self::RemoveDelimited($str, "&amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;!--", '--&amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;', " ");
			$str = self::RemoveDelimited($str, "&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;!--", '--&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;', " ");
			$str = self::RemoveDelimited($str, "&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;!--", '--&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;', " ");
			$str = self::RemoveDelimited($str, "&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;lt;!--", '--&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;gt;', " ");
		}
		return $str;
	}

	public static function RemoveDelimited($str, $from, $end, $repl = '')
	{
		$p1 = strpos($str, $from);
		if ($p1 !== false)
		{
			$pos = strpos($str, $end, $p1 + strlen($from));
			if ($pos !== false)
			{
				$str = substr($str, 0, $p1) . $repl
					. substr($str, $pos + strlen($end));
			}
		}
		return $str;
	}

	public static function GetLastNWords($str, $n)
	{
		$unicode = '';
		if(self::IsUtf8($str))
			$unicode = 'u';
		$words = preg_split('/\s+/i' . $unicode, $str,
			null, PREG_SPLIT_NO_EMPTY);
		return implode(' ',
			array_slice($words, count($words) - $n, $n));
	}

	public static function FormatDateDMY($str)
	{
		if ($str == "")
			return "-";
		return substr($str, 8, 2) . "/" . substr($str, 5, 2) . "/" . substr($str, 2, 2);
	}

	public static function FormatDateYYMD($str)
	{
		if ($str == "")
			return "-";
		return substr($str, 0, 4) . "-" . substr($str, 5, 2) . "-" . substr($str, 8, 2);
	}

	public static function IsNumber($cad)
	{
		return is_numeric($cad);
	}

	public static function IsNumberNotPlaceheld($cad)
	{
		if (strlen($cad) > 1 && $cad[0] === '0' && $cad[1] !== '.')
			return false;

		return self::IsNumber($cad);
	}

	/**
	 * Devuelve strings bien formados para XML.
	 */
	public static function CleanXmlString($str)
	{
		// Los caracteres ascii bajos (menores a 0x20 espacio)
		// rompen los parsers de xml (pasa en chrome y firefox).
		$replace = [
			chr(0x0000), chr(0x0001), chr(0x0002), chr(0x0003),
			chr(0x0004), chr(0x0005), chr(0x0006), chr(0x0007),
			chr(0x0008), chr(0x0009), chr(0x000a), chr(0x000b),
			chr(0x000c), chr(0x000d), chr(0x000e), chr(0x000f),
			chr(0x0010), chr(0x0011), chr(0x0012), chr(0x0013),
			chr(0x0014), chr(0x0015), chr(0x0016), chr(0x0017),
			chr(0x0018), chr(0x0019), chr(0x001a), chr(0x001b),
			chr(0x001c), chr(0x001d), chr(0x001e), chr(0x001f),
		];
		return htmlspecialchars(
			str_replace($replace, '', $str));
	}

	public static function SmartImplode($partsRaw, $trailingCad = "", $normalization = 0)
	{
		// $normalization = 0: Nada
		// $normalization = 1: Convierte Perez, Carlos => Perez, C.
		// $normalization = 2: Convierte Perez, Carlos => Carlos Perez

		$text = "";
		if (is_array($partsRaw))
		{
			$parts = [];
			foreach($partsRaw as $part)
			{
				if (is_array($part))
					$part = trim($part['name']);
				else
					$part = trim($part);
				if ($part != "")
					$parts[] = $part;
			}

			for($n = 0; $n < count($parts); $n++)
			{
				$part = $parts[$n];
				$cleaned = trim($part);

				$cleaned = self::RemoveEnding($cleaned, ",");
				// normaliza
				$cleaned = self::NormalizeName($cleaned, $normalization);

				if ($n > 0)
				{
					if ($n < count($parts) - 1)
						$text .= ", ";
					else
						$text .= " y ";
				}
				$text .= $cleaned;
			}
		}
		else
			$text = trim($partsRaw);
		if (self::EndsWith($text, $trailingCad) == false && $text != "")
			$text .= $trailingCad;
		return $text;
	}

	public static function NormalizeName($cad, $normalization)
	{
		if (Context::Settings()->normalizeNames == false)
			return $cad;
		// $normalization = 0: Nada
		// $normalization = 1: Convierte Perez, Carlos => Perez, C.
		// $normalization = 2: Convierte Perez, Carlos => Carlos Perez
		if ($normalization == 0)
			return $cad;
		if ($normalization == 1)
		{
			$n = strpos($cad, ",");
			if ($n == 0)
				return $cad;
			$pre = substr($cad, 0, $n);
			$post = substr($cad, $n + 1);
			$post = self::Initials($post);
			return $pre . ", " . trim($post);
		}
		if ($normalization == 2)
		{
			$n = strpos($cad, ",");
			if ($n == 0)
				return $cad;
			$cad = substr($cad, $n + 1) . " " . substr($cad, 0, $n);
			$cad = self::Replace($cad, "  ", " ");
			return $cad;
		}
		throw new ErrorException('Invalid normalization argument.');
	}

	public static function Initials($cad)
	{
		$ret = "";
		$parts = explode(" ", $cad);
		$cancelled = false;

		foreach($parts as $part)
		{
			if ($cancelled || $part == "")
				$ret .= " " . $part;
			else
			{
				if (self::IsAllLetters($part))
					$ret .= " " . mb_strtoupper(mb_substr($part, 0, 1)) . ".";
				else
				{
					$ret .= " " . $part;
					$cancelled = true;
					// cancela por si se encontró con una filiación
					// o con cualquier otra cosa rara que no convenga
					// abreviar
				}
			}
		}
		return $ret;
	}

	public static function IsAllLetters($cad)
	{
		$cad = self::RemoveAccents(self::ToLower($cad));
		for($n = 0; $n < strlen($cad); $n++)
		{
			if ($cad[$n] < 'a' || $cad[$n] > 'z')
				return false;
		}
		return true;
	}

	public static function IsRoman($cad)
	{
		$regex = '/^M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/';
		return preg_match($regex, strtoupper($cad));
	}

	public static function HtmlDecode($string)
	{
		return self::DecodeEntities($string);
	}

	public static function DecodeEntities($string, $quotes = ENT_COMPAT, $charset = 'UTF-8')
	{
		$p = html_entity_decode(preg_replace_callback('/&([a-zA-Z][a-zA-Z0-9]+);/', function($a) { return self::ConvertEntity($a); }, $string), $quotes, $charset);
		while(strpos($p, "&#") !== false)
		{
			$pos = strpos($p, "&#");
			$i = intval(substr($p, $pos + 2, 3));
			$p = substr($p, 0, $pos) . chr($i) . substr($p, $pos + 6);
		}
		$p = self::Replace($p, '\\"', '"');
		$p = self::Replace($p, "\\'", "'");
		return $p;
	}

	//array_search case insensitve
	public static function ArraySearchI($needle, $haystack)
	{
		return array_search(mb_strtolower($needle), array_map('mb_strtolower', $haystack));
	}

	public static function RandomString($len = 12)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$rand = random_bytes($len);
		$str = '';
		for($i = 0; $i < $len; $i++)
		{
			$index = ord($rand[$i]) % strlen($chars);
			$str .= $chars[$index];
		}
		return $str;
	}

	public static function FormatLocaleNumber($value, $decimals = 0)
	{
		return number_format($value, $decimals, ",", "");
	}

	public static function FormatNumber($value, $decimals = 0, $leadingZeros = 0)
	{
		$format = '%0';
		if ($leadingZeros > 0)
			$format	.= $leadingZeros;
		$format .= "." . $decimals . "f";
		return sprintf($format, $value);
	}

	public static function FormatPercentage($value, $total)
	{
		if ($total == 0) return "-";
		return number_format($value * 100 / $total, 1, ".", "") . "%";
	}

	public static function ConvertEntity($matches, $destroy = true)
	{
		static $table = [
			'quot' => '&#34;', 'amp' => '&#38;', 'lt' => '&#60;', 'gt' => '&#62;',
			'OElig' => '&#338;', 'oelig' => '&#339;', 'Scaron' => '&#352;', 'scaron' => '&#353;',
			'Yuml' => '&#376;', 'circ' => '&#710;', 'tilde' => '&#732;', 'ensp' => '&#8194;',
			'emsp' => '&#8195;', 'thinsp' => '&#8201;', 'zwnj' => '&#8204;', 'zwj' => '&#8205;',
			'lrm' => '&#8206;', 'rlm' => '&#8207;', 'ndash' => '&#8211;', 'mdash' => '&#8212;',
			'lsquo' => '&#8216;', 'rsquo' => '&#8217;', 'sbquo' => '&#8218;', 'ldquo' => '&#8220;',
			'rdquo' => '&#8221;', 'bdquo' => '&#8222;', 'dagger' => '&#8224;', 'Dagger' => '&#8225;',
			'permil' => '&#8240;', 'lsaquo' => '&#8249;', 'rsaquo' => '&#8250;', 'euro' => '&#8364;',
			'fnof' => '&#402;', 'Alpha' => '&#913;', 'Beta' => '&#914;', 'Gamma' => '&#915;',
			'Delta' => '&#916;', 'Epsilon' => '&#917;', 'Zeta' => '&#918;', 'Eta' => '&#919;',
			'Theta' => '&#920;', 'Iota' => '&#921;', 'Kappa' => '&#922;', 'Lambda' => '&#923;',
			'Mu' => '&#924;', 'Nu' => '&#925;', 'Xi' => '&#926;', 'Omicron' => '&#927;',
			'Pi' => '&#928;', 'Rho' => '&#929;', 'Sigma' => '&#931;', 'Tau' => '&#932;',
			'Upsilon' => '&#933;', 'Phi' => '&#934;', 'Chi' => '&#935;', 'Psi' => '&#936;',
			'Omega' => '&#937;', 'alpha' => '&#945;', 'beta' => '&#946;', 'gamma' => '&#947;',
			'delta' => '&#948;', 'epsilon' => '&#949;', 'zeta' => '&#950;', 'eta' => '&#951;',
			'theta' => '&#952;', 'iota' => '&#953;', 'kappa' => '&#954;', 'lambda' => '&#955;',
			'mu' => '&#956;', 'nu' => '&#957;', 'xi' => '&#958;', 'omicron' => '&#959;',
			'pi' => '&#960;', 'rho' => '&#961;', 'sigmaf' => '&#962;', 'sigma' => '&#963;',
			'tau' => '&#964;', 'upsilon' => '&#965;', 'phi' => '&#966;', 'chi' => '&#967;',
			'psi' => '&#968;', 'omega' => '&#969;', 'thetasym' => '&#977;', 'upsih' => '&#978;',
			'piv' => '&#982;', 'bull' => '&#8226;', 'hellip' => '&#8230;', 'prime' => '&#8242;',
			'Prime' => '&#8243;', 'oline' => '&#8254;', 'frasl' => '&#8260;', 'weierp' => '&#8472;',
			'image' => '&#8465;', 'real' => '&#8476;', 'trade' => '&#8482;', 'alefsym' => '&#8501;',
			'larr' => '&#8592;', 'uarr' => '&#8593;', 'rarr' => '&#8594;', 'darr' => '&#8595;',
			'harr' => '&#8596;', 'crarr' => '&#8629;', 'lArr' => '&#8656;', 'uArr' => '&#8657;',
			'rArr' => '&#8658;', 'dArr' => '&#8659;', 'hArr' => '&#8660;', 'forall' => '&#8704;',
			'part' => '&#8706;', 'exist' => '&#8707;', 'empty' => '&#8709;', 'nabla' => '&#8711;',
			'isin' => '&#8712;', 'notin' => '&#8713;', 'ni' => '&#8715;', 'prod' => '&#8719;',
			'sum' => '&#8721;', 'minus' => '&#8722;', 'lowast' => '&#8727;', 'radic' => '&#8730;',
			'prop' => '&#8733;', 'infin' => '&#8734;', 'ang' => '&#8736;', 'and' => '&#8743;',
			'or' => '&#8744;', 'cap' => '&#8745;', 'cup' => '&#8746;', 'int' => '&#8747;',
			'there4' => '&#8756;', 'sim' => '&#8764;', 'cong' => '&#8773;', 'asymp' => '&#8776;',
			'ne' => '&#8800;', 'equiv' => '&#8801;', 'le' => '&#8804;', 'ge' => '&#8805;',
			'sub' => '&#8834;', 'sup' => '&#8835;', 'nsub' => '&#8836;', 'sube' => '&#8838;',
			'supe' => '&#8839;', 'oplus' => '&#8853;', 'otimes' => '&#8855;', 'perp' => '&#8869;',
			'sdot' => '&#8901;', 'lceil' => '&#8968;', 'rceil' => '&#8969;', 'lfloor' => '&#8970;',
			'rfloor' => '&#8971;', 'lang' => '&#9001;', 'rang' => '&#9002;', 'loz' => '&#9674;',
			'spades' => '&#9824;', 'clubs' => '&#9827;', 'hearts' => '&#9829;', 'diams' => '&#9830;',
			'nbsp' => '&#160;', 'iexcl' => '&#161;', 'cent' => '&#162;', 'pound' => '&#163;',
			'curren' => '&#164;', 'yen' => '&#165;', 'brvbar' => '&#166;', 'sect' => '&#167;',
			'uml' => '&#168;', 'copy' => '&#169;', 'ordf' => '&#170;', 'laquo' => '&#171;',
			'not' => '&#172;', 'shy' => '&#173;', 'reg' => '&#174;', 'macr' => '&#175;',
			'deg' => '&#176;', 'plusmn' => '&#177;', 'sup2' => '&#178;', 'sup3' => '&#179;',
			'acute' => '&#180;', 'micro' => '&#181;', 'para' => '&#182;', 'middot' => '&#183;',
			'cedil' => '&#184;', 'sup1' => '&#185;', 'ordm' => '&#186;', 'raquo' => '&#187;',
			'frac14' => '&#188;', 'frac12' => '&#189;', 'frac34' => '&#190;', 'iquest' => '&#191;',
			'Agrave' => '&#192;', 'Aacute' => '&#193;', 'Acirc' => '&#194;', 'Atilde' => '&#195;',
			'Auml' => '&#196;', 'Aring' => '&#197;', 'AElig' => '&#198;', 'Ccedil' => '&#199;',
			'Egrave' => '&#200;', 'Eacute' => '&#201;', 'Ecirc' => '&#202;', 'Euml' => '&#203;',
			'Igrave' => '&#204;', 'Iacute' => '&#205;', 'Icirc' => '&#206;', 'Iuml' => '&#207;',
			'ETH' => '&#208;', 'Ntilde' => '&#209;', 'Ograve' => '&#210;', 'Oacute' => '&#211;',
			'Ocirc' => '&#212;', 'Otilde' => '&#213;', 'Ouml' => '&#214;', 'times' => '&#215;',
			'Oslash' => '&#216;', 'Ugrave' => '&#217;', 'Uacute' => '&#218;', 'Ucirc' => '&#219;',
			'Uuml' => '&#220;', 'Yacute' => '&#221;', 'THORN' => '&#222;', 'szlig' => '&#223;',
			'agrave' => '&#224;', 'aacute' => '&#225;', 'acirc' => '&#226;', 'atilde' => '&#227;',
			'auml' => '&#228;', 'aring' => '&#229;', 'aelig' => '&#230;', 'ccedil' => '&#231;',
			'egrave' => '&#232;', 'eacute' => '&#233;', 'ecirc' => '&#234;', 'euml' => '&#235;',
			'igrave' => '&#236;', 'iacute' => '&#237;', 'icirc' => '&#238;', 'iuml' => '&#239;',
			'eth' => '&#240;', 'ntilde' => '&#241;', 'ograve' => '&#242;', 'oacute' => '&#243;',
			'ocirc' => '&#244;', 'otilde' => '&#245;', 'ouml' => '&#246;', 'divide' => '&#247;',
			'oslash' => '&#248;', 'ugrave' => '&#249;', 'uacute' => '&#250;', 'ucirc' => '&#251;',
			'uuml' => '&#252;', 'yacute' => '&#253;', 'thorn' => '&#254;', 'yuml' => '&#255;'
		];
		if (isset($table[$matches[1]]))
			return $table[$matches[1]];

		return $destroy ? '' : $matches[0];
	}
}

