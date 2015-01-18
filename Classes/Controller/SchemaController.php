<?php
namespace FluidTYPO3\Schemaker\Controller;
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
 *  the Free Software Foundation; either version 3 of the License, or
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

use FluidTYPO3\Schemaker\Service\SchemaService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition;

/**
 * Schema Controller
 *
 * Renders browsable FE output documenting Viewhelper arguments and usage.
 *
 * @package Schemaker
 * @subpackage Controller
 * @route NoMatch('bypass')
 */
class SchemaController extends ActionController {

	/**
	 * @var SchemaService
	 */
	protected $schemaService;

	/**
	 * @var CacheManager
	 */
	protected $manager;

	/**
	 * @var VariableFrontend
	 */
	protected $cache;

	/**
	 * @var array
	 */
	protected $extensionKeyToNamespaceMap = array('fluid' => 'f', 'vhs' => 'v', 'fluidwidget' => 'w', 'fluidbackend' => 'be');

	/**
	 * @var array
	 */
	protected $markdownBlacklistedExtensionKeys = array('fluid', 'news');

	/**
	 * @param SchemaService $schemaService
	 * @return void
	 */
	public function injectSchemaService(SchemaService $schemaService) {
		$this->schemaService = $schemaService;
	}

	/**
	 * @param CacheManager $manager
	 * @return void
	 */
	public function injectCacheManager(CacheManager $manager) {
		$this->manager = $manager;
	}

	/**
	 * @return void
	 */
	public function initializeObject() {
		$this->cache = $this->manager->getCache('schemaker');
	}

	/**
	 * Renders browsable schema for ViewHelpers in extension selected in
	 * plugin onfiguration. Has a maximum namespace depth of five levels
	 * from the Tx_ExtensionKey_ViewHelpers location which should fit
	 * all reasonable setups.
	 *
	 * @param string $extensionKey
	 * @param string $version
	 * @param string $p1
	 * @param string $p2
	 * @param string $p3
	 * @param string $p4
	 * @param string $p5
	 * @return string
	 * @route NoMatch('bypass')
	 */
	public function schemaAction($extensionKey = NULL, $version = NULL, $p1 = NULL, $p2 = NULL, $p3 = NULL, $p4 = NULL, $p5 = NULL) {
		if (NULL === $extensionKey) {
			$extensionKey = $this->getExtensionKeySetting();
			if (NULL === $version) {
				$version = 'master';
			}
		}
		list ($vendor, $extensionKey) = $this->schemaService->getRealExtensionKeyAndVendorFromCombinedExtensionKey($extensionKey);
		$schemaFile = $this->getXsdStoragePathSetting() . $extensionKey . '-' . $version . '.xsd';
		$schemaFile = GeneralUtility::getFileAbsFileName($schemaFile);
		$schemaFileExists = file_exists($schemaFile);
		$requestArguments = array(
			'extensionKey' => $extensionKey,
			'version' => 'master',
			'p1' => $p1,
			'p2' => $p2,
			'p3' => $p3,
			'p4' => $p4,
			'p5' => $p5
		);
		$namespaceName = str_replace('_', '', $extensionKey);
		$namespaceName = strtolower($namespaceName);
		$namespaceAlias = str_replace('_', '', $extensionKey);
		if (isset($this->extensionKeyToNamespaceMap[$extensionKey])) {
			$namespaceAlias = $this->extensionKeyToNamespaceMap[$extensionKey];
		}

		$relativeSchemaFile = substr($schemaFile, strlen(GeneralUtility::getFileAbsFileName('.')) - 1);

		$segments = array($p1, $p2, $p3, $p4, $p5);
		$segments = $this->trimPathSegments($segments);
		if (TRUE === empty($version)) {
			$version = 'master';
		}

		$arguments = $this->segmentsToArguments($extensionKey, $version, $segments);
		$extensionName = GeneralUtility::underscoredToLowerCamelCase($extensionKey);
		$extensionName = ucfirst($extensionName);
		$extensionKeys = $this->getExtensionKeysSetting();
		$versions = $this->getVersionsByExtensionKey($extensionKey);
		$displayHeadsUp = FALSE;
		if (isset($this->extensionKeyToNamespaceMap[$namespaceName])) {
			$namespaceName = $this->extensionKeyToNamespaceMap[$namespaceName];
		}

		list ($tree, $node, $viewHelperArguments, $docComment, $targetNamespaceUrl) = $this->getSchemaData($extensionKey, $version, $segments);

		$gitCommand = '/usr/bin/git';
		if (FALSE === file_exists($gitCommand)) {
			$gitCommand = '/usr/local/bin/git';
		}

		$className = implode('/', $segments);
		if (TRUE === ExtensionManagementUtility::isLoaded($extensionKey)) {
			$extensionPath = ExtensionManagementUtility::extPath($extensionKey);
			if (FALSE === empty($className)) {
				$relativeFilename = 'Classes/ViewHelpers/' . $className . '.php';
				$historyCacheFile = GeneralUtility::getFileAbsFileName('typo3temp/schemaker-git-log-' . str_replace('/', '-', $relativeFilename) . '.log');
				if (TRUE === file_exists($historyCacheFile) && (time() - 21600) < filemtime($historyCacheFile)) {
					$history = file_get_contents($historyCacheFile);
				} else {
					$command = 'cd ' . $extensionPath . ' && ' . $gitCommand . ' log --reverse ' . $relativeFilename;
					$history = shell_exec($command);
					$history = preg_replace('/(([a-z0-9\.^\s]+)@([a-z0-9\.^\s]+))/u', '*****@$3', $history);
					GeneralUtility::writeFile($historyCacheFile, $history);
				}
			} else {
				$readmeFile = $extensionPath . 'Classes/ViewHelpers/README.md';
				if (TRUE === file_exists($readmeFile)) {
					$readmeFile = file_get_contents($readmeFile);
				} else {
					unset($readmeFile);
				}
			}
		}

		$variables = array(
			'readmeFile' => $readmeFile,
			'history' => $history,
			'name' => end($segments),
			'schemaFile' => $relativeSchemaFile,
			'keys' => array(),
			'namespaceUrl' => $targetNamespaceUrl,
			'displayHeadsUp' => $displayHeadsUp,
			'namespaceName' => $namespaceName,
			'namespaceAlias' => $namespaceAlias,
			'className' => $className,
			'tagExample' => $this->buildTagExample($namespaceAlias, implode('.', array_map('lcfirst', $segments)), $viewHelperArguments, FALSE),
			'inlineExample' => $this->buildInlineExample($namespaceAlias, implode('.', array_map('lcfirst', $segments)), $viewHelperArguments),
			'ns' => $namespaceName,
			'isFile' => (NULL !== $node),
			'arguments' => $arguments,
			'segments' => $segments,
			'markdownBlacklisted' => in_array($extensionKey, $this->markdownBlacklistedExtensionKeys),
			'viewHelperArguments' => $viewHelperArguments,
			'docComment' => $docComment,
			'tree' => $tree,
			'version' => $version,
			'versions' => $versions,
			'extensionKey' => $extensionKey,
			'extensionKeys' => $extensionKeys,
			'extensionName' => $extensionName
		);
		$this->view->assignMultiple($variables);
	}

