<?php

	class Users
	{
		private static $db;

		public function __construct()
		{
			global $db;
			self::$db = $db;
		}

		public function userRegister( $username, $password, $passrep, $email, $name, $lastname )
		{
			$username = trim( $username );
			$email    = trim( $email );
			$name     = trim( $name );
			$lastname = trim( $lastname );

			if ( $password !== $passrep )
			{
				Main::setMessage( "register.php", "Passwords do not match", "alert-error" );
				return;
			}

			if ( $this->checkUsername( $username ) )
			{
				Main::setMessage( "register.php", "The username already exists", "alert-error" );
				return;
			}

			if ( !$this->checkEmail( $email ) )
			{
				Main::setMessage( "register.php", "Please input a valid email address", "alert-error" );
				return;
			}

			$hashedPassword = password_hash( $password, PASSWORD_BCRYPT, ['cost' => 12] );

			$query = "INSERT INTO users (isadmin, username, password, email, name, lastname) VALUES (0, ?, ?, ?, ?, ?)";
			$result = self::$db->query( $query, [$username, $hashedPassword, $email, $name, $lastname] );

			if ( $result !== false )
			{
				Main::setMessage( "register.php", "You have successfully registered", "alert-success" );
			}
			else
			{
				Main::setMessage( "register.php", "The values could not be inserted", "alert-error" );
			}
		}

		private function checkUsername( $user )
		{
			$query = "SELECT id FROM users WHERE username = ? LIMIT 1";
			$result = self::$db->query( $query, [$user] );

			return $result && $result->num_rows > 0;
		}

		private function checkEmail( $email )
		{
			return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
		}

		public function userLogin( $username, $password )
		{
			$username = trim( $username );

			$query = "SELECT id, password FROM users WHERE username = ? LIMIT 1";
			$result = self::$db->query( $query, [$username] );

			if ( !$result || $result->num_rows === 0 )
			{
				return false;
			}

			$row = $result->fetch_assoc();

			if ( password_verify( $password, $row['password'] ) )
			{
				$_SESSION['userid'] = $row['id'];
				$_SESSION['logedin'] = true;
				return true;
			}

			return false;
		}

		public function isLogedin()
		{
			if ( isset( $_SESSION['logedin'] ) && $_SESSION['logedin'] === true )
			{
				Main::setMessage( "index.php", "You are logged in and cannot view this page!", "alert-error" );
			}
		}

		public function notLogedin()
		{
			if ( !isset( $_SESSION['logedin'] ) || $_SESSION['logedin'] !== true )
			{
				Main::setMessage( "index.php", "You are not logged in to view this page!", "alert-error" );
			}
		}

		public function LogedinBool()
		{
			return isset( $_SESSION['logedin'] ) && $_SESSION['logedin'] === true;
		}

		public function passUpdate( $userid, $oldPassword, $newPassword, $passRep )
		{
			if ( $newPassword !== $passRep )
			{
				Main::setMessage( "settings.php", "New passwords do not match", "alert-error" );
				return;
			}

			$query = "SELECT password FROM users WHERE id = ? LIMIT 1";
			$result = self::$db->query( $query, [(int)$userid] );

			if ( !$result || $result->num_rows === 0 )
			{
				Main::setMessage( "settings.php", "User not found", "alert-error" );
				return;
			}

			$row = $result->fetch_assoc();

			if ( !password_verify( $oldPassword, $row['password'] ) )
			{
				Main::setMessage( "settings.php", "Current password is incorrect", "alert-error" );
				return;
			}

			$hashedPassword = password_hash( $newPassword, PASSWORD_BCRYPT, ['cost' => 12] );

			$updateQuery = "UPDATE users SET password = ? WHERE id = ?";
			$updateResult = self::$db->query( $updateQuery, [$hashedPassword, (int)$userid] );

			if ( $updateResult !== false )
			{
				Main::setMessage( "settings.php", "Password updated successfully!", "alert-success" );
			}
			else
			{
				Main::setMessage( "settings.php", "Password could not be updated", "alert-error" );
			}
		}

		public function emailUpdate( $userid, $oldEmail, $newEmail, $password )
		{
			$oldEmail = trim( $oldEmail );
			$newEmail = trim( $newEmail );

			if ( !$this->checkEmail( $newEmail ) )
			{
				Main::setMessage( "settings.php", "Please input a valid email address", "alert-error" );
				return;
			}

			$query = "SELECT password, email FROM users WHERE id = ? LIMIT 1";
			$result = self::$db->query( $query, [(int)$userid] );

			if ( !$result || $result->num_rows === 0 )
			{
				Main::setMessage( "settings.php", "User not found", "alert-error" );
				return;
			}

			$row = $result->fetch_assoc();

			if ( $row['email'] !== $oldEmail || !password_verify( $password, $row['password'] ) )
			{
				Main::setMessage( "settings.php", "Old email or password is incorrect", "alert-error" );
				return;
			}

			$updateQuery = "UPDATE users SET email = ? WHERE id = ?";
			$updateResult = self::$db->query( $updateQuery, [$newEmail, (int)$userid] );

			if ( $updateResult !== false )
			{
				Main::setMessage( "settings.php", "Email updated successfully!", "alert-success" );
			}
			else
			{
				Main::setMessage( "settings.php", "Email could not be updated", "alert-error" );
			}
		}

		public function addHost( $userid, $hostname, $host, $port, $ispublic )
		{
			$hostname = trim( $hostname );
			$host     = trim( $host );
			$port     = (int)$port;
			$ispublic = (int)$ispublic;

			if ( empty( $hostname ) )
			{
				Main::setMessage( "host.php", "Please enter a hostname", "alert-error" );
				return;
			}

			if ( empty( $host ) || !preg_match( "/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$|^([0-9]{1,3}\.){3}[0-9]{1,3}$/", $host ) )
			{
				Main::setMessage( "host.php", "Please enter a valid domain or IP address", "alert-error" );
				return;
			}

			if ( $port < 1 || $port > 65535 )
			{
				Main::setMessage( "host.php", "Please enter a valid port number (1-65535)", "alert-error" );
				return;
			}

			$query = "INSERT INTO hosts (user_id, hostname, host, port, ispublic) VALUES (?, ?, ?, ?, ?)";
			$result = self::$db->query( $query, [(int)$userid, $hostname, $host, $port, $ispublic] );

			if ( $result !== false )
			{
				Main::setMessage( "host.php", "Host added successfully!", "alert-success" );
			}
			else
			{
				Main::setMessage( "host.php", "Error adding host", "alert-error" );
			}
		}

		public function selectPublicHost()
		{
			$query = "SELECT * FROM hosts WHERE ispublic = 1";
			$result = self::$db->query( $query );

			if ( !$result )
			{
				Main::setMessage( "index.php", "Error fetching hosts", "alert-error" );
				return;
			}

			echo "<table class=\"table table-bordered table-hover\">
					<thead>
					<tr>
						<th>Host Name</th>
						<th>Domain or IP</th>
						<th>Port</th>
						<th>Status</th>
					</tr>
					</thead>";

			while ( $row = $result->fetch_assoc() )
			{
				echo "<tr>";
				echo "<td>" . htmlspecialchars( $row['hostname'], ENT_QUOTES, 'UTF-8' ) . "</td>";
				echo "<td>" . htmlspecialchars( $row['host'], ENT_QUOTES, 'UTF-8' ) . "</td>";
				echo "<td>" . htmlspecialchars( $row['port'], ENT_QUOTES, 'UTF-8' ) . "</td>";

				$status = Check::checkServer( $row['host'], $row['port'] ) ? 
					"<span class=\"glyphicon glyphicon-thumbs-up\"></span>" : 
					"<span class=\"glyphicon glyphicon-thumbs-down\"></span>";
				echo "<td style=\"text-align: center;\">" . $status . "</td>";
				echo "</tr>";
			}
			echo "</table>";
		}

		public function selectPrivateHost( $userid )
		{
			$query = "SELECT * FROM hosts WHERE user_id = ?";
			$result = self::$db->query( $query, [(int)$userid] );

			if ( !$result )
			{
				Main::setMessage( "index.php", "Error fetching hosts", "alert-error" );
				return;
			}

			echo "<table class=\"table table-bordered table-hover\">
					<thead>
					<tr>
						<th>Host Name</th>
						<th>Domain or IP</th>
						<th>Port</th>
						<th>Status</th>
						<th>Ping</th>
						<th>Delete</th>
					</tr>
					</thead>";

			while ( $row = $result->fetch_assoc() )
			{
				echo "<tr>";
				echo "<td>" . htmlspecialchars( $row['hostname'], ENT_QUOTES, 'UTF-8' ) . "</td>";
				echo "<td>" . htmlspecialchars( $row['host'], ENT_QUOTES, 'UTF-8' ) . "</td>";
				echo "<td>" . htmlspecialchars( $row['port'], ENT_QUOTES, 'UTF-8' ) . "</td>";

				$status = Check::checkServer( $row['host'], $row['port'] ) ? 
					"<span class=\"glyphicon glyphicon-thumbs-up\"></span>" : 
					"<span class=\"glyphicon glyphicon-thumbs-down\"></span>";
				echo "<td style=\"text-align: center;\">" . $status . "</td>";

				echo "<td style=\"text-align:center;\"><a class=\"btn btn-primary btn-small\" href=\"ping.php?host=" . urlencode( $row['host'] ) . "&port=" . urlencode( $row['port'] ) . "&count=4\"><span class=\"glyphicon glyphicon-signal\"></span></a></td>";
				echo "<td style=\"text-align:center;\"><a class=\"btn btn-info btn-small\" onclick=\"deleteFunction(" . (int)$row['id'] . ")\"><span class=\"glyphicon glyphicon-remove\"></span></a></td>";
				echo "</tr>";
			}
			echo "</table>";
		}

		public function hostDelete( $userid, $hostid )
		{
			$userid = (int)$userid;
			$hostid = (int)$hostid;

			$query = "SELECT user_id FROM hosts WHERE id = ? LIMIT 1";
			$result = self::$db->query( $query, [$hostid] );

			if ( !$result || $result->num_rows === 0 )
			{
				Main::setMessage( "index.php", "Host not found", "alert-error" );
				return;
			}

			$row = $result->fetch_assoc();

			if ( (int)$row['user_id'] !== $userid )
			{
				Main::setMessage( "index.php", "Unauthorized: You cannot delete this host", "alert-error" );
				return;
			}

			$deleteQuery = "DELETE FROM hosts WHERE id = ? AND user_id = ?";
			$deleteResult = self::$db->query( $deleteQuery, [$hostid, $userid] );

			if ( $deleteResult !== false )
			{
				Main::setMessage( "index.php", "Host deleted successfully!", "alert-success" );
			}
			else
			{
				Main::setMessage( "index.php", "Error deleting host", "alert-error" );
			}
		}
	}
