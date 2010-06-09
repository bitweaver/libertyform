<?php
// @version $Header$
global $gBitInstaller;

$infoHash = array(
	'package' => LIBERTYFORM_PKG_NAME,
	'version' => str_replace('.php', '', basename(__FILE__)),
	'description' => "Prototype version of the LibertyForm functionality",
	'post_upgrade' => NULL,
);

$gBitInstaller->registerPackageUpgrade($infoHash, array(
// Empty
)); // registerPackageUpgrade
?>
