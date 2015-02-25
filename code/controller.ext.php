<?php

class module_controller extends ctrl_module
{
		
		static $ok;
		static $empty;
		static $deleteok;
		static $updateok;
		
    /**
     * The 'worker' methods.
     */
	 
	 static function getCheckServer()
	 {
		 $res = "http://" . ctrl_options::GetSystemOption('sentora_domain') . "/etc/apps/caldav";
		 return $res;
		 
	 }
	 static function getShowHelp()
	 {
		 $res = "
		<h4>Apple Calendar (OS X):</h4><br>
		Note: Calendar is called iCal on older OS X versions.<br>
		Add a new CalDAV account:<br>
		In Preferences... > Accounts click the  +  button<br>
		Follow the wizard: Account Type: CalDAV<br>
		User Name: the username you just created<br>
		Password: the password you just defined<br>
		Server Address: http://".ctrl_options::GetSystemOption('sentora_domain') . "/etc/apps/caldav/cal.php/principals/USERNAME<br>
		Change the account description if you want<br><br>
		
		<h4>Apple Calendar (OS X):</h4><br>
		In Settings > Mail, Contacts, Calendar > Add Account > Other <br>
		Tap Add CalDAV Account under CALENDARS<br>
		Configure your account: Server: http://".ctrl_options::GetSystemOption('sentora_domain') . "/etc/apps/caldav/cal.php/principals/USERNAME<br>
		User Name: the username you just created<br>
		Password: the password you just defined <br>
		Description: optional, whatever you want<br>
		Tap Next<br><br>
		
		<h4>Thunderbird/Lightning:</h4><br>
		Navigate to Lightning > New account > On the network > URL<br>
		paste this URL: http://".ctrl_options::GetSystemOption('sentora_domain') . "/etc/apps/caldav/cal.php/calendars/USERNAME/default<br>
		When asked, provide user/password; your CalDAV account should be up and running<br>
		Note: if you need to get access to multiple Baikal accounts on the same server, you need to change the multirealm settings in Thunderbirds about:config. <br>Go to Tools > Options > Advanced > Config Editor. Search for calendar.network.multirealm and change the default (false) to true. <br>Delete all passwords and restart Thunderbird. Now each calendar asks for a user / password.<br>
Hint: Thunderbird's password manager can only store one authentication per auth realm. Thus you can't use two calendars with two different credentials. This problem can be solved by adding the credentials to the URL, like  http://USERNAME:PASSWORD@".ctrl_options::GetSystemOption('sentora_domain') . "/etc/apps/caldav/cal.php/calendars/USERNAME/default<br><br>

		<button class=\"button-loader btn btn-default\" type=\"button\" onclick=\"window.location.href='./?module=caldav';return false;\">Back</button>";

		 return $res;
		 
	 }
	 
	 static function doHelp()
    {
        global $controller;
        runtime_csfr::Protect();
        $currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
            if (isset($formvars['inHelp'])) {
                header('location: ./?module=' . $controller->GetCurrentModule() . '&show=Help');
                exit;
            }
        return true;
    }
	 
	 static function ExecuteUpdateUser($mid, $displayname, $password, $username)
	 {
		 global $zdbh;
		 global $controller;
		 $currentuser = ctrl_users::GetUserDetail();
		if (!fs_director::CheckForEmptyValue($password)) { 
		$sql = $zdbh->prepare("UPDATE sentora_caldav.users SET digesta1=:password WHERE id=:mid LIMIT 1");
        $sql->bindParam(':mid', $mid);
		$password1 = md5("$username:BaikalDAV:$password");
		$sql->bindParam(':password', $password1);
        $sql->execute();
		}
		
		$sql = $zdbh->prepare("UPDATE x_caldav SET cd_disname_fk=:displayname WHERE cd_id_fk=:mid LIMIT 1");
        $sql->bindParam(':mid', $mid);
		$sql->bindParam(':displayname', $displayname);
        $sql->execute();
		
		$sql = $zdbh->prepare("UPDATE sentora_caldav.principals SET displayname=:displayname WHERE id=:mid LIMIT 1");
        $sql->bindParam(':mid', $mid);
		$sql->bindParam(':displayname', $displayname);
        $sql->execute();
		
		$sql = $zdbh->prepare("UPDATE sentora_caldav.calendars SET displayname=:displayname WHERE id=:mid LIMIT 1");
        $sql->bindParam(':mid', $mid);
		$sql->bindParam(':displayname', $displayname);
        $sql->execute();

		self::$updateok = true;
		return true;
	 }
	static function ExecuteDelete($id)
	{	
		global $zdbh;
		global $controller;
		
		$sql = $zdbh->prepare("DELETE FROM x_caldav WHERE cd_id_fk = :id LIMIT 1");
		$sql->bindParam(':id', $id);
        $sql->execute();
		
		$sql = $zdbh->prepare("DELETE FROM sentora_caldav.users WHERE id = :id");
		$sql->bindParam(':id', $id);
        $sql->execute();
		
		$sql = $zdbh->prepare("DELETE FROM sentora_caldav.principals WHERE id = :id");
		$sql->bindParam(':id', $id);
        $sql->execute();
		
		$sql = $zdbh->prepare("DELETE FROM sentora_caldav.calendars WHERE id = :id");
		$sql->bindParam(':id', $id);
        $sql->execute();
		
		$sql = $zdbh->prepare("DELETE FROM sentora_caldav.calendarobjects WHERE calendarid = :id");
		$sql->bindParam(':id', $id);
        $sql->execute();

		self::$deleteok = true;
		return true;
	}
	
