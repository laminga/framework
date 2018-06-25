<?php

namespace minga\framework;


class Db
{
	public $Host = 'localhost';
	public $Name;
	public $User;
	public $NoDb;
	public $Password;
	public $Charset = 'utf8';
	public $TablePrefix;

	private static $db = null;

	public function __construct()
	{
		// Inicia Base de datos
		$this->NoDb = Context::Settings()->Db()->NoDb;
		$this->Host = Context::Settings()->Db()->Host;
		$this->Name = Context::Settings()->Db()->Name;
		$this->User = Context::Settings()->Db()->User;
		$this->Password = Context::Settings()->Db()->Password;
		$this->Charset = 'utf8';
		$this->TablePrefix = Context::Settings()->Db()->Schema . "_";
		$this->Connection();
	}

	private function Connection()
	{
		if (self::$db == null)
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();

			$db = new \PDO('mysql:host='.$this->Host.
				';dbname='.$this->Name.';charset='.$this->Charset,
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

	/**
	 * Devuelve el nombre de la tabla con el
	 * prefijo agregado
	 */
	public function getRealName($tableName)
	{
		return $this->TablePrefix . $tableName;
	}

	public function execute($query, array $data = array())
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$ret = $this->doExecute($query, $data);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	function doExecute($query, array $data = array())
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
	public function fetchAll($query, array $params = array(), $fetch_style = \PDO::FETCH_ASSOC)
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$query = $this->parseArrayParams($query, $params);
		$stmt = $this->Connection()->prepare($query);
		$stmt->execute($params);
		$ret = $stmt->fetchAll($fetch_style);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}


	public function fetchScalarInt($sql, array $params = array())
	{
		return intval($this->fetchScalar($sql, $params));
	}

	public function fetchScalar($sql, array $params = array())
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$ret = $this->fetchAssoc($sql, $params);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret[array_keys($ret)[0]];
	}

	private function fetch($query, array $params = array(), $fetch_style = \PDO::FETCH_ASSOC)
	{
		$query = $this->parseArrayParams($query, $params);
		$stmt = $this->Connection()->prepare($query);
		$stmt->execute($params);
		return $stmt->fetch($fetch_style);
	}

	public function fetchAllColumn($query, array $params = array())
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$data = $this->fetchAll($query, $params);
		for($i=0;$i<count($data);$i++)
		{
			$data[$i] = reset($data[$i]);
		}
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
	 * @return mixed
	 */
	public function fetchColumn($query, array $params = array(), $colnum = 0)
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$query = $this->parseArrayParams($query, $params);
		$stmt = $this->Connection()->prepare($query);
		$stmt->execute($params);
		$ret = $stmt->fetchColumn($colnum);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
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
		Profiling::BeginTimer();
		Performance::BeginDbWait();

		// column names are specified as array keys
		$cols = array();
		$placeholders = array();

		foreach ($data as $columnName => $_)
		{
			$cols[] = $columnName;
			$placeholders[] = '?';
		}
		$query = 'INSERT INTO ' . $tableName
			. ' (' . implode(', ', $cols) . ')'
			. ' VALUES (' . implode(', ', $placeholders) . ')';

		$ret = $this->doExecute($query, array_values($data));
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
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
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$criteria = array();
		foreach (array_keys($identifier) as $columnName)
		{
			$criteria[] = $columnName . ' = ?';
		}

		$query = 'DELETE FROM ' . $tableName . ' WHERE ' . implode(' AND ', $criteria);
		$ret =$this->doExecute($query, array_values($identifier));
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	public function deleteSet($tableName, $columnName, $values)
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$criteria = array_fill(0, count($values), '?');
		$query = 'DELETE FROM ' . $tableName . ' WHERE ' . $columnName . ' IN ( ' . implode(',', $criteria) . ')';
		$ret =$this->doExecute($query, $values);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * Prepares and executes an SQL query and returns the first row of the result
	 * as an associative array.
	 *
	 * @param string $statement The SQL query.
	 * @param array $params The query parameters.
	 * @return array|false
	 */
	public function fetchAssoc($statement, array $params = array())
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$ret = $this->fetch($statement, $params, \PDO::FETCH_ASSOC);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/**
	 * Parse array parameters as a string list, quoting the values based on type.
	 * Non array parameters are returned as-is.
	 *
	 * @param array $params The parameters.
	 * @return array
	 */
	public function parseArrayParams($query, array &$params)
	{
		foreach($params as $k => $v)
		{
			if(is_array($v))
			{
				$query = str_replace(':'.$k, $this->arrayToList($v), $query);
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
	public function arrayToList(array $arr)
	{
		$ret = '';
		foreach($arr as $v)
		{
			$ret .= $this->Connection()->quote($v, $this->getParamType($v)).', ';
		}
		return trim($ret, ', ');
	}

	/**
	 * Get the PDO param constant based on variable type.
	 *
	 * @param mixed $var
	 * @return integer PDO::PARAM constant.
	 */
	public function getParamType($var)
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
	public function fetchArray($statement, array $params = array())
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
	public function truncate($tableName)
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$query = 'TRUNCATE TABLE ' . $tableName;
		$this->doExecute($query);
		Performance::EndDbWait();
		Profiling::EndTimer();
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
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$set = array();
		foreach ($data as $columnName => $_)
		{
			$set[] = $columnName . ' = ?';
		}
		$params = array_merge(array_values($data), array_values($identifier));

		$sql  = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $set)
			. ' WHERE ' . implode(' = ? AND ', array_keys($identifier))
			. ' = ?';

		$ret = $this->doExecute($sql, $params);
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/*
	 * Initiates a transaction.
	 */
	public function beginTransaction()
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$ret = $this->Connection()->beginTransaction();
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/*
	 * Commits a transaction.
	 */
	public function commit()
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$ret = $this->Connection()->commit();
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}

	/*
	 * Rolls back a transaction.
	 */
	public function rollBack()
	{
		Profiling::BeginTimer();
		Performance::BeginDbWait();
		$ret = $this->Connection()->rollBack();
		Performance::EndDbWait();
		Profiling::EndTimer();
		return $ret;
	}
}
