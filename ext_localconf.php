<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup'] = unserialize($_EXTCONF);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Tx_Schemaker_Command_SchemaCommandController';

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['frontend']) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['frontend'] > 0) {
	Tx_Extbase_Utility_Extension::configurePlugin($_EXTKEY, 'Schema', array('Schema' => 'schema'), array());
}