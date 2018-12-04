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

	private function GetBucketFolder()
	{
		$ret = Context::Paths()->GetBucketsPath();
		IO::EnsureExists($ret);
		return $ret;
	}

	public static function Create()
	{
		self::CleanUp();
		return self::Load(self::CreateId());
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
		$this->id = $id;
		$email = Context::LoggedUser();
		if ($email == '')
			$email = "global";
		$this->path = $this->GetBucketFolder() . "/" .
			Str::UrlencodeFriendly($email) . "-" . $id;
	}

}
