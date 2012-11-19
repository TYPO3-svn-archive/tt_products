<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2007 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Part of the tt_products (Shop System) extension.
 *
 * credit card functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @maintainer	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


global $TYPO3_CONF_VARS;


class tx_ttproducts_card {
	var $pibase; // reference to object of pibase
	var $conf;
	var $ccArray;	// credit card data
	var $allowedArray = array(); // allowed uids of credit cards
	var $fieldArray = array('cc_type', 'cc_number_1','cc_number_2','cc_number_3', 'cc_number_4', 'owner_name', 'cvv2', 'endtime_mm', 'endtime_yy');
	var $sizeArray = array('cc_type' => 4, 'cc_number_1' => 4,'cc_number_2' => 4,'cc_number_3' => 4, 'cc_number_4' => 4, 'owner_name' => 0, 'cvv2' => 4, 'endtime_mm' => 2, 'endtime_yy'  => 2);
	var $tablename = 'sys_products_cards';
	var $asteriskArray = array(2 => '**', 4 => '****');

	function init (&$pibase, &$cnf, $formerBasket, $allowedUids='') {

		$this->pibase = &$pibase;
		$this->conf = &$cnf->conf;
		$this->ccArray = array();
		$this->ccArray = $formerBasket['creditcard'];

		if (isset($allowedUids))	{
			$this->allowedArray = t3lib_div::trimExplode(',',$allowedUids);
		}
		$bNumberRecentlyModified = false;

		foreach ($this->fieldArray as $k => $field)	{
			$size = $this->sizeArray[$field];
			if ($size)	{
				if ($this->ccArray[$field] && strcmp ($this->ccArray[$field], $this->asteriskArray[$size]) != 0)	{
					$bNumberRecentlyModified = true;
				}
			}
		}

		if ($bNumberRecentlyModified)	{
			global $TSFE;

			$ccArray = $TSFE->fe_user->getKey('ses','cc');
			if (!$ccArray)	{
				$ccArray = array();
			}

			$allowedTags = '';
			foreach ($ccArray as $type => $ccRow)	{
				$ccArray[$type] = strip_tags ($ccRow, $allowedTags);
			}

			if ($this->ccArray)	{
				$newId = $this->create ($ccArray['cc_uid'], $this->ccArray);

				if ($newId)	{
					$ccArray['cc_uid'] = $newId;
					$TSFE->fe_user->setKey('ses','cc',$ccArray);
					for ($i = 1; $i <= 3; ++$i)	{
						$this->ccArray['cc_number_'.$i] = ($this->ccArray['cc_number_'.$i] ? $this->asteriskArray[$this->sizeArray['cc_number_'.$i]] : '');
					}

					$this->ccArray['cvv2'] = ($this->ccArray['cvv2'] ? $this->asteriskArray[$this->sizeArray['cvv2']] : '' );
					if (!is_array($this->conf['payment.']['creditcardSelect.']['mm.']))	{
						$this->ccArray['endtime_mm'] = ($this->ccArray['endtime_mm'] ? $this->asteriskArray[$this->sizeArray['endtime_mm']] : '');
					}
					if (!is_array($this->conf['payment.']['creditcardSelect.']['yy.']))	{
						$this->ccArray['endtime_yy'] = ($this->ccArray['endtime_yy'] ? $this->asteriskArray[$this->sizeArray['endtime_yy']] : '');
					}
				}
			}
		}
	}


	// **************************
	// ORDER related functions
	// **************************

