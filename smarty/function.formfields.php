<?php
// $Header: /cvsroot/bitweaver/_bit_libertyform/smarty/function.formfields.php,v 1.7 2009/12/10 13:59:02 dansut Exp $
/**
 * Smarty plugin
 * @package bitweaver
 * @subpackage libertyform
 */
require_once('formfield_funcs.php');
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
	require_once($gBitSmarty->_get_plugin_filepath('function', 'formfield'));
	require_once($gBitSmarty->_get_plugin_filepath('block', 'forminput'));
	foreach($fields as $fieldname => $field) {
		$extradiv = '';
		$htmldiv = '<div class="row">';
		$htmldiv .= smarty_function_formlabel(array('label'=>$field['description'], 'for'=>$fieldname), $gBitSmarty);
		$htmldiv .= '<input type="hidden" name="'.$grpname.'[_fields]['.$fieldname.']" id="fields_'.$fieldname.'" value="'.$field['type'].'" />';
		switch($field['type']) {
			case 'checkboxes':
			case 'checkbox':
			case 'radios':
			case 'options':
			case 'date':
			case 'hidden':
			case 'boolack':
				$smartyparams = array(
					'name' => $fieldname,
					'grpname' => $grpname,
					'field' => $field);
				$forminput = smarty_function_formfield($smartyparams, $gBitSmarty);
				if($field['type'] == 'hidden') $htmldiv = ''; // Get rid of row div and forminput
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
							case 'boolack':
								$tdcontent = boolackInput(
									array('value'=>$mfval[$mfname],'acktext'=>$mf['acktext']), $htmlname, $htmlid);
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
						case 'boolack':
							$tdcontent = boolackInput(
								array('value'=>$defval,'acktext'=>$mf['acktext']), $htmlname, $htmlid);
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
				if(empty($newinprow) && empty($field['value'])) $forminput = "<em>Sorry, no options available right now!</em>";
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

?>
