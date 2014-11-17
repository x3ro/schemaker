<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('schemaker');
return array(
	'tx_schemaker_command_schemacommandcontroller' => $extensionPath . 'Classes/Command/SchemaCommandController.php',
	'tx_schemaker_service_schemaservice' => $extensionPath . 'Classes/Service/SchemaService.php',
);
