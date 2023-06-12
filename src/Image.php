<?php

namespace minga\framework;

class Image
{
	/**
	 * Cambia el tamaño de una imagen, pero solo reduce,
	 * no agranda. Siempre proporcional, tomando a escala
	 * el menor valor pasado.
	 * Si ambas medidas son cero, sale.
	 * Si alguna medida es cero calcula la proporción
	 * con la otra.
	 */
	public static function Resize(string $sourceFile, int $maxWidth, int $maxHeight, string $targetFile) : void
	{
		if($maxWidth == 0 && $maxHeight == 0)
			return;

		$ext = IO::GetFileExtension($sourceFile);

		switch(Str::ToLower($ext))
		{
		case 'jpeg':
		case 'jpg':
			$image = imagecreatefromjpeg($sourceFile);
			break;
		case 'png':
			$image = imagecreatefrompng($sourceFile);
			break;
		case 'gif':
			$image = imagecreatefromgif($sourceFile);
			break;
		default:
			throw new ErrorException(Context::Trans('Tipo no soportado: {ext}', ['{ext}' => $ext]));
		}
		// Get current dimensions
		$oldWidth = imagesx($image);
		$oldHeight = imagesy($image);

		// Calculate the scaling we need to do to fit the image inside our frame
		if($maxWidth == 0)
			$scale = $maxHeight / $oldHeight;
		elseif($maxHeight == 0)
			$scale = $maxWidth / $oldWidth;
		else
			$scale = min($maxWidth / $oldWidth, $maxHeight / $oldHeight);

		$scale = min(1, $scale);

		// Get the new dimensions
		$newWidth = (int)ceil($scale * $oldWidth);
		$newHeight = (int)ceil($scale * $oldHeight);
		// Create new empty image
		$new = imagecreatetruecolor($newWidth, $newHeight);
		$whiteBackground = imagecolorallocate($new, 255, 255, 255);
		imagefill($new, 0, 0, $whiteBackground); // fill the background with white

		// Resize old image into new
		imagecopyresampled($new, $image, 0, 0, 0, 0,
			$newWidth, $newHeight, $oldWidth, $oldHeight);

		switch(Str::ToLower($ext))
		{
		case 'jpeg':
		case 'jpg':
			imagejpeg($new, $targetFile);
			break;
		case 'png':
			imagepng($new, $targetFile, 9);
			break;
		case 'gif':
			imagegif($new, $targetFile);
			break;
		default:
			throw new ErrorException(Context::Trans('Tipo no soportado: {ext}', ['{ext}' => $ext]));
		}
		imagedestroy($new);
	}

	public static function IsTransparentPng(string $file) : bool
	{
		//32-bit pngs
		//4 checks for greyscale + alpha and RGB + alpha
		if ((ord(file_get_contents($file, false, null, 25, 1)) & 4) > 0)
			return true;

		//8 bit pngs
		$fd = fopen($file, 'r');
		$continue = true;
		$plte = false;
		$trns = false;
		$idat = false;
		while($continue === true)
		{
			$continue = false;
			$line = fread($fd, 1024);
			if ($plte == false)
				$plte = (stripos($line, 'PLTE') !== false);
			if ($trns == false)
				$trns = (stripos($line, 'tRNS') !== false);
			if ($idat == false)
				$idat = (stripos($line, 'IDAT') !== false);
			if ($idat == false && !($plte && $trns))
				$continue = true;
		}
		fclose($fd);
		return $plte && $trns;
	}

	public static function RemoveAlfa(string $file) : void
	{
		$ext = IO::GetFileExtension($file);
		if ($ext != 'png' || file_exists($file) == false
			|| Image::IsTransparentPng($file) == false)
		{
			return;
		}

		$renamed = IO::GetTempFilename();
		IO::Move($file, $renamed);

		// Get the original image.
		$src = imagecreatefrompng($renamed);

		// Get the width and height.
		$width = imagesx($src);
		$height = imagesy($src);

		// Create a white background, the same size as the original.
		$bg = imagecreatetruecolor($width, $height);
		$white = imagecolorallocate($bg, 255, 255, 255);
		imagefill($bg, 0, 0, $white);

		// Merge the two images.
		imagecopyresampled($bg, $src, 0, 0, 0, 0, $width, $height, $width, $height);

		// Save the finished image.
		imagepng($bg, $file, 6);
		imagedestroy($bg);
		imagedestroy($src);
		IO::Delete($renamed);
	}
}
