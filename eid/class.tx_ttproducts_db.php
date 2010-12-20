<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger <contact@fholzinger.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
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
 * Part of the tt_products (Shop System) extension.
 *
 * main class for eID AJAX function to change the values of records for the
 * variant select box
 *
 * $Id$
 *
 * @author  Franz Holzinger <contact@fholzinger.com>
 * @maintainer	Franz Holzinger <contact@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


require_once (PATH_t3lib.'class.t3lib_stdgraphic.php');
require_once (PATH_tslib.'class.tslib_content.php');
require_once (PATH_tslib.'class.tslib_gifbuilder.php');

require_once(PATH_BE_div2007.'class.tx_div2007_alpha.php');
require_once(PATH_BE_div2007.'class.tx_div2007_ff.php');

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_language.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_config.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_tables.php');
require_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_image.php');
require_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_price.php');
require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_image_view.php');
require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_price_view.php');


class tx_ttproducts_db {
	var $extKey = TT_PRODUCTS_EXTkey;	// The extension key.
	var $conf;				// configuration from template
	var $config;
	var $ajax;
	var $LLkey;
	var $cObj;

	/**
	 * initialization
	 *
	 * @param	array	$conf: setup configuration
	 * @param	array	$config: internal configuration
	 * @param	object	$ajax: tx_ttproducts_ajax
	 * @return	void
	 */
	function init(&$conf, &$config, &$ajax)	{
		$this->conf = &$conf;
		$this->ajax = &$ajax;

		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$cnf->init(
			$conf,
			$config
		);

		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

		$tablesObj->init($this);
		$ajax->taxajax->registerFunction(array(TT_PRODUCTS_EXTkey.'_fetchRow',&$this,'fetchRow'));
		tx_div2007_alpha::loadLL_fh001($this,'EXT:'.TT_PRODUCTS_EXTkey.'/pi1/locallang.xml');

		$this->cObj = t3lib_div::makeInstance('tslib_cObj');	// Local cObj.
		$this->cObj->start(array());
	}

	/**
	 * main function
	 *
	 * @return	void
	 */
	function main()	{
	}

	/**
	 * output of content
	 *
	 * @return	void
	 */
	function printContent()	{
	}

