<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup'] = unserialize($_EXTCONF);

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['frontend']) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['frontend'] > 0) {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('FluidTYPO3.Schemaker', 'Schema', 'ViewHelper schema');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('FluidTYPO3.Schemaker', 'Configuration/TypoScript', 'Schemaker');
}

if (TYPO3_MODE === 'BE' && isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['backend']) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['backend'] > 0) {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'FluidTYPO3.Schemaker',
		'help',
		'txschemakerM1',
		'',
		array(
			'SchemaInspectorModule' => 'index',
		),
		array(
			'icon' => \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:' . $_EXTKEY . '/Resources/Public/Icons/Module.png'),
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf'
		)
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'tools_schema',
		\FluidTYPO3\Schemaker\Controller\SchemaInspectorModuleController::class,
		NULL,
		'ViewHelpers'
	);

}
