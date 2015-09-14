<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* select anon or user
 * join status current page
*/

class user {

var $name = '';
var $login = false;

function check_username($username) {
	return mysql_select_string("SELECT `username` FROM `registers` WHERE `username` = '".mysql_rescue($username)."'");
}
function create_user($username, $password) {
	global $log;
	require_once('./db.php');
	$check = $this->check_username($username);
	if ($check == false) {
		// generate code
		do {
			$payment_code = '';
			for ($i=0; $i<4; $i++)
				$payment_code .= mt_rand(1000, 9999).'-';
			$payment_code = trim($payment_code, '-');
		} while (mysql_select_string("SELECT `payment_code` FROM `registers` WHERE `payment_code` = '".mysql_rescue($payment_code)."'"));
		
		// save
		$result = mysql_insert("INSERT INTO `registers` (`payment_code`, `username`, `password`) VALUES('".$payment_code."', '".mysql_rescue($username)."', '".mysql_rescue($password)."')");
		$log .= '[ create user: '.$result.'('.mysql_error().')';
		$result2 = mysql_insert("INSERT INTO `pages` (`title`, `type`) VALUES('".mysql_rescue($username)."', 'person')");
		$log .= ' and page: '.$result2.'('.mysql_error().') ]';
	}
	else {
		$result = false;
		$log .= '[ create user: can\'t, username taken ]';
	}
	return $result;
}

/*--- cookie functions ---*/
function read_cookie($type, $var=false) {
	if (isset($_COOKIE) && !empty($_COOKIE)) {
		$cookie = $_COOKIE[$type];
		
		if ($var !== false)
			$cookie = $cookie[$var];
	}
	else
		$cookie = false;
	
	return $cookie;
}
function set_cookie($type, $var, $val=true) {
	$http_only = true;
	$result = setcookie($type.'['.$var.']', $val, COOKIE_EXPIRE, COOKIE_PATH, COOKIE_DOMAIN, COOKIE_SECURE, $http_only);
	return $result;
}
function delete_cookie($type, $var) {
	$http_only = true;
	// set empty value and past expire date
	$result = setcookie($type.'['.$var.']', '', time()-3600, COOKIE_PATH, COOKIE_DOMAIN, COOKIE_SECURE, $http_only);
	return $result;
}

/*--- login functions ---*/
function check_login() {
	$login_user = $this->read_cookie('login', 'user');
	if (!empty($login_user)) {
		global $log;
		$log .= '[ login user '.$login_user.' with cookie ]';
		$this->login();
	}
}
function login($user=false, $pass=false) {
	if ($user === false && $pass === false) {
		$login = $this->read_cookie('login');
		if (empty($login))
			return false;
		
		$user = $login['user'];
		$pass = $login['pass'];
	}
	
	$check = mysql_select_string("SELECT `username` FROM `registers` WHERE `username` = '".mysql_rescue($user)."' AND `password` = '".mysql_rescue($pass)."'");
	if ($check == $user) {
		$this->name = $user;
		$this->login = true;
		global $log;
		$log .= '[ logged in '.$this->name.' ]';
	}
	else {
		$this->logout();
		global $log;
		$log .= '[ wrong user/pass ]';
	}
}
function logout() {
	$this->delete_cookie('login', 'user');
	$this->delete_cookie('login', 'pass');
	global $log;
	$log .= '[ logout ]';
}
function set_login($user, $pass) {
	$this->set_cookie('login', 'user', $user);
	$this->set_cookie('login', 'pass', $pass);
	global $log;
	$log .= '[ set login cookies ]';
}

/*--- event functions ---*/
function joined($title) {
	global $log;
	$result = '';
	$title_for_cookie = str_replace(' ', '_', $title);
	if ($this->login === true) {
		$result = mysql_select_string("SELECT `username` FROM `joined` WHERE `username` = '".mysql_rescue($this->name)."' AND `event_title` = '".mysql_rescue($title)."'");
		$log .= '[ checked join-status as '.$this->name.': '.$result.'('.mysql_error().') ]';
	}
	elseif ($this->read_cookie('joined', $title_for_cookie)) {
		$result = true;
		$log .= '[ checked join-status in cookie ]';
	}
	else {
		$log .= '[ checking join-status: nothing to check from ]';
	}
	return (!empty($result)) ? true : false;
}
function join($title) {
	global $log;
	if ($this->login) {
		$check = mysql_select_string("SELECT `username` FROM `joined` WHERE `username` = '".mysql_rescue($this->name)."' AND `event_title` = '".mysql_rescue($title)."'");
		if ($check == false) {
			$result = mysql_insert("INSERT INTO `joined` (`username`, `event_title`) VALUES('".mysql_rescue($this->name)."', '".mysql_rescue($title)."')");
			$log .= '[ joined '.$title.' as '.$this->name.': '.$result.'('.mysql_error().') ]';
		}
		else {
			$log .= '[ already joined '.$title.' as '.$this->name.' ]';
		}
	}
	else {
		$title = str_replace(' ', '_', $title);
		$result = $this->set_cookie('joined', $title);
		$log .= '[ joined '.$title.' in cookie ]';
	}
	return $result;
}
function unjoin($title, $force_cookie=false) {
	global $log;
	if ($this->login && $force_cookie != true) {
		$result = mysql_insert("DELETE FROM `joined` WHERE `username` = '".mysql_rescue($this->name)."' AND `event_title` = '".mysql_rescue($title)."'");
		$log .= '[ un-joined '.$title.' as '.$this->name.': '.$result.'('.mysql_error().') ]';
	}
	else {
		$title = str_replace(' ', '_', $title);
		$result = $this->delete_cookie('joined', $title);
		$log .= '[ un-joined '.$title.' in cookie ]';
	}
	return $result;
}
function move_joined() {
	global $log;
	$log .= '[ moving joins ]';
	$cookies = $this->read_cookie('joined');
	if (!empty($cookies)) {
		$force_cookie = true;
		foreach ($cookies as $title => $true) {
			$title_for_db = str_replace('_', ' ', $title);
			$this->join($title_for_db);
			$this->unjoin($title, $force_cookie);
		}
	}
	else
		$log .= '[ nothing to move: no joins ]';
	$log .= '[ moved joins ]';
}

}

$user = new user;

?>
