<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* process module
 * load page
*/

$log .= '[ special person ]';

$page['no_cache'] = 1;

$events = mysql_select_array("SELECT `event_title` FROM `joined` WHERE `username` = '".$request."'");
if (empty($events)) {
	$page['after_text'] = '<p>Ik heb me nog niet aangemeld voor <a href="./index.php?p=Programma">programmaonderdelen</a>.</p>';
}
else {
	$page['after_text'] = '<p>Ik ga naar ';
	foreach ($events as $event)
		$page['after_text'] .= '<a href="./index.php?p='.$event['event_title'].'">'.$event['event_title'].'</a>, ';
	$page['after_text'] = trim($page['after_text'], ', ');
	$page['after_text'] .= '</p>';
}

?>
