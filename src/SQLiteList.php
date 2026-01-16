<?php

namespace minga\framework;

class SQLiteList
{
	private string $keyColumn;
	private ?array $columns;
	private ?array $intColumns;
	private ?array $uniqueColumns;
	private ?array $blobColumns;

	private string $quotedCommaColumns;
	private string $commaArgs;

	private string $path = '';

	private \SQLite3 $db;
	private bool $checked = false;
	private static array $OpenStreams = [];
	private static array $OpenStreamsSizes = [];
	private static array $OpenStreamsTimes = [];

	public function __construct(string $key, ?array $columns = null, ?array $intColumns = null, ?array $uniqueColumns = null, ?array $blobColumns = null)
	{
		$this->keyColumn = $key;
		$this->columns = $columns;
		$this->intColumns = $intColumns;
		$this->uniqueColumns = $uniqueColumns;
		$this->blobColumns = $blobColumns;

		$this->quotedCommaColumns = "";
		$this->commaArgs = "";
		$n = 2;
		if ($columns != null) {
			foreach ($columns as $col) {
				$this->quotedCommaColumns .= "," . Db::QuoteColumn($col);
				$this->commaArgs .= ",:p" . ($n++);
			}
		}
		if ($intColumns != null) {
			foreach ($intColumns as $col) {
				$this->quotedCommaColumns .= "," . Db::QuoteColumn($col);
				$this->commaArgs .= ",:p" . ($n++);
			}
		}
	}

	public function Query(string $sql)
	{
		return $this->db->query($sql);
	}

	public function QueryAll(string $sql, $args = null): array
	{
		if ($args != null && is_array($args) == false)
			$args = [$args];
		if ($args != null)
			$result = $this->Execute($sql, $args);
		else
			$result = $this->Query($sql);
		$ret = [];
		while ($row = $result->fetchArray(SQLITE3_ASSOC))
			$ret[] = $row;
		return $ret;
	}

	public function QueryRow(string $sql, $params = null): ?array
	{
		$result = $this->Execute($sql, $params);
		if ($result == null)
			return null;
		$res = $result->fetchArray(SQLITE3_ASSOC);

		if ($res === false)
			return null;

		return $res;
	}

