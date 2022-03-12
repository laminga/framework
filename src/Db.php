<?php

namespace minga\framework;

class Db
{
	public $Host = 'localhost';
	public $Name = '';
	public $User = '';
	public $NoDb = false;
	public $Password = '';
	public $Port = 3306;
	public $Charset = 'utf8';

	// comentado esto por ahora: \PDO|\Doctrine\DBAL\Connection
	public $db = null;
	private bool $isInTransaction = false;
	private $lastRows = -1;

	public function __construct($db = null, $profiler = null)
	{
		if($db == null)
		{
			$this->Connect();
			return;
		}

		$this->db = $db;

		if (Context::Settings()->Db()->ForceStrictTables)
			$this->db->executeQuery("SET sql_mode = (SELECT CONCAT(@@session.sql_mode, ',STRICT_TRANS_TABLES'));");
		if (Context::Settings()->Db()->ForceOnlyFullGroupBy)
			$this->db->executeQuery("SET sql_mode = (SELECT CONCAT(@@session.sql_mode, ',ONLY_FULL_GROUP_BY'));");
		if (Context::Settings()->Db()->SetTimeZone)
			$this->db->executeQuery("SET time_zone=?", [(new \DateTime())->format('P')]);

		if (Profiling::IsProfiling() && $profiler !== null)
			$this->db->getConfiguration()->setSQLLogger($profiler);
	}

	public function IsInTransaction() : bool
	{
		return $this->isInTransaction;
	}

	private function Connect() : void
	{
		if ($this->db !== null)
			return;

		Profiling::BeginTimer();
		Performance::BeginDbWait();

		// Inicia Base de datos
		$this->NoDb = Context::Settings()->Db()->NoDb;
		$this->Host = Context::Settings()->Db()->Host;
		$this->Name = Context::Settings()->Db()->Name;
		$this->User = Context::Settings()->Db()->User;
		$this->Port = Context::Settings()->Db()->Port;
		$this->Password = Context::Settings()->Db()->Password;
		$this->Charset = 'utf8';

		$this->db = new \PDO('mysql:host=' . $this->Host
			. ';port=' . $this->Port
			. ';dbname=' . $this->Name
			. ';charset=' . $this->Charset,
			$this->User,
			$this->Password);
		$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

		Performance::EndDbWait();
		Profiling::EndTimer();
	}

	public function execute(string $query, array $data = []) : int
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();

			$query = $this->parseArrayParams($query, $data);

			if(key($data) === 0)
				return $this->doExecute($query, $data);

			return $this->doExecuteNamedParams($query, $data);
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	private function doExecuteNamedParams(string $query, array $data = []) : int
	{
		$stmt = $this->db->prepare($query);
		foreach($data as $k => $v)
			$stmt->bindValue($k, $v, $this->getParamType($v));
		$stmt->execute();
		return $stmt->rowCount();
	}

	private function doExecute(string $query, array $data = []) : int
	{
		$stmt = $this->db->prepare($query);
		$data = $this->fixBoolParams($data);
		$stmt->execute($data);
		return $stmt->rowCount();
	}

	/**
	 * Prepares and executes an SQL query and returns the result as an associative array.
	 *
	 * @param string $sql The SQL query.
	 * @param array $params The query parameters.
	 *
	 * @return array
	 */
	public function fetchAll($sql, array $params = [])
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			if (method_exists($this->db, 'fetchAll'))
				return $this->db->fetchAll($sql, $params);