	/**
	 * @param string $extensionKey
	 * @param string $version
	 * @param array $segments
	 * @return array
	 */
	protected function getSchemaData($extensionKey, $version, $segments) {
		if (FALSE === ExtensionManagementUtility::isLoaded($extensionKey)) {
			return array();
		}
		$baseCacheKey = $extensionKey . $version;
		$baseCacheKey = preg_replace('/[^a-z0-9]+/i', '-', $baseCacheKey);
		$cacheKey = $baseCacheKey . implode('', $segments);
		if (TRUE === $this->cache->has($cacheKey)) {
			return $this->cache->get($cacheKey);
		}
		$className = implode('/', $segments);
		$url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
		$schemaFile = $this->getXsdStoragePathSetting() . $extensionKey . '-' . $version . '.xsd';
		$schemaFile = GeneralUtility::getFileAbsFileName($schemaFile);
		$schemaSource = shell_exec('cat ' . $schemaFile . ' | tr -cd \'[:print:]\r\n\t\'');

		$document = new \DOMDocument();
		$document->validateOnParse = TRUE;
		$document->strictErrorChecking = TRUE;
		$document->loadXML($schemaSource);
		if (TRUE === $this->cache->has($baseCacheKey . 'tree')) {
			$tree = $this->cache->get($baseCacheKey . 'tree');
		} else {
			$tree = $this->buildTreeFromSchema($document);
			$this->cache->set($baseCacheKey . 'tree', $tree);
		}
		$node = $this->findCurrentViewHelperNode($document, $segments);
		$targetNamespaceUrl = $document->documentElement->getAttribute('targetNamespace');
		if (0 < count($segments)) {
			$viewHelperArguments = $this->makeArgumentDefinitions($node, $extensionKey, $className);
			$docComment = $node->getElementsByTagName('documentation')->item(0)->nodeValue;
			$additionalDocumentationFile = ExtensionManagementUtility::extPath($extensionKey, 'Documentation/Classes/ViewHelpers/' . $className . '/README.md');
			$nextDiv = FALSE;
			if (TRUE === file_exists($additionalDocumentationFile)) {
				$alerts = array('warning', 'danger', 'success', 'info');
				$additionalDocumentation = file_get_contents($additionalDocumentationFile);
				$parts = explode('```', $additionalDocumentation);
				foreach ($parts as $index => &$part) {
					$firstText = substr($part, 0, strpos($part, LF));
					if (TRUE === in_array($firstText, $alerts)) {
						$part = '<span class="alert alert-' . $firstText . '"><span class="lead">' . ucfirst($firstText) . '</span><br />' . substr($part, strlen($firstText));
						$nextDiv = TRUE;
					} else {
						if (TRUE === $nextDiv) {
							$part = '</span>' . $part;
							$nextDiv = FALSE;
						} elseif (0 < $index) {
							$part = '```' . $part;
						}
					}
				}
				$additionalDocumentation = implode('', $parts);
				$additionalDocumentation = preg_replace('/Arguments\/([a-z0-9^\s\/]+)\.md/i', $url . '#argument-$1', $additionalDocumentation);
				$additionalDocumentation = preg_replace('/(```)([a-z\s]+)(.[\`]{3})(```)/i', $url . '<div class="alert alert-$1">$2</div>', $additionalDocumentation);
				$docComment .= LF . LF . $additionalDocumentation;
			}
		}
		$data = array($tree, $node, $viewHelperArguments, $docComment, $targetNamespaceUrl);
		$this->cache->set($cacheKey, $data);
		return $data;
	}

