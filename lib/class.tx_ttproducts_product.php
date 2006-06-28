<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Part of the tt_products (Shopping System) extension.
 *
 * functions for the product
 *
 * $Id$
 *
 * @author  Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_article_base.php');



class tx_ttproducts_product extends tx_ttproducts_article_base {
	var $dataArray; // array of read in products
	var $table;		 // object of the type tx_table_db
	var $fields = array();
	var $bIsProduct=true;	// if this is the base for a product
	var $marker = 'PRODUCT';
	var $type = 'product';
	var $tableconf;


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init(&$pibase, &$cnf, &$tt_content, $LLkey, $tablename, &$tableconf, &$prodconf, $useArticles)  {
		global $TYPO3_DB,$TSFE,$TCA;

		$this->cnf = &$cnf;
		$tablename = ($tablename ? $tablename : 'tt_products');
		$this->tableconf = $this->cnf->getTableConf($tablename);
//		if ($this->tableconf['orderBy'] == '{$plugin.tt_products.orderBy}')	{
//			$this->tableconf['orderBy'] = '';
//		}
		$this->table = t3lib_div::makeInstance('tx_table_db');
		
		$tableConfig = array();
		$tableConfig['orderBy'] = $conf['orderBy'];
		
		if (!$tableConfig['orderBy'])	{
			 $tableConfig['orderBy'] = $this->tableconf['orderBy'];				
		}

		$this->table->setConfig($tableConfig);
		$this->table->addDefaultFieldArray(array('sorting' => 'sorting'));
		$this->table->setTCAFieldArray($tablename, 'products');
		
		$requiredFields = 'uid,pid,category,price,price2,tax,inStock';
		if ($this->tableconf['requiredFields'])	{
			$tmp = $this->tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}	
		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->table->setRequiredFieldArray($requiredListArray);
		if (is_array($this->tableconf['language.']) &&
			$this->tableconf['language.']['type'] == 'field' &&
			is_array($this->tableconf['language.']['field.'])
			)	{
			$addRequiredFields = array();
			$addRequiredFields[] = $this->tableconf['language.']['field.'];
			$this->table->addRequiredFieldArray ($addRequiredFields);
		}
	
		if ($cnf->bUseLanguageTable($this->tableconf))	{
			$this->table->setLanguage ($LLkey);
			$this->table->setLangName('tt_products_language');
			$this->table->setTCAFieldArray($this->table->langname);
		}

		if ($this->tableconf['language.'] && $this->tableconf['language.']['type'] == 'csv')	{
			$this->table->initLanguageFile($this->tableconf['language.']['file']);
		}
		
		parent::init($pibase, $cnf, $tt_content);
		$this->variant = t3lib_div::makeInstance('tx_ttproducts_variant');
		$this->variant->init($this->pibase, $tableconf['variant.'], $this, $useArticles);
		$this->fields['itemnumber'] = ($tableconf['itemnumber'] ? $tableconf['itemnumber'] : 'itemnumber');
	} // init


	function get ($uid) {
		global $TYPO3_DB;
		$rc = $this->dataArray[$uid];
		if (!$rc && $uid) {
//			$sql = t3lib_div::makeInstance('tx_table_db_access');
//			$sql->prepareFields($this->table, 'select', '*');
//			$sql->prepareFields($this->table, 'where', 'uid = '.$uid);
//			$sql->prepareWhereFields ($this->table, 'uid', '=', $uid)
			$where = '1=1 '.$this->table->enableFields();
			// Fetching the products
			// $res = $sql->exec_SELECTquery();
			$res = $this->table->exec_SELECTquery('*', $where.'AND uid = '.intval($uid));
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$rc = $this->dataArray[$row['uid']] = $row;
		}
		return $rc;
	}


	// returns the Path of all categories above, separated by '/'
	function getPath ($uid) {
		$rc = '';
		
		return $rc;
	}


