<?php

namespace minga\framework;

class SQLiteList
{
	private $keyColumn;
	private $columns;
	private $intColumns;
	private $uniqueColumns;
	private $blobColumns;

	private $commaColumns;
	private $commaArgs;

	private $path = null;

	private $db = null;

	private static $OpenStreams = [];
	private static $OpenStreamsSizes = [];
	private static $OpenStreamsTimes = [];

	public function __construct($key, $columns = null, $intColumns = null, $uniqueColumns = null, $blobColumns = null)
	{
		$this->keyColumn = $key;
		$this->columns = $columns;
		$this->intColumns = $intColumns;
		$this->uniqueColumns = $uniqueColumns;
		$this->blobColumns = $blobColumns;

		$this->commaColumns = "";
		$this->commaArgs = "";
		$n = 2;
		if ($columns != null)
		{
			foreach($columns as $col)
			{
				$this->commaColumns .= "," . $col;
				$this->commaArgs .= ",:p" . ($n++);
			}
		}
		if ($intColumns != null)
		{
			foreach($intColumns as $col)
			{
				$this->commaColumns .= "," . $col;
				$this->commaArgs .= ",:p" . ($n++);
			}
		}
	}

	public function Query(string $sql)
	{
		return $this->db->query($sql);
	}

	public function QueryAll(string $sql, $args = null) : array
	{
		if ($args != null && is_array($args) == false)
			$args = [$args];
		if ($args != null)
			$result = $this->Execute($sql, $args);
		else
			$result = $this->Query($sql);
		$ret = [];
		while($row = $result->fetchArray(SQLITE3_ASSOC))
			$ret[] = $row;
		return $ret;
	}

	public function QueryRow(string $sql, $params = null) : ?array
	{
		$result = $this->Execute($sql, $params);
		if ($result == null)
			return null;
		$res = $result->fetchArray(SQLITE3_ASSOC);

		if ($res === false)
			return null;
		else
			return $res;
	}

	public function Open(string $path, bool $readonly = false) : void
	{
		Profiling::BeginTimer();
		$existed = file_exists($path);
		$flag = ($readonly && $existed ? SQLITE3_OPEN_READONLY : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
		$db = new \SQLite3($path, $flag);
		$db->enableExceptions(true);
		if ($existed == false)
			$db->query(self::CreateSql());

		$this->db = $db;
		if ($readonly == false)
			$this->db->busyTimeout(30000);
		$this->path = $path;
		$this->Execute('PRAGMA synchronous=OFF');
		$this->Execute('PRAGMA journal_mode=WAL');
		Profiling::EndTimer();
	}
	 
	private function CreateSql() : string
	{
		$sql = "CREATE TABLE data ("
			. "pID INTEGER PRIMARY KEY AUTOINCREMENT, "
			. $this->keyColumn . " VARCHAR(255) UNIQUE COLLATE NOCASE ";
		if ($this->columns != null)
		{
			foreach($this->columns as $column)
			{
				if (!$this->blobColumns || !in_array($column, $this->blobColumns))
					$sql .=	", " . $column . " TEXT ";
				else
					$sql .=	", " . $column . " BLOB ";
				
				if ($this->uniqueColumns != null && in_array($column, $this->uniqueColumns))
					$sql .= " UNIQUE";
				$sql .= " COLLATE NOCASE ";
			}
		}
		if ($this->intColumns != null)
		{
			foreach($this->intColumns as $column)
				$sql .=	", " . $column . " integer ";
		}

		$sql .=	")";
		return $sql;
	}

	public function Close() : void
	{
		Profiling::BeginTimer();
		$this->db->close();
		Profiling::EndTimer();
	}

	public function InsertOrUpdateBlob() : void
	{
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];
		// el 1ro es el key, el 2do es el blob
		if ($args[1] !== null)
			$args[1] = self::GetNamedStream($args[1]); 
		$sql = "INSERT OR REPLACE INTO data (pID, " . $this->keyColumn . $this->commaColumns . ") VALUES
			((SELECT pID FROM data WHERE " . $this->keyColumn . " = :p1), :p1 " . $this->commaArgs . ");";
			
		$this->Execute($sql, $args, 1);
	}

	public function InsertOrUpdate() : void
	{
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$sql = "INSERT OR REPLACE INTO data (pID, " . $this->keyColumn . $this->commaColumns . ") VALUES
			((SELECT pID FROM data WHERE " . $this->keyColumn . " = :p1), :p1 " . $this->commaArgs . ");";

		$this->Execute($sql, $args);
	}

	public function Execute(string $sql, $args = [], $blobIndex = -1)
	{
		if (is_array($args) == false)
			$args = [$args];
		$text = $this->ParamsToText($sql, $args);
		try
		{
			$this->db->enableExceptions(true);
			$statement = $this->db->prepare($sql);
			$n = 1;
			foreach($args as $arg)
			{
				if ($n - 1 === $blobIndex)
				{
					$statement->bindValue(':p' . ($n++), $arg, SQLITE3_BLOB);
				}
				else
				{
					$statement->bindValue(':p' . ($n++), $arg);
				}
			}
			return $statement->execute();
		}
		catch(\Exception $e)
		{
			throw new ErrorException($text . '. Error nativo: ' . $e->getMessage() . ".");
		}
	}

