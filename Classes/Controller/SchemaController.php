<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Claus Due <claus@wildside.dk>, Wildside A/S
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

/**
 * Schema Controller
 *
 * Renders browsable FE output documenting Viewhelper arguments and usage.
 *
 * @package Schemaker
 * @subpackage Controller
 * @route NoMatch('bypass')
 */
class Tx_Schemaker_Controller_SchemaController extends Tx_Extbase_MVC_Controller_ActionController {

	const COUNTER_VIEWHELPERS = 0;
	const COUNTER_ABSTRACTS = 1;
	const COUNTER_TAGBASED = 2;
	const COUNTER_CONDITIONS = 3;
	const COUNTER_WIDGETS = 4;
	const COUNTER_SUBGROUPS = 5;

	/**
	 * @var array
	 */
	protected $counters = array(
		self::COUNTER_VIEWHELPERS => array(
			'counter' => 0,
			'text' => 'Total',
		),
		self::COUNTER_ABSTRACTS => array(
			'counter' => 0,
			'text' => 'Abstract',
		),
		self::COUNTER_TAGBASED => array(
			'counter' => 0,
			'text' => 'Tag Based',
		),
		self::COUNTER_CONDITIONS => array(
			'counter' => 0,
			'text' => 'Conditions',
		),
		self::COUNTER_WIDGETS => array(
			'counter' => 0,
			'text' => 'Widgets',
		),
		self::COUNTER_SUBGROUPS => array(
			'counter' => 0,
			'text' => 'Groups',
		),
	);

	/**
	 * @var Tx_Schemaker_Service_SchemaService
	 */
	protected $schemaService;

	/**
	 * @var array
	 */
	protected $extensionKeyToNamespaceMap = array('fluid' => 'f', 'vhs' => 'v', 'fluidwidget' => 'w');

	/**
	 * @var array
	 */
	protected $markdownBlacklistedExtensionKeys = array('fluid', 'news');

	/**
	 * @param Tx_Schemaker_Service_SchemaService $schemaService
	 * @return void
	 */
	public function injectSchemaService(Tx_Schemaker_Service_SchemaService $schemaService) {
		$this->schemaService = $schemaService;
	}