	/**
	 * fetches the table rows
	 *
	 * @param	array		$data: information received from Ajax
	 * @return	string		answer to XML httpRequest
	 */
	function &fetchRow($data) {
		global $TYPO3_DB, $TSFE;

		$rc = '';
		$view = '';
		$rowArray = array();
		$variantArray = array();
		$theCode = 'ALL';
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');

			// price
		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$priceObj->init(
			$this->cObj,
			$cnf->conf
		);
		$discount = $TSFE->fe_user->user['tt_products_discount'];

        // We put our incomming data to the regular piVars

		if (is_array($data))	{

			foreach ($data as $k => $dataRow)	{

				if ($k == 'view')	{
					$view = $dataRow;
					$theCode = strtoupper($view);
				} else if(is_array($dataRow))	{
					$table = $k;
					$uid = $dataRow['uid'];
					if ($uid)	{
						$enableFields = ' AND deleted="0" AND hidden="0"';
						$where = 'uid = '.intval($uid).$enableFields;
						$res = $TYPO3_DB->exec_SELECTquery('*', $table, $where);
						if ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
							$rowArray[$table] = $row;
							$whereArticle = 'uid_product = '.intval($uid);
							foreach ($row as $field => $v)	{
								if ($field != 'uid' && isset($dataRow[$field]))	{
									$variantArray [] = $field;
									$variantValues = t3lib_div::trimExplode(';', $v);
									$theValue = $variantValues[$dataRow[$field]];
									$rowArray[$table][$field] = $theValue;
									$whereArticle .= ' AND '.$field.' = '. $TYPO3_DB->fullQuoteStr($theValue, 'tt_products_articles').$enableFields;
								}
							}
							$resArticle = $TYPO3_DB->exec_SELECTquery('*', 'tt_products_articles', $whereArticle);
							if ($rowArticle = $TYPO3_DB->sql_fetch_assoc($resArticle))	{

								$tmpRow = array_merge($rowArray[$table], $rowArticle);
								$tmpRow['uid'] = $uid;
								$priceFieldArray = array('price', 'price2');
								foreach ($priceFieldArray as $priceField)	{
									$priceArray = $priceObj->getPriceArray($priceField,$tmpRow);
									foreach ($priceArray as $displayTax => $priceValue)	{
										$fieldname = $priceField.str_replace('_','',$displayTax);
										$tmpRow[$fieldname] = round ($priceValue, 2);
									}
								}
								$tmpRow['price'] = $priceObj->getDiscountPrice($tmpRow['price'], $discount);

								if (!$rowArticle['image'])	{
									$rowArticle['image'] = $rowArray[$table]['image'];
									$tmpRow['image'] = $rowArticle['image'];
								}

								$articleConf = $cnf->getTableConf('tt_products_articles', $theCode);
								if (
									isset($articleConf['fieldIndex.']) && is_array($articleConf['fieldIndex.']) &&
									isset($articleConf['fieldIndex.']['image.']) && is_array($articleConf['fieldIndex.']['image.'])
								)	{
									$prodImageArray = t3lib_div::trimExplode(',',$rowArray[$table]['image']);
									$artImageArray = t3lib_div::trimExplode(',',$rowArticle['image']);
									$tmpDestArray = $prodImageArray;
									foreach ($articleConf['fieldIndex.']['image.'] as $kImage => $vImage)	{
										$tmpDestArray[$vImage-1] = $artImageArray[$kImage-1];
									}
									$tmpRow['image'] = implode (',', $tmpDestArray);
								}
								$rowArray[$table] = $tmpRow;
							} else {
								$rowArray[$table]['pricetax'] = round($priceObj->getDiscountPrice($rowArray[$table]['price'], $discount),2); // TODO

								$rowArray[$table]['price2tax'] = round( $rowArray[$table]['price2'],2);
							}
							$TYPO3_DB->sql_free_result($resArticle);
						}
						$TYPO3_DB->sql_free_result($res);
					}
				}
			}
			$this->ajax->setConf($data['conf']);
		}
		$rc = $this->generateResponse($view, $rowArray, $variantArray);
		return $rc;
	}

	/**
	 * generate response to XML HttpRequest
	 *
	 * @param	string		$view: plugin's code
	 * @param	array		$rowArray: records of the found product and articles
	 * @param	array		$variantArray
	 * @return	string		response in XML format
	 */
	function &generateResponse($view, &$rowArray, &$variantArray)	{
		global $TSFE;

		$charset = $TSFE->renderCharset;
		$theCode = strtoupper($view);
		$imageObj = &t3lib_div::getUserObj('&tx_ttproducts_field_image');
		$imageViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_image_view');
		$langObj = t3lib_div::makeInstance('tx_ttproducts_language');	// language object which replaces pibase
		$langObj->init($this->cObj, $this->conf);
		$imageObj->init($this->cObj);
		$imageViewObj->init ($langObj, $imageObj);

		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');
			// price
		$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$priceViewObj->init(
			$langObj,
			$this->cObj,
			$priceObj
		);

		$priceFieldArray = $priceObj->getPriceFieldArray();
		$tableObjArray = array();
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$objResponse = new tx_taxajax_response('iso-8859-1', true);

		foreach ($rowArray as $functablename => $row)	{ // tt-products-list-1-size
			if (!is_object($tableObjArray[$functablename]))	{
				$tableObjArray[$functablename] = $tablesObj->get ('tt_products_articles');
				$tablename = 'tt_products_articles';
			} else {
				$tablename = $functablename;
			}

			$jsTableName = str_replace('_','-',$tablename);
			$uid = $row['uid'];
			foreach ($row as $field => $v)	{

				if ($field == 'additional')	{
					continue;
				}

				if (($field == 'title') || ($field == 'note') || ($field == 'note2'))	{
					$v = htmlentities($v,ENT_QUOTES,$charset);
				}

				if (!in_array($field, $variantArray))	{
					$tagId = $jsTableName.'-'.$view.'-'.$uid.'-'.$field;
					switch ($field)	{
						case 'image': // $this->cObj
							$imageRenderObj = 'image';
							if ($theCode == 'LIST')	{
								$imageRenderObj = 'listImage';
							}
							$imageArray = $imageObj->getImageArray($row, 'image');
							$dirname = $imageObj->getDirname($row);
							$theImgDAM = array();
							$markerArray = array();
							$linkWrap = '';
							$imgCodeArray = $imageViewObj->getCodeMarkerArray(
								'tt_products_articles',
								'ARTICLE_IMAGE',
								$theCode,
								$row,
								$imageArray,
								$dirname,
								10,
								$imageRenderObj,
								$linkWrap,
								$markerArray,
								$theImgDAM,
								$specialConf = array()
							);
							$v = $imgCodeArray;
							break;
						default:
							// nothing
							break;
					}
					if (in_array($field, $priceFieldArray))	{
						$v = $priceViewObj->priceFormat($v);
					}
					if (is_array($v))	{
						reset ($v);
						$vFirst = current($v);
						$objResponse->addAssign($tagId,'innerHTML', $vFirst);
						$c = 0;
						foreach ($v as $k => $v2)	{
							$c++;
							$tagId2 = $tagId.'-'.$c;
							$objResponse->addAssign($tagId2,'innerHTML', $v2);
						}
					} else {
						$objResponse->addAssign($tagId,'innerHTML', $v);
					}
				}
			}
		}

		$rc = &$objResponse->getXML();
		return $rc;
	}
}


?>
