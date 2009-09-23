<?php
// @version $Header: /cvsroot/bitweaver/_bit_libertyform/admin/upgrades/0.1.0.php,v 1.1 2009/09/23 15:19:25 spiderr Exp $
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
