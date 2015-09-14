<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* process module
 * load page
*/

$log .= '[ special events ]';

$page['no_cache'] = 1;

$events = mysql_select_array("SELECT * FROM `pages` WHERE `type` = 'event'");
if (empty($events)) {
	$page['text'] = 'Sorry, ik kan helemaal geen programma vinden..'.NLtxt
									.'Neem <a href="./index.php?=contact">contact</a> met ons op.';
}
else {
	$class = 'events';
	if ($request == 'Welkom!')
		$class = 'events-start';
	
	if ($request == 'Welkom!')
		$place = 'before_text';
	else
		$place = 'after_text';
	
	$page[$place] = '<p class="'.$class.'">';
	$tpl = file_get_contents('./tpls/events.html');
	foreach ($events as $event) {
		// skip own page
		if ($event['title'] == $request)
			continue;
		// skip text on other pages
		if ($request != 'events')
			$event['text'] = '';
		// add event
		$event['text'] = str_replace(NLtxt, '</p>'.NLtxt.'<p>', $event['text']);
		$find = array('{TITLE}', '{TEXT}');
		$replace = array($event['title'], $event['text']);
		$page[$place] .= str_replace($find, $replace, $tpl);
	}
	$page[$place] .= '</p>';
}

?>
