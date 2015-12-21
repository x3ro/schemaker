<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['schemaker'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['schemaker'] = array();
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup'] = unserialize($_EXTCONF);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'FluidTYPO3\\Schemaker\\Command\\SchemaCommandController';

if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['frontend']) && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['schemaker']['setup']['frontend'] > 0) {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('FluidTYPO3.Schemaker', 'Schema', array('Schema' => 'schema'), array());
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
# mapping info translating extension keys (legacy) to Vendor.ExtensionName format. Required for correct operation.
module.tx_schemaker.settings.extensionKeyClassNamespaceMap {
	fluid = TYPO3.Fluid
	beuser = TYPO3.Beuser
	filelist = TYPO3.Filelist
	vhs = FluidTYPO3.Vhs
	flux = FluidTYPO3.Flux
	news = GeorgRinger.News
}
# blacklisted extensions. Any keys included here will be ignored when detecting extensions containing ViewHelpers - useful to exclude extensions which provide internal or otherwise irrelevant ViewHelpers.
module.tx_schemaker.settings.blacklistedExtensionKeys = core,install,belog,sys_note,cshmanual,extensionmanager,scheduler,reports,backend,form
');
