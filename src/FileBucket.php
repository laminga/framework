<?php

namespace minga\framework;

use minga\framework\Params;

class FileBucket
{
	public $path;
	public $id;

	private static function CleanUp()
	{
		$folder = Context::Paths()->GetBucketsPath();
		IO::EnsureExists($folder);
		$time = time();

		$directories = IO::GetDirectoriesCursor($folder);
		while($directories->GetNext())
		{
			$directoryOnly = $directories->Current;
			$directory = $folder . "/" . $directoryOnly;
			if($time - IO::FileMTime($directory . "/.") >= 60 * 60 * 24) // 24 horas
				IO::RemoveDirectory($directory);
		}
		$directories->Close();
	}

	public function GetBucketFolder()
	{
		return $this->path;
	}

	public static function Create($defaultBucketId = null)
	{
		self::CleanUp();
		if ($defaultBucketId === null)
			$defaultBucketId = self::CreateId();
		return self::Load($defaultBucketId);
	}

	public static function CreateId()
	{
		return uniqid();
	}
	public static function Exists($id)
	{
		$ret = new FileBucket();
		$ret->ResolvePath($id);
		return file_exists($ret->path);
	}

	public static function ResolveFromParam($forceCreate = false)
	{
		$ret = Params::SafeGet("b");
		if ($ret == "" && $forceCreate)
			$ret = self::CreateId();
		return $ret;
	}

	public static function Load($id)
	{
		$ret = new FileBucket();
		$ret->ResolvePath($id);
		IO::EnsureExists($ret->path);
		return $ret;
	}

	public function Delete()
	{
		IO::RemoveDirectory($this->path);
	}

	private function ResolvePath($id)
	{
		if ($id === null || trim($id) === '' || ctype_alnum($id) === false || sizeof($id) > 40)
		{	// verifica este parámetro para evitar saltos en el filesystem fuera de tmp
			throw new ErrorException('Invalid bucket Id');
		}
		$this->id = $id;
		$email = Context::LoggedUser();
		if ($email == '')
			$email = "global";
		$this->path = Context::Paths()->GetBucketsPath() . "/" .
			Str::UrlencodeFriendly($email) . "-" . $id;
	}

}