	/**
	 * @param array $segments
	 * @return array
	 */
	protected function trimPathSegments($segments) {
		foreach ($segments as $index => $value) {
			if ($value === NULL) {
				unset($segments[$index]);
			}
		}
		return $segments;
	}

	/**
	 * @return string
	 */
	protected function getExtensionKeySetting() {
		$fallback = (TRUE === ExtensionManagementUtility::isLoaded($GLOBALS['TSFE']->page['title']) ? $GLOBALS['TSFE']->page['title'] : NULL);
		return TRUE === isset($this->settings['extensionKey']) ? $this->settings['extensionKey'] : $fallback;
	}

	/**
	 * @return string
	 */
	protected function getXsdStoragePathSetting() {
		return TRUE === isset($this->settings['xsdStoragePath']) ? $this->settings['xsdStoragePath'] : NULL;
	}

	/**
	 * @return string
	 */
	protected function getExtensionKeysSetting() {
		$keys = TRUE === isset($this->settings['extensionKeys']) ? $this->settings['extensionKeys'] : $this->getExtensionKeySetting();
		if (FALSE === is_array($keys)) {
			$keys = GeneralUtility::trimExplode(',', $keys);
		}
		sort($keys);
		return $keys;
	}

	/**
	 * @param string $extensionKey
	 * @return string
	 */
	protected function getVersionsByExtensionKey($extensionKey) {
		$path = $this->getXsdStoragePathSetting();
		$pattern = GeneralUtility::getFileAbsFileName($path) . $extensionKey . '-*.xsd';
		$versions = array();
		foreach (glob($pattern) as $file) {
			$version = basename($file, '.xsd');
			$version = substr($version, strlen($extensionKey) + 1);
			array_push($versions, $version);
		}
		rsort($versions);
		return $versions;
	}

	/**
	 * @param \DOMDocument $document
	 * @return array
	 */
	protected function buildTreeFromSchema(\DOMDocument $document) {
		$tree = array();
		$nodes = $document->getElementsByTagName('element');
		foreach ($nodes as $element) {
			$name = $element->getAttribute('name');
			$parts = explode('.', $name);
			$node =& $tree;
			while ($part = array_shift($parts)) {
				$part = ucfirst($part);
				if (0 === count($parts)) {
					$part .= 'ViewHelper';
					$node[$part] = $part;
				} elseif (FALSE === is_array($node[$part]) && 0 < count($parts)) {
					$node[$part] = array();
				}
				$node = &$node[$part];
			}
		}
		return $this->sortTree($tree);
	}

	/**
	 * @param mixed $tree
	 * @return mixed
	 */
	protected function sortTree($tree) {
		if (FALSE === is_array($tree)) {
			return $tree;
		}
		$folders = $files = array();
		foreach ($tree as $key => $item) {
			if (TRUE === is_array($item)) {
				$folders[$key] = $this->sortTree($item);
			} else {
				$files[$key] = $item;
			}
		}
		$tree = $folders + $files;
		return $tree;
	}

