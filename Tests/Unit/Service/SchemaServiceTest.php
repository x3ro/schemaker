<?php
namespace FluidTYPO3\Schemaker\Tests\Unit\Service;
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

use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class SchemaServiceTest
 */
class SchemaServiceTest extends UnitTestCase {

	/**
	 * @param string $class
	 * @param string $expected
	 * @test
	 * @dataProvider getTagNameForClassTestValues
	 */
	public function testGetTagNameForClass($class, $expected) {
		$instance = $this->getMock('FluidTYPO3\\Schemaker\\Service\\SchemaService', array(), array(), '', FALSE);
		$result = $this->callInaccessibleMethod($instance, 'getTagNameForClass', $class);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return array
	 */
	public function getTagNameForClassTestValues() {
		return array(
			array('Tx_Vhs_ViewHelpers_Content_RenderViewHelper', 'content.render'),
			array('Tx_Vhs_ViewHelpers_Content_GetViewHelper', 'content.get'),
			array('Tx_Vhs_ViewHelpers_ContentViewHelper', 'content'),
			array('FluidTYPO3\\Vhs\\ViewHelpers\\Content\\RenderViewHelper', 'content.render'),
			array('FluidTYPO3\\Vhs\\ViewHelpers\\Content\\GetViewHelper', 'content.get'),
			array('TYPO3\\CMS\\Fluid\\ViewHelpers\\IfViewHelper', 'if'),
			array('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlentitiesViewHelper', 'format.htmlentities'),
		);
	}

}
