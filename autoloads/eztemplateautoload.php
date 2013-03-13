<?php
require_once(dirname(__FILE__)."/"."../../customExtension.php");
$dirParts = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
$className = $dirParts[sizeof($dirParts)-2];
$operators = array_keys(customExtension::getExtensionDetails($className));
$eZTemplateOperatorArray = array();
$eZTemplateOperatorArray[] = array( 
	'class' => $className,
	'script' => "extension/$className/classes/$className.php",
 	'operator_names' => $operators,
);
?>