<?php

namespace minga\framework;

class ZipArchiveExtended extends \ZipArchive
{
	public function count() : int
	{
		return $this->numFiles;
	}

	public function hasSubdir($subdir) : bool
	{
		$subdir = str_replace(["/", "\\"], "/", $subdir);
		if (Str::EndsWith($subdir, "/") == false)
			$subdir .= "/";
		for ($i = 0; $i < $this->numFiles; $i++)
		{
			$filename = $this->getNameIndex($i);
			if (substr($filename, 0, mb_strlen($subdir, "UTF-8")) == $subdir)
				return true;
		}
		return false;
	}

	public function extractSubdirTo($destination, $subdir) : array
	{
		$errors = [];

		// Prepare dirs
		$destination = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $destination);
		$subdir = str_replace(["/", "\\"], "/", $subdir);

		if (substr($destination, mb_strlen(DIRECTORY_SEPARATOR, "UTF-8") * -1) != DIRECTORY_SEPARATOR)
			$destination .= DIRECTORY_SEPARATOR;

		if (Str::EndsWith($subdir, "/") == false)
			$subdir .= "/";

		// Extract files
		for ($i = 0; $i < $this->numFiles; $i++)
		{
			$filename = $this->getNameIndex($i);

			if (substr($filename, 0, mb_strlen($subdir, "UTF-8")) == $subdir)
			{
				$relativePath = substr($filename, mb_strlen($subdir, "UTF-8"));
				$relativePath = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $relativePath);

				if (mb_strlen($relativePath, "UTF-8") > 0)
				{
					if (Str::EndsWith($filename, "/")) // Directory
					{
						// New dir
						if (!is_dir($destination . $relativePath))
							if (!@mkdir($destination . $relativePath, 0755, true))
								$errors[$i] = $filename;
					}
					else
					{
						if (dirname($relativePath) != ".")
						{
							if (!is_dir($destination . dirname($relativePath)))
							{
								// New dir (for file)
								@mkdir($destination . dirname($relativePath), 0755, true);
							}
						}

						// New file
						if (@file_put_contents($destination . $relativePath, $this->getFromIndex($i)) === false)
							$errors[$i] = $filename;
					}
				}
			}
		}
		return $errors;
	}
}
