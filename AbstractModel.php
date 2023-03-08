<?php

namespace ClinicModels;

require_once PROJ_ROOT . "/includes/HTTP/HTTPException.php";

use \Exception;
use MFunc\HTTPException;

abstract class AbstractModel
{
	public $id;
	protected $database;
	public static array $columns;

	const TableName = "abstract";

	abstract protected function validateCreateFields();
	abstract protected function validateFetchFields();
	abstract protected function validateDeleteFields();
	abstract protected function validateUpdateFields();

	/**
	 * Throws `HTTPException::NotFound` if last operation affects no rows
	 *
	 * @author Mohammed Abdulsalam
	 *  */
	public function validateLastOperation()
	{
		$lastRowCount = $this->database->getLastRowCount();

		$noRowAffected = !boolval($lastRowCount);
		if($noRowAffected)
			throw new HTTPException("Not found", HTTPException::NotFound);
	}

	/**
	 *	Checks if column exists in the table
	 *
	 *	@param string $column Column name
	 *	@return bool
	 *
	 *	@author Mohammed Abdulsalam
	 * */
	public static function columnExists(string $column) : bool
	{
		return array_key_exists($column, static::$columns);
	}

	/**
	 *	Checks if column is special, thus require certain authorize
	 *
	 *	@param string $column Column name
	 *	@return bool
	 *
	 *	@author Mohammed Abdulsalam
	 * */
	public static function columnSpecial(string $column) : bool
	{
		return static::$columns[$column]["special"];
	}

	/**
	 *	Checks if column is self initialized
	 *
	 *	@param string $column Column name
	 *	@return bool
	 *
	 *	@author Mohammed Abdulsalam
	 * */
	public static function columnSelfInit(string $column) : bool
	{
		return static::$columns[$column]["selfInit"];
	}

	/**
	 * Prepare changes and parameters passed to sql query before create
	 *
	 * @param mixed &$changes String to store changing columns names and parameter name: "col1 = :col1, col2 = :col2"
	 * @param mixed &$params Array to store parameter names corresponding to those in `$changes` and their values:
	 *	[
	 *		"col1" => val1,
	 *		"col2" => vla2
	 *	]
	 * @author Mohammed Abdulsalam
	 * */
	public function initCreateColumns(mixed &$changes, mixed &$params)
	{
		$params  = [];
		$changes = "";

		foreach(static::$columns as $col => $colInfo)
		{
			$columnSelfInit  = self::columnSelfInit($col);
			if($columnSelfInit)
				continue;

			$changes        .= "{$colInfo["name"]} = :$col, ";

			$params[$col]    = $this->{$col};
		}

		$changes = trim($changes, ", ");
	}

	/**
	 * Prepare columns names based on desired fields
	 * If `$fields === null`, it assign $columns with `"*"`, which indicates all columns
	 *
	 * @param mixed $fields Desired fields
	 * @param mixed &$columns String to store columns name
	 * @author Mohammed Abdulsalam
	 * */
	public static function initFetchColumns(mixed $fields, mixed &$columns)
	{
		$columns = "*";

		if(!is_null($fields))
		{
			$columns = "";
			$fieldsCount = count($fields);

			// Add fields that exists in the table to $columns
			for($i = 0; $i < $fieldsCount; $i++)
			{
				$field = $fields[$i];

				if(!self::columnExists($field))
					continue;

				$columns .= static::$columns[$field]["name"] . ", ";
			}

			// Remove trailing comma, or reset $columns if it is empty string
			$columns = $columns === "" ?
				"*" :
				trim($columns, ", ");
		}
	}

	/**
	 * Indicates wether `$str` can cause damage to database
	 *
	 * @param string $str String to test against
	 * @author Mohammed Abdulsalam
	 *  */
	public static function strDanger(string $str)
	{
		$danger = preg_match("/^update$|^delete$|^drop$|^create$|^truncate$|^alter$|^grant$|^1=1$/i", $str);

		return boolval($danger);
	}

	/**
	 * Prepare `$sqlOptions` based on `$options` provided
	 *
	 * @param array $options Array of strings. Those strings can be any filtering or sorting sql command
	 * @param mixed &$sqlOptions String to store sql conformed options
	 * @author Mohammed Abdulsalam
	 * */
	public static function initSqlOptions(array $options, mixed &$sqlOptions)
	{
		$sqlOptions = "";
		$optionsCount = count($options);

		if($optionsCount != 0)
		{
			$sqlOptions = "WHERE";

			for($i = 0; $i < $optionsCount; $i++)
			{
				$sqlOptions .= " {$options[$i]} ";

				if(self::strDanger($options[$i]))
					throw new HTTPException("Fortified you bitch", HTTPException::Forbidden);

				$lastIndex = $optionsCount - 1;
				if($i != $lastIndex)
					$sqlOptions .= "AND";
			}
		}
	}

