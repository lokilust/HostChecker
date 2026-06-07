<?php

	class Database
	{
		private $_localhost;
		private $_database;
		private $_dbuser;
		private $_dbpass;
		private $_connection;

		public function __construct( $host = "localhost", $db = "database", $user = "user", $pass = "pass" )
		{
			$this->_localhost = $host;
			$this->_database  = $db;
			$this->_dbuser    = $user;
			$this->_dbpass    = $pass;
		}

		public function connect()
		{
			$this->_connection = new mysqli( $this->_localhost, $this->_dbuser, $this->_dbpass, $this->_database );
			if ( $this->_connection->connect_error )
			{
				return false;
			}
			$this->_connection->set_charset("utf8mb4");
			return true;
		}

		public function getConnection()
		{
			return $this->_connection;
		}

		public function disconnect()
		{
			if ( $this->_connection )
			{
				$this->_connection->close();
			}
		}

		public function query( $query, $params = array() )
		{
			if ( empty( $params ) )
			{
				return $this->_connection->query( $query );
			}

			$stmt = $this->_connection->prepare( $query );
			if ( !$stmt )
			{
				return false;
			}

			if ( count( $params ) > 0 )
			{
				$types = '';
				foreach ( $params as $param )
				{
					if ( is_int( $param ) )
						$types .= 'i';
					elseif ( is_float( $param ) )
						$types .= 'd';
					else
						$types .= 's';
				}
				$stmt->bind_param( $types, ...$params );
			}

			$stmt->execute();
			return $stmt->get_result();
		}
	}
