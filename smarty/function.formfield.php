<?php
// $Header$
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
		  case 'value':
			$value = $val;
			break;
		  case 'field':
			$field = $val;
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
	if(!isset($value)) {
		if(isset($field['value'])) {
			$value = $field['value'];
		} elseif(isset($field['defval'])) {
			$value = $field['defval'];
		} else {
			$value = NULL;
		}
	}

	$inpname = $grpname.'['.$name.']';
	$inpid = str_replace('[', '_', str_replace(']', '', $grpname)).'_'.$name;
	if(isset($disabled)) $field['disabled'] = $disabled;
	$xparams = (empty($field['disabled']) ? '' : 'disabled="disabled" ');
	$forminput = '';
	if(!empty($field['chkenables'])) {
		$chkparams = $xparams;
		if(!empty($value)) $chkparams .= 'checked="checked" ';
		$chkname = $grpname.'['.$name.'_chk]';
		$forminput .= '<input type="checkbox" name="'.$chkname.'" id="'.$inpid.'_chk" value="y" class="ff-boolfield" '.$chkparams.'/>';
		$forminput .= '<div id="'.$inpid.'_chk_fielddiv" class="subfield noanimate">';
	}
	switch($field['type']) {
	  case 'checkboxes':
		$smartyparams = array(
			'name' => $inpname,
			//'id' => $inpid, this makes no sense as all inputs end up with same id, illegal HTML
			'options' => $field['options'],
			// If value is not an array assume it is a bitfield
			'selected' => (is_array($value) ? $value : bf2array($value)));
		if(isset($field['typopt']) && (strncasecmp($field['typopt'], 'vertical', 4) == 0)) {
			$smartyparams['separator'] = '<br />';
		}
		if(!empty($field['disabled'])) $smartyparams['disabled'] = 'disabled';
		$gBitSmarty->loadPlugin( 'smarty_modifier_html_checkboxes' );
		$forminput .= smarty_function_html_checkboxes($smartyparams, $gBitSmarty);
		break;
	  case 'checkbox':
		$xparams .= (($value == 'y') ? 'checked="checked" ' : '');
		$forminput .= '<input type="checkbox" name="'.$inpname.'" id="'.$inpid.'" value="y" '.$xparams.'/>';
		break;
	  case 'options':
		$smartyparams = array(
			'name' => $inpname,
			'id' => $inpid,
			'options' => optionsArray($field),
			'selected' => $value);
		$optinput = optionsInput($smartyparams, $field, $gBitSmarty);
		$forminput .= (empty($optinput) ? "<em>Sorry, no options available right now!</em>" : $optinput);
		break;
	  case 'radios':
		if(empty($field['disabled'])) {
			$smartyparams = array(
				'name' => $inpname,
				'id' => $inpid,
				'label_ids' => TRUE,
				'options' => $field['options']);
			if(!empty($value)) $smartyparams['selected'] = $value;
			if(isset($field['onclick'])) $smartyparams['onclick'] = $field['onclick'];
			if(isset($field['typopt']) && (strncasecmp($field['typopt'], 'vertical', 4) == 0)) {
				$smartyparams['separator'] = '<br />';
			}
			$gBitSmarty->loadPlugin( 'smarty_modifier_html_radios' );
			$forminput .= smarty_function_html_radios($smartyparams, $gBitSmarty);
		} else {
			$forminput .= (empty($field['options'][$value]) ? '' : $field['options'][$value]);
		}
		break;
	  case 'date':
		if(empty($field['disabled'])) {
			$smartyparams = array(
				'field_array' => $inpname,
				'prefix' => "",
				'time' => $value,
				'start_year' => "-100",
				'end_year' => "+100");
			if(isset($field['typopt'])) {
				if($field['typopt'] == 'past') {
					$smartyparams['end_year'] = '-0';
				} elseif($field['typopt'] == 'future') {
					$smartyparams['start_year'] = '-0';
				}
			}
			$gBitSmarty->loadPlugin( 'smarty_modifier_html_select_date' );
			$forminput .= smarty_function_html_select_date($smartyparams, $gBitSmarty);
		} else {
			if(empty($value)) {
				$forminput .= tra('unknown');
			} else {
				$gBitSmarty->loadPlugin( 'smarty_modifier_cal_date_format' );
				$forminput .= smarty_modifier_cal_date_format($value);
			}
		}
		break;
	  case 'hidden':
		$forminput .= '<input type="hidden" name="'.$inpname.'" id="'.$inpid.'" value="'.$value.'" />';
		break;
	  case 'boolack':
		$forminput .= boolackInput($field, $inpname, $inpid);
		break;
	  case 'currency':
		$dollars = intval($value/100);
		$cents = abs($value%100);
		$forminput .= '$<input type="text" size="7" maxlength="7" class="forminp_currency"
			name="'.$inpname.'[unit]" id="'.$inpid.'_unit" value="'.$dollars.'" '.$xparams.'/>';
		$forminput .= '.<input type="text" size="2" maxlength="2" class="forminp_currency"
			name="'.$inpname.'[frac]" id="'.$inpid.'_frac" value="'.$cents.'" '.$xparams.'/>';
		break;
	  case 'section':
		$forminput .= "<hr>"; // Just used as a spacer for now
		break;
	  case 'textarea':
		$forminput .= '<textarea id="'.$inpid.'" name="'.$inpname.'" '.$xparams;
		if(isset($field['rows'])) $forminput .= 'rows="'.$field['rows'].'"';
		if(isset($field['cols'])) $forminput .= 'cols="'.$field['cols'].'"';
		$forminput .= '>'.$value.'</textarea>';
		break;
	  case 'package_id':
		// this is experimental and really only functional for some LibertyForm derived content
		global $gLibertySystem;
		// If field is not disabled and is not an empty createonly field
		if(empty($field['disabled']) && (empty($value) || empty($field['createonly']))) {
			$forminput .= '<input type="text" size="'.$field['maxlen'].'" maxlength="'.$field['maxlen'].'"
				name="'.$inpname.'" id="'.$inpid.'" value="'.$value.'" />';
		} else {
			$forminput .= '<input type="hidden" name="'.$inpname.'" id="'.$inpid.'" value="'.$value.'" />';
		}
		if(!empty($value) && !empty($field['content_type_guid']) &&
		   ($content = $gLibertySystem->getLibertyClass($field['content_type_guid'])) &&
		   method_exists($content, 'getDataShort') && $content->loadId($value)) {
		    if(!empty($forminput)) $forminput .= '&nbsp;';
			$fielddisp = $content->getDataShort();
			if(empty($fielddisp)) $fielddisp = 'Id:'.$value;
			$forminput .= '<a href="'.$content->getDisplayUrl().'">'.htmlspecialchars($fielddisp, ENT_QUOTES, 'ISO-8859').'</a>';
		}
		break;
	  case 'text':
	  default:
		$forminput .= '<input type="text" size="'.$field['maxlen'].'" maxlength="'.$field['maxlen'].'"
			name="'.$inpname.'" id="'.$inpid.'" value="'.$value.'" '.$xparams.'/>';
		break;
	}
	if(!empty($field['chkenables'])) $forminput .= '</div>';
	return $forminput;
}

?>
