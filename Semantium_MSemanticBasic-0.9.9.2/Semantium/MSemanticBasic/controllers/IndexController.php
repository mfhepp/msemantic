<?php
 /**
 * MSemanticBasic Magento Extension
 * @package Semantium_MSemanticBasic
 * @copyright (c) 2010 Semantium, Uwe Stoll <stoll@semantium.de>
 * @author Michael Lambertz <michael@digitallifedesign.net>
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>
**/

class Semantium_MSemanticBasic_IndexController extends Mage_Core_Controller_Front_Action
{
	public function indexAction()
	{
		/**$this->getResponse()->setHeader('Content-type', 'application/rdf+xml');
		
		$this->loadLayout();
		$this->getLayout()
			->getBlock('root')
			->setTemplate("semantium/dump.phtml");		// change
		$this	->getLayout()
				->getBlock('content')->append(
					$this->getLayout()->createBlock('semantium_msemanticbasic/datadump')
				);
		$this->renderLayout(); */
	}
}