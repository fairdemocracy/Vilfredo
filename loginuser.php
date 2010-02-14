<?php
require_once 'config.inc.php';

function login()
{
	
	if (empty($_POST['username']) || empty($_POST['pass'] ))
	{
		echo "0";
		exit();
	}
	
	$username = GetEscapedPostParam('username');
	$password = GetEscapedPostParam('pass');
	
	set_log('Logging in ' . $username . ' ' . $password);
	
	// checks it against the database
	$sql = "SELECT * FROM users WHERE username = '$username'";
	$check = mysql_query($sql);

	if (!$check)
	{
		handle_db_error($check);
		echo "0";
		exit();
	}
	
	//Gives error if user dosen't exist
	if (mysql_num_rows($check) == 0) 
	{
		echo "User $username not registered";
		exit();
	}

	$info = mysql_fetch_array( $check );
	$password = md5($password);

	//gives error if the password is wrong
	if ($password != $info['password']) 
	{
		echo "Incorrect password for $username";
		exit();
	}
	else
	{
		// success
		set_log('Log in successful');
		// log user in
		$_SESSION[USER_LOGIN_ID] = $info['id'];
		$_SESSION[USER_LOGIN_MODE] = 'VGA';
		echo '1';
		exit();
	}
}

$action =  GetEscapedPostParam('action');

switch ($action) {
	case 'login':
		echo login();
		break;
	case 'logout':
		echo 'Not yet implememnted';
		break;
	default:
		echo "Unknown action.";
}
?>