<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Franz Holzinger <franz@ttproducts.de>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the tt_products (Shopping System) extension.
 *
 * related product list view functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_pid_list.php');


class tx_ttproducts_relatedlist_view {
	public $conf;
	public $config;
	public $pidListObj;
	public $cObj;


	public function init (&$cObj, $pid_list, $recursive)	{
		$this->cObj = &$cObj;

		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->pidListObj = &t3lib_div::getUserObj('tx_ttproducts_pid_list');
		$this->pidListObj->init($cObj);
		$this->pidListObj->applyRecursive($recursive, $pid_list, TRUE);
		$this->pidListObj->setPageArray();
	}


	public function getQuantityMarkerArray (
		$theCode,
		$functablename,
		$marker,
		$itemArray,
		$useArticles,
		&$markerArray,
		$viewTagArray
	) {
		require_once (PATH_BE_ttproducts.'control/class.tx_ttproducts_control_basketquantity.php');

		$addListArray = $this->getAddListArray (
			$theCode,
			$functablename,
			$marker,
			'',
			$this->useArticles
		);
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$itemObj = &$tablesObj->get($functablename);

		$rowArray = array();
		$rowArray[$functablename] = $itemArray;

		foreach ($addListArray as $subtype => $funcArray)	{

			if (isset($viewTagArray[$funcArray['marker']]) && $funcArray['require'])	{
				$relatedIds = $itemObj->getRelated($uid, $subtype);

				if (count($relatedIds))	{

					$quantitiyMarkerArray = array();
					tx_ttproducts_control_basketquantity::getQuantityMarkerArray (
						$relatedIds,
						$rowArray,
						$quantitiyMarkerArray
					);
					$markerArray = array_merge($markerArray, $quantitiyMarkerArray);
				}
			}
		}
	}


	public function getAddListArray (
		$theCode,
		$functablename,
		$marker,
		$uid,
		$useArticles
	)	{

		switch ($functablename)	{
			case 'tt_products':
				$rc =
					array(
						'articles' => array(
							'marker' => 'PRODUCT_RELATED_ARTICLES',
							'template' => 'ITEM_LIST_RELATED_ARTICLES_TEMPLATE',
							'require' => $useArticles,
							'code' => 'LISTARTICLES',
							'additionalPages' => $this->conf['pidsRelatedArticles'],
							'mergeRow' => array(),
							'functablename' => 'tt_products_articles',
							'callFunctableArray' => array()
						),
						'accessories' => array(
							'marker' => 'PRODUCT_ACCESSORY_UID',
							'template' => 'ITEM_LIST_ACCESSORY_TEMPLATE',
							'require' => TRUE,
							'code' => 'LISTACCESSORY',
							'additionalPages' => $this->conf['pidsRelatedAccessories'],
							'mergeRow' => array(),
							'functablename' => 'tt_products',
							'callFunctableArray' => array()
						),
						'products' => array(
							'marker' => 'PRODUCT_RELATED_UID',
							'template' => 'ITEM_LIST_RELATED_TEMPLATE',
							'require' => TRUE,
							'code' => 'LISTRELATED',
							'additionalPages' => $this->conf['pidsRelatedProducts'],
							'mergeRow' => array(),
							'functablename' => 'tt_products',
							'callFunctableArray' => array()
						)
					);
				break;

			case 'tx_dam':
				if (t3lib_extMgm::isLoaded('dam'))	{
					if ($uid > 0)	{
						$damext = array('tx_dam' =>
							array(
								array('uid' => $uid)
							)
						);
						$extArray = array('ext' => $damext);
					} else {
						$extArray = array();
					}
					$rc =
						array(
							'products' => array(
								'marker' => 'DAM_PRODUCTS',
								'template' => 'DAM_ITEM_LIST_TEMPLATE',
								'require' => TRUE,
								'code' => $theCode,
								'additionalPages' => FALSE,
								'mergeRow' => $extArray,
								'functablename' => 'tt_products',
								'callFunctableArray' => array($marker => 'tx_dam')
							)
						);
				}
				break;
		}

		return $rc;
	}


	public function getListMarkerArray (
		$theCode,
		$pibaseClass,
		$templateCode,
		$markerArray,
		$viewTagArray,
		$functablename,
		$uid,
		$uidArray,
		$useArticles,
		$pageAsCategory,
		$pid,
		&$error_code
	)	{
		$result = FALSE;
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$itemViewObj = &$tablesObj->get($functablename, TRUE);
		$addListArray = $this->getAddListArray($theCode, $functablename, $itemViewObj->getMarker(), $uid, $useArticles);

		if (is_array($addListArray))	{
			$listView = '';
			$itemObj = &$itemViewObj->getModelObj();

			foreach ($addListArray as $subtype => $funcArray)	{

				if (isset($viewTagArray[$funcArray['marker']]) && $funcArray['require'])	{
					$relatedIds = $itemObj->getRelated($uid, $subtype);

					if (count($relatedIds))	{
						// List all products:
						include_once (PATH_BE_ttproducts.'view/class.tx_ttproducts_list_view.php');
						if (!is_object($listView))	{

							$listView = t3lib_div::makeInstance('tx_ttproducts_list_view');
							$listView->init (
								$pibaseClass,
								$pid,
								$useArticles,
								$uidArray,
								$tmp = $this->pidListObj->getPidlist(),
								0
							);
						}
						$callFunctableArray = $funcArray['callFunctableArray'];
						$listPids = $funcArray['additionalPages'];
						if ($listPids != '')	{
							$this->pidListObj->applyRecursive($this->config['recursive'], $listPids);
						} else {
							$listPids = $this->pidListObj->getPidlist();
						}

						$tmpContent = $listView->printView (
							$templateCode,
							$funcArray['code'],
							$funcArray['functablename'],
							implode(',', $relatedIds),
							$listPids,
							$error_code,
							$funcArray['template'],
							$pageAsCategory,
							array(),
							1,
							$callFunctableArray
						);

						$result['###' . $funcArray['marker'] . '###'] = $tmpContent;
					} else {
						$result['###' . $funcArray['marker'] . '###'] = '';
					}
				} else {
					$result['###' . $funcArray['marker'] . '###'] = '';
				}
			}
		}
		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_relatedlist_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_relatedlist_view.php']);
}

?>