	static function ExecuteNewUser($username, $displayname, $domain, $password)
	{
		global $zdbh;
		global $controller;
		$currentuser = ctrl_users::GetUserDetail();
		if (!file_exists("/etc/sentora/panel/etc/apps/caldav/Specific/config.system.php")) {
		include('cnf/db.php');
		$db_user = $user;
		$db_pass = $pass;
		$handle = fopen('/etc/sentora/panel/etc/apps/caldav/Specific/config.system.php', 'w+') or die("Unable to open config.system.php");
		$content = "<?php
define(\"BAIKAL_PATH_SABREDAV\", PROJECT_PATH_FRAMEWORKS . \"SabreDAV/lib/Sabre/\"); 
define(\"BAIKAL_AUTH_REALM\", 'BaikalDAV');
define(\"BAIKAL_CARD_BASEURI\", PROJECT_BASEURI . \"card.php/\");
define(\"BAIKAL_CAL_BASEURI\", PROJECT_BASEURI . \"cal.php/\");
define(\"PROJECT_DB_MYSQL\", TRUE);
define(\"PROJECT_DB_MYSQL_HOST\", 'localhost');
define(\"PROJECT_DB_MYSQL_DBNAME\", 'sentora_caldav');  
define(\"PROJECT_DB_MYSQL_USERNAME\", '$db_user');
define(\"PROJECT_DB_MYSQL_PASSWORD\", '$db_pass'); 
define(\"BAIKAL_ENCRYPTION_KEY\", '7628333e652677e1a5e8c475a6f7f9b1');
define(\"BAIKAL_CONFIGURED_VERSION\", '0.2.7');
		?>";
			@fwrite($handle, $content); 
			fclose($handle);
			chmod("/etc/sentora/panel/etc/apps/caldav/Specific/config.system.php", 0775);
		}
		if (fs_director::CheckForEmptyValue($username, $displayname, $domain, $password)) {
			self::$empty = true;
            return false;
        }
		 $sql = $zdbh->prepare("INSERT INTO x_caldav (cd_acc_fk, cd_name_vc, cd_disname_fk, cd_mail_fk, cd_created_ts) 
								VALUES (:uid, :username, :displayname, :mail, :time);");		
        $sql->bindParam(':uid', $currentuser["userid"]);
		$myusername = "$username"."@"."$domain";
		$sql->bindParam(':username', $myusername);
		$sql->bindParam(':displayname', $displayname);
		$sql->bindParam(':mail', $myusername);
        $time = time();
        $sql->bindParam(':time', $time);
        $sql->execute();
		$caldav = 'modules/' . $controller->GetControllerRequest('URL', 'module') . '/code/caldav.php';
		if (file_exists($caldav)) {
            include($caldav);
        }
		self::$ok = true;
		return true;

	}
	
	static function ListDomains($uid)
    {
        global $zdbh;
        $currentuser = ctrl_users::GetUserDetail($uid);
        $sql = "SELECT * FROM x_vhosts WHERE vh_acc_fk=:userid AND vh_enabled_in=1 AND vh_deleted_ts IS NULL ORDER BY vh_name_vc ASC";
        //$numrows = $zdbh->query($sql);
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':userid', $currentuser['userid']);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0) {
            $sql = $zdbh->prepare($sql);
            $sql->bindParam(':userid', $currentuser['userid']);
            $res = array();
            $sql->execute();
            while ($rowdomains = $sql->fetch()) {
                $res[] = array('domain' => ui_language::translate($rowdomains['vh_name_vc']));
            }
            return $res;
        } else {
            return false;
        }
    }
	
	static function ListUsers($uid)
    {
        global $zdbh;
		global $controller;
        $currentuser = ctrl_users::GetUserDetail($uid);
        $sql = "SELECT * FROM x_caldav WHERE cd_acc_fk=:userid AND cd_deleted_ts IS NULL ORDER BY cd_name_vc ASC";
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':userid', $currentuser['userid']);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0) {
            $sql = $zdbh->prepare($sql);
            $sql->bindParam(':userid', $currentuser['userid']);
            $res = array();
            $sql->execute();
            while ($rowdomains = $sql->fetch()) {
                $res[] = array('UserName' => $rowdomains['cd_name_vc'], 'DisplayName' => $rowdomains['cd_disname_fk'], 'id' => $rowdomains['cd_id_fk']);
            }
            return $res;
        } else {
            return false;
        }
    }
	
	static function ListCurrentUsers($mid)
    {
        global $zdbh;
        $sql = "SELECT * FROM x_caldav WHERE cd_id_fk=:mid AND cd_deleted_ts IS NULL";
        //$numrows = $zdbh->query($sql);
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':mid', $mid);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0) {
            $sql = $zdbh->prepare($sql);
            $sql->bindParam(':mid', $mid);
            $res = array();
            $sql->execute();
            while ($rowmailboxes = $sql->fetch()) {
                $res[] = array('username' => $rowmailboxes['cd_name_vc'], 'displayname' => $rowmailboxes['cd_disname_fk'], 'id' => $rowmailboxes['cd_id_fk']);
            }
            return $res;
        } else {
            return false;
        }
    }
		
	/**
     * End 'worker' methods.
     */

    /**
     * Webinterface sudo methods.
     */ 	
	static function getUsernameList()
    {
        $currentuser = ctrl_users::GetUserDetail();
        return self::ListUsers($currentuser['userid']);
    }
	
	static function doNewUser()
    {
		global $controller;
        runtime_csfr::Protect();
        $currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
		if (self::ExecuteNewUser($formvars['inUserName'], $formvars['inDisplayName'], $formvars['inDomain'], $formvars['inPassword']))
        return true;
	}
	
	static function getDomainList()
    {
        $currentuser = ctrl_users::GetUserDetail();
        return self::ListDomains($currentuser['userid']);
    }
	
	static function doUserList()
    {
        global $controller;
        runtime_csfr::Protect();
        $currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
            if (isset($formvars['inDelete'])) {
                if (self::ExecuteDelete($formvars['inId']))
				return true;
            }
            if (isset($formvars['inEdit'])) {
                header('location: ./?module=' . $controller->GetCurrentModule() . '&show=Edit&other=' . $formvars['inId']);
                exit;
            }
        return true;
    }
	
	 static function doUpdateUser() 
	 {
		global $controller;
        $currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
		if (self::ExecuteUpdateUser($formvars['inSave'], $formvars['inDisplayName'], $formvars['inPassword'], $formvars['inUser']))
		return true;
	} 
	
	static function getisEditUser()
    {
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        return (isset($urlvars['show'])) && ($urlvars['show'] == "Edit");
    }
	static function getisHelp()
    {
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        return (isset($urlvars['show'])) && ($urlvars['show'] == "Help");
    }
	
	static function getEditCurrentUsers()
    {
        global $controller;
        return self::ListCurrentUsers($controller->GetControllerRequest('URL', 'other'));
    }
	
	static function getisCreateUser()
    {
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        return !isset($urlvars['show']);
    }
	
	static function getResult()
    {
		 if (!fs_director::CheckForEmptyValue(self::$empty)) {
            return ui_sysmessage::shout(ui_language::translate("A filed is empty"), "zannounceerror");
        }
		if (!fs_director::CheckForEmptyValue(self::$ok)) {
            return ui_sysmessage::shout(ui_language::translate("Account is made"), "zannounceok");
        }
		if (self::$deleteok) {
			return ui_sysmessage::shout(ui_language::translate("User account is deletet"), "zannounceok");
		}
		if (self::$updateok) {
			return ui_sysmessage::shout(ui_language::translate("User account updatet"), "zannounceok");
		}
        return;
    }
	
	 /**
     * Webinterface sudo methods.
     */
	
}
?>