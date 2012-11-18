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
 * @package Schemaker
 * @subpackage Service
 */
class Tx_Schemaker_Service_SchemaService implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_Reflection_ClassReflection
	 */
	protected $abstractViewHelperReflectionClass;

	/**
	 * @var Tx_Extbase_Reflection_DocCommentParser
	 */
	protected $docCommentParser;

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param Tx_Extbase_Reflection_DocCommentParser $docCommentParser
	 * @return void
	 */
	public function injectDocCommentParser(Tx_Extbase_Reflection_DocCommentParser $docCommentParser) {
		$this->docCommentParser = $docCommentParser;
	}

	/**
	 * @param Tx_Extbase_Reflection_Service $reflectionService
	 * @return void
	 */
	public function injectReflectionService(Tx_Extbase_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 *
	 */
	public function __construct() {
		Tx_Fluid_Fluid::$debugMode = TRUE; // We want ViewHelper argument documentation
		$this->abstractViewHelperReflectionClass = new Tx_Extbase_Reflection_ClassReflection('Tx_Fluid_Core_ViewHelper_AbstractViewHelper');
	}

	/**
	 * Get all class names inside this namespace and return them as array.
	 *
	 * @param string $extensionKey
	 * @return array
	 */
	protected function getClassNamesInExtension($extensionKey) {
		$allViewHelperClassNames = array();
		$path = t3lib_extMgm::extPath($extensionKey, 'Classes/ViewHelpers/');
		$filesInPath = t3lib_div::getAllFilesAndFoldersInPath(array(), $path, 'php');
		$pathLength = strlen($path);
		foreach ($filesInPath as $filePathAndFilename) {
			$stripped = substr($filePathAndFilename, $pathLength);
			$stripped = substr($stripped, 0, -4);
			$classNamePart = str_replace('/', '_', $stripped);
			$className = 'Tx_' . ucfirst(t3lib_div::underscoredToLowerCamelCase($extensionKey)) . '_ViewHelpers_' . $classNamePart;
			if (class_exists($className)) {
				$parent = $className;
				while ($parent = get_parent_class($parent)) {
					if ($parent === 'Tx_Fluid_Core_ViewHelper_AbstractViewHelper' || $parent === 'TYPO3\\CMS\\Fluid\Core\\ViewHelper\\AbstractViewHelper') {
						array_push($allViewHelperClassNames, $className);
					}
				}
			}
		}

		foreach ($allViewHelperClassNames as $viewHelperClassName) {
			$classReflection = new ReflectionClass($viewHelperClassName);
			if ($classReflection->isAbstract() === TRUE) {
				continue;
			}
			if (strncmp($namespace, $viewHelperClassName, strlen($namespace)) === 0) {
				$affectedViewHelperClassNames[] = $viewHelperClassName;
			}
		}
		sort($affectedViewHelperClassNames);
		return $affectedViewHelperClassNames;
	}

	/**
	 * Get a tag name for a given ViewHelper class.
	 * Example: For the View Helper Tx_Fluid_ViewHelpers_Form_SelectViewHelper, and the
	 * namespace prefix Tx_Fluid_ViewHelpers, this method returns "form.select".
	 *
	 * @param string $className Class name
	 * @return string
	 */
	protected function getTagNameForClass($className) {
		$className = substr($className, 0, -10);
		$classNameParts = explode('_', $className);
		$classNameParts = array_slice($classNameParts, 3);
		$classNameParts = array_map('lcfirst', $classNameParts);
		$tagName = implode('.', $classNameParts);
		return $tagName;
	}

	/**
	 * Add a child node to $parentXmlNode, and wrap the contents inside a CDATA section.
	 *
	 * @param SimpleXMLElement $parentXmlNode Parent XML Node to add the child to
	 * @param string $childNodeName Name of the child node
	 * @param string $childNodeValue Value of the child node. Will be placed inside CDATA.
	 * @return SimpleXMLElement the new element
	 */
	protected function addChildWithCData(SimpleXMLElement $parentXmlNode, $childNodeName, $childNodeValue) {
		$parentDomNode = dom_import_simplexml($parentXmlNode);
		$domDocument = new DOMDocument();

		$childNode = $domDocument->appendChild($domDocument->createElement($childNodeName));
		$childNode->appendChild($domDocument->createCDATASection($childNodeValue));
		$childNodeTarget = $parentDomNode->ownerDocument->importNode($childNode, TRUE);
		$parentDomNode->appendChild($childNodeTarget);
		return simplexml_import_dom($childNodeTarget);
	}

	/**
	 * Generate the XML Schema definition for a given namespace.
	 * It will generate an XSD file for all view helpers in this namespace.
	 *
	 * @param string $extensionKey Namespace identifier to generate the XSD for, without leading Backslash.
	 * @param string $xsdNamespace $xsdNamespace unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers")
	 * @return string XML Schema definition
	 * @throws Exception
	 */
	public function generateXsd($extensionKey, $xsdNamespace, $namespaceAlias = NULL) {
		if (t3lib_extMgm::isLoaded($extensionKey) === FALSE) {
			throw new Exception('Extension key "' . $extensionKey . '" is not loaded', 1353200005);
		}

		$classNames = $this->getClassNamesInExtension($extensionKey);
		if (count($classNames) === 0) {
			throw new Exception(sprintf('No ViewHelpers found in namespace "%s"', $extensionKey), 1330029328);
		}

		$xmlRootNode = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
			<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema" targetNamespace="' . $xsdNamespace . '"></xsd:schema>');

		foreach ($classNames as $className) {
			$this->generateXmlForClassName($className, $xmlRootNode);
		}

		return $xmlRootNode->asXML();
	}

	/**
	 * Generate the XML Schema for a given class name.
	 *
	 * @param string $className Class name to generate the schema for.
	 * @param SimpleXMLElement $xmlRootNode XML root node where the xsd:element is appended.
	 * @return void
	 */
	protected function generateXmlForClassName($className, SimpleXMLElement $xmlRootNode) {
		$reflectionClass = new Tx_Extbase_Reflection_ClassReflection($className);
		if (!$reflectionClass->isSubclassOf($this->abstractViewHelperReflectionClass)) {
			return;
		}

		$tagName = $this->getTagNameForClass($className);

		$xsdElement = $xmlRootNode->addChild('xsd:element');
		$xsdElement['name'] = $tagName;
		$this->docCommentParser->parseDocComment($reflectionClass->getDocComment());
		$this->addDocumentation($this->docCommentParser->getDescription(), $xsdElement);

		$xsdComplexType = $xsdElement->addChild('xsd:complexType');
		$xsdComplexType['mixed'] = 'true';
		$xsdSequence = $xsdComplexType->addChild('xsd:sequence');
		$xsdAny = $xsdSequence->addChild('xsd:any');
		$xsdAny['minOccurs'] = '0';
		$xsdAny['maxOccurs'] = 'unbounded';

		$this->addAttributes($className, $xsdComplexType);
	}

	/**
	 * Add attribute descriptions to a given tag.
	 * Initializes the view helper and its arguments, and then reads out the list of arguments.
	 *
	 * @param string $className Class name where to add the attribute descriptions
	 * @param SimpleXMLElement $xsdElement XML element to add the attributes to.
	 * @return void
	 */
	protected function addAttributes($className, SimpleXMLElement $xsdElement) {
		$viewHelper = $this->objectManager->get($className);
		$argumentDefinitions = $viewHelper->prepareArguments();

		foreach ($argumentDefinitions as $argumentDefinition) {
			$xsdAttribute = $xsdElement->addChild('xsd:attribute');
			$xsdAttribute['type'] = 'xsd:string';
			$xsdAttribute['name'] = $argumentDefinition->getName();
			$this->addDocumentation($argumentDefinition->getDescription(), $xsdAttribute);
			if ($argumentDefinition->isRequired()) {
				$xsdAttribute['use'] = 'required';
			}
		}
	}

	/**
	 * Add documentation XSD to a given XML node
	 *
	 * @param string $documentation Documentation string to add.
	 * @param SimpleXMLElement $xsdParentNode Node to add the documentation to
	 * @return void
	 */
	protected function addDocumentation($documentation, SimpleXMLElement $xsdParentNode) {
		$xsdAnnotation = $xsdParentNode->addChild('xsd:annotation');
		$this->addChildWithCData($xsdAnnotation, 'xsd:documentation', $documentation);
	}

}