				$query = $this->parseArrayParams($sql, $params);
				$stmt = $this->db->prepare($query);
				if(key($params) === 0)
					$stmt->execute($params);
				else
				{
					foreach($params as $k => $v)
						$stmt->bindValue($k, $v, $this->getParamType($v));
					$stmt->execute();
				}
				return $stmt->fetchAll(\PDO::FETCH_ASSOC);

		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	public function fetchAllByPos($sql, array $params = [])
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		$ret = $stmt->fetchAll(\PDO::FETCH_NUM);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * Prepares and executes a multi SQL query and returns the
	 * results sets as an array of associative arrays.
	 *
	 * @param string $query The multi SQL query (FACET in sphinx).
	 * @param array $params The query parameters.
	 * @paran int $fetchStyle Fetch style PDO Constant
	 *
	 * @return array
	 */
	public function fetchAllMultipleResults(string $query, array $params = [], int $fetchStyle = \PDO::FETCH_ASSOC) : array
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();

			$this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
			$query = $this->parseArrayParams($query, $params);
			$stmt = $this->db->prepare($query);

			if(key($params) === 0)
				$stmt->execute($params);
			else
			{
				foreach($params as $k => $v)
					$stmt->bindValue($k, $v, $this->getParamType($v));
				$stmt->execute();
			}

			$res = [];
			do
				$res[] = $stmt->fetchAll($fetchStyle);
			while ($stmt->nextRowset());

