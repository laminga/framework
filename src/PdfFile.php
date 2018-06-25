<?php

namespace minga\framework;
use minga\framework\tfpdf\tFPDF;

class PdfFile extends tFPDF
{
	var $B;
	var $I;
	var $U;
	var $HREF;

	function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
	{
		// Call parent constructor
		parent::__construct($orientation, $unit, $size);
		// Initialization
		$this->B = 0;
		$this->I = 0;
		$this->U = 0;
		$this->HREF = '';

		$this->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
		$this->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
		$this->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);

	}
	function HtmlEncode($text)
	{
		$text = Str::Replace($text, "&", "&amp;");
		$text = Str::Replace($text, "<", "&lt;");
		$text = Str::Replace($text, ">", "&gt;");
		// rescata las itálicas de citado
		$text = Str::Replace($text, "&lt;i&gt;", "<i>");
		$text = Str::Replace($text, "&lt;/i&gt;", "</i>");
		return $text;
	}
	function HtmlDecode($text)
	{
		$text = Str::Replace($text, "&lt;", "<");
		$text = Str::Replace($text, "&gt;", ">");
		$text = Str::Replace($text, "&amp;", "&");
		return $text;
	}

	function WriteHTML($htmlraw, $size, $showLinks = true)
	{
		$html = $htmlraw;

		$lineHeight = $size / 2;
		$this->SetFontSize($size);

		// HTML parser
		$html = str_replace("\n", ' ', $html);
		$a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach($a as $i => $e)
		{
			if($i %2 == 0)
			{
				// Text
				if($this->HREF && $showLinks == true)
					$this->PutLink($this->HREF, Str::DecodeEntities($e));
				else
					$this->Write($lineHeight, Str::DecodeEntities($e));
			}
			else
			{
				// Tag
				if($e[0] == '/')
					$this->CloseTag(strtoupper(substr($e, 1)));
				else
				{
					// Extract attributes
					$a2 = explode(' ', $e);
					$tag = strtoupper(array_shift($a2));
					$attr = array();
					foreach($a2 as $v)
					{
						if(preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3))
							$attr[strtoupper($a3[1])] = $a3[2];
					}
					$this->OpenTag($tag, $attr, $lineHeight, $showLinks);
				}
			}
		}
	}

	function OpenTag($tag, $attr, $lineHeight, $showLinks)
	{
		// Opening tag
		if($tag == 'B' || $tag == 'I' || $tag == 'U')
			$this->SetStyle($tag, true);
		if($tag == 'A' && $showLinks)
			$this->HREF = $attr['HREF'];
		if($tag == 'BR')
			$this->Ln($lineHeight);
	}

	function CloseTag($tag)
	{
		// Closing tag
		if($tag == 'B' || $tag == 'I' || $tag == 'U')
			$this->SetStyle($tag, false);
		if($tag == 'A')
			$this->HREF = '';
	}

	function SetStyle($tag, $enable)
	{
		// Modify style and select corresponding font
		$this->$tag += ($enable ? 1 : -1);
		$style = '';
		foreach(array('B', 'I', 'U') as $s)
		{
			if($this->$s > 0)
				$style .= $s;
		}
		$this->SetFont('', $style);
	}

	function PutLink($URL, $txt)
	{
		// Put a hyperlink
		$this->SetTextColor(0, 0, 255);
		$this->SetStyle('U', true);
		$this->Write(5, $txt, $URL);
		$this->SetStyle('U', false);
		$this->SetTextColor(0);
	}

	function ImageEps ($file, $x, $y, $w = 0, $h = 0, $link = '', $useBoundingBox = true)
	{

		$data = file_get_contents($file);
		if ($data === false)
			$this->Error('EPS file not found: ' . $file);

		$regs = array();

		# EPS/AI compatibility check (only checks files created by Adobe Illustrator!)
		preg_match ('/%%Creator:([^\r\n]+)/', $data, $regs); # find Creator
		if (count($regs) > 1)
		{
			$version_str = trim($regs[1]); # e.g. "Adobe Illustrator(R) 8.0"
			if (strpos($version_str, 'Adobe Illustrator') !== false)
			{
				$ar = explode(' ', $version_str);
				$version = (float)array_pop($ar);
				if ($version >= 9)
					$this->Error('File was saved with wrong Illustrator version: '.$file);
				#return false; # wrong version, only 1.x, 3.x or 8.x are supported
			}
			#else
				#$this->Error('EPS wasn\'t created with Illustrator: '.$file);
		}

		# strip binary bytes in front of PS-header
		$start = strpos($data, '%!PS-Adobe');
		if ($start > 0)
			$data = substr($data, $start);

		# find BoundingBox params
		$x1 = $y1 = $x2 = $y2 = null;
		preg_match ("/%%BoundingBox:([^\r\n]+)/", $data, $regs);
		if (count($regs) > 1)
			list($x1, $y1, $x2, $y2) = explode(' ', trim($regs[1]));
		else
			$this->Error('No BoundingBox found in EPS file: '.$file);

		$start = strpos($data, '%%EndSetup');
		if ($start === false)
			$start = strpos($data, '%%EndProlog');
		if ($start === false)
			$start = strpos($data, '%%BoundingBox');

		$data = substr($data, $start);

		$end = strpos($data, '%%PageTrailer');
		if ($end === false)
			$end = strpos($data, 'showpage');
		if ($end)
			$data = substr($data, 0, $end);

		# save the current graphic state
		$this->_out('q');

		$k = $this->k;

		if ($useBoundingBox)
		{
			$dx = $x * $k - $x1;
			$dy = $y * $k - $y1;
		}
		else
		{
			$dx = $x * $k;
			$dy = $y * $k;
		}

		# translate
		$this->_out(sprintf('%.3f %.3f %.3f %.3f %.3f %.3f cm', 1, 0, 0, 1, $dx, $dy + ($this->hPt - 2 * $y * $k - ($y2 - $y1))));

		$scale_x = null;
	  	$scale_y = null;
		if ($w > 0)
		{
			$scale_x = $w / (($x2 - $x1) / $k);
			if ($h > 0)
			{
				$scale_y = $h / (($y2 - $y1) / $k);
			}
			else
			{
				$scale_y = $scale_x;
				$h = ($y2 - $y1) / $k * $scale_y;
			}
		}
		else
		{
			if ($h > 0)
			{
				$scale_y = $h / (($y2 - $y1) / $k);
				$scale_x = $scale_y;
				$w = ($x2 - $x1) / $k * $scale_x;
			}
			else
			{
				$w = ($x2 - $x1) / $k;
				$h = ($y2 - $y1) / $k;
			}
		}

		# scale
		if (isset($scale_x))
			$this->_out(sprintf('%.3f %.3f %.3f %.3f %.3f %.3f cm', $scale_x, 0, 0, $scale_y, $x1 * (1 - $scale_x), $y2 * (1 - $scale_y)));

		# handle pc/unix/mac line endings
		$data = Str::Replace($data, "\r\n", "\r");
		$lines = explode("\r", $data);

		$u = 0;
		$cnt = count($lines);
		for ($i = 0; $i < $cnt; $i++)
		{
			$line = $lines[$i];
			if ($line == '' || $line{0} == '%')
				continue;

			$len = strlen($line);

			$chunks = explode(' ', $line);
			$cmd = array_pop($chunks);

			# RGB
			if ($cmd == 'Xa'||$cmd == 'XA')
			{
				$b = array_pop($chunks); $g = array_pop($chunks); $r = array_pop($chunks);
				$this->_out("$r $g $b ". ($cmd == 'Xa' ? 'rg' : 'RG')); #substr($line, 0, -2).'rg' -> in EPS (AI8): c m y k r g b rg!
				continue;
			}

			switch ($cmd)
			{
				case 'm':
				case 'l':
				case 'v':
				case 'y':
				case 'c':

				case 'k':
				case 'K':
				case 'g':
				case 'G':

				case 's':
				case 'S':

				case 'J':
				case 'j':
				case 'w':
				case 'M':
				case 'd' :

				case 'n' :
				case 'v' :
					$this->_out($line);
					break;

				case 'x': # custom fill color
					list($c, $m, $y, $k) = $chunks;
					$this->_out("$c $m $y $k k");
					break;

				case 'X': # custom stroke color
					list($c, $m, $y, $k) = $chunks;
					$this->_out("$c $m $y $k K");
					break;

				case 'Y':
				case 'N':
				case 'V':
				case 'L':
				case 'C':
					$line{$len - 1} = Str::ToLower($cmd);
					$this->_out($line);
					break;

				case 'b':
				case 'B':
					$this->_out($cmd . '*');
					break;

				case 'f':
				case 'F':
					if ($u>0)
					{
						$isU = false;
						$max = min($i + 5, $cnt);
						for ($j = $i + 1; $j < $max; $j++)
							$isU = ($isU || ($lines[$j] == 'U' || $lines[$j] == '*U'));
						if ($isU)
							$this->_out("f*");
					}
					else
						$this->_out("f*");
					break;

				case '*u':
					$u++;
					break;

				case '*U':
					$u--;
					break;

				#default: echo "$cmd<br>"; #just for debugging
			}

		}

		# restore previous graphic state
		$this->_out('Q');

		if ($link)
			$this->Link($x, $y, $w, $h, $link);

		return true;
	}

}
