<?php

namespace MFunc
{
	use \PDO;
	use \Exception;

	require_once __DIR__ . "/Configurations.php";

	class Database
	{
		private $dsn;
		private $user;
		private $password;
		private $dbObject;
		private $lastRowCount;

		private static $instance;
		public static $displayErrors = false;
		public static $fetchMode = PDO::FETCH_OBJ;


		private function __construct()
		{
			global $Configurations;

			try
			{
				$this->dsn = $Configurations->dbConnectionString;
				$this->user = $Configurations->dbUser;
				$this->password = $Configurations->dbPassword;

				$this->dbObject = new PDO($this->dsn, $this->user, $this->password);
				$this->dbObject->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, self::$fetchMode);
				$this->dbObject->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			}
			catch(Exception $err)
			{
				if(self::$displayErrors)
				{
					echo "<div style='color: green'>";
					echo "Database creation error: ";
					echo "<span style='color: red'>" . $err . "</span>";
					echo "</div>";
				}

                http_response_code(500);
				throw new Exception("Error in creating database object");
			}
		}

		//
		// Get instance of the Database class (singleton)
		//
		// @return Database A singleton Database object
		public static function getInstance() : Database
		{
			Database::$instance ??= new Database();

			return Database::$instance;
		}

		/**
		 *	Execute sql query.
		 *	Set `Database::$displayErrors` to true to display colored errors
		 *	@param string $sql valid sql query
		 *	@param array $paramsAssoc associative array, consists of parameter name as key and its value
		 *	@return array|object|null object[s] or no return, depends on the query. May returns void
		 *	@author Mohammed Abdulsalam
		*/
		public function executeSQL($sql, $paramsAssoc = null) : array|\stdClass|null
		{
			$result = null;

			try
			{
				$conn = $this->dbObject;
				$stmt = $conn->prepare($sql);

				
				$paramsAssoc == null
					? $stmt->execute()
					: $stmt->execute($paramsAssoc);

				$result = $stmt->fetchAll(self::$fetchMode);
				$result = !count($result)
					? null
					: $result;

				// Store rows affected count
				$this->lastRowCount = $stmt->rowCount();
			}
			catch(Exception $err)
			{
				if(self::$displayErrors)
				{
					echo "<div style='color: green'>";
					echo "SQL execution error: ";
					echo "<span style='color: red'>" . $err . "</span>";
					echo "</div>";
				}

				throw new Exception("Error in sql query");
			}

			return $result;
		}

		/**
		 * Get count of rows affected by last transaction
		 *
		 * @return int | null Number of rows or null if no pre-transaction
		 *  */
		public function getLastRowCount() : int | null
		{
			return $this->lastRowCount;
		}

		/**
		 * Generate UUID
		 * This Method fetch UUID from DBMS with `SELECT` statement
		 *
		 * @return string UUID
		 * */
		public function genUUID() : string
		{
			$sql = "SELECT UUID() as uuid";

			$UUID = $this->executeSQL($sql)[0]->uuid;

			return $UUID;
		}
	}
}

