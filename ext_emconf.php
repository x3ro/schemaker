<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "schemaker".
 *
 * Auto generated 09-01-2013 01:11
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Schemaker: Fluid ViewHelper Schema Generator',
	'description' => 'Generates XSD schemas (and optional browsable schema as FE plugin) for the ViewHelpers in any installed extension in its current version. See: https://github.com/NamelessCoder/schemaker',
	'category' => 'misc',
	'author' => 'Claus Due',
	'author_email' => 'claus@namelesscoder.net',
	'author_company' => 'Wildside A/S',
	'shy' => '',
	'dependencies' => 'cms,extbase,fluid',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'version' => '2.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5-0.0.0',
			'cms' => '',
			'extbase' => '',
			'fluid' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:10:{s:16:"ext_autoload.php";s:4:"f55e";s:21:"ext_conf_template.txt";s:4:"456a";s:12:"ext_icon.gif";s:4:"68b4";s:17:"ext_localconf.php";s:4:"57e0";s:14:"ext_tables.php";s:4:"809f";s:9:"README.md";s:4:"07b9";s:43:"Classes/Command/SchemaCommandController.php";s:4:"7556";s:39:"Classes/Controller/SchemaController.php";s:4:"2e41";s:33:"Classes/Service/SchemaService.php";s:4:"2c55";s:46:"Resources/Private/Templates/Schema/Schema.html";s:4:"dfe1";}',
);

?>