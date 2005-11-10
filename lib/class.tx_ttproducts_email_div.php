<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2005 Franz Holzinger <kontakt@fholzinger.com>
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
 * Part of the tt_products (Shopping System) extension.
 *
 * email functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_t3lib.'class.t3lib_htmlmail.php');
require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_order.php');
require_once (PATH_BE_ttproducts.'pi1/class.tx_ttproducts_htmlmail.php');


class tx_ttproducts_email_div {


	/**
	 * Extended mail function
	 */
	function send_mail($email,$subject,$message,$fromEMail,$fromName,$attachment='') {
		$cls=t3lib_div::makeInstanceClassName('t3lib_htmlmail');
		if (class_exists($cls))	{
			$Typo3_htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$Typo3_htmlmail->start();
			$Typo3_htmlmail->useBase64();

			$Typo3_htmlmail->subject = $subject;
			$Typo3_htmlmail->from_email = $fromEMail;
			$Typo3_htmlmail->from_name = $fromName;
			$Typo3_htmlmail->replyto_email = $Typo3_htmlmail->from_email;
			$Typo3_htmlmail->replyto_name = $Typo3_htmlmail->from_name;
			$Typo3_htmlmail->organisation = '';
			$Typo3_htmlmail->priority = 3;

			$Typo3_htmlmail->addPlain($message);
			if ($attachment != '')
				$Typo3_htmlmail->addAttachment($attachment);

			$Typo3_htmlmail->setHeaders();
			$Typo3_htmlmail->setContent();
			$Typo3_htmlmail->setRecipient(explode(',', $email));
			$Typo3_htmlmail->sendTheMail();
		}
	}


	/**
	 * Send notification email for tracking
	 */
	function sendNotifyEmail(&$pibase, &$conf, &$order,  $recipient, $v, $tracking, $orderRow, $templateCode, $templateMarker, $sendername='', $senderemail='')	{
		global $TSFE;

		$uid = $order->getOrderNumber($orderRow['uid']);
			// initialize order data.
		$orderData = unserialize($orderRow['orderData']);

		$sendername = ($sendername ? $sendername : $conf['orderEmail_fromName']);
		$senderemail = ($senderemail ? $senderemail : $conf['orderEmail_from']);

			// Notification email

		$recipients = $recipient;
		$recipients=t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim($pibase->cObj->getSubpart($templateCode,'###'.$templateMarker.'###'));
			if ($emailContent)	{		// If there is plain text content - which is required!!
				$markerArray['###ORDER_STATUS_TIME###']=$pibase->cObj->stdWrap($v['time'],$conf['statusDate_stdWrap.']);
				$markerArray['###ORDER_STATUS###']=$v['status'];
				$markerArray['###ORDER_STATUS_INFO###']=$v['info'];
				$markerArray['###ORDER_STATUS_COMMENT###']=$v['comment'];
				$markerArray['###PID_TRACKING###'] = $this->conf['PIDtracking'];
				$markerArray['###PERSON_NAME###'] =  $orderData['personInfo']['name'];
				$markerArray['###DELIVERY_NAME###'] =  $orderData['deliveryInfo']['name'];

				$markerArray['###ORDER_TRACKING_NO###']=$tracking;
				$markerArray['###ORDER_UID###']=$uid;

				$emailContent=$pibase->cObj->substituteMarkerArrayCached($emailContent, $markerArray);

				$parts = split(chr(10),$emailContent,2);
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);

				tx_ttproducts_email_div::send_mail(implode($recipients,','), $subject, $plain_message, $senderemail, $sendername);
			}
		}
	}
	
		

	/**
	 * Send notification email for gift certificates
	 */
	function sendGiftEmail(&$pibase,$conf,&$basket, $recipient, $comment, $giftRow, $templateCode, $templateMarker)	{
		global $TSFE;

		$sendername = ($giftRow['personname'] ? $giftRow['personname'] : $conf['orderEmail_fromName']);
		$senderemail = ($giftRow['personemail'] ? $giftRow['personemail'] : $conf['orderEmail_from']);

		$recipients = $recipient;
		$recipients=t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients))	{	// If any recipients, then compile and send the mail.
			$emailContent=trim($pibase->cObj->getSubpart($templateCode,'###'.$templateMarker.'###'));
			if ($emailContent)	{		// If there is plain text content - which is required!!
				$parts = split(chr(10),$emailContent,2);		// First line is subject
				$subject=trim($parts[0]);
				$plain_message=trim($parts[1]);

				$markerArray = array();
				$markerArray['###CERTIFICATES_TOTAL###'] = $giftRow['amount'];
				$markerArray['###CERTIFICATES_UNIQUE_CODE###'] =  $giftRow['uid'].'-'.$giftRow['crdate'];
				$markerArray['###PERSON_NAME###'] = $giftRow['personname'];
				$markerArray['###DELIVERY_NAME###'] = $giftRow['deliveryname'];
				$markerArray['###ORDER_STATUS_COMMENT###'] = $giftRow['note'].'\n'.$comment;

				$emailContent = $pibase->cObj->substituteMarkerArrayCached($plain_message, $markerArray);

				$cls  = t3lib_div::makeInstanceClassName('tx_ttproducts_htmlmail');
				if (class_exists($cls) && $conf['orderEmail_htmlmail'])	{	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell=$pibase->cObj->getSubpart($this->templateCode,'###EMAIL_HTML_SHELL###');
					$HTMLmailContent=$pibase->cObj->substituteMarker($HTMLmailShell,'###HTML_BODY###',$emailContent);
					$HTMLmailContent=$pibase->cObj->substituteMarkerArray($HTMLmailContent, $pibase->globalMarkerArray);

					$V = array (
						'from_email' => $senderemail,
						'from_name'  => $sendername,
						'attachment' => $conf['GiftAttachment']
					);

					$Typo3_htmlmail = t3lib_div::makeInstance('tx_ttproducts_htmlmail');
					$Typo3_htmlmail->useBase64();
					$Typo3_htmlmail->start(implode($recipients,','), $subject, $emailContent, $HTMLmailContent, $V);
					$Typo3_htmlmail->sendtheMail();
				} else {		// ... else just plain text...
					// $headers variable überall entfernt!
					tx_ttproducts_email_div::send_mail($recipients, $subject, $emailContent, $senderemail, $sendername, $conf['GiftAttachment']);
					tx_ttproducts_email_div::send_mail($conf['orderEmail_to'], $subject, $emailContent, $basket->personInfo['email'], $basket->personInfo['name'], $conf['GiftAttachment']);
				}
			}
		}

	}



}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_email_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_email_div.php']);
}


?>
