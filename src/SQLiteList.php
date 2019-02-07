<?php

namespace minga\framework;

class SQLiteList
{
	private $keyColumn;
	private $columns;
	private $intColumns;
	private $uniqueColumns;

	private $commaColumns;
	private $commaArgs;

	private $path = null;

	private $db = null;

	public function __construct($key, $columns = null, $intColumns = null, $uniqueColumns = null)
	{
		$this->keyColumn = $key;
		$this->columns = $columns;
		$this->intColumns = $intColumns;
		$this->uniqueColumns = $uniqueColumns;

		$this->commaColumns = "";
		$this->commaArgs = "";
		$n = 2;
		if ($columns != null)
			foreach($columns as $col)
			{
				$this->commaColumns .= "," . $col;
				$this->commaArgs .= ",:p" . ($n++);
			}
		if ($intColumns != null)
			foreach($intColumns as $col)
			{
				$this->commaColumns .= "," . $col;
				$this->commaArgs .= ",:p" . ($n++);
			}
	}
	public function Query($sql)
	{
		return $this->db->query($sql);
	}

	public function QueryAll($sql, $args = null)
	{
		if ($args != null && is_array($args) == false)
			$args = array($args);
		if ($args != null)
			$result = $this->Execute($sql, $args);
		else
			$result = $this->Query($sql);
		$ret = array();
		while($row = $result->fetchArray(SQLITE3_ASSOC))
			$ret[] = $row;
		return $ret;
	}

	public function QueryRow($sql, $params = null)
	{
		$result = $this->Execute($sql, $params);
		if ($result == null)
			return null;
		else
			return $result->fetchArray(SQLITE3_ASSOC);
	}

	public function Open($path, $readonly = false)
	{
		$existed = file_exists($path);
		$flag = ($readonly && $existed ? SQLITE3_OPEN_READONLY : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
		$db = new \SQLite3($path, $flag);
		$db->enableExceptions(true);
		if ($existed == false)
		{
			$db->query(self::CreateSql());
		}
		$this->db = $db;
		if ($readonly == false)
			$this->db->busyTimeout(30000);
		$this->path = $path;
		$this->Execute('PRAGMA synchronous=OFF');
		$this->Execute('PRAGMA journal_mode=WAL');
	}

	private function CreateSql()
	{
		$sql = "CREATE TABLE data ("
			. "pID     INTEGER PRIMARY KEY AUTOINCREMENT, "
			. $this->keyColumn . " varchar(255) UNIQUE COLLATE NOCASE ";
		if ($this->columns != null)
			foreach($this->columns as $column)
			{
				$sql .=	", " . $column . " varchar(255) ";
				if ($this->uniqueColumns != null && in_array($column, $this->uniqueColumns))
					$sql .= " UNIQUE";
				$sql .= " COLLATE NOCASE ";
			}
		if ($this->intColumns != null)
			foreach($this->intColumns as $column)
				$sql .=	", " . $column . " integer ";

		$sql .=	")";
		return $sql;
	}
	public function Close()
	{
		$this->db->close();
	}
	public function InsertOrUpdate()
	{
		$args = func_get_args();
		if (sizeof($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$sql = "insert or replace into data (pID, " . $this->keyColumn . $this->commaColumns . ") values
			((select pID from data where " . $this->keyColumn . " = :p1), :p1 " . $this->commaArgs . ");";

		$this->Execute($sql, $args);
	}

	public function Execute($sql, $args = array())
	{
		if (is_array($args) == false)
			$args = array($args);
		$text = $this->ParamsToText($sql, $args);
		try {
			$this->db->enableExceptions(true);
			$statement = $this->db->prepare($sql);
			$n = 1;
			foreach($args as $arg)
				$statement->bindValue(':p' . ($n++), $arg);
			return $statement->execute();
		}
		catch(\Exception $e)
		{
			throw new \Exception($text . '. Error nativo: ' . $e->getMessage() . ".");
		}
	}
	private function ParamsToText($sql, $args)
	{
		$text = 'No se ha podido completar la operación en SQLite. ';
		if ($this->path != null)
		{
			$text .= 'Path: ' . $this->path;
		}
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

	public function Insert()
	{
		$args = func_get_args();
		if (sizeof($args) == 1 && is_array($args[0]))
			$args = $args[0];

		// Arma el insert
		$sql = "insert into data (" . $this->keyColumn . $this->commaColumns . ") values
			(:p1 " . $this->commaArgs . ");";

		$statement = $this->db->prepare($sql);
		$n = 1;
		foreach($args as $arg)
			$statement->bindValue(':p' . ($n++), $arg);
		$statement->execute();
	}

	public function Update($key, $column, $value)
	{
		$sql = "update data set " . $column . " = :p2 where " . $this->keyColumn . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);
		$statement->bindValue(':p2', $value);

		$statement->execute();
	}

	public function DeleteAll()
	{
		$sql = "delete from data";
		$statement = $this->db->prepare($sql);
		$statement->execute();
	}
	public function Delete($key)
	{
		$sql = "delete from data where " . $this->keyColumn . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);

		$statement->execute();
	}
	public function ReadValue($key, $column)
	{
		$sql = "select pID, ". $column .
			" from data where " . $this->keyColumn . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);

		$result = $statement->execute();

		$res = $result->fetchArray(SQLITE3_NUM);

		if ($res === false)
			return null;
		else
			return $res;
	}
	public function ReadRowByKey($key)
	{
		$sql = "select * from data where " . $this->keyColumn . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);

		$result = $statement->execute();

		$res = $result->fetchArray(SQLITE3_ASSOC);

		if ($res === false)
		{
			return null;
		}else
			return $res;
	}

	public function Begin()
	{
		$this->Execute('PRAGMA journal_mode=DELETE');
		$this->db->query("BEGIN TRANSACTION;");
	}
	public function Commit()
	{
		$this->db->query("COMMIT TRANSACTION;");
	}

	public function Increment($key, $column)
	{
		Profiling::BeginTimer();
		$res = $this->ReadValue($key, $column);

		if ($res != null)
		{
			// update
			$id = $res[0];
			$n = intval($res[1]) + 1;
			$sql = "update data set ". $column . " = " . $n .
				" where pID = :p1;";
			$statement = $this->db->prepare($sql);
			$statement->bindValue(':p1', $id);

			$statement->execute();
		}
		else
		{
			// insert
			$n = 1;
			$sql = "insert into data (" . $this->keyColumn . ", " . $column . ") values (:p1, :p2);";
			$statement = $this->db->prepare($sql);
			$statement->bindValue(':p1', $key);
			$statement->bindValue(':p2', $n);

			$statement->execute();
		}
		Profiling::EndTimer();
	}

}
