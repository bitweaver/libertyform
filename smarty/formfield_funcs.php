<?php
// $Header$
/**
 * Functions used in formfield Smarty plugins
 * @package bitweaver
 * @subpackage libertyform
 */

function bf2array($bits) {
	$ret = array();
	for($bit=1; $bits!=0; $bit<<=1) {
		if($bit & $bits) {
			$ret[] = $bit;
			$bits ^= $bit;
		}
	}
	return $ret;
}

function optionsArray($field) {
	if(isset($field['notopts'])) { // Have a list of ids to filter out of options
		$ret = array();
		foreach($field['options'] as $key => $val) {
			if(is_array($val)) { // Deal with optgroup hierarchy
				foreach($val as $subkey => $subval) {
					if(!array_key_exists($subkey, $field['notopts'])) $ret[$key][$subkey] = $subval;
				}
			} else { // More normal straight forward options
				if(!array_key_exists($key, $field['notopts'])) $ret[$key] = $val;
			}
		}
		return $ret;
	} else { // no need to filter just return the options provided
		return $field['options'];
	}
}

function optionsInput($sparams, $field, &$smarty) {

	if(isset($field['typopt']) && ($field['typopt'] == 'multiple')) {
		$sparams['multiple'] = 'multiple';
		if(!is_array($sparams['selected'])) $sparams['selected'] = bf2array($field['value']);
		$sparams['name'] .= '[]';
	}

	$realopts = 0;
	$lastkey = 0;
	if(isset($sparams['options'])) foreach($sparams['options'] as $key => $val) {
		if(is_array($val)) { // Deal with optgroup hierarchy
			foreach($val as $subkey => $subval) {
				$optgroups[$subkey] = $key.', '.$subval;
				if(!empty($subkey)) {
					$realopts++;
					$lastkey = $subkey;
				}
			}
		} else if(!empty($key)) {
			$realopts++;
			$lastkey = $key;
		}
	}

	if((($realopts == 1) && !isset($field['shownullopt'])) || // Only 1 (real) option and not showing null option, or
	   !(empty($field['createonly']) && empty($field['disabled']))) { // display only field
		// Don't bother with the dropdown, just show text and a hidden field for value.
		$selected = (empty($sparams['selected']) ? 0 : $sparams['selected']); // if nothing selected use zero
		if(isset($field['displayfunc']) && is_callable($field['displayfunc'])) {
			$display = call_user_func($field['displayfunc'], $selected);
		} elseif(isset($optgroups) && !empty($optgroups[$selected])) { // The selections are organized in hierarchical optgroups
			$display = $optgroups[$selected];
		} elseif(isset($field['options'][$selected])) { // Regular option display
			$display = $field['options'][$selected];
		} elseif(empty($selected)) {
			$display = "<em>".tr('Unknown')."</em>";
		} else { // Shouldn't ever really use this default display value, but if can't get other good text
			$display = 'No displayable val, here\'s the Id: <em>optionval=</em>&quot;'.$selected.'&quot;';
		}
		return $display.'<input type="hidden" name="'.$sparams['name'].'" id="'.$sparams['id'].'" value="'.$selected.'" />';
	} elseif(($realopts == 0) && // No real options and
	   !(isset($field['shownullopt']) && array_key_exists(0, $sparams['options']))) { // no NULL option we want to show
		return "";
	} else { // normal drop down option processing
		require_once($smarty->_get_plugin_filepath('function', 'html_options'));
		return smarty_function_html_options($sparams, $smarty);
	}
}

function boolackInput($pField, $pName, $pId) {
	$boolparams = '';
	$ackparams = '';
	$xparams = (empty($pField['disabled']) ? '' : 'disabled="disabled" ');
	if($pField['value'] == 'y') { // field true but not acknowledged
		$boolparams = 'checked="checked" ';
	} elseif($pField['value'] == 'a') { // field true and acknowledged
		$boolparams = 'checked="checked" ';
		$ackparams = 'checked="checked" ';
	}
	$forminput = '<input type="checkbox" name="'.$pName.'[]" id="'.$pId.'" value="y" class="ff-boolack" '.
		$boolparams.$xparams.'/>';
	$forminput .= ' '.$pField['acktext'].
		'<input type="checkbox" name="'.$pName.'[]" id="'.$pId.'_ack" value="a" '.$ackparams.$xparams.'/>';

	return $forminput;
}

?>
