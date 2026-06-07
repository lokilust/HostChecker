<?php

	class Main
	{
		static public function pageTitle( $name )
		{
			$page = isset( $_SERVER['PHP_SELF'] ) ? $_SERVER['PHP_SELF'] : '';
			$string = strrchr( $page, '/' );

			if ( $string === false )
			{
				return $name;
			}

			$page = substr( $string, 1 );

			switch ( $page )
			{
				case "index.php":
					return $name . " - Homepage";
					break;
				case "ping.php":
					return $name . " - Ping";
					break;
				case "host.php":
					return $name . " - Add Host";
					break;
				case "settings.php":
					return $name . " - Member Settings";
					break;
				case "register.php":
					return $name . " - Register";
					break;
				case "login.php":
					return $name . " - Login";
					break;
				default :
					return $name;
					break;
			}
		}

		static public function setMessage( $page, $errString, $status = "" )
		{
			$page = basename( $page );
			$url = $page . '?msg=' . urlencode( $errString ) . '&status=' . urlencode( $status );
			header( 'Location: ' . $url );
			exit;
		}

		static public function handleMessages()
		{
			if ( isset( $_REQUEST['msg'] ) )
			{
				$message = htmlspecialchars( $_REQUEST['msg'], ENT_QUOTES, 'UTF-8' );
				$status = isset( $_REQUEST['status'] ) ? htmlspecialchars( $_REQUEST['status'], ENT_QUOTES, 'UTF-8' ) : '';

				if ( !empty( $status ) )
				{
					return '<div class="alert ' . $status . '">' . $message . '</div>';
				}
				else
				{
					return '<div class="alert">' . $message . '</div>';
				}
			}

			return '';
		}

		static public function sendEmail( $userid, $hostname )
		{
			global $db;

			$query = "SELECT email, name, lastname FROM users WHERE id = ? LIMIT 1";
			$result = $db->query( $query, [(int)$userid] );

			if ( !$result || $result->num_rows === 0 )
			{
				echo "Error: User not found\n";
				return;
			}

			$row = $result->fetch_assoc();

			$email = $row['email'];
			$name = htmlspecialchars( $row['name'], ENT_QUOTES, 'UTF-8' );
			$lastname = htmlspecialchars( $row['lastname'], ENT_QUOTES, 'UTF-8' );
			$hostname = htmlspecialchars( $hostname, ENT_QUOTES, 'UTF-8' );

			if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) )
			{
				echo "Error: Invalid email address\n";
				return;
			}

			$headers = array(
				'MIME-Version: 1.0',
				'Content-type: text/html; charset=UTF-8',
				'From: noreply@hostchecker.local'
			);
			$headers_str = implode( "\r\n", $headers );

			$subject = $hostname . " is Down!";

			$emailcontent = <<<EOF
<html>
  <body>
    <p>Hello $name $lastname,</p>
    <p>The Host name <b>$hostname</b> is DOWN!</p>
    <p>Thank you, and have a good day</p>
  </body>
</html>
EOF;

			if ( mail( $email, $subject, $emailcontent, $headers_str ) )
			{
				echo "Email sent successfully\n";
			}
			else
			{
				echo "Error sending email\n";
			}
		}
	}