			return $res;
		}
		finally
		{
			$this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	public function fetchScalarInt(string $query, array $params = []) : int
	{
		return (int)$this->fetchScalar($query, $params);
	}

	public function fetchScalarIntNullable(string $query, array $params = []) : ?int
	{
		$ret = $this->fetchScalarNullable($query, $params);
		if ($ret === null)
			return null;

		return (int)$ret;
	}

	public function fetchScalarNullable(string $query, array $params = [])
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			$ret = $this->fetchAssoc($query, $params);
			if($ret === null)
				return null;

			return current($ret);
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	public function fetchScalar(string $query, array $params = [])
	{
		$ret = $this->fetchScalarNullable($query, $params);
		if($ret === null)
			throw new PublicException("No se ha obtenido ningún resultado en la consulta cuando se esperaba uno.");
		return $ret;
	}

	private function fetch(string $query, array $params = [], int $fetchStyle = \PDO::FETCH_ASSOC)
	{
		$query = $this->parseArrayParams($query, $params);
		$stmt = $this->db->prepare($query);
		if(key($params) === 0)
			$stmt->execute($params);
		else
		{
			foreach($params as $k => $v)
				$stmt->bindValue($k, $v, $this->getParamType($v));
			$stmt->execute();
		}
		return $stmt->fetch($fetchStyle);
	}

	public function fetchAllColumn(string $query, array $params = [])
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$data = $this->fetchAll($query, $params);
		for($i = 0; $i < count($data); $i++)
			$data[$i] = reset($data[$i]);

		Performance::EndDbWait();
		Profiling::EndTimer();
		return $data;
	}

	/**
	 * Prepares and executes an SQL query and returns the value of a single column
	 * of the first row of the result.
	 *
	 * @param string $query sql query to be executed
	 * @param array $params prepared statement params
	 * @param int $colnum 0-indexed column number to retrieve
	 *
	 * @return mixed
	 */
	public function fetchColumn(string $query, array $params = [], int $colnum = 0)
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$query = $this->parseArrayParams($query, $params);
		$stmt = $this->db->prepare($query);
		if(key($params) === 0)
			$stmt->execute($params);
		else
		{
			foreach($params as $k => $v)
				$stmt->bindValue($k, $v, $this->getParamType($v));
			$stmt->execute();
		}
		$stmt->execute($params);
		$ret = $stmt->fetchColumn($colnum);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * Returns the ID of the last inserted row or sequence value.
	 *
	 * @return integer The last inserted id.
	 */
	public function lastInsertId() : int
	{
		return (int)$this->db->lastInsertId();
	}

	/**
	 * Inserts or Replaces a table row with specified data.
	 */
	private function insertOrReplace(string $tableName, array $data, string $command) : int
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$query = $command . ' INTO ' . self::QuoteTable($tableName)
			. ' (' . implode(', ', self::QuoteColumn(array_keys($data))) . ')'
			. ' VALUES (' . rtrim(str_repeat('?,', count($data)), ',') . ')';

		$ret = $this->doExecute($query, array_values($data));
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * Inserts a table row with specified data.
	 *
	 * @param string $tableName The name of the table to insert data into.
	 * @param array $data An associative array containing column-value pairs.
	 *
	 * @return integer The number of affected rows.
	 */
	public function insert(string $tableName, array $data) : int
	{
		return $this->insertOrReplace($tableName, $data, 'INSERT');
	}

	/**
	 * Replaces a table row with specified data.
	 *
	 * @param string $tableName The name of the table to replace data into.
	 * @param array $data An associative array containing column-value pairs.
	 *
	 * @return integer The number of affected rows.
	 */
	public function replace(string $tableName, array $data) : int
	{
		return $this->insertOrReplace($tableName, $data, 'REPLACE');
	}

	/**
	 * Executes an SQL DELETE statement on a table.
	 *
	 * @param string $tableName The name of the table on which to delete.
	 * @param array $identifier The deletion criteria. An associative array containing column-value pairs.
	 *
	 * @return integer The number of affected rows.
	 */
	public function delete(string $tableName, array $identifier) : int
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$criteria = [];
		foreach ($identifier as $columnName => $_)
			$criteria[] = self::QuoteColumn($columnName) . ' = :' . $columnName;

		$query = 'DELETE FROM ' . self::QuoteTable($tableName) . ' WHERE ' . implode(' AND ', $criteria);
		$ret = $this->doExecuteNamedParams($query, $identifier);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	public function deleteSet(string $tableName, string $columnName, array $values) : int
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$query = 'DELETE FROM ' . self::QuoteTable($tableName)
			. ' WHERE ' . self::QuoteColumn($columnName)
			. ' IN (' . self::JoinPlaceholders($values) . ')';
		$ret = $this->doExecute($query, $values);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	private function JoinPlaceholders(array $values) : string
	{
		return implode(',', array_fill(0, count($values), '?'));
	}

	/**
	 * Prepares and executes an SQL query and returns the first row of the result
	 * as an associative array.
	 *
	 * @param string $statement The SQL query.
	 * @param array $params The query parameters.
	 *
	 * @return ? array
	 */
	public function fetchAssoc(string $statement, array $params = [])
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$ret = $this->fetch($statement, $params, \PDO::FETCH_ASSOC);
		if ($ret === false)
			$ret = null;
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * Parse array parameters as a string list, quoting the values based on type.
	 * Non array parameters are returned as-is.
	 *
	 * @param array $params The parameters.
	 *
	 * @return string
	 */
	private function parseArrayParams(string $query, array &$params) : string
	{
		foreach($params as $k => $v)
		{
			if(is_array($v))
			{
				$query = str_replace(':' . $k, $this->arrayToList($v), $query);
				unset($params[$k]);
			}
		}
		return $query;
	}

	/**
	 * Convert an array into a \PDO quoted comma separated list, based on variable type.
	 *
	 * @param array $arr
	 *
	 * @return string
	 */
	private function arrayToList(array $arr) : string
	{
		$ret = '';
		foreach($arr as $v)
		{
			if($this->getParamType($v) == \PDO::PARAM_INT
				|| $this->getParamType($v) == \PDO::PARAM_BOOL)
			{
				$ret .= (int)$v . ', ';
			}
			else
				$ret .= $this->db->quote($v, $this->getParamType($v)) . ', ';
		}

		return rtrim($ret, ', ');
	}

	/**
	 * Get the PDO param constant based on variable type.
	 *
	 * @param mixed $var
	 *
	 * @return integer PDO::PARAM constant.
	 */
	private function getParamType($var) : int
	{
		if($var === null)
			return \PDO::PARAM_NULL;
		elseif(is_bool($var))
			return \PDO::PARAM_BOOL;
		elseif(is_int($var))
			return \PDO::PARAM_INT;

			return \PDO::PARAM_STR;
	}

	/**
	 * Prepares and executes an SQL query and returns the first row of the result
	 * as a numerically indexed array.
	 *
	 * @param string $statement query to be executed
	 * @param array $params statement params
	 *
	 * @return array
	 */
	public function fetchArray(string $statement, array $params = []) : array
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$ret = $this->fetch($statement, $params, \PDO::FETCH_NUM);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * Truncate table.
	 *
	 * @param string $tableName The name of the table to truncate.
	 */
	public function truncate(string $tableName) : int
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$query = 'TRUNCATE TABLE ' . self::QuoteTable($tableName);
		$ret = $this->doExecute($query);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * Executes an SQL UPDATE statement on a table.
	 *
	 * @param string $tableName The name of the table to update.
	 * @param array $data The data to update. An associative array containing column-value pairs.
	 * @param array $identifier The update criteria. An associative array containing column-value pairs.
	 *
	 * @return integer The number of affected rows.
	 */
	public function update(string $tableName, array $data, array $identifier) : int
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();

		$i = 0;
		$dataNew = [];
		$set = [];
		foreach ($data as $columnName => $value)
		{
			$name = $columnName . $i++;
			$set[] = self::QuoteColumn($columnName) . ' = :' . $name;
			$dataNew[$name] = self::ConvertType($value);
		}

		$identifierNew = [];
		$where = [];
		foreach ($identifier as $columnName => $value)
		{
			$name = $columnName . $i++;
			$where[] = self::QuoteColumn($columnName) . ' = :' . $name;
			$identifierNew[$name] = self::ConvertType($value);
		}

		$params = array_merge($dataNew, $identifierNew);

		$sql = 'UPDATE ' . self::QuoteTable($tableName) . ' SET ' . implode(', ', $set)
			. ' WHERE ' . implode(' AND ', $where);

		$ret = $this->doExecuteNamedParams($sql, $params);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * MySql no toma bien los bools, es mejor cambiarlos por 1 o 0.
	 *
	 * @param mixed $value El valor a convertir (si es bool).
	 *
	 * @return mixed El valor convertido.
	 */
	private static function ConvertType($value)
	{
		if(is_bool($value))
			return (int)$value;
		return $value;
	}

	/**
	 * Alias de beginTransacion()
	 */
	public function begin() : bool
	{
		return $this->beginTransaction();
	}

	/**
	 * Initiates a transaction.
	 */
	public function beginTransaction() : bool
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$this->isInTransaction = true;
		$ret = $this->db->beginTransaction();
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * Commits a transaction.
	 */
	public function commit() : bool
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$this->isInTransaction = false;
		$ret = $this->db->commit();
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * Rolls back a transaction.
	 */
	public function rollBack() : bool
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$this->isInTransaction = false;
		$ret = $this->db->rollBack();
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	public function ensureBegin() : bool
	{
		if ($this->isInTransaction == false)
			return $this->begin();
		return true;
	}

	public function ensureCommit() : bool
	{
		if ($this->isInTransaction)
			return $this->commit();
		return true;
	}

	public function ensureRollback() : bool
	{
		if ($this->isInTransaction)
			return $this->rollback();
		return true;
	}

	public static function QueryToString(string $sql, array $params) : string
	{
		$ret = $sql;
		foreach($params as $k => $v)
		{
			$quote = "'";
			if(is_int($v))
				$quote = '';
			$ret = str_replace(':' . $k, $quote . $v . $quote, $ret);
		}
		return $ret;
	}

	public static function QuoteColumn($name)
	{
		if(is_array($name))
		{
			$ret = [];
			foreach($name as $item)
				$ret[] = self::QuoteColumn($item);
			return $ret;
		}

		//filtra alfanumérico y guion bajo.
		$name = preg_replace('/[^A-Za-z0-9_]+/', '', $name);
		return '`' . $name . '`';
	}

	public static function QuoteTable(string $name) : string
	{
		return self::QuoteColumn($name);
	}

	public function GetDBSize()
	{
		try
		{
			Profiling::BeginTimer();
			$sql = "SELECT
				SUM(data_length) AS data, SUM(index_length) AS `index`
				FROM information_schema.tables
				WHERE table_schema = ?
				GROUP BY table_schema";
			return $this->fetchAssoc($sql,
				[Context::Settings()->Db()->Name]);
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
			return '-1';
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public function dropTable(string $table) : void
	{
		Profiling::BeginTimer();
		$this->ensureBegin();
		$sql = "DROP TABLE IF EXISTS " . self::QuoteTable($table);
		$this->execDDL($sql);
		Profiling::EndTimer();
	}

	public function dropTemporaryTable(string $table) : void
	{
		Profiling::BeginTimer();
		$this->ensureBegin();
		$sql = "DROP TEMPORARY TABLE " . self::QuoteTable($table);
		$this->exec($sql);
		Profiling::EndTimer();
	}

	public function rowExists(string $table, string $field, string $value) : bool
	{
		Profiling::BeginTimer();
		$sql = "SELECT CASE WHEN EXISTS (SELECT * FROM " . self::QuoteTable($table) . " WHERE " . self::QuoteColumn($field) . " = ?) THEN 1 ELSE 0 END";
		$ret = $this->fetchScalarInt($sql, [$value]);
		Profiling::EndTimer();
		return $ret === 1;
	}

	public function tableExists(string $table) : bool
	{
		Profiling::BeginTimer();
		$query = "SELECT 1 FROM information_schema.tables
			WHERE table_schema = ? AND table_name = ? LIMIT 1";
		$ret = $this->fetchScalarIntNullable($query, [Context::Settings()->Db()->Name, $table]);
		Profiling::EndTimer();
		return $ret !== null;
	}

	public function renameTable(string $tableSource, string $tableTarget) : void
	{
		Profiling::BeginTimer();
		$this->ensureBegin();
		$sql = "RENAME TABLE " . self::QuoteTable($tableSource) . " TO " . self::QuoteTable($tableTarget);
		$this->execDDL($sql);
		Profiling::EndTimer();
	}

	public function execDDL(string $sql, array $params = [])
	{
		// Los cambios de estructura finalizan la transacción activa
		$wasInTransaction = $this->isInTransaction;
		// Cierra si había una
		if ($wasInTransaction)
			$this->commit();

		$ret = $this->executeQuery($sql, $params);

		if ($wasInTransaction)
		{
			$this->commit();
			// Reabre
			$this->ensureBegin();
		}
		return $ret;
	}

	public function GetTableSize(string $table)
	{
		try
		{
			Profiling::BeginTimer();

			$sql = "SELECT
				data_length `data`,
				index_length `index`,
				data_length + index_length `total`,
				table_rows `rows`
				FROM information_schema.tables
				WHERE table_schema = ?
				AND table_name = ?";

			return $this->fetchAssoc($sql,
				[Context::Settings()->Db()->Name, $table]);
		}
		catch(\Exception $e)
		{
			Log::HandleSilentException($e);
			return '-';
		}
		finally
		{
			Profiling::EndTimer();
		}
	}

	public function setFetchMode(int $mode) : void
	{
		$this->db->setFetchMode($mode);
	}

	public function lastRowsAffected() : int
	{
		return $this->lastRows;
	}

	public function exec(string $query, array $params = []) : int
	{
		return $this->executeQuery($query, $params);
	}

	public function executeQuery(string $query, array $params = []) : int
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$this->ensureBegin();
		$this->lastRows = $this->db->executeQuery($query, $params)->rowCount();
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $this->lastRows;
	}

	public function execRead(string $query, array $params = []) : int
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$this->ensureBegin();
		$ret = $this->db->executeQuery($query, $params)->rowCount();
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	private function fixBoolParams(array $data) : array
	{
		foreach($data as $k => $v)
		{
			if(is_bool($v))
				$data[$k] = (int)$v;
		}
		return $data;
	}
}

