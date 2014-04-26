<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "schemaker".
 *
 * Auto generated 26-04-2014 04:58
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Schemaker: Fluid ViewHelper Schema Generator',
	'description' => 'Generates XSD schemas (and optional browsable schema as FE plugin) for the ViewHelpers in any installed extension in its current version. See: https://github.com/NamelessCoder/schemaker',
	'category' => 'misc',
	'author' => 'FluidTYPO3 Team',
	'author_email' => 'claus@namelesscoder.net',
	'author_company' => '',
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
	'version' => '3.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.2.99',
			'cms' => '',
			'extbase' => '',
			'fluid' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'vhs' => '',
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:26:{s:13:"composer.json";s:4:"d601";s:16:"ext_autoload.php";s:4:"f55e";s:21:"ext_conf_template.txt";s:4:"cef1";s:12:"ext_icon.gif";s:4:"68b4";s:17:"ext_localconf.php";s:4:"949d";s:14:"ext_tables.php";s:4:"07a3";s:10:"LICENSE.md";s:4:"c813";s:9:"README.md";s:4:"2b01";s:22:"Build/ImportSchema.sql";s:4:"c524";s:28:"Build/LocalConfiguration.php";s:4:"0afc";s:23:"Build/PackageStates.php";s:4:"7921";s:43:"Classes/Command/SchemaCommandController.php";s:4:"427e";s:39:"Classes/Controller/SchemaController.php";s:4:"305d";s:33:"Classes/Service/SchemaService.php";s:4:"4d22";s:34:"Configuration/TypoScript/setup.txt";s:4:"1a18";s:33:"Documentation/ComplexityChart.png";s:4:"9a0f";s:30:"Documentation/PyramidChart.png";s:4:"b3db";s:42:"Resources/Private/Partials/BreadCrumb.html";s:4:"7c6c";s:37:"Resources/Private/Partials/Class.html";s:4:"a110";s:37:"Resources/Private/Partials/Index.html";s:4:"8ccf";s:36:"Resources/Private/Partials/Link.html";s:4:"8ebd";s:40:"Resources/Private/Partials/Overview.html";s:4:"e5f4";s:40:"Resources/Private/Scripts/GithubHook.php";s:4:"76be";s:46:"Resources/Private/Templates/Schema/Schema.html";s:4:"0d94";s:42:"Resources/Public/Javascript/Application.js";s:4:"0253";s:43:"Resources/Public/Stylesheet/Application.css";s:4:"b04d";}',
);

?>