	/**
	 * Reduces the instock value of the orderRecord with the sold items and returns the result
	 * 
	 */
	function reduceInStock(&$itemArray, $useArticles)	{
		global $TYPO3_DB, $TCA;
		$rc = '';

		if ($this->table->name == 'tt_products' || is_array(($TCA[$this->table->name]['columns']['inStock'])) )	{		
			// Reduce inStock
	
			// loop over all items in the basket indexed by a sorting text
			foreach ($itemArray as $sort=>$actItemArray) {
				if ($useArticles) {
					foreach ($actItemArray as $k1=>$actItem) {
						$query='uid_product=\''.intval($actItem['rec']['uid']).'\' AND color='.$TYPO3_DB->fullQuoteStr($actItem['rec']['color'],'tt_products_articles').' AND size='.$TYPO3_DB->fullQuoteStr($actItem['rec']['size'],'tt_products_articles').' AND description='.$TYPO3_DB->fullQuoteStr($actItem['rec']['description'],'tt_products_articles').' AND gradings='.$TYPO3_DB->fullQuoteStr($actItem['rec']['gradings'],'tt_products_articles');	
						$res = $TYPO3_DB->exec_SELECTquery('inStock', 'tt_products_articles', $query);
							//  TODO: Saving the order record support color, size, description and gradings here
					}
				} else {
					foreach ($actItemArray as $k1=>$actItem) {
						$query='uid=\''.intval($actItem['rec']['uid']).'\'';

						$res = $TYPO3_DB->exec_SELECTquery('inStock', $this->table->name, $query);

						if ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
							if ($row['inStock'] > 0) {
								$newInStock = intval($row['inStock'])-intval($actItem['count']);
								if ($newInStock < 0) {
									$newInStock = 0;
								}

								$fieldsArray =array();
										// Setting tstamp, deleted and tracking code
								$fieldsArray['inStock']=$newInStock;

								$res = $TYPO3_DB->exec_UPDATEquery($this->table->name, 'uid='.intval($actItem['rec']['uid']), $fieldsArray);
							}
						}
					}
				}
			}
		}
		return $rc;
	}


	/**
	 * Returns true if the item has the $check value checked
	 * 
	 */
	function hasAdditional(&$row, $check)  {
		$hasAdditional = false;
		$additional = t3lib_div::xml2array($row['additional']);
		$hasAdditional = $this->pibase->pi_getFFvalue($additional, $check);
		return $hasAdditional; 
	}



	/**
	 * Generates a search where clause.
	 */
	function searchWhere(&$searchFieldList, $sw)	{
		$where=$this->pibase->cObj->searchWhere($sw, $searchFieldList, $this->table->getAliasName());
		return $where;
	} // searchWhere


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function getItemMarkerArray (&$item, &$markerArray, $catTitle, &$basketExt, $imageNum=0, $imageRenderObj='image', &$tagArray, $forminfoArray=array(), $code='', $id='1')	{
			// Returns a markerArray ready for substitution with information for the tt_producst record, $row
		$row = &$item['rec'];
		parent::getItemMarkerArray($item, $markerArray, $catTitle, $basketExt, $imageNum, $imageRenderObj, $tagArray, $forminfoArray, $code, $id);
		
		
			// Subst. fields
		$markerArray['###PRODUCT_UNIT###'] = $row['unit'];
		$markerArray['###PRODUCT_UNIT_FACTOR###'] = $row['unit_factor'];
		$iconImgCode = $this->pibase->cObj->IMAGE($this->conf['datasheetIcon.']);
		$markerArray['###ICON_DATASHEET###'] = $iconImgCode;
		$markerArray['###PRODUCT_WWW###'] = $row['www'];
		$markerArray['###CATEGORY_TITLE###'] = $catTitle;

//		$markerArray["###FIELD_NAME###"]="recs[tt_products][".$row["uid"]."]";

		$markerArray['###FIELD_NAME_BASKET###'] = 'ttp_basket['.$row['uid'].']['.md5($row['extVars']).']';
		$markerArray['###FIELD_ID###'] = TT_PRODUCTS_EXTkey.'_'.strtolower($code).'_id_'.$id;
		$markerArray['###BULKILY_WARNING###'] = $row['bulkily'] ? $this->conf['bulkilyWarning'] : '';

		if ($row['special_preparation'])	{
			$markerArray['###PRODUCT_SPECIAL_PREP###'] = $this->conf['specialPreparation'];
		} else	{
			$markerArray['###PRODUCT_SPECIAL_PREP###'] = '';
		}

		// Fill the Currency Symbol or not
		if ($this->conf['itemMarkerArrayFunc'])	{
			$markerArray = $this->pibase->userProcess('itemMarkerArrayFunc',$markerArray);
		}
		
	} // getItemMarkerArray


	function addWhereCat($cat, $pid_list)	{
		$where = '';
		if($cat) {
			$cat = implode(',',t3lib_div::intExplode(',', $cat));
			$where = ' AND ( category IN ('.$cat.')';
		}

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addWhereCat')) {
					$whereNew = $hookObj->addWhereCat($this, $cat, $where, $pid_list);
					$where .= ($whereNew ? ' OR '.$whereNew : '');
				}
			}
		}

		if ($where)	{
			$where .= ' )';
		}
		return $where;
	}


	function addselectConfCat($cat, &$selectConf)	{
		$tableNameArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'addselectConfCat')) {
					$tableNameArray[] = $hookObj->addselectConfCat($this, $cat, $selectConf);
				}
			}
		}
		
		return implode(',', $tableNameArray);	
	}


	function getPageUidsCat($cat)	{
		$uidArray = array();

			// Call all addWhere hooks for categories at the end of this method
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey]['prodCategory'] as $classRef) {
				$hookObj= &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'getPageUidsCat')) {
					$hookObj->getPageUidsCat($this, $cat, $uidArray);
				}
			}
		}

		$uidArray = array_unique($uidArray);
		return (implode(',',$uidArray));
	}

	function getProductField(&$row, $field)	{
		return $row[$field];
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_product.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_product.php']);
}


?>
