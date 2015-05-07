<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Franz Holzinger <franz@ttproducts.de>
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
 * $Id:$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


require_once (PATH_t3lib.'class.t3lib_stdgraphic.php');
require_once (PATH_tslib.'class.tslib_content.php');
require_once (PATH_tslib.'class.tslib_gifbuilder.php');

require_once (PATH_BE_div2007.'class.tx_div2007_alpha.php');
require_once (PATH_BE_div2007.'class.tx_div2007_alpha5.php');

require_once (PATH_BE_ttproducts.'control/class.tx_ttproducts_control_creator.php');
require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_model_creator.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_paymentshipping.php');
require_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_image.php');
require_once (PATH_BE_ttproducts.'model/field/class.tx_ttproducts_field_price.php');
require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_image_view.php');
require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_price_view.php');
require_once (PATH_BE_ttproducts.'view/field/class.tx_ttproducts_field_note_view.php');



class tx_ttproducts_db {
	protected $extKey = TT_PRODUCTS_EXTkey;	// The extension key.
	protected $conf;			// configuration from template
	protected $config;
	protected $ajax;
	protected $LLkey;
	protected $cObj;
	public $LOCAL_LANG = Array();		// Local Language content
	public $LOCAL_LANG_charset = Array();	// Local Language content charset for individual labels (overriding)
	public $LOCAL_LANG_loaded = 0;		// Flag that tells if the locallang file has been fetch (or tried to be fetched) already.


	public function init (&$conf, &$config, &$ajax, &$pObj)	{
		$this->conf = &$conf;

		if (isset($ajax) && is_object($ajax))	{
			$this->ajax = &$ajax;

			$ajax->taxajax->registerFunction(array(TT_PRODUCTS_EXTkey.'_fetchRow',$this,'fetchRow'));
			$this->ajax->taxajax->registerFunction(array(TT_PRODUCTS_EXTkey.'_commands',$this,'commands'));
		}
		if (is_object($pObj))	{
			$this->cObj = &$pObj->cObj;
		} else {
		    $this->cObj = &t3lib_div::makeInstance('tslib_cObj');	// Local cObj.
		    $this->cObj->start(array());
		}

		$controlCreatorObj = &t3lib_div::getUserObj('&tx_ttproducts_control_creator');
		$controlCreatorObj->init($conf, $config, $pObj, $this->cObj);

		$modelCreatorObj = &t3lib_div::getUserObj('&tx_ttproducts_model_creator');
		$modelCreatorObj->init($conf, $config, $this->cObj);
	}


	public function main ()	{
	}


	public function printContent ()	{
	}


