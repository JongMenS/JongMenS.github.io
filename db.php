<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

$log .= '[ db system ]';

/*--- load config ---*/
$config = parse_ini_file('./config.php');

/*--- connect with db ---*/
@mysql_connect($config['db_host'], $config['db_user'], base64_decode($config['db_pass']));
@mysql_select_db($config['db_name']);
if (mysql_error()) {
	$log .= '[ error: '.mysql_error().' ]';
	define('DB', false);
}
else
	define('DB', true);

/*--- functions ---*/
function mysql_rescue($value) {
	// Real Escape String CUE
	return mysql_real_escape_string($value);
}
function mysql_select_array($query) {
	$result = mysql_query($query);
  if ($result !== false) {
		$final = array();
	  while ($row = mysql_fetch_assoc($result))
	    $final[] = $row;
	  return $final;
	}
	else
		return false;
}
function mysql_select_row($query) {
	$result = mysql_query($query);
  if ($result !== false) {
	  $final = mysql_fetch_assoc($result);
	  return $final;
	}
	else
		return false;
}
function mysql_select_string($query) {
	$result = mysql_query($query);
  if ($result !== false) {
	  $string = mysql_fetch_array($result, MYSQL_NUM);
	  $final = $string[0];
	  return $final;
	}
	else
		return false;
}
function mysql_update($query) {
	mysql_query($query);
	if (mysql_error())
		return mysql_error();
	else
		return true;
}
function mysql_insert($query) {
	mysql_query($query);
	if (mysql_error())
		return mysql_error();
	else
		return true;
}
function mysql_delete($query) {
	$result = mysql_query($query);
	if (mysql_error())
		return mysql_error();
	else
		return true;
}

?>
