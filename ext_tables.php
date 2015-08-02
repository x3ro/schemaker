<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup'] = unserialize($_EXTCONF);

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['frontend']) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['frontend'] > 0) {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('FluidTYPO3.Schemaker', 'Schema', 'ViewHelper schema');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('FluidTYPO3.Schemaker', 'Configuration/TypoScript', 'Schemaker');
}
