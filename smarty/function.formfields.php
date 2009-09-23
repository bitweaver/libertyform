<?php
/**
 * Smarty plugin
 * @package bitweaver
 * @subpackage libertyform
 */

/**
 * Smarty {formfields} function plugin
 * Type:     function
 * Name:     formfields
 * Input:
 */
function smarty_function_formfields($params, &$gBitSmarty) {
	$unexpected = array();
	detoxify($params);
	foreach($params as $key => $val) {
		switch($key) {
			case 'fields':
				$fields = $val;
				break;
			case 'errors':
				$errors = $val;
				break;
			case 'grpname':
				$grpname = $val;
				break;
			default:
				$unexpected[$key] = $val;
				break;
		}
	}

	$html = '';
	require_once($gBitSmarty->_get_plugin_filepath('function', 'formlabel'));
	require_once($gBitSmarty->_get_plugin_filepath('block', 'forminput'));
	foreach($fields as $fieldname => $field) {
		$extradiv = '';
		$htmldiv = '<div class="row">';
		$htmldiv .= smarty_function_formlabel(array('label'=>$field['description'], 'for'=>$fieldname), $gBitSmarty);
		switch($field['type']) {
			case 'checkboxes':
				$smartyparams = array(
					'name' => $grpname."[".$fieldname."]",
					'options' => $field['options'],
					// If value is not an array assume it is a bitfield
					'selected' => (is_array($field['value']) ? $field['value'] : bf2array($field['value'])),
					'id' => $fieldname);
				require_once($gBitSmarty->_get_plugin_filepath('function', 'html_checkboxes'));
				$forminput = smarty_function_html_checkboxes($smartyparams, $gBitSmarty);
				break;
			case 'checkbox':
				$boolparams = (($field['value'] == 'y') ? 'checked="checked" ' : '');
				$forminput = '<input type="checkbox" name="'.$grpname.'['.$fieldname.']" id="'.$fieldname.'"
					value="y" '.$boolparams.'/>';
				break;
			case 'options':
				$smartyparams = array(
					'name' => $grpname."[".$fieldname."]",
					'options' => optionsArray($field),
					'selected' => $field['value'],
					'id' => $fieldname);
				$forminput = optionsInput($smartyparams, $field, $gBitSmarty);
				if(empty($forminput)) $forminput = "<em>Sorry, no options available right now!</em>";
				break;
			case 'radios':
				$smartyparams = array(
					'name' => $grpname."[".$fieldname."]",
					'options' => $field['options'],
					'selected' => $field['value'],
					'id' => $fieldname);
				require_once($gBitSmarty->_get_plugin_filepath('function', 'html_radios'));
				$forminput = smarty_function_html_radios($smartyparams, $gBitSmarty);
				break;
			case 'date':
				$smartyparams = array(
					'field_array' => $grpname."[".$fieldname."]",
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
				$forminput = '<input type="hidden" name="'.$grpname.'['.$fieldname.']" id="'.$fieldname.'" value="'.$field['value'].'" />';
				$htmldiv = ''; // Get rid of row div and forminput
				break;
			case 'boolfields':
				$boolparams = $divparams = '';
				if($field['value'] == 'y') {
					$boolparams = 'checked="checked" ';
				} else {
					$divparams = 'style="display:none" ';
				}
				$forminput = '<input type="checkbox" name="'.$grpname.'['.$fieldname.']" id="'.$fieldname.'"
					value="y" onchange="boolfieldsFlip(this)" '.$boolparams.'/>';
				$smartyparams = array(
					'fields' => $field['fields'],
					'grpname' => $grpname);
				$subform = smarty_function_formfields($smartyparams, $gBitSmarty);
				$extradiv = '<div id="'.$fieldname.'_fielddiv" '.$divparams.' class="subform">'.$subform.'</div>';
				break;
			case 'boolack':
				$boolparams = '';
				$ackparams = 'disabled="disabled" ';
				if($field['value'] == 'y') { // field true but not acknowledged
					$boolparams = 'checked="checked" ';
					$ackparams = '';
				} elseif($field['value'] == 'a') { // field true and acknowledged
					$boolparams = 'checked="checked" ';
					$ackparams = 'checked="checked" ';
				}
				$forminput = '<input type="checkbox" name="'.$grpname.'['.$fieldname.'][]" id="'.$fieldname.'" 
					value="y" onchange="boolackFlip(this)" '.$boolparams.'/>';
				$forminput .= ' '.$field['acktext'].
					'<input type="checkbox" name="'.$grpname.'['.$fieldname.'][]" id="'.$fieldname.'_ack" value="a" '.$ackparams.'/>';
				break;
			case 'multiple':
				// If no values currently in this 'multiple' field
				if(!isset($field['value']) || empty($field['value']) || !is_array($field['value'])) {
					$field['value'] = array(); // set to empty array to avoid check later
				}
				$forminput = '<table><tr>';
				// Create table headings for the multiple field input table
				foreach($field['fields'] as $mfname => $mf) {
					if((array_key_exists('description', $mf)) &&
					   !(($mf['type'] == 'remove') && empty($field['value']))) {
						$forminput .= '<td class="formsublabel">'.$mf['description'].'</td>';
					} elseif($mf['type'] != 'hidden') {
						$forminput .= '<td></td>';
					}
				}
				$forminput .= '</tr>';
				// Loop through the multiple fields creating a row for each existing element
				$idx = 1; // Can't start at zero as that is reserved for NULL or no entry
				foreach($field['value'] as $mfval) {
					$forminput .= '<tr>';
					foreach($field['fields'] as $mfname => $mf) {
						$tdcontent = '';
						$params = '';
						$htmlid = $fieldname.'_'.$mfname.'_'.$idx;
						$htmlname = $grpname.'['.$fieldname.']['.$mfname.']['.$idx.']';
						switch($mf['type']) {
							case 'options':
								$smartyparams = array(
									'name' => $htmlname,
									'options' => optionsArray($mf),
									'selected' => $mfval[$mfname],
									'id' => $htmlid);
								$tdcontent = optionsInput($smartyparams, $mf, $gBitSmarty); // might be empty if no options
								break;
							case 'checkbox': // Lack of 'break' and fallthrough to 'remove' is intentional
								if($mfval[$mfname] == 'y') $params = 'checked="checked"';
							case 'remove':
								$tdcontent .= '<input type="checkbox" '.$params.' id="'.$htmlid.'" name="'.$htmlname.'"
									value="'.$idx.'">';
								break;
							case 'radio':
								if($mf['value'] == $mfval[$field['idfield']]) $params = 'checked="checked"';
								// Radio fields are special as they are not really multi fields, hence different 'name'
								$tdcontent .= '<input type="radio" '.$params.' id="'.$htmlid.'"
									name="'.$grpname.'['.$fieldname.']['.$mfname.']" value="'.$idx.'">';
								break;
							case 'hidden':
								$forminput .= '<input type="hidden" id="'.$htmlid.'" name="'.$htmlname.'"
									value="'.$mfval[$mfname].'" />';
								break;
							case 'text':
							default:
								$tdcontent .= '<input type="text" size="'.$mf['maxlen'].'" maxlength="'.$mf['maxlen'].'"
									id="'.$htmlid.'" name="'.$htmlname.'"
									value="'.$mfval[$mfname].'" />';
								break;
						}
						if(!empty($tdcontent)) $forminput .= "<td>$tdcontent</td>";
					}
					$forminput .= '</tr>';
					$idx++;
				}
				// Last Row to add a new multiple value
				$newinprow = '';
				foreach($field['fields'] as $mfname => $mf) {
					$tdcontent = '';
					$params = '';
					$defval = (isset($mf['defval']) ? $mf['defval'] : '');
					$htmlid = $fieldname.'_'.$mfname.'_'.$idx;
					$htmlname = $grpname.'['.$fieldname.']['.$mfname.']['.$idx.']';
					// As this is row to 'create' we temporary reset any 'createonly' flag to be false.
					if(isset($mf['createonly'])) $mf['createonly'] = FALSE;
					switch($mf['type']) {
						case 'options':
							$smartyparams = array(
								'name' => $htmlname,
								'options' => optionsArray($mf),
								'selected' => (empty($defval) ? $defval : 0),
								'id' => $htmlid);
							$tdcontent = optionsInput($smartyparams, $mf, $gBitSmarty); // might be empty if no options
							if(empty($tdcontent)) $nooptions = TRUE;
							break;
						case 'remove':
							$tdcontent = '&nbsp;';
							break;
						case 'checkbox':
							if($defval == 'y') $params = 'checked="checked"';
							$tdcontent = '<input type="checkbox" '.$params.' id="'.$htmlid.'" name="'.$htmlname.'"
								value="'.$idx.'">';
							break;
						case 'radio':
							if(($idx == 1) && $mf['required']) $params = 'checked="checked"'; // no vals and required field
							// Radio fields are special as they are not really multi fields, hence different 'name'
							$tdcontent = '<input type="radio" '.$params.' id="'.$htmlid.'"
								name="'.$grpname.'['.$fieldname.']['.$mfname.']" value="'.$idx.'">';
							break;
						case 'hidden':
							$newinprow = '<input type="hidden" id="'.$htmlid.'" name="'.$htmlname.'"
								value="'.$defval.'" />';
							break;
						case 'text':
						default:
							$tdcontent = '<input type="text" size="'.$mf['maxlen'].'" maxlength="'.$mf['maxlen'].'"
								id="'.$htmlid.'" name="'.$htmlname.'"
								value="'.$defval.'" />';
							break;
					}
					if(isset($nooptions)) { // abandon creating whole input field row, optionless options means it is pointless
						$newinprow = '';
						break;
					}
					if(!empty($tdcontent)) $newinprow .= '<td>'.$tdcontent.'</td>';
				}
				if(!empty($newinprow)) $forminput .= '<tr>'.$newinprow.'</tr>';
				$forminput .= '<input type="hidden" name="'.$grpname.'['.$fieldname.'][lastindex]" id="'.$fieldname.'_lastindex"
					value="'.$idx.'" />';
				$forminput .= '</table>';
				break;
			case 'text':
			default:
				$forminput = '<input type="text" size="'.$field['maxlen'].'" maxlength="'.$field['maxlen'].'"
					name="'.$grpname.'['.$fieldname.']" id="'.$fieldname.'"
					value="'.$field['value'].'" />';
				break;
		}
		if(isset($errors[$fieldname])) {
			require_once($gBitSmarty->_get_plugin_filepath('function', 'formfeedback'));
			$forminput .= smarty_function_formfeedback(array('warning'=>$errors[$fieldname]), $gBitSmarty);
		}
		if(array_key_exists('helptext', $field)) {
			require_once($gBitSmarty->_get_plugin_filepath('function', 'formhelp'));
			$forminput .= smarty_function_formhelp(array('note'=>$field['helptext']), $gBitSmarty);
		}
		if(!empty($extradiv)) $forminput .= $extradiv;
		if(empty($htmldiv)) {
			$html .= $forminput;
		} else {
			$htmldiv .= smarty_block_forminput(array(), $forminput, $gBitSmarty);
			$htmldiv .= '</div>';
			$html .= $htmldiv;
		}
	}
	return $html;
}

function bf2array($bits) {
	$ret = array();
	for($bit=1; $bits!=0; $bit=$bit<<1) {
		if($bit & $bits) {
			$ret[] = $bit;
			$bits = $bits ^ $bit;
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
	$optgroups = FALSE;
	if(isset($sparams['options'])) foreach($sparams['options'] as $key => $val) {
		if(is_array($val)) { // Deal with optgroup hierarchy
			$optgroups = FALSE;
			foreach($val as $subkey => $subval) {
				if(!empty($subkey)) $realopts++;
			}
		} else if(!empty($key)) {
			$realopts++;
		}
		if($realopts > 1) break; // Currently don't care if any more than 1 real option so don't waste time
	}

	if(($realopts == 0) && // No real options and
	   !(isset($field['shownullopt']) && array_key_exists(0, $sparams['options']))) { // no NULL option we want to show
		return "";
	} elseif((($realopts == 1) && !isset($field['shownullopt'])) || // Only 1 (real) option and not showing null option, or
	         (isset($field['createonly']) && ($field['createonly'] == TRUE))) { // this is a 'create only' (no edit) field
		// Don't bother with the dropdown, just show text and a hidden field for value.
		// Shouldn't ever really use this display value, but if can't get good text it defaults to this
		$display = '<em>optionval=</em>&quot;'.$sparams['selected'].'&quot;';
		if(isset($field['displayfunc']) && is_callable($field['displayfunc'])) {
			$display = call_user_func($field['displayfunc'], $sparams['selected']);
		} elseif($optgroups) { // The selections are organized in hierarchical optgroups
			$display .= '<strong>optgroup</strong>';
		} elseif(isset($field['options'][$sparams['selected']])) { // Regular option display
			$display = $field['options'][$sparams['selected']];
		}
		return $display.'<input type="hidden" name="'.$sparams['name'].'" id="'.$sparams['id'].'" value="'.$sparams['selected'].'" />';
	} else { // normal drop down option processing
		require_once($smarty->_get_plugin_filepath('function', 'html_options'));
		return smarty_function_html_options($sparams, $smarty);
	}
}

?>
