<?php
	include_once ("./chaozzDB.php"); // include chaozzdb
	
	echo "<strong>SELECT name FROM user WHERE name = chaozz AND id = 1</strong>";
	echo "<br>";
	$user = chaozzdb_query ("SELECT name FROM user WHERE name = chaozz AND id = 1");
	print_r ($user);
	echo "<hr>";
	
	echo "<strong>SELECT name FROM user WHERE name = chaozz OR name = rampage</strong>";
	echo "<br>";
	$user = chaozzdb_query ("SELECT name FROM user WHERE name = chaozz OR name = rampage");
	print_r ($user);
	echo "<hr>";
	
	echo "<strong>SELECT * FROM user</strong>";
	echo "<br>";
	$user = chaozzdb_query ("SELECT * FROM user"); // select all fields from all users
	print_r ($user);
	echo "<hr>";
	
	if (count($user) == 0)
		echo "No users found."; // no users found
	else
	{
		echo count($user)." users found..<br><br>";
		for ($i = 0; $i < count($user); $i ++)
		{
			// find the group of this user
			$group = chaozzdb_query ("SELECT * FROM group WHERE id = {$user[$i]['group_id']}");
			// print the result
			echo "The user named <b>".urldecode($user[$i]['name'])."</b> is a member of the group <b>".urldecode($group[0]['name'])."</b><br>";
			
			// the passwords were stored in plain text, so lets encrypt them
			$password = chaozzdb_password($user[$i]['password']); // encrypt the plain text password
			$result = chaozzdb_query ("UPDATE user SET password = $password WHERE id = {$user[$i]['id']}"); // update the user record
			echo "The password of this user ({$user[$i]['password']}) is now hashed and stored in the database.<br><br>";
		}
	}
?>