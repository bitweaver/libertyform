<?php
// $Header: /cvsroot/bitweaver/_bit_libertyform/smarty/function.formfield.php,v 1.3 2009/12/30 21:12:50 dansut Exp $
/**
 * Smarty plugin
 * @package bitweaver
 * @subpackage libertyform
 */
require_once('formfield_funcs.php');
/**
 * Smarty {formfield} function plugin
 * Type:     function
 * Name:     formfield
 * Input:
 */
function smarty_function_formfield($params, &$gBitSmarty) {
	$unexpected = array();
	detoxify($params);
	foreach($params as $key => $val) {
		switch($key) {
			case 'name':
				$name = $val;
				break;
			case 'field':
				$field = $val;
				break;
			case 'grpname':
				$grpname = $val;
				break;
			default:
				$unexpected[$key] = $val;
				break;
		}
	}

	$inpname = $grpname.'['.$name.']';
	$inpid = str_replace('[', '_', str_replace(']', '', $grpname)).'_'.$name;
	switch($field['type']) {
		case 'checkboxes':
			$smartyparams = array(
				'name' => $inpname,
				'id' => $inpid,
				'options' => $field['options'],
				// If value is not an array assume it is a bitfield
				'selected' => (is_array($field['value']) ? $field['value'] : bf2array($field['value'])));
			require_once($gBitSmarty->_get_plugin_filepath('function', 'html_checkboxes'));
			$forminput = smarty_function_html_checkboxes($smartyparams, $gBitSmarty);
			break;
		case 'checkbox':
			$boolparams = (($field['value'] == 'y') ? 'checked="checked" ' : '');
			$forminput = '<input type="checkbox" name="'.$inpname.'" id="'.$inpid.'" value="y" '.$boolparams.'/>';
			break;
		case 'options':
			$smartyparams = array(
				'name' => $inpname,
				'id' => $inpid,
				'options' => optionsArray($field),
				'selected' => $field['value']);
			$forminput = optionsInput($smartyparams, $field, $gBitSmarty);
			if(empty($forminput)) $forminput = "<em>Sorry, no options available right now!</em>";
			break;
		case 'radios':
			$smartyparams = array(
				'name' => $inpname,
				'id' => $inpid,
				'options' => $field['options'],
				'selected' => $field['value']);
			require_once($gBitSmarty->_get_plugin_filepath('function', 'html_radios'));
			$forminput = smarty_function_html_radios($smartyparams, $gBitSmarty);
			break;
		case 'date':
			$smartyparams = array(
				'field_array' => $inpname,
				'prefix' => "",
				'time' => $field['value'],
				'start_year' => "-100",
				'end_year' => "+100");
			if(isset($field['typopt'])) {
				if($field['typopt'] == 'past') {
					$smartyparams['end_year'] = '-0';
				} elseif($field['typopt'] == 'future') {
					$smartyparams['start_year'] = '-0';
				}
			}
			require_once($gBitSmarty->_get_plugin_filepath('function', 'html_select_date'));
			$forminput = smarty_function_html_select_date($smartyparams, $gBitSmarty);
			break;
		case 'hidden':
			$forminput = '<input type="hidden" name="'.$inpname.'" id="'.$inpid.'" value="'.$field['value'].'" />';
			// $htmldiv = ''; // Get rid of row div and forminput - TODO might need to fix if use from formfields
			break;
		case 'boolack':
			$forminput = boolackInput($field, $inpname, $inpid);
			break;
		case 'currency':
			$dollars = $field['value']/100;
			$cents = $field['value']%100;
			$forminput = '$<input type="text" size="7" maxlength="7" class="forminp_currency"
				name="'.$inpname.'[unit]" id="'.$inpid.'_unit" value="'.$dollars.'" />';
			$forminput .= '.<input type="text" size="2" maxlength="2" class="forminp_currency"
				name="'.$inpname.'[frac]" id="'.$inpid.'_frac" value="'.$cents.'" />';
			break;
		case 'text':
		default:
			$forminput = '<input type="text" size="'.$field['maxlen'].'" maxlength="'.$field['maxlen'].'"
				name="'.$inpname.'" id="'.$inpid.'" value="'.$field['value'].'" />';
			break;
	}
	return $forminput;
}

?>
