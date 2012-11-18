<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Claus Due <claus@wildside.dk>, Wildside A/S
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
	 * @param string $extensionKey Namespace of the Fluid ViewHelpers without leading backslash (for example 'TYPO3\Fluid\ViewHelpers'). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!
	 * @param string $xsdNamespace Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "http://typo3.org/ns/<php namespace>".
	 * @return void
	 */
	public function generateCommand($extensionKey, $xsdNamespace = NULL) {
		if ($xsdNamespace === NULL) {
			$xsdNamespace = sprintf('http://typo3.org/ns/%s', str_replace('_', '/', $extensionKey));
		}
		try {
			$xsdSchema = $this->schemaService->generateXsd($extensionKey, $xsdNamespace);
		} catch (Exception $exception) {
			$this->outputLine('An error occured while trying to generate the XSD schema:');
			$this->outputLine('%s', array($exception->getMessage()));
			$this->quit(1);
		}
		if (function_exists('tidy_repair_string') === TRUE) {
			$xsdSchema = tidy_repair_string($xsdSchema, array(
				'output-xml' => TRUE,
				'input-xml' => TRUE
			));
		}
		$this->output($xsdSchema);
	}
}
