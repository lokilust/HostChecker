<?php
	session_start();
	ob_start();

	$scriptName = "HostChecker";
	define( 'LIB_PATH', "lib/" );

	define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
	define('DB_NAME', getenv('DB_NAME') ?: 'hostchecker');
	define('DB_USER', getenv('DB_USER') ?: 'user');
	define('DB_PASS', getenv('DB_PASS') ?: '');

	function class_autoload( $class )
	{
		require_once LIB_PATH . $class . '.php';
	}

	spl_autoload_register( 'class_autoload' );

	$db = new Database( DB_HOST, DB_NAME, DB_USER, DB_PASS );
	$db->connect() or die( "Database could not connect" );

	$myUser = new Users();
	$myPing = new Check();
