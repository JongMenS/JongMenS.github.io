<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* startup
   * safety first
   * get url params
 * go to module
   * process module
   * load page
 * template
   * load template
   * merge content in tpl
   * save cache
 * show page
*/

/*--- startup ---*/
require_once('./startup.php');

/*--- go to module ---*/
require_once('./page.php');

/*--- template ---*/
if (!isset($html)) {
	require_once('./template.php');
	$html = run_template($request, $page);
}

/*--- show page ---*/
if (LOG)
	$html .= '<!--'.$log.'-->';
header("content-type: text/html; charset=utf-8");
echo $html;

die;
?>
