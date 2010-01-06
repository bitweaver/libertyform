<?php
/**
 * @package libertyform
 * @subpackage smarty
 */

/**
 * Basic function that initially will just convert an integer number of cnets into a dallar and cents string
 * 
 * @param int $pAmount
 * @access public
 * @return converted string on success, FALSE on failure
 */
function smarty_modifier_currency_format($pAmount) {
	if(!is_numeric($pAmount)) return '\$invalid';
	$amount = (int)$pAmount;
	$ret = ($amount < 0) ? '-' : '';
	$ret .= '$'.((int)($amount/100)).'.'.sprintf('%02u', $amount%100);
	return $ret;
}
?>
