<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup'] = unserialize($_EXTCONF);

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['frontend']) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['frontend'] > 0) {
	Tx_Extbase_Utility_Extension::registerPlugin($_EXTKEY, 'Schema', 'ViewHelper schema');
	t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Schemaker');
}