<?php

namespace minga\framework;

class Db
{
	public $Host = 'localhost';
	public $Name;
	public $User;
	public $NoDb;
	public $Password;
	public $Port = 3306;
	public $Charset = 'utf8';

	private static $db = null;

	public function __construct()
	{
		// Inicia Base de datos
		$this->NoDb = Context::Settings()->Db()->NoDb;
		$this->Host = Context::Settings()->Db()->Host;
		$this->Name = Context::Settings()->Db()->Name;
		$this->User = Context::Settings()->Db()->User;
		$this->Port = Context::Settings()->Db()->Port;
		$this->Password = Context::Settings()->Db()->Password;
		$this->Charset = 'utf8';
		$this->Connection();
	}

	private function Connection()
	{
		if (self::$db == null)
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();

			$db = new \PDO('mysql:host=' . $this->Host
				. ';port=' . $this->Port
				. ';dbname=' . $this->Name
				. ';charset=' . $this->Charset,
				$this->User,
				$this->Password);
			$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

			Performance::EndDbWait();
			Profiling::EndTimer();
			self::$db = $db;
		}
		return self::$db;
	}


	public function execute($query, array $data = [])
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

	private function doExecuteNamedParams($query, array $data = [])
	{
		$stmt = $this->Connection()->prepare($query);
		foreach($data as $k => $v)
			$stmt->bindValue($k, $v, $this->getParamType($v));
		$stmt->execute();
		return $stmt->rowCount();
	}

	private function doExecute($query, array $data = [])
	{
		$stmt = $this->Connection()->prepare($query);
		$stmt->execute($data);
		return $stmt->rowCount();
	}

	/**
	 * Prepares and executes an SQL query and returns the result as an associative array.
	 *
	 * @param string $query The SQL query.
	 * @param array $params The query parameters.
	 * @paran int $fetch_style Fetch style PDO Constant
	 * @return array
	 */
	public function fetchAll($query, array $params = [], $fetch_style = \PDO::FETCH_ASSOC)
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			$query = $this->parseArrayParams($query, $params);
			$stmt = $this->Connection()->prepare($query);
			if(key($params) === 0)
				$stmt->execute($params);
			else
			{
				foreach($params as $k => $v)
					$stmt->bindValue($k, $v, $this->getParamType($v));
				$stmt->execute();
			}
			return $stmt->fetchAll($fetch_style);
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}


	public function fetchScalarInt($sql, array $params = [])
	{
		return (int)$this->fetchScalar($sql, $params);
	}

	public function fetchScalar($sql, array $params = [])
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			$ret = $this->fetchAssoc($sql, $params);
			if($ret === false)
				return null;
			return $ret[array_keys($ret)[0]];
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	private function fetch($query, array $params = [], $fetch_style = \PDO::FETCH_ASSOC)
	{
		$query = $this->parseArrayParams($query, $params);
		$stmt = $this->Connection()->prepare($query);
		if(key($params) === 0)
			$stmt->execute($params);
		else
		{
			foreach($params as $k => $v)
				$stmt->bindValue($k, $v, $this->getParamType($v));
			$stmt->execute();
		}
		return $stmt->fetch($fetch_style);
	}

	public function fetchAllColumn($query, array $params = [])
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			$data = $this->fetchAll($query, $params);
			for($i = 0; $i < count($data); $i++)
				$data[$i] = reset($data[$i]);

			return $data;
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	/**
	 * Prepares and executes an SQL query and returns the value of a single column
	 * of the first row of the result.
	 *
	 * @param string $query sql query to be executed
	 * @param array $params prepared statement params
	 * @param int $colnum 0-indexed column number to retrieve
	 * @return mixed
	 */
	public function fetchColumn($query, array $params = [], $colnum = 0)
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			$query = $this->parseArrayParams($query, $params);
			$stmt = $this->Connection()->prepare($query);
			if(key($params) === 0)
				$stmt->execute($params);
			else
			{
				foreach($params as $k => $v)
					$stmt->bindValue($k, $v, $this->getParamType($v));
				$stmt->execute();
			}
			$stmt->execute($params);
			return $stmt->fetchColumn($colnum);
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	/**
	 * Inserts or Replaces a table row with specified data.
	 */
	private function insertOrReplace($tableName, array $data, $command)
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();

			$query = $command . ' INTO ' . self::QuoteTable($tableName)
				. ' (' . implode(', ', self::QuoteColumn(array_keys($data))) . ')'
				. ' VALUES (' . rtrim(str_repeat('?,', count($data)),',') . ')';

			return $this->doExecute($query, array_values($data));
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	/**
	 * Inserts a table row with specified data.
	 *
	 * @param string $tableName The name of the table to insert data into.
	 * @param array $data An associative array containing column-value pairs.
	 * @return integer The number of affected rows.
	 */
	public function insert($tableName, array $data)
	{
		return $this->InsertOrReplace($tableName, $data, 'INSERT');
	}

	/**
	 * Replaces a table row with specified data.
	 *
	 * @param string $tableName The name of the table to replace data into.
	 * @param array $data An associative array containing column-value pairs.
	 * @return integer The number of affected rows.
	 */
	public function replace($tableName, array $data)
	{
		return $this->insertOrReplace($tableName, $data, 'REPLACE');
	}

	/**
	 * Executes an SQL DELETE statement on a table.
	 *
	 * @param string $tableName The name of the table on which to delete.
	 * @param array $identifier The deletion criteria. An associative array containing column-value pairs.
	 * @return integer The number of affected rows.
	 */
	public function delete($tableName, array $identifier)
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			$criteria = [];
			foreach ($identifier as $columnName => $_)
				$criteria[] = self::QuoteColumn($columnName) . ' = :' . $columnName;

			$query = 'DELETE FROM ' . self::QuoteTable($tableName) . ' WHERE ' . implode(' AND ', $criteria);
			return $this->doExecuteNamedParams($query, $identifier);
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	public function deleteSet($tableName, $columnName, $values)
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			$criteria = array_fill(0, count($values), '?');
			$query = 'DELETE FROM ' . self::QuoteTable($tableName) . ' WHERE ' . self::QuoteColumn($columnName) . ' IN ( ' . implode(',', $criteria) . ')';
			return $this->doExecute($query, $values);
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	/**
	 * Prepares and executes an SQL query and returns the first row of the result
	 * as an associative array.
	 *
	 * @param string $statement The SQL query.
	 * @param array $params The query parameters.
	 * @return array|false
	 */
	public function fetchAssoc($statement, array $params = [])
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			return $this->fetch($statement, $params, \PDO::FETCH_ASSOC);
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	/**
	 * Parse array parameters as a string list, quoting the values based on type.
	 * Non array parameters are returned as-is.
	 *
	 * @param array $params The parameters.
	 * @return array
	 */
	private function parseArrayParams($query, array &$params)
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
	 * @return string
	 */
	private function arrayToList(array $arr)
	{
		$ret = '';
		foreach($arr as $v)
		{
			if($this->getParamType($v) == \PDO::PARAM_INT)
				$ret .= (int)$v . ', ';
			elseif($this->getParamType($v) == \PDO::PARAM_BOOL)
			{
				if($v)
					$ret .= '1, ';
				else
					$ret .= '0, ';
			}
			else
				$ret .= $this->Connection()->quote($v, $this->getParamType($v)) . ', ';
		}

		return rtrim($ret, ', ');
	}

	/**
	 * Get the PDO param constant based on variable type.
	 *
	 * @param mixed $var
	 * @return integer PDO::PARAM constant.
	 */
	private function getParamType($var)
	{
		if($var === null)
			return \PDO::PARAM_NULL;
		elseif(is_bool($var))
			return \PDO::PARAM_BOOL;
		elseif(is_int($var))
			return \PDO::PARAM_INT;
		else
			return \PDO::PARAM_STR;
	}

	/**
	 * Prepares and executes an SQL query and returns the first row of the result
	 * as a numerically indexed array.
	 *
	 * @param string $statement         sql query to be executed
	 * @param array $params             prepared statement params
	 * @return array
	 */
	public function fetchArray($statement, array $params = [])
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			return $this->fetch($statement, $params, \PDO::FETCH_NUM);
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}


	/**
	 * Truncate table.
	 *
	 * @param string $tableName The name of the table to truncate.
	 */
	public function truncate($tableName)
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			$query = 'TRUNCATE TABLE ' . self::QuoteTable($tableName);
			$this->doExecute($query);
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	/**
	 * Executes an SQL UPDATE statement on a table.
	 *
	 * @param string $tableName The name of the table to update.
	 * @param array $data
	 * @param array $identifier The update criteria. An associative array containing column-value pairs.
	 * @return integer The number of affected rows.
	 */
	public function update($tableName, array $data, array $identifier)
	{
		try
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
				$dataNew[$name] = $value;
			}

			$identifierNew = [];
			$where = [];
			foreach ($identifier as $columnName => $value)
			{
				$name = $columnName . $i++;
				$where[] = self::QuoteColumn($columnName) . ' = :' . $name;
				$identifierNew[$name] = $value;
			}

			$params = array_merge($dataNew, $identifierNew);

			$sql  = 'UPDATE ' . self::QuoteTable($tableName) . ' SET ' . implode(', ', $set)
				. ' WHERE ' . implode(' AND ', $where);

			return $this->doExecuteNamedParams($sql, $params);
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	/*
	 * Initiates a transaction.
	 */
	public function beginTransaction()
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			return $this->Connection()->beginTransaction();
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	/*
	 * Commits a transaction.
	 */
	public function commit()
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			return $this->Connection()->commit();
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	/*
	 * Rolls back a transaction.
	 */
	public function rollBack()
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			return $this->Connection()->rollBack();
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	public static function QueryToString($sql, $params)
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

	public static function QuoteTable($name)
	{
		return self::QuoteColumn($name);
	}

}
