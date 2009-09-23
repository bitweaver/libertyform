<?php
global $gBitSystem, $gBitSmarty, $gBitThemes;

$registerHash = array(
	'package_name' => 'libertyform',
	'package_path' => dirname(__FILE__).'/',
	'homeable' => FALSE,
);
$gBitSystem->registerPackage($registerHash);

// Add our Smarty plugin directory.
$gBitSmarty->plugins_dir[] = $registerHash['package_path']."smarty";

$gBitThemes->loadCss(LIBERTYFORM_PKG_PATH.'bit_pkgstyle.css');

?>
