<?php

namespace minga\framework;

class FileBucket
{
	public string $path = '';
	public string $id = '';

	private static function CleanUp() : void
	{
		$folder = Context::Paths()->GetBucketsPath();
		IO::EnsureExists($folder);
		IO::ClearDirectoriesOlderThan($folder, 7);
	}

	public function GetBucketFolder() : string
	{
		return $this->path;
	}

	public static function Create(?string $defaultBucketId = null) : FileBucket
	{
		self::CleanUp();
		if ($defaultBucketId === null)
		{
			$defaultBucketId = self::CreateId();
			while(self::Exists($defaultBucketId))
			{
				usleep(500);
				$defaultBucketId = self::CreateId();
			}
		}
		return self::Load($defaultBucketId);
	}

	public static function CreateId() : string
	{
		return uniqid();
	}

	public static function Exists(string $id) : bool
	{
		$ret = new FileBucket();
		$ret->ResolvePath($id);
		return file_exists($ret->path);
	}

	public static function ResolveFromParam(bool $forceCreate = false) : string
	{
		$ret = Params::SafeGet("b");
		if ($ret == "" && $forceCreate)
			$ret = self::CreateId();
		return $ret;
	}

	public static function Load(string $id) : FileBucket
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

	private function ResolvePath(string $id) : void
	{
		if (trim($id) === '' || ctype_alnum($id) === false || Str::Length($id) > 40)
		{	// verifica este parámetro para evitar saltos en el filesystem fuera de tmp
			throw new ErrorException('Bucket id inválido');
		}
		$this->id = $id;
		$email = Context::LoggedUser();
		if ($email == '')
			$email = "global";
		$this->path = Context::Paths()->GetBucketsPath() . "/"
			. Str::UrlencodeFriendly($email) . "-" . $id;
	}
}
