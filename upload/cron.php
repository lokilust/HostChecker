<?php
	require_once 'config.php';

	$query  = "SELECT h.id, h.host, h.port, h.user_id, h.hostname FROM hosts h";
	$result = $GLOBALS['db']->getConnection()->query( $query );

	if ( !$result )
	{
		echo "Error fetching hosts\n";
		exit;
	}

	while ( $row = $result->fetch_assoc() )
	{
		try
		{
			if ( !Check::checkServer( $row['host'], $row['port'] ) )
			{
				Main::sendEmail( $row['user_id'], $row['hostname'] );
				echo "Alert sent for: " . htmlspecialchars( $row['hostname'] ) . "\n";
			}
		}
		catch ( Exception $e )
		{
			echo "Error checking host\n";
		}
	}
