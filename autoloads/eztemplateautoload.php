<?php
if (!class_exists("customExtension")) {
	// try a few extra places
	// first, in the local extension, classes/customExtension/customExtension.php_noauto (here for easier distribution, and so we don't have this autoincluded everywhere)
	$localCopyPath = dirname(__FILE__)."/"."../classes/customExtension/customExtension.php_noauto";
	if (file_exists($localCopyPath)) {
		include_once($localCopyPath);
	} else { // look at the root of all extensions
		$extensionRootPath = dirname(__FILE__)."/"."../../customExtension.php";
		if (file_exists($extensionRootPath)) {
			include_once($extensionRootPath);
		}
	}
	if (!class_exists("customExtension")) {
		die("You are missing the customExtension class. It should be supplied as a part of bfcore extension, in this local extension, or it should be placed at the PROJECTROOT/extension/ directory.\n");
	}
}
$dirParts = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
$extensionName = $dirParts[sizeof($dirParts)-2];
$className = $extensionName; // in this extension, in classes, you should have a class using the extension name
$operators = array_keys(customExtension::getExtensionDetails($className));
$eZTemplateOperatorArray = array();
$eZTemplateOperatorArray[] = array(
	'class' => $className,
	'script' => "extension/$className/classes/$className.php",
	'operator_names' => $operators,
);
?>