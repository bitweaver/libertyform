<?php
global $gBitInstaller;
require_once(LIBERTYFORM_PKG_PATH.'LibertyForm.php');

$gBitInstaller->registerPackageInfo(LIBERTYFORM_PKG_NAME, array(
	'description' => "LibertyForm is a protoype of a forms helper infrastucture.",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
));

// Requirements
$gBitInstaller->registerRequirements(LIBERTYFORM_PKG_NAME, array(
	'liberty' => array('min' => '2.1.0'),
	'kernel' => array('min' => '2.0.0'),
));
?>