	public function Open(string $path, bool $readonly = false): void
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
		if ($readonly == false)
			$this->Execute('PRAGMA journal_mode=WAL');
		Profiling::EndTimer();
	}

	private function CreateSql(): string
	{
		$sql = "CREATE TABLE data ("
			. "pID INTEGER PRIMARY KEY AUTOINCREMENT, "
			. Db::QuoteColumn($this->keyColumn) . " VARCHAR(255) UNIQUE COLLATE NOCASE ";
		if ($this->columns != null) {
			foreach ($this->columns as $column) {
				if ($this->blobColumns == false || in_array($column, $this->blobColumns) == false)
					$sql .= ", " . Db::QuoteColumn($column) . " TEXT ";
				else
					$sql .= ", " . Db::QuoteColumn($column) . " BLOB ";

				if ($this->uniqueColumns != null && in_array($column, $this->uniqueColumns))
					$sql .= " UNIQUE";
				$sql .= " COLLATE NOCASE ";
			}
		}
		if ($this->intColumns != null) {
			foreach ($this->intColumns as $column)
				$sql .= ", " . Db::QuoteColumn($column) . " integer ";
		}

		$sql .= ", last_accessed INTEGER DEFAULT 0)";
		return $sql;
	}

	public function Close(): void
	{
		Profiling::BeginTimer();
		$this->db->close();
		Profiling::EndTimer();
	}

	public function InsertOrUpdateBlob(): void
	{
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];
		// el 1ro es el key, el 2do es el blob
		if ($args[1] !== null)
			$args[1] = self::GetNamedStream($args[1]);
		$sql = "INSERT OR REPLACE INTO data (pID, " . Db::QuoteColumn($this->keyColumn) . $this->quotedCommaColumns . ", last_accessed) VALUES
			((SELECT pID FROM data WHERE " . Db::QuoteColumn($this->keyColumn) . " = :p1), :p1 " . $this->commaArgs . ", strftime('%s', 'now'));";

		$this->Execute($sql, $args, 1, true);
	}

	public function FreeQuota(int $percentage): int
	{
		// Calcular cuántos registros eliminar
		$total = $this->db->querySingle("SELECT COUNT(*) FROM data");
		$toDelete = (int) ($total * $percentage / 100);
		if ($toDelete === 0) {
			return 0;
		}
		// Eliminar los más antiguos
		$sql = "DELETE FROM data WHERE rowid IN (
			SELECT rowid FROM data
			ORDER BY last_accessed ASC
			LIMIT :limit
		)";
		$statement = $this->db->prepare($sql);
		$statement->bindValue(':limit', $toDelete, SQLITE3_INTEGER);
		$statement->execute();
		// Retornar cuántos se eliminaron
		return $this->db->changes();
	}

	public function DataSizeMB(): int
	{
		// Tamaño actual
		$used = $this->DiskSizeMB();
		// Páginas libres (espacio recuperable)
		$free = $this->db->querySingle("SELECT freelist_count * page_size FROM pragma_freelist_count(), pragma_page_size();") / 1024 / 1024;

		return $used - $free;
	}

	public function DiskSizeMB(): int
	{
		// Tamaño actual
		$used = $this->db->querySingle("SELECT page_count * page_size FROM pragma_page_count(), pragma_page_size();");
		return $used / 1024 / 1024;
	}

	public function InsertOrUpdate(): void
	{
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$sql = "INSERT OR REPLACE INTO data (pID, " . Db::QuoteColumn($this->keyColumn) . $this->quotedCommaColumns . ", last_accessed) VALUES
					((SELECT pID FROM data WHERE " . Db::QuoteColumn($this->keyColumn) . " = :p1), :p1 " . $this->commaArgs . ", strftime('%s', 'now'));";

		$this->Execute($sql, $args, -1, true);
	}

	public function Execute(string $sql, $args = [], int $blobIndex = -1, $doColumnCheck = false)
	{
		if (is_array($args) == false)
			$args = [$args];
		try {
			$this->db->enableExceptions(true);

			if ($doColumnCheck) {
				$this->doColumnCheck();
			}

			$statement = $this->db->prepare($sql);
			$n = 1;
			foreach ($args as $arg) {
				$val = $arg;
				if (is_bool($arg))
					$val = (int) $arg;
				if ($n - 1 === $blobIndex)
					$statement->bindValue(':p' . ($n++), $val, SQLITE3_BLOB);
				else
					$statement->bindValue(':p' . ($n++), $val);
			}
			return $statement->Execute();
		} catch (\Exception $e) {
			throw new ErrorException($this->ParamsToText($sql, $args) . '. Error nativo: ' . $e->getMessage() . ".");
		}
	}

	private function ParamsToText(string $sql, array $args): string
	{
		$text = 'No se ha podido completar la operación en SQLite. ';
		if ($this->path != '')
			$text .= 'Path: ' . $this->path;

		$text .= '. Comando: ' . $sql;
		$paramsAsText = '';
		foreach ($args as $arg) {
			if ($paramsAsText != '')
				$paramsAsText .= ', ';
			$paramsAsText .= $arg;
		}
		if ($paramsAsText == '')
			$paramsAsText = 'Ninguno';
		$text .= '. Parámetros: ' . $paramsAsText;
		return $text;
	}

	public function Insert(): void
	{
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->doColumnCheck();

		// Arma el insert
		$sql = "INSERT INTO data (" . Db::QuoteColumn($this->keyColumn) . $this->quotedCommaColumns . ", last_accessed) VALUES
			(:p1 " . $this->commaArgs . ", strftime('%s', 'now'));";

		$statement = $this->db->prepare($sql);
		$n = 1;
		foreach ($args as $arg) {
			$val = $arg;
			if (is_bool($arg))
				$val = (int) $arg;
			$statement->bindValue(':p' . ($n++), $val);
		}
		$statement->execute();
	}

	public function Update($key, string $column, $value): void
	{
		if (is_bool($value))
			$value = (int) $value;

		$sql = "UPDATE data SET " . Db::QuoteColumn($column) . " = :p2 WHERE " . Db::QuoteColumn($this->keyColumn) . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);
		$statement->bindValue(':p2', $value);

		$statement->execute();
	}

	public function Replace($key, string $column, $oldValue, $newValue): void
	{
		if (is_bool($oldValue))
			$oldValue = (int) $oldValue;
		if (is_bool($newValue))
			$newValue = (int) $newValue;

		$sql = "UPDATE data SET " . Db::QuoteColumn($column)
			. " = REPLACE(" . Db::QuoteColumn($column) . ", :p2, :p3) WHERE " . Db::QuoteColumn($this->keyColumn) . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);
		$statement->bindValue(':p2', $oldValue);
		$statement->bindValue(':p3', $newValue);

		$statement->execute();
	}

	public function AppendColumn(string $columnName, bool $isNumber, bool $indexed, bool $caseSensitive): void
	{
		$sql = "ALTER TABLE data ADD COLUMN " . Db::QuoteColumn($columnName) . " ";
		if ($isNumber)
			$sql .= " INTEGER ";
		else
			$sql .= " VARCHAR(255) " . ($caseSensitive ? "" : "COLLATE NOCASE ");

		$this->Execute($sql);

		if ($indexed) {
			$sql = "CREATE INDEX " . Db::QuoteTable("short_" . $columnName) . " ON data (" . Db::QuoteColumn($columnName) . ");";
			$this->Execute($sql);
		}
	}

	public function DeleteAll(): void
	{
		$sql = "DELETE FROM data";
		$statement = $this->db->prepare($sql);
		$statement->execute();
	}

	public function Delete($key): void
	{
		$sql = "DELETE FROM data WHERE " . Db::QuoteColumn($this->keyColumn) . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);

		$statement->execute();
	}

	public function Truncate(): void
	{
		$this->Close();
		unlink($this->path);
	}

	public function ReadBlobValue($key, string $column): string
	{
		Profiling::BeginTimer();
		$row = $this->ReadValue($key, ['RowId', 'length', 'time']);
		if ($row === null)
			return '';

		$lob = $this->GetBlob('data', $column, $row[1]);
		if ($lob === null)
			return '';
		$tmpFilename = self::CreateNameStreamFromStream($lob, $row[2], $row[3]);
		Profiling::EndTimer();
		return $tmpFilename;
	}

	private function GetBlob(string $table, string $column, int $id)
	{
		try {
			$ret = $this->db->openBlob($table, $column, $id);
			if ($ret === false)
				return null;
			return $ret;
		} catch (\Exception $e) {
			return null;
		}
	}

	public static function CreateNamedStreamFromFile(string $filename): string
	{
		$key = "streams::" . Str::Guid();
		$lob = fopen($filename, 'rb');
		self::$OpenStreams[$key] = $lob;
		self::$OpenStreamsSizes[$key] = filesize($filename);
		self::$OpenStreamsTimes[$key] = filemtime($filename);
		return $key;
	}

	public static function CreateNameStreamFromStream($lob, int $size, $time): string
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

	public static function GetNamedStreamSize(string $key): int
	{
		return self::$OpenStreamsSizes[$key];
	}

	private function doColumnCheck()
	{
		if (!$this->checked) {
			$result = $this->db->query("PRAGMA table_info(data)");

			$hasLastAccessed = false;
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				if ($row['name'] === 'last_accessed') {
					$hasLastAccessed = true;
					break;
				}
			}
			if (!$hasLastAccessed) {
				$this->db->exec("ALTER TABLE data ADD COLUMN last_accessed INTEGER DEFAULT 0");
			}
			$this->checked = true;
		}
		return;
	}

	public static function GetNamedStreamDateTime(string $key)
	{
		return self::$OpenStreamsTimes[$key];
	}

	/**
	 * @param array|string $column
	 */
	public function ReadValue($key, $column): ?array
	{
		Profiling::BeginTimer();
		try {
			if (is_array($column)) {
				$text = '';
				foreach ($column as $col)
					$text .= Db::QuoteColumn($col) . ',';
				$text = rtrim($text, ',');
			} else
				$text = Db::QuoteColumn($column);

			$sql = "SELECT pID, " . $text
				. " FROM data WHERE " . Db::QuoteColumn($this->keyColumn) . " = :p1;";

			$statement = $this->db->prepare($sql);
			$statement->bindValue(':p1', $key);

			$result = $statement->execute();

			$res = $result->fetchArray(SQLITE3_NUM);

			if ($res === false)
				return null;

			return $res;
		} finally {
			Profiling::EndTimer();
		}
	}

	public function ReadRowByKey($key): ?array
	{
		$sql = "SELECT * FROM data WHERE " . Db::QuoteColumn($this->keyColumn) . " = :p1;";

		$statement = $this->db->prepare($sql);
		$statement->bindValue(':p1', $key);

		$result = $statement->execute();

		$res = $result->fetchArray(SQLITE3_ASSOC);

		if ($res === false)
			return null;
		return $res;
	}

	public function Begin(): void
	{
		$this->Execute('PRAGMA journal_mode=DELETE');
		$this->db->query("BEGIN TRANSACTION;");
	}

	public function Commit(): void
	{
		$this->db->query("COMMIT TRANSACTION;");
	}

	public function Increment($key, string $column): void
	{
		Profiling::BeginTimer();
		try {

			$res = $this->ReadValue($key, $column);

			if ($res !== null) {
				$id = $res[0];
				$n = (int) $res[1] + 1;
				$sql = "UPDATE data SET " . Db::QuoteColumn($column) . " = :p1"
					. " WHERE pID = :p2;";
				$statement = $this->db->prepare($sql);
				$statement->bindValue(':p1', $n);
				$statement->bindValue(':p2', $id);

				$statement->execute();
			} else {
				$sql = "INSERT INTO data (" . Db::QuoteColumn($this->keyColumn) . ", " . Db::QuoteColumn($column) . ", last_accessed) VALUES (:p1, 1, strftime('%s', 'now'));";
				$statement = $this->db->prepare($sql);
				$statement->bindValue(':p1', $key);
				$this->doColumnCheck();
				$statement->execute();
			}

		} catch (\Exception $e) {
			// Si se corrompe el archivo sqlite y es referer, se borra...
			if (
				Str::Contains($e->getMessage(), 'no such table: data')
				&& Str::EndsWith($this->path, 'referers.db')
			) {
				IO::Delete($this->path);
			} else
				throw $e;
		} finally {
			Profiling::EndTimer();
		}
	}
}