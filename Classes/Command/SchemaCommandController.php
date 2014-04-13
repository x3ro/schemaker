<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Claus Due <claus@namelesscoder.net>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Command controller for Fluid documentation rendering
 *
 * @package Schemaker
 * @subpackage Command
 */
class Tx_Schemaker_Command_SchemaCommandController extends Tx_Extbase_MVC_Controller_CommandController {

	/**
	 * @var Tx_Schemaker_Service_SchemaService
	 */
	protected $schemaService;

	/**
	 * @param Tx_Schemaker_Service_Schema $schemaService
	 * @return void
	 */
	public function injectSchemaService(Tx_Schemaker_Service_SchemaService $schemaService) {
		$this->schemaService = $schemaService;
	}

	/**
	 * Generate Fluid ViewHelper XSD Schema
	 *
	 * Generates Schema documentation (XSD) for your ViewHelpers, preparing the
	 * file to be placed online and used by any XSD-aware editor.
	 * After creating the XSD file, reference it in your IDE and import the namespace
	 * in your Fluid template by adding the xmlns:* attribute(s):
	 * <html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...>
	 *
	 * @param string $extensionKey Extension key of generated extension. If namespaces are desired, the extension key should be in the format VendorName.ExtensionName (e.g. UpperCamelCase, dot-containing, no underscores)
	 * @param string $xsdNamespace Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "http://typo3.org/ns/<php namespace>".
	 * @return void
	 */
	public function generateCommand($extensionKey, $xsdNamespace = NULL) {
		try {
			$schema = $this->generate($extensionKey, $xsdNamespace);
			$this->output($schema);
		} catch (Exception $exception) {
			$this->outputLine('An error occured while trying to generate the XSD schema for "' . $extensionKey . '":');
			$this->outputLine('%s', array($exception->getMessage()));
			$this->quit(1);
		}
	}

	/**
	 * Generates scheduled XSD file output
	 *
	 * Uses either $extensionKey or $spool. If $extensionKey is not set,
	 * $spool (file path) is expected to exist. If $spool is not set,
	 * $extensionKey's XSD will be regenerated on every execution.
	 * $spool is a JSON-encoded file containing a simple object of
	 * extension keys and a TRUE vale. Example file content:
	 * {"vhs": true, "flux": true}. This spool file can be written by
	 * github hook listener scripts such as the one included with this
	 * extension under Resources/Private/Scripts. The included hook
	 * listener is extremely simple - intentionally so - in order to
	 * minimise the time it takes for services to call the hook.
	 *
	 * @param string $extensionKey Optional extension key; if not specified, $spool is read and extension keys gathered from here. If specified, schemaker will only generate XSD for this extension key.
	 * @param string $spool Optional (but default set) spool file which contains an {"extkey": true} object to indicate that this extension's XSD must be regenerated.
	 * @param string $outputDir The target folder for XSD files. The resulting files will be written here, inside a folder named according to extension key.
	 * @param boolean $gitMode If TRUE, the extension's folder is considered a git repository and Schemaker will attempt to check out branches for each of the tags contained on the master branch and generate an XSD for each. The resulting files will be named with versions in the filename except for the current master which bears the "raw" XSD schema name.
	 * @return void
	 */
	public function scheduledCommand($extensionKey = NULL, $spool = 'typo3temp/schemaker-spool.json', $outputDir = 'fileadmin/', $gitMode = FALSE) {
		if (FALSE === (boolean) $gitMode) {
			$outputFile = t3lib_div::getFileAbsFileName($outputDir . $extensionKey . '.xsd');
			$schema = $this->generate($extensionKey);
			t3lib_div::writeFile($outputFile, $schema);
		} else {
			$spool = t3lib_div::getFileAbsFileName($spool);
			if (FALSE === file_exists($spool) && NULL !== $extensionKey) {
				$schemas = $this->generateWithGit($extensionKey);
				$this->writeSchemas($outputDir . $extensionKey, $schemas);
			} else {
				$spoolData = json_decode(file_get_contents($spool));
				if (NULL !== $extensionKey) {
					if (TRUE === $spoolData->$extensionKey) {
						$schemas = $this->generateWithGit($extensionKey);
						$this->writeSchemas($outputDir . $extensionKey, $schemas);
					}
				} else {
					foreach ($spoolData as $spooledExtensionKey => $unused) {
						$schemas = $this->generateWithGit($spooledExtensionKey);
						$this->writeSchemas($outputDir . $spooledExtensionKey, $schemas);
					}
				}
			}
			unlink($spool);
		}
	}

	/**
	 * @param string $baseName
	 * @param array $schemas
	 * @return void
	 */
	protected function writeSchemas($baseName, $schemas) {
		foreach ($schemas as $name => $schema) {
			$filename = $baseName . '-' . $name . '.xsd';
			if (0 !== strpos($filename, '/')) {
				$filename = t3lib_div::getFileAbsFileName($filename);
			}
			t3lib_div::writeFile($filename, $schema);
		}
	}

	/**
	 * @param string $extensionKey
	 * @return array
	 */
	protected function generateWithGit($extensionKey) {
		$tags = array();
		$code = 0;
		$path = t3lib_extMgm::extPath($extensionKey);
		$command = 'cd ' . $path . ' && git tag';
		exec($command, $tags, $code);
		exec('cd ' . $path . ' && git checkout master && git pull origin master --tags');
		$schemas = array(
			'master' => $this->generate($extensionKey)
		);
		$output = array();
		foreach ($tags as $tag) {
			exec('cd ' . $path . ' && git checkout -b ' . $tag . ' ' . $tag, $output, $code);
			if (0 !== $code) {
				$this->output('Could not check out tag ' . $tag . ' from git repository ' . $extensionKey . ', skipping this tag.');
				continue;
			}
			$schemas[$tag] = $this->generate($extensionKey);
			exec('cd ' . $path . ' && git checkout master -f');
			exec('cd ' . $path . ' && git branch -D ' . $tag);
		}
		exec($command);
		return $schemas;
	}

	/**
	 * @param string $extensionKey
	 * @param string $xsdNamespace
	 * @return string
	 */
	protected function generate($extensionKey, $xsdNamespace = NULL) {
		if ($xsdNamespace === NULL) {
			$xsdExtensionKeySegment = FALSE !== strpos($extensionKey, '.') ? str_replace('.', '/', $extensionKey) : $extensionKey;
			$xsdNamespace = sprintf('http://typo3.org/ns/%s/ViewHelpers', $xsdExtensionKeySegment);
		}
		$xsdSchema = $this->schemaService->generateXsd($extensionKey, $xsdNamespace);
		if (function_exists('tidy_repair_string') === TRUE) {
			$xsdSchema = tidy_repair_string($xsdSchema, array(
				'output-xml' => TRUE,
				'input-xml' => TRUE
			));
		}
		return $xsdSchema;
	}

}
