<?php

	class Check
	{
		static public function checkServer( $hostname, $port = 80, $timeout = 10 )
		{
			if ( !self::validateHostname( $hostname ) || !self::validatePort( $port ) )
			{
				throw new UnexpectedValueException( "Invalid hostname or port" );
			}

			$errno = 0;
			$errstr = '';
			$connection = @fsockopen( $hostname, (int)$port, $errno, $errstr, $timeout );

			if ( $connection )
			{
				fclose( $connection );
				return true;
			}
			return false;
		}

		private static function validateHostname( $hostname )
		{
			$ipPattern = '/^(\d{1,3}\.){3}\d{1,3}$/';
			$domainPattern = '/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/';
			return preg_match( $ipPattern, $hostname ) || preg_match( $domainPattern, $hostname );
		}

		private static function validatePort( $port )
		{
			$port = (int)$port;
			return $port >= 1 && $port <= 65535;
		}

		public static function ping( $host, $port, $count = 4 )
		{
			if ( !self::validateHostname( $host ) || !self::validatePort( $port ) )
			{
				return array( "Invalid host or port" );
			}

			$ping_exec_result = self::ping_exec( $host, $count );

			if ( !empty( $ping_exec_result ) )
			{
				return $ping_exec_result;
			}
			else
			{
				$array_result = array();
				for ( $i = 0; $i < $count; $i++ )
				{
					$result = self::ping_socket( $host, $port );
					if ( $result !== false )
					{
						$array_result[] = $result;
					}
				}
				return $array_result;
			}
		}

		private static function ping_exec( $host, $count )
		{
			$host  = preg_replace( "/[^A-Za-z0-9.\-]/", "", $host );
			$count = (int)$count;

			if ( $count < 1 || $count > 10 )
			{
				return array();
			}

			$host_escaped = escapeshellarg( $host );
			$return_array = array();
			exec( "ping -c " . (int)$count . " " . $host_escaped, $return_array );

			return $return_array;
		}

		private static function ping_socket( $host, $port = 80 )
		{
			$timeA = microtime( true );
			$errno = 0;
			$errstr = '';
			$con = @fsockopen( $host, (int)$port, $errno, $errstr, 5 );
			$timeB = microtime( true );

			if ( $con )
			{
				fclose( $con );
				return "Reply from " . htmlspecialchars( $host ) . ": bytes=32 time=" . round( ( ( $timeB - $timeA ) * 1000 ), 0 ) . "ms TTL=NULL";
			}
			return false;
		}

		public function traceRoute( $hostname )
		{
			if ( !self::validateHostname( $hostname ) )
			{
				throw new UnexpectedValueException( "Invalid hostname" );
			}

			$output = array();
			$hostname_escaped = escapeshellarg( $hostname );
			exec( "traceroute " . $hostname_escaped, $output );

			return $output;
		}

		public function dnsLookup( $hostname, $type )
		{
			if ( !self::validateHostname( $hostname ) )
			{
				throw new UnexpectedValueException( "Invalid hostname" );
			}

			$output = array();
			$hostname_escaped = escapeshellarg( $hostname );
			$type = strtoupper( trim( $type ) );

			$validTypes = array( "A", "AAAA", "CNAME", "MX", "NS", "TXT" );
			if ( !in_array( $type, $validTypes ) )
			{
				throw new UnexpectedValueException( "Invalid DNS record type" );
			}

			switch ( $type )
			{
				case "A":
					exec( "host -t A " . $hostname_escaped . " | awk '{print $4}'", $output );
					break;
				case "AAAA":
					exec( "host -t AAAA " . $hostname_escaped . " | awk '{print $5}'", $output );
					break;
				case "CNAME":
					exec( "host -t CNAME " . $hostname_escaped . " | awk '{print $6}'", $output );
					break;
				case "MX":
					exec( "host -t MX " . $hostname_escaped . " | awk '{print $7}'", $output );
					break;
				case "NS":
					exec( "host -t NS " . $hostname_escaped . " | awk '{print $4}'", $output );
					break;
				case "TXT":
					exec( "host -t TXT " . $hostname_escaped . " | awk '{print $4 $5}'", $output );
					break;
			}

			sort( $output );
			return $output;
		}

		public function whoIs( $domain )
		{
			if ( !self::validateHostname( $domain ) )
			{
				throw new UnexpectedValueException( "Invalid domain" );
			}

			$output = array();
			$domain_escaped = escapeshellarg( $domain );
			exec( "jwhois " . $domain_escaped, $output );

			return $output;
		}
	}
