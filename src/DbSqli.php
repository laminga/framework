<?php

namespace minga\framework;

class DbSqli
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
			$db = new \mysqli($this->Host, $this->User, $this->Password, $this->Name, $this->Port);

/*			$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			$db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
			*/
			Performance::EndDbWait();
			Profiling::EndTimer();
			self::$db = $db;
		}
		return self::$db;
	}


	/**
	 * Prepares and executes an SQL query and returns the result as an associative array.
	 *
	 * @param string $query The SQL query.
	 * @param array $params The query parameters.
	 * @paran int $fetchStyle Fetch style PDO Constant
	 * @return array
	 */
	public function fetchAll($query, array $params = [], $fetchStyle = MYSQLI_ASSOC)
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			$query = $this->parseArrayParams($query, $params);
			if(key($params) !== 0)
			{
				$query = $this->QueryToString($query, $params);
			}
			$result = self::$db->query($query);
			$ret = $result->fetch_all($fetchStyle);
      $result->free();
      return $ret;
		}
		finally
		{
			Performance::EndDbWait();
			Profiling::EndTimer();
		}
	}

	/**
	 * Prepares and executes an SQL query and returns the result as an associative array.
	 *
	 * @param string $query The SQL query.
	 * @param array $params The query parameters.
	 * @paran int $fetch_style Fetch style PDO Constant
	 * @return array
	 */
	public function fetchAllMultipleResults($query, array $params = [], $fetchStyle = MYSQLI_ASSOC)
	{
		try
		{
			Profiling::BeginTimer();
			Performance::BeginDbWait();
			$query = $this->parseArrayParams($query, $params);
			if(key($params) !== 0)
			{
				$query = $this->QueryToString($query, $params);
			}
			$res = self::$db->multi_query($query);
			$ret = [];
			do
			{
        if ($result = self::$db->store_result())
				{
						$ret[] = $result->fetch_all($fetchStyle);
            $result->free();
        }
			}
			while (self::$db->more_results() && self::$db->next_result());

			return $ret;
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

	public static function QueryToString($sql, $params)
	{
		$ret = $sql;
		foreach($params as $k => $v)
		{
			$quote = "'";
			if(is_int($v))
				$quote = '';
			else
				$v = mysqli_real_escape_string(self::$db, $v);
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