	public function &fetchRow ($data) {
		global $TYPO3_DB, $TSFE;


		$rc = '';
		$view = '';
		$rowArray = array();
		$variantArray = array();
		$theCode = 'ALL';
		$cnf = &t3lib_div::getUserObj('&tx_ttproducts_config');
		$langObj = &t3lib_div::getUserObj('&tx_ttproducts_language');
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

			// price
		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$priceObj->init(
			$this->cObj,
			$this->conf
		);
		$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$priceViewObj->init(
			$langObj,
			$this->cObj,
			$priceObj
		);
		$discount = $TSFE->fe_user->user['tt_products_discount'];

        // We put our incomming data to the regular piVars
		$itemTable = &$tablesObj->get('tt_products', FALSE);

		if (is_array($data))	{
			$useArticles = $itemTable->variant->getUseArticles();

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

							if ($useArticles == 3)	{
								$itemTable->fillVariantsFromArticles($row);
								$articleRows = $itemTable->getArticleRows(intval($row['uid']));
							}
							$rowArray[$table] = $row;
						//	$whereArticleArray = array();
							foreach ($row as $field => $v)	{
								if ($field != 'uid' && isset($dataRow[$field]))	{
									$variantArray[] = $field;
									$variantValues = t3lib_div::trimExplode(';', $v);

									$theValue = $variantValues[$dataRow[$field]];
									$rowArray[$table][$field] = $theValue;
								}
							}

							$tmpRow = $rowArray[$table];
							if ($useArticles == 1)	{
								$rowArticle = $itemTable->getArticleRow($rowArray[$table], $theCode);

							} else if ($useArticles == 3) {

								$rowArticle = $itemTable->getMatchingArticleRows($tmpRow, $articleRows);
							}

							if ($rowArticle)	{

								$itemTable->mergeAttributeFields($tmpRow, $rowArticle, FALSE,TRUE);
								$tmpRow['uid'] = $uid;
							}
							$priceTaxArray = $priceObj->getPriceTaxArray('price',$tmpRow);
							$csConvObj = &$TSFE->csConvObj;

							foreach ($priceTaxArray as $priceKey => $priceValue)	{
								$fieldname = 'price'.str_replace('_','',$priceKey);
								$tmpRow[$fieldname] = $priceValue;
							}

							if ($rowArticle)	{
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
									foreach($articleConf['fieldIndex.']['image.'] as $kImage => $vImage)	{
										$tmpDestArray[$vImage-1] = $artImageArray[$kImage-1];
									}
									$tmpRow['image'] = implode (',', $tmpDestArray);
								}
							}
							$rowArray[$table] = $tmpRow;
						} // if ($row ...)
						$TYPO3_DB->sql_free_result($res);
					}
				}
			}
			$this->ajax->setConf($data['conf']);
		}

		$rc = $this->generateResponse($view, $rowArray, $variantArray);
		return $rc;
	}


	protected function &generateResponse ($view, &$rowArray, &$variantArray)	{
		global $TSFE;

		$csConvObj = &$TSFE->csConvObj;

		$theCode = strtoupper($view);
		$langObj = &t3lib_div::getUserObj('&tx_ttproducts_language');
		$imageObj = &t3lib_div::getUserObj('&tx_ttproducts_field_image');
		$imageViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_image_view');
		$imageObj->init($this->cObj);
		$imageViewObj->init($langObj, $imageObj);

		$priceObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price');
			// price
		$priceViewObj = &t3lib_div::getUserObj('&tx_ttproducts_field_price_view');

		$priceFieldArray = $priceObj->getPriceFieldArray();
		$tableObjArray = array();
		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');

		// Instantiate the tx_xajax_response object
		$objResponse = new tx_taxajax_response($this->ajax->taxajax->getCharEncoding(), TRUE);

		foreach ($rowArray as $functablename => $row)	{ // tt-products-list-1-size
			if (!is_object($tableObjArray[$functablename]))	{
				$suffix = '-from-tt-products-articles';
			} else {
				$suffix = '';
			}

			$itemTableView = &$tablesObj->get($functablename, TRUE);
			$itemTable = &$itemTableView->getModelObj();

			$jsTableNamesId = str_replace('_','-',$functablename).$suffix;
			$uid = $row['uid'];
			foreach ($row as $field => $v)	{

				if ($field == 'additional')	{
					continue;
				}
				if (($field == 'title') || ($field == 'subtitle') || ($field == 'note') || ($field == 'note2'))	{
					$v = $csConvObj->conv($v, $TSFE->renderCharset, $this->ajax->taxajax->getCharEncoding());
					if (($field == 'note') || ($field == 'note2'))	{
						$noteObj = &t3lib_div::getUserObj('&tx_ttproducts_field_note_view');
						$classAndPath = $itemTable->getFieldClassAndPath($field);

						if ($classAndPath['class'])	{
							$tmpArray = array();
							$fieldViewObj = $itemTableView->getObj($classAndPath);
							$modifiedValue =
								$fieldViewObj->getRowMarkerArray	(
									$functablename,
									$field,
									$row,
									$tmp,
									$tmpArray,
									$tmpArray,
									$theCode,
									'',
									$tmp=FALSE,
									TRUE,
									'',
									'',
									'',
									''
								);

							if ($modifiedValue)	{
								$v = $csConvObj->conv(
									$modifiedValue,
									$TSFE->renderCharset,
									$this->ajax->taxajax->getCharEncoding()
								);
							}
						}
					}
				}
				if (!in_array($field, $variantArray))	{
					$tagId = $jsTableNamesId.'-'.$view.'-'.$uid.'-'.$field;
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

						case 'inStock':
							if ($v > 0)	{
								$objResponse->addClear('basket-into-id-'.$uid,'disabled');
							} else {
								$objResponse->addAssign('basket-into-id-'.$uid,'disabled', '1');
							}
							$objResponse->addAssign('in-stock-id-'.$uid,'innerHTML',tx_div2007_alpha5::getLL_fh002($langObj, ($v > 0 ? 'in_stock' : 'not_in_stock')));

							break;

						default:
							// nothing
							break;
					}
					if (in_array($field, $priceFieldArray))	{
						$v = $priceViewObj->priceFormat($v);
						$v = $csConvObj->conv($v, $TSFE->renderCharset, $this->ajax->taxajax->getCharEncoding());
					}
					if (is_array($v))	{
						reset($v);
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

	public function &commands ($cmd,$param1='',$param2='',$param3=''){
		$objResponse = new tx_taxajax_response($this->ajax->taxajax->getCharEncoding());

		switch ($cmd) {
			default:
				$hookVar = 'ajaxCommands';
				if ($hookVar && is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar])) {
					foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar] as $classRef) {
						$hookObj= &t3lib_div::getUserObj($classRef);
						if (method_exists($hookObj, 'init')) {
							$hookObj->init($this);
						}
						if (method_exists($hookObj, 'commands')) {
							$tmpArray = $hookObj->commands($cmd,$param1,$param2,$param3, $objResponse);
						}
					}
				}
			break;
		}

		return $objResponse->getXML();
	}


	public function destruct () {
		$controlCreatorObj = &t3lib_div::getUserObj('&tx_ttproducts_control_creator');
		$controlCreatorObj->destruct();

		$modelCreatorObj = &t3lib_div::getUserObj('&tx_ttproducts_model_creator');
		$modelCreatorObj->destruct();

		$tablesObj = &t3lib_div::getUserObj('&tx_ttproducts_tables');
		$tablesObj->destruct();
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/eid/class.tx_ttproducts_db.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/eid/class.tx_ttproducts_db.php']);
}

?>
