<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* load template
 * merge content in tpl
 * save cache
*/
$log .= '[ template system ]';

function get_tpl($name) {
	return file_get_contents('./tpls/'.$name.'.html');
}

function translate_tpl($tpl, $data, $as_text=false) {
	if ($as_text)
		$data['text'] = paragraphs($data['text']);
	
	$replace = $find = array();
	foreach ($data as $key => $val) {
		$find[] = strtoupper($key);
		$replace[] = $val;
	}
	$html = str_replace($find, $replace, $tpl);
	
	return $html;
}

function run_template($request, $page) {
	$tpl_main = get_tpl('main');
	$tpl = ($page['type'] == 'event') ? 'event' : 'info';
	$tpl_page = get_tpl($tpl);
	
	/*--- globals ---*/
	$translate = array('[title]'=>TITLE, '[base_url]'=>BASE_URL, '{title}'=>$page['title']);
	$tpl_main = translate_tpl($tpl_main, $translate);
	
	/*--- page ---*/
	$translate = array('{event_join}'=>$page['event_join'], '{event_people}'=>$page['event_people'], '{event_author}'=>$page['author'], '{after_text}'=>$page['after_text']);
	$tpl_page = translate_tpl($tpl_page, $translate);
	$translate = array('{title}'=>$page['title'], '{text}'=>$page['text']);
	$tpl_page = translate_tpl($tpl_page, $translate, true);
	
	/*--- place page in main ---*/
	$translate = array('@before_page'=>$page['before_text'], '@page'=>$tpl_page, '@after_page'=>$page['after_text']);
	$html = translate_tpl($tpl_main, $translate);
	
	/*--- save html as cache ---*/
	if (CACHE && !isset($page['no_cache'])) {
		$cachefile = './pages/'.$request.'.html';
		if (file_exists($cachefile) == false)
			file_put_contents($cachefile, $html);
	}
	
	return $html;
}

?>
