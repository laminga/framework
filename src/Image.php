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
	 *
	 * Por default siempre grababa el resultado en png
	 * se agregó parámetro opcional para que no lo haga.
	 */
	public static function Resize($type, $sourceFile,
		$maxWidth, $maxHeight, $targetFile, $useTypeForSave = false)
	{

		if($maxWidth == 0 && $maxHeight == 0)
			return;

		switch(Str::ToLower($type))
		{
		case 'image/jpeg':
		case 'image/pjpeg':
			$image = imagecreatefromjpeg($sourceFile);
			break;
		case 'image/x-png':
		case 'image/png':
			$image = imagecreatefrompng($sourceFile);
			break;
		case 'image/gif':
			$image = imagecreatefromgif($sourceFile);
			break;
		default:
			throw new \Exception('Unsupported type: ' . $type);
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

		//Por razones de compatibilidad se agrega este parámetro,
		//esta función grababa siempre en formato png, habría que
		//revisar el código y actualizar esta función toda.
		if($useTypeForSave == false)
			$type = 'image/png';

		switch(Str::ToLower($type))
		{
		case 'image/jpeg':
		case 'image/pjpeg':
			imagejpeg($new, $targetFile);
			break;
		case 'image/x-png':
		case 'image/png':
			imagepng($new, $targetFile, 9);
			break;
		case 'image/gif':
			imagegif($new, $targetFile);
			break;
		default:
			throw new \Exception('Unsupported type: ' . $type);
		}
		imagedestroy($new);
	}
}