	/**
	 * @param string $namespace
	 * @param string $name
	 * @param ArgumentDefinition[] $arguments
	 * @param boolean $onlyRequired
	 * @param boolean $selfClosing
	 * @return string
	 */
	protected function buildTagExample($namespace, $name, $arguments, $onlyRequired, $selfClosing = FALSE) {
		$example = '<' . $namespace . ':' . substr($name, 0, -10) . '';
		foreach ($arguments as $argument) {
			if ($onlyRequired && !$argument->isRequired()) {
				continue;
			}
			$example .= ' ' . $argument->getName() . '="' . $this->buildArgumentTypeDummyRepresentation($argument, FALSE) . '"';
		}
		if ($selfClosing) {
			$example .= ' />';
		} else {
			$example .= '>' . LF;
			$example .= '	<!-- tag content - may be ignored! -->' . LF;
			$example .= '</' . $namespace . ':' . substr($name, 0, -10) . '>';
		}
		return $example;
	}

	/**
	 * @param string $namespace
	 * @param string $name
	 * @param ArgumentDefinition[] $arguments
	 * @param boolean $onlyRequired
	 * @return string
	 */
	protected function buildInlineExample($namespace, $name, $arguments, $onlyRequired = FALSE) {
		$example = '{' . $namespace . ':' . substr($name, 0, -10) . '(';
		$argumentsRendered = FALSE;
		foreach ($arguments as $argument) {
			if ($onlyRequired && !$argument->isRequired()) {
				continue;
			}
			$example .= $argument->getName() . ': ' . $this->buildArgumentTypeDummyRepresentation($argument);
			$example .= ', ';
			$argumentsRendered = TRUE;
		}
		if ($argumentsRendered) {
			$example = substr($example, 0, -2);
		}
		$example .= ')}';
		return $example;
	}

	/**
	 * @param \DOMDocument $document
	 * @param array $segments
	 * @return \DOMElement
	 */
	protected function findCurrentViewHelperNode(\DOMDocument $document, $segments) {
		$segments = array_map('lcfirst', $segments);
		$name = substr(implode('.', $segments), 0, -10);
		$elements = $document->getElementsByTagName('element');
		foreach ($elements as $element) {
			if ($name === $element->getAttribute('name')) {
				return $element;
			}
		}
		return NULL;
	}

	/**
	 * @param \DOMElement $node
	 * @param string $extensionKey
	 * @param string $className
	 * @return ArgumentDefinition[]
	 */
	protected function makeArgumentDefinitions(\DOMElement $node, $extensionKey, $className) {
		$arguments = $node->getElementsByTagName('attribute');
		$definitions = array();
		$url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
		foreach ($arguments as $attribute) {
			$complexType = 'xsd:complexType' === $attribute->parentNode->tagName ? $attribute->parentNode : NULL;
			$name = $attribute->getAttribute('name');
			$type = array_pop(explode(':', $attribute->getAttribute('type')));
			if ($attribute->getAttribute('php:type')) {
				$type = $attribute->getAttribute('php:type');
			}
			$default = $attribute->getAttribute('default');
			$description = $attribute->getElementsByTagName('documentation')->item(0)->nodeValue;
			$additionalDocumentationFile = ExtensionManagementUtility::extPath($extensionKey, 'Documentation/Classes/ViewHelpers/' . $className . '/Arguments/' . $name . '.md');
			if (TRUE === file_exists($additionalDocumentationFile)) {
				$additionalDocumentation = file_get_contents($additionalDocumentationFile);
				$pattern = '/([a-z0-9^\s\/]+)\.md/i';
				$additionalDocumentation = preg_replace($pattern, $url . '#argument-$1', $additionalDocumentation);
				$description .= LF . LF . $additionalDocumentation;
			}
			$required = (boolean) ($complexType->getElementsByTagName('any')->item(0)->getAttribute('minOccurs') || 'required' === $attribute->getAttribute('use'));
			$definition = new ArgumentDefinition($name, $type, $description, $required, $default);
			$definitions[$name] = $definition;
		}
		return $definitions;
	}

	/**
	 * @param ArgumentDefinition $argument
	 * @param boolean $quoteStrings
	 * @return string
	 */
	protected function buildArgumentTypeDummyRepresentation(ArgumentDefinition $argument, $quoteStrings = TRUE) {
		switch ($argument->getType()) {
			case 'string': $representation = (!$quoteStrings ? '' : "'") . ($argument->getDefaultValue() ? $argument->getDefaultValue() : 'foo') . (!$quoteStrings ? '' : "'"); break;
			case 'array': $representation = "{foo: 'bar'}"; break;
			case 'integer': $representation = 123; break;
			case 'boolean': $representation = '1'; break;
			default: $representation = '[' . $argument->getType() . ']';
		}
		return $representation;
	}

	/**
	 * @param string $extensionKey
	 * @param string $version
	 * @param array $segments
	 * @return array
	 */
	protected function segmentsToArguments($extensionKey, $version, $segments) {
		$arguments = array(
			'extensionKey' => $extensionKey,
			'version' => $version
		);
		foreach ($segments as $index => $segment) {
			$arguments['p' . ($index + 1)] = $segment;
		}
		return $arguments;
	}

}