	private function ParamsToText(string $sql, array $args) : string
	{
		$text = 'No se ha podido completar la operación en SQLite. ';
		if ($this->path != null)
			$text .= 'Path: ' . $this->path;

		$text .= '. Comando: ' . $sql;
		$paramsAsText = '';
		foreach($args as $arg)
		{
			if ($paramsAsText != '')
				$paramsAsText .= ', ';
			$paramsAsText .= $arg;
		}
		if ($paramsAsText == '') $paramsAsText = 'Ninguno';
		$text .= '. Parámetros: ' . $paramsAsText;
		return $text;
	}

	public function Insert() : void
	{
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		// Arma el insert
		$sql = "INSERT INTO data (" . $this->keyColumn . $this->commaColumns . ") VALUES
			(:p1 " . $this->commaArgs . ");";

		$statement = $this->db->prepare($sql);
		$n = 1;
		foreach($args as $arg)
			$statement->bindValue(':p' . ($n++), $arg);
		$statement->execute();
	}

	public function Update($key, string $column, $value) : void
	{
		$sql = "UPDATE data SET " . $column . " = :p2 WHERE " . $this->keyColumn . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);
		$statement->bindValue(':p2', $value);

		$statement->execute();
	}
	public function AppendColumn(string $columnName, bool $isNumber, bool $indexed, bool $caseSensitive) : void
	{
		$sql = "ALTER TABLE data ADD COLUMN " . $columnName . " ";
		if ($isNumber)
			$sql .= " INTEGER ";
		else
			$sql .= " VARCHAR(255) " . ($caseSensitive ? "" : "COLLATE NOCASE ");

		$this->Execute($sql);

		if ($indexed)
		{
			$sql = "CREATE INDEX short_" . $columnName . " ON data (" . $columnName . ");";
			$this->Execute($sql);
		}
	}
	public function DeleteAll() : void
	{
		$sql = "DELETE FROM data";
		$statement = $this->db->prepare($sql);
		$statement->execute();
	}

	public function Delete($key) : void
	{
		$sql = "DELETE FROM data WHERE " . $this->keyColumn . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);

		$statement->execute();
	}

	public function Truncate() : void
	{
		$this->Close();
		unlink($this->path);
	}

	
	public function ReadBlobValue($key, string $column)
	{
		Profiling::BeginTimer();

		$row = $this->ReadValue($key, 'RowId, length, time'); 
		
		if ($row === null)
			return null;

		$lob = $this->db->openBlob('data', $column, $row[1]);
		$tmpFilename = self::CreateNameStreamFromStream($lob, $row[2], $row[3]);
		Profiling::EndTimer();
		return $tmpFilename;
	}

	public static function CreateNamedStreamFromFile(string $filename) : string
	{
		$key = "streams::" . Str::Guid();
		$lob = fopen($filename, 'rb');
		self::$OpenStreams[$key] = $lob;
		self::$OpenStreamsSizes[$key] = filesize($filename);
		self::$OpenStreamsTimes[$key] = filemtime($filename);
		return $key;
	}

	public static function CreateNameStreamFromStream($lob, int $size, $time) : string
	{
		$key = "streams::" . Str::Guid();
		self::$OpenStreams[$key] = $lob;
		self::$OpenStreamsSizes[$key] = $size;
		self::$OpenStreamsTimes[$key] = $time;
		return $key;
	}

	public static function GetNamedStream(string $key) 
	{
		return self::$OpenStreams[$key];
	}
	public static function GetNamedStreamSize(string $key) : int
	{
		return self::$OpenStreamsSizes[$key];
	}
	public static function GetNamedStreamDateTime(string $key) 
	{
		return self::$OpenStreamsTimes[$key];
	}
	public function ReadValue($key, string $column)
	{
		Profiling::BeginTimer();
		$sql = "SELECT pID, ". $column .
			" FROM data WHERE " . $this->keyColumn . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);

		$result = $statement->execute();

		$res = $result->fetchArray(SQLITE3_NUM);

		if ($res === false)
			return null;

		Profiling::EndTimer();
		return $res;
	}

	public function ReadRowByKey($key) : ?array
	{
		$sql = "SELECT * FROM data WHERE " . $this->keyColumn . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);

		$result = $statement->execute();

		$res = $result->fetchArray(SQLITE3_ASSOC);

		if ($res === false)
			return null;
		return $res;
	}

	public function Begin() : void
	{
		$this->Execute('PRAGMA journal_mode=DELETE');
		$this->db->query("BEGIN TRANSACTION;");
	}

	public function Commit() : void
	{
		$this->db->query("COMMIT TRANSACTION;");
	}

	public function Increment($key, string $column) : void
	{
		Profiling::BeginTimer();
		$res = $this->ReadValue($key, $column);

		if ($res != null)
		{
			// update
			$id = $res[0];
			$n = intval($res[1]) + 1;
			$sql = "UPDATE data SET ". $column . " = " . $n .
				" WHERE pID = :p1;";
			$statement = $this->db->prepare($sql);
			$statement->bindValue(':p1', $id);

			$statement->execute();
		}
		else
		{
			// insert
			$n = 1;
			$sql = "INSERT INTO data (" . $this->keyColumn . ", " . $column . ") VALUES (:p1, :p2);";
			$statement = $this->db->prepare($sql);
			$statement->bindValue(':p1', $key);
			$statement->bindValue(':p2', $n);

			$statement->execute();
		}
		Profiling::EndTimer();
	}

}
