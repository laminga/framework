<?php

namespace minga\framework;

class CreativeCommons
{
	public static function GetVersions()
	{
		return ["4.0/deed.es" => "Internacional 4.0 (recomendada)",
			"4.0" => "Internacional 4.0 (inglés)",
			"4.0/deed.pt" => "Internacional 4.0 (portugués)",
			"" => "-------- Otras versiones -----------",
			"2.5/ar" => "Argentina 2.5",
			"3.0/br" => "Brasil 3.0",
			"3.0/cl" => "Chile 3.0",
			"2.5/co" => "Colombia 2.5",
			"3.0/cr" => "Costa Rica 3.0",
			"3.0/ec" => "Ecuador 3.0",
			"3.0/es" => "España 3.0",
			"3.0/ph" => "Filipinas 3.0",
			"3.0/gt" => "Guatemala 3.0",
			"2.5/mx" => "México 2.5",
			"2.5/pe" => "Perú 2.5",
			"3.0/pr" => "Puerto Rico 3.0",
			"3.0/ve" => "Venezuela 3.0", ];


				/*
				<!--option value="" => "------ Otras ---------",

				"de" => "Alemania",
				"au" => "Australia",
				"at" => "Austria",
				"bg" => "Bulgaria",
				"be" => "Bélgica",
				"ca" => "Canadá",
				"cn" => "China",
				"co" => "Colombia",
				"kr" => "Corea del Sur",
				"hr" => "Croacia",
				"dk" => "Dinamarca",
				"ec" => "Ecuador",
				"eg" => "Egipto",
				"scotland" => "Escocia",
				"si" => "Eslovenia",
				"us" => "Estados Unidos",
				"ee" => "Estonia",
				"ph" => "Filipinas",
				"fi" => "Finlandia",
				"fr" => "Francia",
				"uk" => "Gran Bretaña",
				"gr" => "Grecia",
				"nl" => "Holanda",
				"hk" => "Hong Kong",
				"hu" => "Hungría",
				"in" => "India",
				"ie" => "Irlanda",
				"il" => "Israel",
				"it" => "Italia",
				"jp" => "Japón",
				"lu" => "Luxemburgo",
				"mk" => "Macedonia",
				"my" => "Malasia",
				"mt" => "Malta",
				"no" => "Noruega",
				"nz" => "Nueva Zelanda",
				"pl" => "Polonia",
				"pt" => "Portugal",
				"cz" => "República Checa",
				"ro" => "Rumania",
				"rs" => "Serbia",
				"sg" => "Singapur",
				"za" => "Sudáfrica",
				"se" => "Suecia",
				"ch" => "Suiza",
				"th" => "Tailandia",
				"tw" => "Taiwán",
				"ug" => "Uganda",
				"vn" => "Vietnam</option-- => "
				 */
	}

	public static function ResolveUrl($entity)
	{
		// pattern: https://creativecommons.org/licenses/by/2.5/ar/
		$ret = "https://creativecommons.org/licenses/by";
		$licenseType = $entity->attributes["licenseType"];
		$licenseVersion = $entity->attributes["licenseVersion"];
		$licenseCommercial = $entity->attributes["licenseCommercial"];
		$licenseOpen = $entity->attributes["licenseOpen"];
		if ($licenseType == "0")
			return "";
		if ($licenseCommercial != "1")
			$ret .= "-nc";
		if ($licenseOpen == "never")
			$ret .= "-nd";
		else
			if ($licenseOpen == "same")
				$ret .= "-sa";
		$ret .= "/" . $licenseVersion;
		return $ret;
	}
	public static function GetLeyendByUrl($url, $wide = false)
	{
		// backward compatibility
		return self::GetLegendByUrl($url, $wide);
	}
	public static function GetLegendByUrl($url, $wide = false)
	{
		if (self::UrlIsCC($url) == false)
			return "";

		// define texto y link
		$licenseText = "Esta obra está bajo una licencia de Creative Commons.<br>";
		$licenseText .= "Para ver una copia de esta licencia, visite ";
		if (!$wide) $licenseText .= "<br>";
		$licenseText .= "<a style='text-decoration: none' href='" . $url . "' target='_blank'>" . $url . "</a>.";

		return $licenseText;
	}

	public static function GetLicenseImageEpsByUrl($url)
	{
		return self::GetLicenseImageByUrl($url, "eps");
	}
	public static function GetLicenseImageSvgByUrl($url)
	{
		return self::GetLicenseImageByUrl($url, "svg");
	}

	public static function GetLicenseImageByUrl($url, $extension = "png")
	{
		if (self::UrlIsCC($url) == false)
			return "";
		$availables = ["by", "by-nc", "by-nc-nd", "by-nc-sa", "by-nd", "by-sa"];
		foreach($availables as $image)
			if (Str::Contains($url, "/" . $image . "/"))
				return "/images/licenses/cc/" . $image . "." . $extension;
		return "";
	}

	private static function UrlIsCC($url)
	{
		return Str::StartsWith($url, "http://creativecommons.")
			|| Str::StartsWith($url, "http://www.creativecommons.")
			|| Str::StartsWith($url, "https://creativecommons.")
			|| Str::StartsWith($url, "https://www.creativecommons.");
	}
}