	/**
	 * Create a new credit card record
	 *
	 * This creates a new credit card record on the page with pid PID_sys_products_orders. That page must exist!
	 * Should be called only internally by eg. $order->getBlankUid, that first checks if a blank record is already created.
	 */
	function create ($uid, $ccArray)	{
		global $TSFE, $TYPO3_DB;

		$newId = 0;
		$pid = intval($this->conf['PID_sys_products_orders']);
		if (!$pid)	$pid = intval($TSFE->id);

		if ($ccArray['cc_number_1'] && $TSFE->sys_page->getPage_noCheck($pid))	{
			$time = time();
			$timeArray =
				array(
					'hour' => 0, // hour
					'minute' => 0, // minute
					'second' => 0, // second
					'month' => intval($ccArray['endtime_mm']), // month
					'day' => 28, // day
					'year' => intval($ccArray['endtime_yy']) // year
				);
			$endtime = mktime ($timeArray['hour'], $timeArray['minute'], $timeArray['second'], $timeArray['month'], $timeArray['day'], $timeArray['year']);

			for ($i = 1; $i <= 4; ++$i)	{
				$ccArray['cc_number_'.$i] = ($ccArray['cc_number_'.$i] ? $ccArray['cc_number_'.$i] : '   ');
			}

			$newFields = array (
				'pid' => intval($pid),
				'tstamp' => $time,
				'crdate' => $time,
				'endtime' => $endtime,
				'owner_name' => $ccArray['owner_name'],
				'cc_number' => $ccArray['cc_number_1'].$ccArray['cc_number_2'].$ccArray['cc_number_3'].$ccArray['cc_number_4'],
				'cc_type' => $ccArray['cc_type'],
				'cvv2' => $ccArray['cvv2']
			);

			if ($uid)	{
				$where_clause = 'uid='.$uid;
				$res = $TYPO3_DB->exec_SELECTquery('*',$this->tablename,$where_clause);
				$row = $TYPO3_DB->sql_fetch_assoc($res);
				for ($i = 1; $i <= 4; ++$i)	{
					$tmpOldPart = substr($row['cc_number'],($i-1) * 4, 4);
					if (strcmp($ccArray['cc_number_'.$i],$this->asteriskArray[$this->sizeArray['cc_number_'.$i]]) == 0)	{
						$ccArray['cc_number_'.$i] = $tmpOldPart;
					}
				}
				$fieldArray = array('cc_type', 'owner_name', 'cvv2');

				foreach ($fieldArray as $k => $field)	{
					if (strcmp($ccArray[$field], $this->asteriskArray[$this->sizeArray[$field]]) == 0)	{
						unset($newFields[$field]); // prevent from change into asterisks
					}
				}
				$newFields['cc_number'] = $ccArray['cc_number_1'].$ccArray['cc_number_2'].$ccArray['cc_number_3'].$ccArray['cc_number_4'];
				$oldEndtime = getdate($row['endtime']);
				if (strcmp($ccArray['endtime_mm'], $this->asteriskArray[$this->sizeArray['endtime_mm']]) == 0)	{
					$ccArray['endtime_mm'] = $oldEndtime['mon'];
				}
				if (strcmp($ccArray['endtime_yy'], $this->asteriskArray[$this->sizeArray['endtime_yy']]) == 0)	{
					$ccArray['endtime_yy'] = $oldEndtime['year'];
				}

				$timeArray =
					array(
						'hour' => 0, // hour
						'minute' => 0, // minute
						'second' => 0, // second
						'month' => intval($ccArray['endtime_mm']), // month
						'day' => 28, // day
						'year' => intval($ccArray['endtime_yy']) // year
					);
				$endtime = mktime($timeArray['hour'], $timeArray['minute'], $timeArray['second'], $timeArray['month'], $timeArray['day'], $timeArray['year']);
				$newFields['endtime'] = $endtime;

				$TYPO3_DB->exec_UPDATEquery($this->tablename,$where_clause,$newFields);
				$newId = $uid;
			} else {
				$TYPO3_DB->exec_INSERTquery($this->tablename, $newFields);
				$newId = $TYPO3_DB->sql_insert_id();
			}
		}
		return $newId;
	} // create


	function getUid ()	{
		global $TSFE;

		$ccArray = $TSFE->fe_user->getKey('ses','cc');
		return $ccArray['cc_uid'];
	}


