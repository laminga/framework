<?php

namespace minga\framework;

class FileBucket
{
	public $path;
	public $id;

	private static function CleanUp()
	{
		$folder = Context::Paths()->GetBucketsPath();
		IO::EnsureExists($folder);
		IO::ClearDirectoriesOlderThan($folder, 7);
	}

	public function GetBucketFolder()
	{
		return $this->path;
	}

	public static function Create($defaultBucketId = null)
	{
		self::CleanUp();
		if ($defaultBucketId === null)
		{
			$defaultBucketId = self::CreateId();
			while(self::Exists($defaultBucketId))
			{
				usleep(1000);
				$defaultBucketId = self::CreateId();
			}
		}
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

	public function Delete() : void
	{
		IO::RemoveDirectory($this->path);
	}

	private function ResolvePath($id)
	{
		if ($id === null || trim($id) === '' || ctype_alnum($id) === false || Str::Length($id) > 40)
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
