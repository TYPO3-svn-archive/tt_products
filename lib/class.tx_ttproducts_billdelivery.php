<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger <franz@ttproducts.de>
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
 * bill and delivery functions
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



// require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_marker.php');
// require_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_subpartmarker.php');

class tx_ttproducts_billdelivery {
	public $cObj;
	public $conf;		  // original configuration
	public $config;		// updated configuration
	public $tableArray;
	public $price;		 // object for price functions
	public $typeArray = array('bill', 'delivery');


	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basketObj is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string	  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	public function init ($cObj) {
		global $TSFE;

		$this->cObj = $cObj;
		$cnf = t3lib_div::getUserObj('&tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
	}


	public function getTypeArray ()	{
		return $this->typeArray;
	}


	/**
	 * get the relative filename of the bill or delivery file by the tracking code
	 */
	public function getRelFilename ($tracking, $type)	{
		$rc = $this->conf['outputFolder'] . '/' . $type . '/' . $tracking . '.htm';

		return $rc;
	}


	public function getMarkerArray (&$markerArray, $tracking, $type)	{
		$markerprefix = strtoupper($type);
		$relfilename = $this->getRelFilename($tracking, $type);
		$markerArray['###'.$markerprefix.'_FILENAME###'] = $relfilename;
	}


	public function writeFile ($type, $tracking, $content)	{

		$relfilename = $this->getRelFilename($tracking, $type);
		$filename = t3lib_div::getFileAbsFileName($relfilename);

		$theFile = fopen($filename, 'wb');
		fwrite($theFile, $content);
		fclose($theFile);
	}


	/**
	 * Bill,Delivery Generation from tracking code
	 */
	public function getInformation ($theCode, $orderRow, $templateCode, $tracking, $type)	{
		/*
		Bill or delivery information display, which needs tracking code to be shown
		This is extension information to tracking at another page
		See Tracking for further information
		*/
		global $TSFE;

		$priceObj = t3lib_div::getUserObj('&tx_ttproducts_field_price');
		$basketObj = t3lib_div::getUserObj('&tx_ttproducts_basket');
		$tablesObj = t3lib_div::getUserObj('&tx_ttproducts_tables');
		$priceViewObj = t3lib_div::getUserObj('&tx_ttproducts_field_price_view');
		$subpartmarkerObj = t3lib_div::getUserObj('&tx_ttproducts_subpartmarker');
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();

			// initialize order data.
		$orderData = unserialize($orderRow['orderData']);
		$markerArray = array();
		$subpartArray = array();
		$wrappedSubpartArray = array();

		$tmp = $orderData['itemArray'];
		$version = $orderData['version'];
		if (version_compare($version, '2.5.0', '>=') && is_array($tmp))	{
			$tableName = key($tmp);
			$itemArray = current($tmp);
		} else {
			$itemArray = (is_array($tmp) ? $tmp : array());
		}

		$tmp = $orderData['calculatedArray'];
		$calculatedArray = ($tmp ? $tmp : array());

		if ($type == 'bill') {
			$subpartMarker='###BILL_TEMPLATE###';
		} else {
			$subpartMarker='###DELIVERY_TEMPLATE###';
		}

			// Getting subparts from the template code.
		$t=array();
			// If there is a specific section for the billing address if user is logged in (used because the address may then be hardcoded from the database
		$t['orderFrameWork'] = $this->cObj->getSubpart($templateCode,$subpartmarkerObj->spMarker($subpartMarker));
		$t['orderFrameWork'] = &$this->cObj->substituteMarkerArray($t['orderFrameWork'],$globalMarkerArray);
		$t['categoryFrameWork'] = $this->cObj->getSubpart($t['orderFrameWork'],'###ITEM_CATEGORY###');
		$t['itemFrameWork'] = $this->cObj->getSubpart($t['orderFrameWork'],'###ITEM_LIST###');
		$t['item'] = $this->cObj->getSubpart($t['itemFrameWork'],'###ITEM_SINGLE###');
		$categoryQty = array();
		$categoryArray = array();

	//	  $countTotal = 0;
	//
		// Calculate quantities for all categories

		// loop over all items in the ordered items indexed by sorting text
		foreach ($itemArray as $sort=>$actItemArray) {
			foreach ($actItemArray as $k1=>$actItem) {
				$currentCategory=$actItem['rec']['category'];
				$categoryArray[$currentCategory] = 1;
				$categoryQty[$currentCategory] += $actItem['count'];
			}
		}

		$itemsOut='';
		$out='';

		$itemViewTable = $tablesObj->get('tt_products', TRUE);
		$itemTable = $itemViewTable->getModelObj();
		$articleViewObj = $tablesObj->get('tt_products_articles', TRUE);
		$articleTable = $articleViewObj->getModelObj();
		$viewTagArray = array();
		$parentArray = array();
		$markerObj = t3lib_div::getUserObj('&tx_ttproducts_marker');
		$markerFieldArray = array('BULKILY_WARNING' => 'bulkily',
			'PRODUCT_SPECIAL_PREP' => 'special_preparation',
			'PRODUCT_ADDITIONAL_SINGLE' => 'additional',
			'PRODUCT_LINK_DATASHEET' => 'datasheet');
		$fieldsArray = $markerObj->getMarkerFields(
			$t['item'],
			$itemTable->getTableObj()->tableFieldArray,
			$itemTable->getTableObj()->requiredFieldArray,
			$markerFieldArray,
			$itemTable->marker,
			$viewTagArray,
			$parentArray
		);

		if ($this->conf['useArticles'] == 1 || $this->conf['useArticles'] == 3) {
			$markerFieldArray = array();
			$articleViewTagArray = array();
			$articleParentArray = array();
			$articleFieldsArray = $markerObj->getMarkerFields(
				$t['item'],
				$itemTable->getTableObj()->tableFieldArray,
				$itemTable->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$articleTable->marker,
				$articleViewTagArray,
				$articleParentArray
			);
		}

		$count = 0;
		foreach ($categoryArray as $currentCategory=>$value)	{
			$categoryChanged = 1;
			// loop over all ordered items indexed by a sorting text
			foreach ($itemArray as $sort=>$actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {

					$count++;
					$row = $actItem['rec'];

					// $itemTable->mergeAttributeFields($row, $articleRow, FALSE);
					$itemTable->variant->modifyRowFromVariant($row);

					// $row = $itemTable->getRowFromExt('tt_products', $row, $this->conf['useArticles']);
// 					$theItem = $basketObj->getItem($row, 'useExt');
// 					$row = $theItem['rec'];

						// Print Category Title
					if ($row['category']==$currentCategory)	{
						if ($categoryChanged == 1)	{
							$markerArray=array();
							$tmpCategory = $tablesObj->get('tt_products_cat')->get($currentCategory);
							$catTitle= ($tmpCategory ? $tmpCategory['title']: '');

							$this->cObj->setCurrentVal($catTitle);
							$markerArray['###CATEGORY_TITLE###'] = $this->cObj->cObjGetSingle($this->conf['categoryHeader'],$this->conf['categoryHeader.'], 'categoryHeader');
							$markerArray['###CATEGORY_QTY###'] = $categoryQty[$currentCategory];
							$categoryPriceTax = $calculatedArray['categoryPriceTax']['goodstotal'][$currentCategory];
							$markerArray['###PRICE_GOODS_TAX###'] = $priceViewObj->priceFormat($categoryPriceTax);
							$categoryPriceNoTax = $calculatedArray['categoryPriceNoTax']['goodstotal'][$currentCategory];
							$markerArray['###PRICE_GOODS_NO_TAX###'] = $priceViewObj->priceFormat($categoryPriceNoTax);
							$markerArray['###PRICE_GOODS_ONLY_TAX###'] = $priceViewObj->priceFormat($categoryPriceTax - $categoryPriceNoTax);

							$out2 = $this->cObj->substituteMarkerArray($t['categoryFrameWork'], $markerArray);
							$out .= $out2;
						}

						// Print Item Title
						$wrappedSubpartArray=array();
						$markerArray = array();
						$subpartArray = array();
						$itemViewTable->getModelMarkerArray (
							$row,
							'',
							$markerArray,
							$catTitle,
							1,
							'image',
							$viewTagArray,
							array(),
							$theCode,
							$count
						);

						$variantFieldArray = $itemTable->variant->getFieldArray();
						$marker = $itemViewTable->getMarker();

						foreach($variantFieldArray as $k2 => $field)	{
							$tmpkey = '###' . $marker . '_' . strtoupper($field) . '###';
							$markerArray[$tmpkey] = $row[$field];
						}

						$prodVariantRow = $row;

						if ($this->conf['useArticles'] == 1 || $this->conf['useArticles'] == 3) {

							$articleRow = $itemTable->getArticleRowFromExt($row);

							if ($articleRow)	{
								$bKeepNotEmpty = FALSE;
								if ($this->conf['useArticles'] == 3)	{
									$itemTable->fillVariantsFromArticles($prodVariantRow);
									$itemTable->variant->modifyRowFromVariant($prodVariantRow);
									// $bKeepNotEmpty = TRUE;
								}
								$itemTable->mergeAttributeFields($prodVariantRow, $articleRow, $bKeepNotEmpty, TRUE);
							} else {
								$variant = $itemTable->variant->getVariantFromRow($row);
								$itemTable->variant->modifyRowFromVariant($prodVariantRow, $variant);
							}
								// use the fields of the article instead of the product
							//
							$prodVariantItem['rec'] = $prodVariantRow;
							$articleViewObj->getModelMarkerArray (
								$prodVariantRow,
								'',
								$markerArray,
								$catTitle,
								$this->config['limitImage'],
								'basketImage',
								$articleViewTagArray,
								$tmp=array(),
								$theCode,
								$count,
								'',
								'',
								TRUE,
								$TSFE->renderCharset
							);

							$articleViewObj->getItemMarkerSubpartArrays (
								$t['item'],
								$articleViewObj->getModelObj()->getFuncTablename(),
								$prodVariantRow,
								$markerArray,
								$subpartArray,
								$wrappedSubpartArray,
								$articleViewTagArray,
								$theCode
							);
						}

						$itemViewTable->variant->removeEmptyMarkerSubpartArray(
							$markerArray,
							$subpartArray,
							$wrappedSubpartArray,
							$row,
							$this->conf,
							$itemTable->hasAdditional($row, 'isSingle'),
							!$itemTable->hasAdditional($row, 'noGiftService')
						);


						$markerArray['###PRICE_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($actItem['priceTax'], $taxInclExcl));
						$markerArray['###PRICE_NO_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($actItem['priceNoTax'], $taxInclExcl));
						$markerArray['###PRICE_ONLY_TAX###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($actItem['priceTax'] - $actItem['priceNoTax']));

						$markerArray['###FIELD_QTY###'] = $actItem['count'];
						$markerArray['###PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($actItem['totalTax']);
						$markerArray['###PRICE_TOTAL_NO_TAX###'] = $priceViewObj->priceFormat($actItem['totalNoTax']);
						$markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($actItem['totalTax'] - $actItem['totalNoTax']);
						$itemsOut = $this->cObj->substituteMarkerArrayCached($t['item'], $markerArray, $subpartArray, $wrappedSubpartArray);

						if ($itemsOut) {
							$out2 =$this->cObj->substituteSubpart($t['itemFrameWork'], '###ITEM_SINGLE###', $itemsOut);
							$out .= $out2;
						}
						$itemsOut='';		// Clear the item-code var
						$categoryChanged = 0;
					}
				}
			}
		}

		$subpartArray['###ITEM_CATEGORY_AND_ITEMS###'] = $out;

			// Final things
			// Personal and delivery info:

		$orderData['billing']['salutation'] = tx_div2007_alpha5::getLL_fh002($langObj, 'salutation' . $orderData['billing']['salutation']);
		$orderData['delivery']['salutation'] = tx_div2007_alpha5::getLL_fh002($langObj, 'salutation'.$orderData['delivery']['salutation']);

		$infoFields = explode(',','feusers_uid,name,cnum,first_name,last_name,company,salutation,address,telephone,fax,email,company,city,zip,state,country');
		  // Fields...
		foreach($infoFields as $fName)	{
			$markerArray['###PERSON_'.strtoupper($fName).'###'] = $orderData['billing'][$fName];
			$markerArray['###DELIVERY_'.strtoupper($fName).'###'] = $orderData['delivery'][$fName];
		}
		$staticInfo = tx_ttproducts_static_info::getStaticInfo();

		if (is_object($staticInfo) && isset($orderData['billing']['country_code'])) {
			$markerArray['###PERSON_COUNTRY###'] =
			$staticInfo->getStaticInfoName('COUNTRIES', $orderData['billing']['country_code'],'','');
		}
		if (is_object($staticInfo) && isset($orderData['delivery']['country_code'])) {
			$markerArray['###DELIVERY_COUNTRY###'] =
			$staticInfo->getStaticInfoName('COUNTRIES', $orderData['delivery']['country_code'],'','');
		}

		$markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
		$markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);
		$temp = explode(' ', $orderRow['payment']);
		$markerArray['###PAYMENT_TITLE###'] = $temp[1];
		$markerArray['###PRICE_PAYMENT_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['payment']);
		$markerArray['###PRICE_PAYMENT_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceNoTax']['payment']);
		$temp = explode(' ', $orderRow['shipping']);
		$markerArray['###SHIPPING_TITLE###'] = $temp[1];
		$markerArray['###SHIPPING_WEIGHT###'] = doubleval($calculatedArray['weight']);
		$markerArray['###PRICE_SHIPPING_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['shipping']);
		$markerArray['###PRICE_SHIPPING_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceNoTax']['shipping']);
		$markerArray['###PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['total']);
		$markerArray['###PRICE_TOTAL_NO_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceNoTax']['total']);
		$markerArray['###PRICE_TOTAL_ONLY_TAX###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['total']-$calculatedArray['priceNoTax']['total']);

		$taxRateArray = t3lib_div::trimExplode(',', $this->conf['TAXrates']);
		if (isset($taxRateArray) && is_array($taxRateArray))	{
			foreach ($taxRateArray as $k => $taxrate)	{
				$taxstr = strval(number_format($taxrate,2));
				$label = chr(ord('A') + $k);
				$markerArray['###PRICE_TAXRATE_NAME' . ($k + 1) . '###'] = $label;
				$markerArray['###PRICE_TAXRATE_TAX' . ($k + 1) . '###'] = $taxrate;
				$label = $priceViewObj->priceFormat($calculatedArray['priceNoTax']['sametaxtotal'][$taxstr]);
				$markerArray['###PRICE_TAXRATE_GOODSTOTAL' . ($k + 1) . '###'] = $label;
				$label = $priceViewObj->priceFormat($calculatedArray['priceNoTax']['sametaxtotal'][$taxstr] * ($taxrate / 100));
				$markerArray['###PRICE_TAXRATE_ONLY_TAX' . ($k + 1) . '###'] = $label;
			}
		}
		$markerArray['###PRICE_VOUCHERTOTAL_TAX###'] = $calculatedArray['priceTax']['vouchertotal'];
		$markerArray['###PRICE_VOUCHERTOTAL_NO_TAX###'] = $calculatedArray['priceNoTax']['vouchertotal'];

			// Delivery note.
		$markerArray['###DELIVERY_NOTE###'] = $orderData['delivery']['note'];
		$markerArray['###DELIVERY_NOTE_DISPLAY###'] = nl2br($orderData['delivery']['note']);
		$markerArray['###DELIVERY_GIFT_SERVICE###'] = $orderData['delivery']['giftservice'];
		$markerArray['###DELIVERY_GIFT_SERVICE_DISPLAY###'] = nl2br($orderData['delivery']['giftservice']);
		$markerArray['###DELIVERY_RADIO1_1###'] = ($orderData['delivery']['radio1'] == '1' ? 'checked ' : '');
		$markerArray['###DELIVERY_RADIO1_2###'] = ($orderData['delivery']['radio1'] == '2' ? 'checked ' : '');

			// Desired delivery date.
		$markerArray['###DELIVERY_DESIRED_DATE###'] = $orderData['delivery']['desired_date'];
		$markerArray['###DELIVERY_DESIRED_TIME###'] = $orderData['delivery']['desired_time'];
		$markerArray['###DELIVERY_DATE_OF_BIRTH###'] = $orderData['delivery']['date_of_birth'];
		$orderObj = $tablesObj->get('sys_products_orders');
		$markerArray['###ORDER_UID###'] = $orderObj->getNumber($orderRow['uid']);
		$markerArray['###ORDER_DATE###'] = $this->cObj->stdWrap($orderRow['crdate'], $this->conf['orderDate_stdWrap.']);

		$markerArray['###FE_USER_UID###'] = $orderRow['feusers_uid'];
		$cnum = 'XXX';

		if ($orderRow['feusers_uid'] > 0) {
			$feuserRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'fe_users', 'uid=' . intval($orderRow['feusers_uid']));

			if (isset($feuserRow) && is_array($feuserRow)) {
				$cnum = $feuserRow['0']['cnum'];
			}
		}
		$markerArray['###FE_USER_CNUM###'] = $cnum;

		$content = $this->cObj->substituteMarkerArrayCached($t['orderFrameWork'], $markerArray, $subpartArray);

// 		$relfilename = $this->getRelFilename($tracking, $type);
// 		$filename = t3lib_div::getFileAbsFileName($relfilename);
//
// 		$theFile = fopen($filename, 'wb');
// 		fwrite($theFile, $content);
// 		fclose($theFile);
		return $content;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_billdelivery.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_billdelivery.php']);
}


?>