	/**
	 * Renders browsable schema for ViewHelpers in extension selected in
	 * plugin onfiguration. Has a maximum namespace depth of five levels
	 * from the Tx_ExtensionKey_ViewHelpers location which should fit
	 * all reasonable setups.
	 *
	 * @param string $p1
	 * @param string $p2
	 * @param string $p3
	 * @param string $p4
	 * @param string $p5
	 * @return string
	 * @route NoMatch('bypass')
	 */
	public function schemaAction($p1 = NULL, $p2 = NULL, $p3 = NULL, $p4 = NULL, $p5 = NULL) {

		$extensionKey = $this->getExtensionKeySetting();
		$namespaceName = str_replace('_', '', $extensionKey);
		$namespaceName = strtolower($namespaceName);
		if (isset($this->extensionKeyToNamespaceMap[$extensionKey])) {
			$namespaceAlias = $this->extensionKeyToNamespaceMap[$extensionKey];
		} else {
			$namespaceAlias = str_replace('_', '', $extensionKey);
		}
		$namespaceUrl = 'http://' . ('fluid' === $namespaceName ? 'typo3.org' : 'fedext.net') . '/ns/' . $namespaceName . '/ViewHelpers';;
		$schemaSource = $this->schemaService->generateXsd($extensionKey, $namespaceUrl, $namespaceAlias);
		switch ($p1) {
			case 'download-xsd':
				header('Content-type: text/xml');
				header('Content-disposition: attachment; filename=' . $extensionKey . '.xsd');
				echo $schemaSource;
				exit();
			case 'view-xsd':
				header('Content-type: text/xml');
				echo $schemaSource;
				exit();
			default:
		}

		$segments = array($p1, $p2, $p3, $p4, $p5);
		$segments = $this->trimPathSegments($segments);
		$dirPath = $this->getFolderPathFromSegments($segments);
		$arguments = $this->segmentsToArguments($segments);
		$extensionName = t3lib_div::underscoredToLowerCamelCase($extensionKey);
		$extensionName = ucfirst($extensionName);
		$displayHeadsUp = FALSE;
		if (isset($this->extensionKeyToNamespaceMap[$namespaceName])) {
			$namespaceName = $this->extensionKeyToNamespaceMap[$namespaceName];
		}
		$tree = $this->buildTree($this->getFolderPathFromSegments(array()));
		$isFolder = is_dir($dirPath);
		$isFile = $this->isFile($segments);
		if ($isFolder) {
			$subFolders = $this->getSubFolders($dirPath);
			$files = $this->getViewHelperClassFileBaseNames($dirPath);
		}
		if ($isFile) {
			$className = 'Tx_' . ucfirst(t3lib_div::camelCaseToLowerCaseUnderscored($extensionKey)) . '_ViewHelpers_' . implode('_', $segments);
			$name = $namespaceName . ':' . implode('.', array_map('lcfirst', $segments));
			$name = substr($name, 0, -10);
			if (!class_exists($className)) {
				$problem = 1;
			} else {
				/** @var $instance Tx_Fluid_Core_ViewHelper_AbstractViewHelper */
				$instance = $this->objectManager->create($className);
				if (is_subclass_of($instance, 'Tx_Fluid_Core_Widget_AbstractWidgetViewHelper')) {
					$viewHelperType = 'Widget';
					$isAjaxWidget = Tx_Extbase_Reflection_ObjectAccess::getProperty($instance, 'ajaxWidget', TRUE);
					$isAjaxWidgetSingleton = $isAjaxWidget && !$instance instanceof Tx_Fluidwidget_Core_Widget_AbstractWidgetViewHelper;
					$displayHeadsUp = ($isAjaxWidgetSingleton);
				} elseif (is_subclass_of($instance, 'Tx_Fluid_Core_ViewHelper_AbstractConditionViewHelper')) {
					$viewHelperType = 'Condition';
					$displayHeadsUp = TRUE;
				} elseif (is_subclass_of($instance, 'Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper')) {
					$viewHelperType = 'Tag Based';
					$displayHeadsUp = TRUE;
				} else {
					$viewHelperType = 'Basic';
				}
				$viewHelperArguments = $instance->prepareArguments();
				$classReflection = new ReflectionClass($instance);
				$docComment = $classReflection->getDocComment();
				$lines = explode("\n", trim($docComment));
				array_shift($lines);
				array_pop($lines);
				$tags = array();
				foreach ($lines as $lineIndex => $line) {
					if ($line === ' *') {
						$line = '';
					} else {
						$line = str_replace(' * ', '', $line);
					}
					if (substr(trim($line), 0, 1) === '@') {
						array_push($tags, substr($line, 1));
						unset($lines[$lineIndex]);
					} else {
						$lines[$lineIndex] = $line;
					}
				}
				$docComment = implode("\n", $lines);
			}
		}
		$this->view->assignMultiple(array(
			'name' => $name,
			'viewHelperType' => $viewHelperType,
			'ajaxWidget' => $isAjaxWidget,
			'tagBased' => ($viewHelperType == 'Tag Based'),
			'ajaxWidgetSingleton' => $isAjaxWidgetSingleton,
			'displayHeadsUp' => $displayHeadsUp,
			'namespaceName' => $namespaceName,
			'className' => $className,
			'tagExample' => $this->buildTagExample($className, $viewHelperArguments),
			'tagExampleRequired' => $this->buildTagExample($className, $viewHelperArguments, TRUE),
			'tagExampleSelfClosingRequired' => $this->buildTagExample($className, $viewHelperArguments, TRUE, TRUE),
			'inlineExample' => $this->buildInlineExample($className, $viewHelperArguments),
			'inlineExampleRequired' => $this->buildInlineExample($className, $viewHelperArguments, TRUE),
			'ns' => $namespaceName,
			'problem' => $problem,
			'dirPath' => $dirPath,
			'isFolder' => $isFolder,
			'isFile' => $isFile,
			'arguments' => $arguments,
			'segments' => $segments,
			'subFolders' => $subFolders,
			'files' => $files,
			'markdownBlacklisted' => in_array($extensionKey, $this->markdownBlacklistedExtensionKeys),
			'viewHelperArguments' => $viewHelperArguments,
			'docComment' => $docComment,
			'tags' => $tags,
			'tree' => $tree,
			'extensionKey' => $extensionKey,
			'extensionName' => $extensionName,
			'counters' => $this->counters
		));
	}

	/**
	 * @param string $dirPath
	 * @return array
	 */
	protected function getViewHelperClassFileBaseNames($dirPath) {
		$classBaseNames = glob($dirPath . '/*ViewHelper.php');
		$classBaseNames = array_map('basename', $classBaseNames);
		foreach ($classBaseNames as $index => $baseName) {
			if (strpos($baseName, 'Abstract') === 0) {
				$this->increaseCounter(self::COUNTER_ABSTRACTS, 1);
				unset($classBaseNames[$index]);
				continue;
			}
			$classBaseNames[$index] = basename($baseName, '.php');
		}
		sort($classBaseNames, SORT_NATURAL);
		return array_values($classBaseNames);
	}

	/**
	 * @param string $dirPath
	 * @return array
	 */
	protected function getSubFolders($dirPath) {
		$folders = scandir($dirPath);
		foreach ($folders as $index => $folderName) {
			if ($folderName{0} === '.' || strpos($folderName, 'Controller') === 0 || is_file($dirPath  . '/' . $folderName)) {
				unset($folders[$index]);
			}
		}
		sort($folders, SORT_NATURAL);
		return array_values($folders);
	}

	/**
	 * @param array $segments
	 * @return boolean
	 */
	protected function isFile($segments) {
		$fileBaseName = array_pop($segments);
		$dirPath = $this->getFolderPathFromSegments($segments);
		$filePath = $dirPath . '/' . $fileBaseName . '.php';
		return file_exists($filePath) && is_file($filePath);
	}

