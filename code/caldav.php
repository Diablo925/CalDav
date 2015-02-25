<?php

include('cnf/db.php');
$db_user = $user;
$db_pass = $pass;
try {
    $ftp_db = new db_driver("mysql:host=" . $host . ";dbname=sentora_caldav", $db_user, $db_pass);
} catch (PDOException $e) {
    
}

	$sql = $ftp_db->prepare("INSERT INTO users (username, digesta1) VALUES (:username, :password);");
    $sql->bindParam(':username', $myusername);
	$password1 = md5("$myusername:BaikalDAV:$password");
	$sql->bindParam(':password', $password1);
    $sql->execute();
	
    $sql = $ftp_db->prepare("INSERT INTO principals (uri, email, displayname, vcardurl) VALUES (:uri, :mail, :displayname, 'NULL');");
	$uri = "principals/".$myusername;
	$sql->bindParam(':uri', $uri);
	$sql->bindParam(':mail', $myusername);
    $sql->bindParam(':displayname', $displayname);
    $sql->execute();
	
	$sql = $ftp_db->prepare("INSERT INTO calendars (principaluri, displayname, uri, ctag, description, calendarorder, calendarcolor, timezone, components, transparent) VALUES (:principaluri, :displayname, 'default', '1', 'Default calendar', '0', ' ', ' ', 'VEVENT,VTODO,VJOURNAL', '0');");
	$principaluri = "principals/".$myusername;
	$sql->bindParam(':principaluri', $principaluri);
    $sql->bindParam(':displayname', $displayname);
    $sql->execute();
	?>