	/**
	 * Prepare changes and parameters passed to sql query before update
	 *
	 * @param array $column_val Associative array contains column name and its new value
	 * @param mixed &$changes String to store changing columns names and parameter name: "col1 = :col1, col2 = :col2"
	 * @param mixed &$params Array to store parameter names corresponding to those in `$changes` and their values:
	 *	[
	 *		"col1" => val1,
	 *		"col2" => vla2
	 *	]
	 * @author Mohammed Abdulsalam
	 * */
	public static function initUpdateParams(array $column_val, mixed &$changes, mixed &$params)
	{
		$params  = [];
		$changes = "";

		foreach($column_val as $col => $val)
		{
			if(is_null($val))
				throw new HTTPException("Values cannot be nulls", HTTPException::BadRequest);

			$columnNotExists = !self::columnExists($col);
			if($columnNotExists)
				continue;

			$columnSpecial   = self::columnSpecial($col);
			if($columnSpecial)
				continue;

			$changes      .= static::$columns[$col]["name"] . " = " . ":$col, ";

			$params[$col]  = $val;
		}

		$changes      = trim($changes, ", ");
	}

	/**
	 *	Create new row
	 *	This process requires create fields are in the right form. If not, `HTTPException` is thrown with BadRequest
	 *
	 *  @author Mohammed Abdulsalam
	 * */
	public function create()
	{
		$this->validateCreateFields();

		$this->initCreateColumns($changes, $params);

		$sql = "INSERT INTO " . static::TableName . " SET $changes";

		try
		{
			$this->database->executeSQL($sql, $params);
		}
		catch(Exception $ex)
		{
			throw new HTTPException("Failed to add", HTTPException::InternalError);
		}
	}

	/**
	 * Fetch single row
	 *
	 * @param array $fields Fields to fetch only. If equals null, all fields will be fetched
	 * @return array | null The fetched row
	 * @author Mohammed Abdulsalam
	 * */
	public function fetchSingle(array $fields = null) : array | null
	{
		$this->validateFetchFields();

		self::initFetchColumns($fields, $columns);

		$sql = "SELECT $columns FROM " . static::TableName . " WHERE id = :id";

		$params = [
			"id" => $this->id
		];

		try
		{
			$result = $this->database->executeSQL($sql, $params);

			$this->validateLastOperation();

			return $result;
		}
		catch(HTTPException $ex)
		{
			throw new HTTPException($ex->getMessage(), $ex->getStatusCode());
		}
		catch(Exception $ex)
		{
			throw new HTTPException("Failed to fetch", HTTPException::InternalError);
		}
	}

	/**
	 * Fetch all rows
	 *
	 * @param array $fields Fields to fetch only. If equals null, all fields will be fetched
	 * @param string $limit Max limit of number of rows
	 * @param string $offset Offset to begin fetching. Usually used in pagination
	 * @param array $sqlOptions Conditions to filter & sort the result
	 * @return array | null The fetched rows
	 * @author Mohammed Abdulsalam
	 * */
	public function fetchAll(
		array $fields  = null,
		string $limit  = null,
		string $offset = null,
		string $order  = null,
		array $options = []
	) : array | null
	{
		self::initFetchColumns($fields, $columns);

		$offset = is_null($offset) ?
			"" :
			"OFFSET $offset";
		$limit = is_null($limit) ?
			"" :
			"LIMIT $limit";

		if(self::strDanger($offset) || self::strDanger($limit))
			throw new HTTPException("Fortified you bitch", HTTPException::Forbidden);


		self::initSqlOptions($options, $sqlOptions);

		$sql = "SELECT $columns FROM " . static::TableName . " $sqlOptions $order $limit $offset";

		try
		{
			$result = $this->database->executeSQL($sql);

			// Fetch total count;
			$sql    = "SELECT COUNT(*) as total FROM " . static::TableName . " $sqlOptions";
			$count  = $this->database->executeSQL($sql);

			return [
				"data"  => $result,
				"total" => $count[0]->total
			];
		}
		catch(Exception $ex)
		{
			throw new HTTPException("Failed to fetch", HTTPException::InternalError);
		}
	}

	/**
	 * Delete row from the table of the model
	 *
	 * @author Mohammed Abdulsalam
	 * */
	public function delete()
	{
		$this->validateDeleteFields();

		$sql = "DELETE FROM " . static::TableName . " WHERE id = :id";

		$param = [
			"id" => $this->id
		];

		try
		{
			$this->database->executeSQL($sql, $param);

			$this->validateLastOperation();
		}
		catch(HTTPException $ex)
		{
			throw new HTTPException($ex->getMessage(), $ex->getStatusCode());
		}
		catch(Exception $ex)
		{
			throw new HTTPException("Failed to delete", HTTPException::InternalError);
		}
	}

	/**
	 * Update information
	 * Throws `HTTPException` 400 if one of the values is null
	 * Sends 404 if no rows affected, that is if row not found or row found but nothing changed
	 *
	 * @param array $column_val Associative array contains column name and its new value
	 * @author Mohammed Abdulsalam
	 * */
	public function update(array $column_val)
	{
		$this->validateUpdateFields();

		self::initUpdateParams($column_val, $changes, $params);

		if($changes === "")
			throw new Exception("No valid fields provided", HTTPException::BadRequest);

		$sql          = "UPDATE " . static::TableName . " SET $changes WHERE id = :id";
		$params["id"] = $this->id;

		try
		{
			$this->database->executeSQL($sql, $params);
		}
		catch(HTTPException $ex)
		{
			throw new HTTPException($ex->getMessage(), $ex->getStatusCode());
		}
		catch(Exception $ex)
		{
			throw new Exception("Failed to update", HTTPException::InternalError);
		}
	}
}

