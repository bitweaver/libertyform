<?php
// $Header$
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
			if(!is_array($fields)) { 
				require_once($gBitSmarty->_get_plugin_filepath('function', 'formfeedback'));
				return smarty_function_formfeedback(array('warning'=>'Invalid form fields provided'), $gBitSmarty);
			}
			break;
		  case 'errors':
			$errors = $val;
			break;
		  case 'grpname':
			$grpname = $val;
			break;
		  case 'disabled':
			$disabled = (boolean)$val;
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
		if($field['type'] == 'hidden') {
			$htmldiv = ''; // Empty row div and forminput
		} else {
			$htmldiv = '<div class="row">';
			$htmldiv .= smarty_function_formlabel(array('label'=>$field['description'], 'for'=>$fieldname), $gBitSmarty);
			$htmldiv .= '<input type="hidden" name="'.$grpname.'[_fields]['.$fieldname.']" id="fields_'.$fieldname.'" value="'.$field['type'].'" />';
		}
		if(isset($disabled)) $field['disabled'] = $disabled;
		$xparams = (empty($field['disabled']) ? '' : 'disabled="disabled" ');
		switch($field['type']) {
		  case 'checkboxes':
		  case 'checkbox':
		  case 'radios':
		  case 'options':
		  case 'date':
		  case 'hidden':
		  case 'boolack':
		  case 'currency':
		  case 'textarea':
		  case 'package_id':
			$smartyparams = array(
				'name' => $fieldname,
				'grpname' => $grpname,
				'field' => $field);
			$forminput = smarty_function_formfield($smartyparams, $gBitSmarty);
			break;
		  case 'boolfields':
			if($field['value'] == 'y') $xparams .= 'checked="checked" ';
			$forminput = '<input type="checkbox" name="'.$grpname.'['.$fieldname.']" id="'.$fieldname.'"
				value="y" class="ff-boolfield" '.$xparams.'/>';
			$smartyparams = array(
				'fields' => $field['fields'],
				'grpname' => $grpname);
			if(isset($field['disabled'])) $smartyparams['disabled'] = $field['disabled'];
			$subform = smarty_function_formfields($smartyparams, $gBitSmarty);
			$extradiv = '<div id="'.$fieldname.'_fielddiv" class="subform">'.$subform.'</div>';
			break;
		  case 'multiple':
			// If no values currently in this 'multiple' field set to empty array to avoid check later
			if(empty($field['value']) || !is_array($field['value'])) $field['value'] = array();
			$forminput = '<table><tr>';
			// Create table headings for the multiple field input table
			foreach($field['fields'] as $mfname => $mf) {
				if((array_key_exists('description', $mf)) &&
				   !(($mf['type'] == 'remove') && empty($field['value']))) {
					$forminput .= '<td class="formsublabel">'.$mf['description'].'</td>';
				} else {
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
					if(isset($disabled)) {
						$mf['disabled'] = $disabled;
					} elseif(isset($field['disabled'])) {
						$mf['disabled'] = $field['disabled'];
					}
					$xparams = (empty($mf['disabled']) ? '' : 'disabled="disabled" ');
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
						$fparams = array(
							'value' => $mfval[$mfname],
							'acktext' => $mf['acktext']);
						if(isset($mf['disabled'])) $fparams['disabled'] = $mf['disabled'];
						$tdcontent = boolackInput($fparams, $htmlname, $htmlid);
						break;
					  case 'checkbox': // Lack of 'break' and fallthrough to 'remove' is intentional
						if($mfval[$mfname] == 'y') $xparams .= 'checked="checked" ';
					  case 'remove':
						$tdcontent .= '<input type="checkbox" id="'.$htmlid.'" name="'.$htmlname.'" value="'.$idx.'" '.
							$xparams.'/>';
						break;
					  case 'radio':
						if($mf['value'] == $mfval[$field['idfield']]) $xparams .= 'checked="checked"';
						// Radio fields are special as they are not really multi fields, hence different 'name'
						$tdcontent .= '<input type="radio" id="'.$htmlid.
							'" name="'.$grpname.'['.$fieldname.']['.$mfname.']" value="'.$idx.'" '.$xparams.'/>';
						break;
					  case 'hidden':
						$tdcontent .= '<input type="hidden" id="'.$htmlid.'" name="'.$htmlname.'"
							value="'.$mfval[$mfname].'" />';
						break;
					  case 'text':
					  default:
						$tdcontent .= '<input type="text" size="'.$mf['maxlen'].'" maxlength="'.$mf['maxlen'].'"
							id="'.$htmlid.'" name="'.$htmlname.'"
							value="'.$mfval[$mfname].'" '.$xparams.'/>';
						break;
					}
					if(!empty($tdcontent)) $forminput .= "<td>$tdcontent</td>";
				}
				$forminput .= '</tr>';
				$idx++;
			}
			// Last Row to add a new multiple value
			$newinprow = '';
			if(empty($field['disabled'])) {
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
							'selected' => (empty($defval) ? 0 : $defval),
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
							value="'.$idx.'" />';
						break;
					  case 'radio':
						if(($idx == 1) && $mf['required']) $params = 'checked="checked"'; // no vals and required field
						// Radio fields are special as they are not really multi fields, hence different 'name'
						$tdcontent = '<input type="radio" '.$params.' id="'.$htmlid.'"
							name="'.$grpname.'['.$fieldname.']['.$mfname.']" value="'.$idx.'" />';
						break;
					  case 'hidden':
						$tdcontent = '<input type="hidden" id="'.$htmlid.'" name="'.$htmlname.'"
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
				value="'.$field['value'].'" '.$xparams.'/>';
			break;
		}
		if(isset($errors[$fieldname])) {
			require_once($gBitSmarty->_get_plugin_filepath('function', 'formfeedback'));
			$forminput .= smarty_function_formfeedback(array('warning'=>$errors[$fieldname]), $gBitSmarty);
		}
		if(isset($field['helptext']) && ($field['type'] != 'hidden') && empty($field['disabled'])) {
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