	/**
	 * @param array $segments
	 * @return string
	 */
	protected function getFolderPathFromSegments($segments) {
		$extensionKey = $this->getExtensionKeySetting();
		$dirPathRelativeFromExtension = t3lib_extMgm::extPath($extensionKey, 'Classes/ViewHelpers/' . implode('/', $segments));
		return $dirPathRelativeFromExtension;
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
		return isset($this->settings['extensionKey']) ? $this->settings['extensionKey'] : $GLOBALS['TSFE']->page['title'];
	}

	/**
	 * @param string $dirPath
	 * @param array $segments
	 * @return array
	 */
	protected function buildTree($dirPath, $segments = array()) {
		$branches = $tree = $arguments = array();
		$folders = $this->getSubFolders($dirPath);
		$classes = $this->getViewHelperClassFileBaseNames($dirPath);
		$classes = array_combine($classes, $classes);
		$extensionKey = $this->getExtensionKeySetting();
		$extensionName = ucfirst(t3lib_div::camelCaseToLowerCaseUnderscored($extensionKey));
		foreach ($classes as $class) {
			$className = 'Tx_' . $extensionName . '_ViewHelpers_' . implode('_', $segments) . (count($segments) > 0 ? '_' : '') . $class;
			if (class_exists($className) === FALSE) {
				unset($classes[$class]);
				continue;
			}
			$instance = $this->objectManager->get($className);
			$this->increaseCounter(self::COUNTER_VIEWHELPERS, 1);
			if (is_subclass_of($instance, 'Tx_Fluid_Core_Widget_AbstractWidgetViewHelper')) {
				$this->increaseCounter(self::COUNTER_WIDGETS, 1);
			} elseif (is_subclass_of($instance, 'Tx_Fluid_Core_ViewHelper_AbstractConditionViewHelper')) {
				$this->increaseCounter(self::COUNTER_CONDITIONS, 1);
			} elseif (is_subclass_of($instance, 'Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper')) {
				$this->increaseCounter(self::COUNTER_TAGBASED, 1);
			}
			$classSegments = array_merge($segments, array($class));
			$classes[$class] = $this->segmentsToArguments($classSegments);
		}
		$this->increaseCounter(self::COUNTER_SUBGROUPS, count($folders));
		foreach ($folders as $folder) {
			$subSegments = array_merge($segments, array($folder));
			$branches[$folder] = $this->buildTree($dirPath . $folder . '/', $subSegments);
		}
		$tree['branches'] = $branches;
		$tree['classes'] = $classes;
		$tree['segments'] = $segments;
		$tree['arguments'] = $this->segmentsToArguments($segments);
		return $tree;
	}

	/**
	 * @param string $className
	 * @param Tx_Fluid_Core_ViewHelper_ArgumentDefinition[] $arguments
	 * @param boolean $onlyRequired
	 * @param boolean $selfClosing
	 * @return string
	 */
	protected function buildTagExample($className, $arguments, $onlyRequired, $selfClosing = FALSE) {
		$name = $this->buildViewHelperTemplateSyntax($className);
		$example = '<' . $name . '';
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
			$example .= '	<!-- tag content - please note that not all ViewHelpers use this! -->' . LF;
			$example .= '</' . $name . '>';
		}
		return $example;
	}

	/**
	 * @param string $className
	 * @param Tx_Fluid_Core_ViewHelper_ArgumentDefinition[] $arguments
	 * @param boolean $onlyRequired
	 * @return string
	 */
	protected function buildInlineExample($className, $arguments, $onlyRequired) {
		$name = $this->buildViewHelperTemplateSyntax($className);
		$example = '{' . $name . '(';
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
	 * @param Tx_Fluid_Core_ViewHelper_ArgumentDefinition $argument
	 * @param boolean $quoteStrings
	 * @return string
	 */
	protected function buildArgumentTypeDummyRepresentation(Tx_Fluid_Core_ViewHelper_ArgumentDefinition $argument, $quoteStrings = TRUE) {
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
	 * @param string $className
	 * @return string
	 */
	protected function buildViewHelperTemplateSyntax($className) {
		$className = substr($className, 0, -10);
		$parts = explode('_', $className);
		array_shift($parts);
		$extensionName = array_shift($parts);
		$extensionName = t3lib_div::camelCaseToLowerCaseUnderscored($extensionName);
		array_shift($parts);
		if (isset($this->extensionKeyToNamespaceMap[$extensionName])) {
			$extensionName = $this->extensionKeyToNamespaceMap[$extensionName];
		}
		foreach ($parts as $index => $part) {
			$parts[$index] = t3lib_div::lcfirst($part);
		}
		$syntax = t3lib_div::lcfirst($extensionName) . ':' . implode('.', $parts);
		return $syntax;
	}

	/**
	 * @param array $segments
	 * @return array
	 */
	protected function segmentsToArguments($segments) {
		$arguments = array();
		foreach ($segments as $index => $segment) {
			$arguments['p' . ($index + 1)] = $segment;
		}
		return $arguments;
	}

	/**
	 * @param integer $counter
	 * @param float $amount
	 */
	protected function increaseCounter($counter, $amount) {
		$this->counters[$counter]['counter'] += ($amount);
	}
}