	function get ($uid, $bFieldArrayAll=false) {
		global $TYPO3_DB;
		$rcArray = array();
		if ($bFieldArrayAll)	{
			foreach ($this->fieldArray as $k => $field)	{
				$rcArray [$field] = '';
			}
		}

		if ($uid) {
			$where = 'uid = '.intval($uid);

			$fields = '*';
			if ($bFieldArrayAll)	{
				$fields = implode(',',$this->fieldArray);
			}
			$res = $TYPO3_DB->exec_SELECTquery($fields, $this->tablename, $where);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			if ($row)	{
				$rcArray = $row;
			}
		}
		return $rcArray;
	}


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
	function getItemMarkerArray (&$markerArray)	{
		global $TCA, $TSFE;
		global $TYPO3_CONF_VARS;

		include_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_form_div.php');
		$langObj = t3lib_div::getUserObj('&tx_ttproducts_language');

		$ccNumberArray = array();
		$ccTypeTextSelected = '';
		if (count($this->allowedArray))	{
			$ccTypeText = tx_ttproducts_form_div::createSelect($langObj, $TCA[$this->tablename]['columns']['cc_type']['config']['items'], 'recs[creditcard][cc_type]', $this->ccArray['cc_type'], $this->allowedArray);
			for ($i = 1; $i <= 4; ++$i)	{
				$ccNumberArray[$i - 1] = $this->ccArray['cc_number_'.$i];
			}
			$ccOwnerName = $this->ccArray['owner_name'];
		} else {
			$ccTypeText = '';
			$ccNumber = '';
			$ccOwnerName = '';
		}

		$markerArray['###PERSON_CARDS_OWNER_NAME###'] = htmlentities($ccOwnerName,ENT_QUOTES,$TSFE->renderCharset);
		$markerArray['###PERSON_CARDS_CC_TYPE###'] = $ccTypeText;
		$markerArray['###PERSON_CARDS_CC_TYPE_SELECTED###'] = $this->ccArray['cc_type'];
		if (isset($this->ccArray['cc_type']))	{ // tx_fhlibrary_language::sL
			$tmp = $TCA[$this->tablename]['columns']['cc_type']['config']['items'][$this->ccArray['cc_type']]['0'];
			$tmp = tx_div2007_alpha::sL_fh001($tmp);
			$ccTypeTextSelected = tx_div2007_alpha5::getLL_fh002($langObj,$tmp);
		}
		$markerArray['###PERSON_CARDS_CC_TYPE_SELECTED###'] = $ccTypeTextSelected;
		for ($i = 1; $i <= 4; ++$i)	{
			$markerArray['###PERSON_CARDS_CC_NUMBER_'.$i.'###'] = $ccNumberArray[$i - 1];
		}
		$markerArray['###PERSON_CARDS_CVV2###'] = $this->ccArray['cvv2'];
		$markerArray['###PERSON_CARDS_ENDTIME_MM###'] = $this->ccArray['endtime_mm'];
		$markerArray['###PERSON_CARDS_ENDTIME_YY###'] = $this->ccArray['endtime_yy'];
		$markerArray['###PERSON_CARDS_ENDTIME_YY_SELECT###'] = '';
		$markerArray['###PERSON_CARDS_ENDTIME_MM_SELECT###'] = '';

		if (is_array($this->conf['payment.']['creditcardSelect.']))	{
			$mmArray = $this->conf['payment.']['creditcardSelect.']['mm.'];
			if (is_array($mmArray))	{
				$valueArray = tx_ttproducts_form_div::fetchValueArray($mmArray['valueArray.']);
				$markerArray['###PERSON_CARDS_ENDTIME_MM_SELECT###'] =
					tx_ttproducts_form_div::createSelect($langObj, $valueArray, 'recs[creditcard][endtime_mm]', $this->ccArray['endtime_mm']);
			}
			$yyArray = $this->conf['payment.']['creditcardSelect.']['yy.'];
			if (is_array($yyArray))	{
				$valueArray = tx_ttproducts_form_div::fetchValueArray($yyArray['valueArray.']);
				$markerArray['###PERSON_CARDS_ENDTIME_YY_SELECT###'] =
					tx_ttproducts_form_div::createSelect($langObj, $valueArray, 'recs[creditcard][endtime_yy]', $this->ccArray['endtime_yy']);
			}
		}
	} // getMarkerArray


	/**
	 * Checks if required fields for credit cards and bank accounts are filled in
	 */
	function checkRequired ()	{
		$rc = '';

		foreach ($this->fieldArray as $k => $field)	{
			$testVal = $this->ccArray[$field];

			if (
				!$testVal &&
				(
					class_exists('t3lib_utility_Math') ? !t3lib_utility_Math::canBeInterpretedAsInteger($testVal) :
					!t3lib_div::testInt($testVal)
				)
			)	{
				$rc = $field;
				break;
			}
		}
		return $rc;
	} // checkRequired
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_card.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_card.php']);
}


?>
