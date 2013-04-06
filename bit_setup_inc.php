<?php
global $gBitSystem, $gBitThemes;

$registerHash = array(
	'package_name' => 'libertyform',
	'package_path' => dirname(__FILE__).'/',
	'homeable' => FALSE,
);
$gBitSystem->registerPackage($registerHash);

$gBitThemes->loadCss(LIBERTYFORM_PKG_PATH.'css/libertyform.css');

?>
