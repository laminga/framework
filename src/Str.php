<?php

namespace minga\framework;

class Str
{
	public static function Guid() : string
	{
		$str = bin2hex(random_bytes(16));
		return substr($str, 0, 8) . '-' . substr($str, 8, 4)
			. '-' . substr($str, 12, 4) . '-' . substr($str, 16, 4)
			. '-' . substr($str, 20, 12);
	}

	public static function IsEmail(string $email) : bool
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
	}

	public static function Convert(string $str, string $to = 'Windows-1252', string $from = 'UTF-8', bool $translit = false, bool $ignore = false) : string
	{
		if($translit)
			$to .= '//TRANSLIT';
		if($ignore)
			$to .= '//IGNORE';
		$ret = iconv($from, $to, $str);
		if($ret === false)
			throw new ErrorException(Context::Trans('Error convirtiendo texto.'));
		return $ret;
	}

	public static function DetectEncoding(string $str) : ?string
	{
		$encodings = [
			'UTF-8',
			'macintosh',
			'Windows-1252',
			'SJIS',
			'ISO-8859-1',
		];

		foreach ($encodings as $encoding)
		{
			if ($encoding === "macintosh")
			{
				if (self::MacCheckEncoding($str))
					return $encoding;
			}
			else if (mb_check_encoding($str, $encoding))
				return $encoding;
		}
		return null;
	}

	private static function MacCheckEncoding(string $str) : bool
	{
		// Estos caracteres son infrecuentes y representan caracteres extendidos castellanos
		// en el encoding MACROMAN (macintosh)
		$tokens = [
			chr(0x87) // á -> ‡
			, chr(0x8E) // é -> Ž
			//, chr(0x92) // í -> ’
			//, chr(0x97) // ó -> —
			//, chr(0x9c) // ú -> œ
			//, chr(0xe7) // Á -> ç (en portugués es frecuente ç; en castellano, no tanto Á)
			, chr(0x83) // É -> ƒ
			//, chr(0xea) // Í -> ê
			, chr(0xEE) // Ó -> î
			//, chr(0xf2) // Ú -> ò

			, chr(0x9F) // ü -> Ÿ
			, chr(0x86) // Ü -> †
			//, chr(0x96) // ñ -> –
			, chr(0x84), // Ñ -> „
		];
		foreach($tokens as $token)
		{
			if (strpos($str, $token) !== false)
				return true;
		}
		return false;
	}

	public static function PolygonToCoordinates(string $polygon) : array
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
	public static function ExplodeNoEmpty(string $delimiter, string $str) : array
	{
		if($delimiter == "")
			return [$str];
		return array_values(array_filter(explode($delimiter, $str)));
	}

	public static function CultureCmp(?string $a, ?string $b) : int
	{
		if($a === null)
			$a = "";
		if($b == null)
			$b = "";
		$a2 = self::RemoveAccents($a);
		$b2 = self::RemoveAccents($b);
		return strcasecmp($a2, $b2);
	}

	public static function IntCmp(?int $a, ?int $b) : int
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

	public static function Uncompact(?string $a, array $dict) : ?string
	{
		$ret = $a;
		for($n = count($dict) - 1; $n >= 0; $n--)
			$ret = self::Replace($ret, $dict[$n]['k'], $dict[$n]['v']);
		return $ret;
	}

	public static function UrlencodeFriendly(?string $cad) : string
	{
		if($cad === null)
			$cad = "";
		return str_replace('%40', '@', urlencode($cad));
	}

	public static function FixEncoding(?string $cad) : string
	{
		if($cad === null)
			$cad = "";
		$src = [
			'Â¡', 'Â¢', 'Â£', 'Â¤', 'Â¥', 'Â¦', 'Â§', 'Â¨', 'Â©', 'Âª', 'Â«', 'Â­', 'Â®', 'Â¯', 'Â°', 'Â±', 'Â²',
			'Â³', 'Â´', 'Âµ', 'Â·', 'Â¸', 'Â¹', 'Âº', 'Â»', 'Â¼', 'Â½', 'Â¾', 'Â¿', 'Ã€', 'Ã', 'Ã‚', 'Ãƒ', 'Ã„',
			'Ã…', 'Ã†', 'Ã‡', 'Ãˆ', 'Ã‰', 'ÃŠ', 'Ã‹', 'ÃŒ', 'ÃŽ', 'Ã‘', 'Ã’', 'Ã“', 'Ã”', 'Ã•', 'Ã–', 'Ã—', 'Ã˜',
			'Ã™', 'Ãš', 'Ã›', 'Ãœ', 'Ãž', 'ÃŸ', 'Ã¡', 'Ã¢', 'Ã£', 'Ã¤', 'Ã¥', 'Ã¦', 'Ã§', 'Ã¨', 'Ã©', 'Ãª', 'Ã«',
			'Ã­', 'Ã®', 'Ã¯', 'Ã°', 'Ã±', 'Ã²', 'Ã³', 'Ã´', 'Ãµ', 'Ã·', 'Ã¸', 'Ã¹', 'Ãº', 'Ã»', 'Ã¼', 'Ã½', 'Ã¾', 'Ã¿',
		];
		$dst = [
			'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ',
			'÷', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'þ', 'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê',
			'Ë', 'Ì', 'Î', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', '×', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Þ', 'ß', 'á', 'â', 'ã',
			'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', '÷', 'ø', 'ù',
			'ú', 'û', 'ü', 'ý', 'þ', 'ÿ',
		];
		return str_replace($src, $dst, $cad);
	}

	public static function UrlDecodeFriendly(string $cad) : string
	{
		return urldecode(str_replace('@', '%40', $cad));
	}

	public static function CrawlerUrlEncode(?string $name) : string
	{
		if ($name === null)
			$name = "";

		$name = self::RemoveAccents(self::ToLower($name));
		$name = self::ReplaceGroup($name, [" ", "(", ")", ",", ".", "/", "\\", "__"], "_");
		if (self::EndsWith($name, "_"))
			$name = substr($name, 0, strlen($name) - 1);
		return urlencode($name);
	}

	/**
	 * @param string|int $bytes
	 */
	public static function SizeToHumanReadable($bytes, int $precision = 2) : string
	{
		if ($bytes == "-")
			return '';
		$units = ['b', 'KB', 'MB', 'GB', 'TB'];
		$bytes = max($bytes, 0);
		$pow = (int)floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);
		return number_format($bytes, $precision, ".", ",") . ' ' . $units[$pow];
	}

	public static function StartsWith(?string $haystack, ?string $needle) : bool
	{
		if ($needle === null)
			return false;
		if ($haystack === null)
			return false;
		return (bool)strncmp($haystack, $needle, strlen($needle)) == false;
	}

	public static function StartsWithI(?string $haystack, ?string $needle) : bool
	{
		if ($haystack === null)
			$haystack = "";
		if ($needle === null)
			return false;
		return (bool)strncasecmp($haystack, $needle, strlen($needle)) == false;
	}

	//Contains case insensitve
	public static function ContainsI(?string $haystack, string $needle) : bool
	{
		if($haystack === null)
			$haystack = "";
		$pos = stripos($haystack, $needle);
		return $pos !== false;
	}

	public static function Contains(?string $haystack, string $needle) : bool
	{
		if($haystack === null)
			$haystack = "";
		$pos = strpos($haystack, $needle);
		return $pos !== false;
	}

	public static function ContainsAny(string $haystack, array $needles) : bool
	{
		foreach($needles as $needle)
			if (self::Contains($haystack, $needle))
				return true;
		return false;
	}

	public static function ContainsAnyI(?string $haystack, array $needles) : bool
	{
		foreach($needles as $needle)
		{
			if (self::ContainsI($haystack, $needle))
				return true;
		}
		return false;
	}

	public static function EscapeJavascript(string $string) : string
	{
		return str_replace("'", '\'', str_replace("\n", '\n', str_replace('"', '\"',
			addcslashes(str_replace("\r", '', (string)$string), "\0..\37'\\"))));
	}

	public static function BooleanToText(bool $value) : string
	{
		if ($value)
			return Context::Trans('Sí');
		return Context::Trans('No');
	}

	public static function SpanishSingle(?string $value) : string
	{
		if (self::EndsWith($value, "les"))
			$value = self::RemoveEnding($value, "es");
		else if (self::EndsWith($value, "s"))
			$value = self::RemoveEnding($value, "s");
		return $value;
	}

	private static function AssociateShortWords(array $words) : array
	{
		$ret = [];
		$min = Context::Settings()->Db()->FullTextMinWordLength;
		for($n = 0; $n < count($words); $n++)
		{
			$isLast = ($n === count($words) - 1);
			$isBeforeLast = ($n === count($words) - 2);
			// si es corta la asocia con la siguiente, o si es la anteúltima y
			// la última es corta
			if (($isLast == false && strlen($words[$n]) < $min)
				|| ($isBeforeLast && strlen($words[$n + 1]) < $min))
			{
				$ret[] = '"' . $words[$n] . ' ' . $words[$n + 1] . '"';
				$n++;
			}
			else
				$ret[] = $words[$n];
		}
		return $ret;
	}

	private static function HasShortWord(array $arr) : bool
	{
		$min = Context::Settings()->Db()->FullTextMinWordLength;
		foreach($arr as $str)
			if (strlen($str) < $min)
				return true;
		return false;
	}

	public static function AppendFulltextEndsWithAndRequiredSigns(string $originalQuery) : string
	{
		return self::ProcessQuotedBlock($originalQuery, function(array $keywords) : string {
			$keywordsFiltered = array_filter($keywords, function(string $word) : bool {
				return strlen($word) >= Context::Settings()->Db()->FullTextMinWordLength;
			});

			$subQuery = implode("* +", $keywordsFiltered);
			if ($subQuery != '')
				$subQuery = '+' . $subQuery . '*';
			return $subQuery;
		});
	}

	public static function ProcessQuotedBlock(string $originalQuery, callable $replacer) : string
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
					$keyBlocks = explode(",", $part);
					foreach($keyBlocks as $block)
					{
						$keywords = explode(" ", trim($block));
						$keywordBlocks = self::AssociateShortWords($keywords);
						$ret .= $replacer($keywordBlocks) . " ";
						//if (self::HasShortWord($keywords))
						// $ret .= $replacer(['"' . trim($block) . '"']) . ' ';
						//else
						// $ret .= $replacer($keywords) . " ";
					}
				}
				else
					$ret .= $replacer(['"' . $part . '"']) . ' ';
			}
			$even = !$even;
		}
		return trim($ret);
	}

	public static function EatUntil(string $haystack, string $needle) : string
	{
		$pos = mb_strpos($haystack, $needle);
		if ($pos === false)
			return $haystack;

		return substr($haystack, $pos + strlen($needle));
	}

	public static function CheapSqlEscape(?string $cad) : string
	{
		if ($cad === null)
			return 'null';
		return "'" . Str::Replace($cad, "'", "\'") . "'";
	}

	public static function TwoSplit(?string $text, ?string $separator, ?string &$first, ?string &$last) : void
	{
		if($text === null)
			$text = "";
		if($separator === null)
			$separator = "";

		$pos = strpos($text, $separator);
		if ($pos === false)
		{
			$first = $text;
			$last = '';
		}
		else
		{
			$first = (string)substr($text, 0, $pos);
			$last = (string)substr($text, $pos + strlen($separator));
		}
	}

	public static function TwoSplitReverse(?string $text, ?string $separator, ?string &$first, ?string &$last) : void
	{
		if($text === null)
			$text = "";

		if($separator === null)
			$separator = "";

		$pos = strrpos($text, $separator);
		if ($pos === false)
		{
			$first = $text;
			$last = '';
		}
		else
		{
			$first = (string)substr($text, 0, $pos);
			$last = (string)substr($text, $pos + strlen($separator));
		}
	}

	public static function AppendParam(?string $url, ?string $param, ?string $value = "") : string
	{
		if($url === null)
			$url = "";
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

	public static function EatFrom(?string $haystack, ?string $needle) : string
	{
		if($haystack === null)
			$haystack = "";
		if($needle === null)
			$needle = "";
		$pos = strpos($haystack, $needle);
		if ($pos === false)
			return $haystack;

		return substr($haystack, 0, $pos);
	}

	public static function EnsureEndsWith(?string $haystack, ?string $needle) : string
	{
		if($haystack === null)
			$haystack = "";
		if($needle === null)
			$needle = "";
		if (self::EndsWith($haystack, $needle))
			return $haystack;
		return $haystack . $needle;
	}

	public static function EndsWith(?string $haystack, ?string $needle) : bool
	{
		if($haystack === null)
			$haystack = "";
		if($needle === null)
			$needle = "";
		$length = strlen($needle);
		if ($length == 0)
			return true;
		return substr($haystack, -$length) === $needle;
	}

	private static function InsecureHash(string $cad) : string
	{
		return crypt($cad, 'universo');
	}

	public static function SecurePasswordHash(string $password) : string
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}

	public static function ValidatePassword(string $password, string $hash, ?bool &$needRehash) : bool
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

	public static function TextContainsWordList(array $list, ?string $cad) : array
	{
		$ret = [];
		$cadSpaced = ' ' . $cad . ' ';
		foreach($list as $word)
			if (self::ContainsI($cadSpaced, ' ' . $word . ' '))
				$ret[] = $word;
		return $ret;
	}

	public static function ReplaceGroup(?string $subject, array $search, ?string $replace) : string
	{
			if($subject === null)
				$subject = "";
			if($replace === null)
				$replace = "";
		return str_replace($search, $replace, $subject);
	}

	public static function ReplaceI(?string $subject, ?string $search, ?string $replace) : string
	{
		if($subject === null)
			$subject = "";
		if($search === null)
			$search = "";
		if($replace === null)
			$replace = "";
		return str_ireplace($search, $replace, $subject);
	}

	public static function Replace(?string $subject, ?string $search, ?string $replace) : string
	{
		if($subject === null)
			$subject = "";
		if($search === null)
			$search = "";
		if($replace === null)
			$replace = "";
		return str_replace($search, $replace, $subject);
	}

	public static function ReplaceOnce(?string $subject, $str, $replace) : string
	{
		if($subject === null)
			$subject = "";
		$pos = strpos($subject, $str);
		if ($pos !== false)
			return substr_replace($subject, $replace, $pos, strlen($str));

		return $subject;
	}

	public static function ReplaceLast(?string $subject, ?string $search, ?string $replace) : string
	{
		if($subject === null)
			$subject = "";
		if($search === null)
			$search = "";
		if($replace === null)
			$replace = "";
		$pos = strrpos($subject, $search);
		if($pos !== false)
			$subject = substr_replace($subject, $replace, $pos, strlen($search));

		return $subject;
	}

	public static function RemoveNonAlphanumeric(?string $cad) : ?string
	{
		if($cad === null)
			$cad = "";
		return preg_replace("/[^A-Za-z0-9 ]/", '', $cad);
	}
	public static function RemoveAccents($cad) : string
	{
		$table = [
			'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c',
			'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
			'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
			'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
			'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
			'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
			'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b',
			'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r',
		];

		return strtr($cad, $table);
	}

	public static function RemoveDot(string $cad) : string
	{
		if (self::EndsWith($cad, "."))
			return substr($cad, 0, strlen($cad) - 1);
		return $cad;
	}

	public static function RemoveWordHiddenFormat(string $cad) : string
	{
		$cad = self::RemoveBlock($cad, "<xml>", "</xml>");
		return self::RemoveBlock($cad, "<!--[if ", "<![endif]-->");
	}

	public static function RemoveHtmlFormat(string $cad) : string
	{
		return self::RemoveBlock($cad, "<", ">");
	}

	public static function RemoveBlock(string $cad, string $startTag, string $endTag) : string
	{
		// le saca los tags de html
		while(false !== ($n = strpos($cad, $startTag)))
		{
			$end = strpos($cad, $endTag, $n);
			if ($end > 0)
			{
				$end += strlen($endTag);
				// remueve el pedazo
				$cad = substr($cad, 0, $n)
					. substr($cad, $end);
			}
			else
				$cad = substr($cad, 0, $n);
		}
		return $cad;
	}

	public static function RemoveBegining(?string $cad, $part) : string
	{
		if($cad === null)
			$cad = "";
		if (self::StartsWith($cad, $part))
			$cad = substr($cad, strlen($part));
		return $cad;
	}

	public static function RemoveEnding(?string $cad, $part) : string
	{
		if($cad === null)
			$cad = "";
		if (self::EndsWith($cad, $part))
			$cad = substr($cad, 0, strlen($cad) - strlen($part));
		return $cad;
	}

	public static function RemoveParenthesis(string $cad) : string
	{
		$cad = self::RemoveBegining($cad, "(");
		return self::RemoveEnding($cad, ")");
	}

	public static function IsNullOrEmpty(?string $cad) : bool
	{
		return $cad === '' || $cad === null;
	}

	public static function Ellipsis(string $cad, int $maxSize = 50) : string
	{
		// Distintas versiones de php dan distintos resultados
		// con size = 0 o negativo.
		if($maxSize <= 0)
			throw new ErrorException('Argumento invalido maxSize.');

		return mb_strimwidth($cad, 0, $maxSize, '…', 'UTF-8');
	}

	public static function EllipsisAnsi(string $cad, int $maxSize = 40, string $signal = '...') : string
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

	public static function Capitalize(?string $cad) : string
	{
		if($cad === null)
			$cad = "";
		return mb_strtoupper(mb_substr($cad, 0, 1)) . mb_substr($cad, 1);
	}

	public static function StartsWithAlfabetic(string $cad) : bool
	{
		return ctype_alpha(self::RemoveAccents(mb_substr($cad, 0, 1)));
	}

	public static function TextAreaTextToHtml(string $cad) : string
	{
		$cad = htmlspecialchars($cad);
		return nl2br($cad);
	}

	public static function Length(?string $str, string $encoding = 'UTF-8') : int
	{
		if($str === null)
			$str = "";
		return mb_strlen($str, $encoding);
	}

	public static function Substr(?string $str, int $start, ?int $length = null, string $encoding = 'UTF-8') : string
	{
		if($str === null)
			$str = "";
		return mb_substr($str, $start, $length, $encoding);
	}

	public static function Concat(string $a, ?string $b, string $separator) : string
	{
		if ($b === null)
			return $a;
		if (trim($a) == "" || trim($b) == "")
			$separator = "";
		return trim($a) . $separator . trim($b);
	}

	public static function ToLower(?string $str) : string
	{
		if($str === null)
			$str = "";
		return mb_convert_case($str, MB_CASE_LOWER);
	}

	public static function ToUpper(?string $str) : string
	{
		if($str === null)
			$str = "";
		return mb_convert_case($str, MB_CASE_UPPER);
	}

	public static function JoinInts(array $arr, string $separator = ",") : string
	{
		return implode($separator, array_map('intval', $arr));
	}

	public static function CountWords(string $str) : int
	{
		$unicode = '';
		if(self::IsUtf8($str))
			$unicode = 'u';
		return count(preg_split('/\s+/i' . $unicode, $str,
			0, PREG_SPLIT_NO_EMPTY));
	}

	public static function TryConvertUtf8(string $str, string $to = 'Windows-1252', string $from = 'UTF-8', bool $translit = false, bool $ignore = false) : string
	{
		try
		{
			return self::Convert($str, $to, $from, $translit, $ignore);
		}
		catch (\Exception $e)
		{
			Log::HandleSilentException(new \Exception('Error convirtiendo encoding: ' . $str . ', From: ' . $from . ', To: ' . $to, 0, $e));
		}
		return $str;
	}

	public static function IsUtf8(string $str) : bool
	{
		return mb_check_encoding($str, 'UTF-8');
	}

	public static function GetNWords(string $str, int $n) : string
	{
		$unicode = '';
		if(self::IsUtf8($str))
			$unicode = 'u';
		$words = preg_split('/\s+/i' . $unicode, $str,
			0, PREG_SPLIT_NO_EMPTY);
		return implode(' ', array_slice($words, 0, $n));
	}

	public static function RemoveResumenWord(string $str) : string
	{
		return preg_replace("/^resumen[:\.]|^abstract[:\.]/iu", '', $str);
	}

	public static function RemoveWordFormats(string $str) : string
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

	public static function RemoveDelimited(string $str, string $from, string $end, string $repl = '') : string
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

	public static function GetLastNWords(string $str, int $n) : string
	{
		$unicode = '';
		if(self::IsUtf8($str))
			$unicode = 'u';
		$words = preg_split('/\s+/i' . $unicode, $str,
			0, PREG_SPLIT_NO_EMPTY);
		return implode(' ',
			array_slice($words, count($words) - $n, $n));
	}

	public static function IsNumber(?string $cad) : bool
	{
		if($cad === null)
			$cad = "";
		return is_numeric($cad);
	}

	public static function IsNumberNotPlaceheld(?string $cad) : bool
	{
		if($cad === null)
			$cad = "";
		if (strlen($cad) > 1 && $cad[0] === '0' && $cad[1] !== '.')
			return false;

		return self::IsNumber($cad);
	}

	/**
	 * Devuelve strings bien formados para XML.
	 */
	public static function CleanXmlString(string $str) : string
	{
		// Los caracteres ascii bajos (menores a 0x20 espacio)
		// rompen los parsers de xml (pasa en chrome y firefox).
		$replace = [
			chr(0x0000), chr(0x0001), chr(0x0002), chr(0x0003),
			chr(0x0004), chr(0x0005), chr(0x0006), chr(0x0007),
			chr(0x0008), chr(0x0009), chr(0x000A), chr(0x000B),
			chr(0x000C), chr(0x000D), chr(0x000E), chr(0x000F),
			chr(0x0010), chr(0x0011), chr(0x0012), chr(0x0013),
			chr(0x0014), chr(0x0015), chr(0x0016), chr(0x0017),
			chr(0x0018), chr(0x0019), chr(0x001A), chr(0x001B),
			chr(0x001C), chr(0x001D), chr(0x001E), chr(0x001F),
		];
		return htmlspecialchars(
			str_replace($replace, '', $str));
	}

	/**
	 * @param array|string $partsRaw
	 */
	public static function SmartImplode($partsRaw, string $trailingCad = "", int $normalization = 0) : string
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

	public static function NormalizeName(string $cad, int $normalization) : string
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
			return self::Replace($cad, "  ", " ");
		}
		throw new ErrorException('Argumento de normalización inválido.');
	}

	public static function Initials(string $cad) : string
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

	public static function SanitizeFilename(string $title) : string
	{
		$title = trim($title);

		// Extrae la extensión si existe
		$extension = '';
		$dotPosition = strrpos($title, '.');
		if ($dotPosition !== false && $dotPosition > 0)
		{
			$extension = substr($title, $dotPosition);
			$title = substr($title, 0, $dotPosition);
		}

		// Remueve caracteres no válidos en nombres de archivo, windows y mac.
		$title = preg_replace('#[/¿¡\?\<\>\\:\*\|"\^\r\n\t]#u', '', $title);

		// 140 es un número razonable para el máximo, windows soporta 256 sumando directorios.
		$title = self::EllipsisAnsi($title, 140, "(...)");

		// Vuelve a agregar la extensión
		return $title . $extension;
	}

	public static function IsAllLetters(string $cad) : bool
	{
		$cad = self::RemoveAccents(self::ToLower($cad));
		for($n = 0; $n < strlen($cad); $n++)
		{
			if ($cad[$n] < 'a' || $cad[$n] > 'z')
				return false;
		}
		return true;
	}

	public static function IsRoman(string $cad) : bool
	{
		$regex = '/^M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/';
		return (bool)preg_match($regex, strtoupper($cad));
	}

	public static function DecodeEntities(string $str) : string
	{
		//Algunos strings vienen dobleencodeados
		$str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
		$str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
		$str = self::Replace($str, '\\"', '"');
		return self::Replace($str, "\\'", "'");
	}

	public static function RandomString(int $length = 12,
		string $keyspace = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') : string
	{
		$ret = '';
		$max = mb_strlen($keyspace, '8bit') - 1;
		for ($i = 0; $i < $length; $i++)
			$ret .= $keyspace[random_int(0, $max)];

		return $ret;
	}

	public static function RandomStringNoAmbiguous(int $length = 12) : string
	{
		return self::RandomString($length, "abcdefghkmnopqrstuvwxyzABCDEFGHKMNOPQRSTUVWXYZ0123456789");
	}

	public static function RandomStringLowerCase(int $length = 12) : string
	{
		return self::RandomString($length, "abcdefghijklmnopqrstuvwxyz0123456789");
	}

	public static function GenerateLink() : string
	{
		return 'l-' . self::RandomStringLowerCase(16);
	}

	public static function FormatLocaleNumber($value, int $decimals = 0) : string
	{
		return number_format($value, $decimals, ",", "");
	}

	public static function FormatNumber($value, int $decimals = 0, int $leadingZeros = 0) : string
	{
		$format = '%0';
		if ($leadingZeros > 0)
			$format .= $leadingZeros;
		$format .= "." . $decimals . "f";
		return sprintf($format, $value);
	}
}

