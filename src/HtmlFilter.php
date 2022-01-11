<?php

namespace minga\framework;

class HtmlFilter
{
	public static function DefaultConfig() : \HTMLPurifier_Config
	{
		$config = \HTMLPurifier_Config::createDefault();
		$config->set('Cache.SerializerPath', Context::Paths()->GetHtmlPurifierCachePath());
		$config->set("AutoFormat.RemoveEmpty", true);
		return $config;
	}

	/**
	 * Permite solo links y formato básico.
	 * Filtra todo lo demás, está relacionado
	 * a la función setupMinimal del ckeditor.
	 * Permite lo mismo que los botones visibles
	 * del editor.
	 * También remueve líneas vacías.
	 */
	public static function BasicFormat(string $html) : string
	{
		Profiling::BeginTimer();
		$config = self::DefaultConfig();
		$config->set("AutoFormat.AutoParagraph", true);
		$config->set('HTML.Allowed', 'p,ul,ol,li,b,i,s,em,strong,a[href]');

		$purifier = new \HTMLPurifier($config);

		$html = $purifier->purify($html);

		// Quita líneas vacías
		$html = str_replace(["\n", "\r"], '', $html);
		// Quita párrafos vacíos
		$html = preg_replace("/\s*<p>\s*<\/p>\s*/u", '', $html);
		$html = trim($html);
		Profiling::EndTimer();
		return $html;
	}

	/**
	 * Filtro muy permisivo deja casi todo
	 * salvo scripts, formularios e
	 * iframes que no sean de youtube
	 * o vimeo. Quita otros riesgos de
	 * seguridad básicos.
	 *
	 * El filtro es muy permisivo para ser
	 * compatible hacia atrás con los eventos
	 * existentes debería restringirse un poco más.
	 */
	public static function BasicSecurity(string $html) : string
	{
		Profiling::BeginTimer();
		$config = HtmlFilter::DefaultConfig();
		$config->set('URI.AllowedSchemes', [
			'http' => true,
			'https' => true,
			'mailto' => true,
			'data' => true,
		]);
		$config->set("Attr.AllowedFrameTargets", ['_blank']);
		$config->set("CSS.MaxImgLength", '5000px');

		//Urls permitidas en iframes, youtube y vimeo.
		$config->set('HTML.SafeIframe', true);
		$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');

		$purifier = new \HTMLPurifier($config);

		$html = $purifier->purify($html);

		// Quita líneas vacías
		// $html = str_replace(["\n", "\r"], '', $html);
		$html = trim($html);
		Profiling::EndTimer();
		return $html;
	}

	public static function ResizeBase64Images(string $html) : string
	{
		Profiling::BeginTimer();
		$dom = new \simple_html_dom();
		$dom->load($html);
		foreach($dom->find('img') as $node)
		{
			if(Str::Contains($node->src, "data:image") == false)
				continue;

			$parts = explode(',', $node->src, 2);
			$mime = self::GetMimeType($parts[0]);

			$newSize = self::GetAttributesSize($node);
			if($newSize[0] == 0 && $newSize[1] == 0)
				continue;

			$srcFile = IO::GetTempFilename();
			file_put_contents($srcFile, base64_decode($parts[1]));

			$size = getimagesize($srcFile);
			if($newSize[0] >= $size[0] || $newSize[1] >= $size[1])
			{
				IO::Delete($srcFile);
				continue;
			}

			$dstFile = IO::GetTempFilename();

			Image::Resize($mime, $srcFile, $newSize[0], $newSize[1], $dstFile, true);
			$node->src = $parts[0] . ',' . base64_encode(file_get_contents($dstFile));

			IO::Delete($srcFile);
			IO::Delete($dstFile);
		}
		$ret = $dom->save();
		Profiling::EndTimer();
		return $ret;
	}

	private static function GetValueFromStyle(string $prop, string $text) : int
	{
		//TODO: implementar con otras unidades no pixeles
		$ret = preg_match("/\b" . $prop . "\s*:\s*(\d+)px/", $text, $match);
		if($ret === false || $ret == 0)
			return 0;

		return (int)$match[1];
	}

	private static function GetMimeType(string $str) : string
	{
		$str = Str::RemoveBegining($str, 'data:');
		return Str::RemoveEnding($str, ';base64');
	}

	private static function GetAttributesSize($node) : array
	{
		$width = 0;
		$height = 0;
		if($node->style != false)
		{
			$width = self::GetValueFromStyle('width', $node->style);
			$height = self::GetValueFromStyle('height', $node->style);
		}
		if($width == 0)
			$width = (int)$node->width;
		if($height == 0)
			$heigth = (int)$node->heigth;

		return [$width, $height];
	}

}
