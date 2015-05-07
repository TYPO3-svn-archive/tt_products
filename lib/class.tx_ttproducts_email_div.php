<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2011 Franz Holzinger <franz@ttproducts.de>
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
 * email functions
 *
 * $Id: class.tx_ttproducts_email_div.php 3665 2006-09-06 18:52:11Z franzholz $
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



require_once (PATH_BE_ttproducts.'model/class.tx_ttproducts_orderaddress.php');
require_once (PATH_BE_div2007 . 'class.tx_div2007_alpha5.php');

class tx_ttproducts_email_div {

	/**
	 * Extended mail function
	 */
	function send_mail ($toEMail,$subject,&$message,&$html,$fromEMail,$fromName,$attachment='',$bcc='') {
		global $TYPO3_CONF_VARS;

		$typo3Version = class_exists('t3lib_utility_VersionNumber') ? t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) : t3lib_div::int_from_ver(TYPO3_version);

		if (
			$typo3Version >= 4007000 ||
			(
				isset($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']) &&
				is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']) &&
				isset($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery']) &&
				is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery']) &&
				(
					array_search('t3lib_mail_SwiftMailerAdapter', $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery']) !== FALSE ||
					array_search('TYPO3\CMS\Core\Mail\SwiftMailerAdapter', $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery']) !== FALSE
				)
			)
		) {
			if (!is_array($toEMail)) {
				$emailArray = t3lib_div::trimExplode(',', $toEMail);

				$toEMail = array();
				foreach ($emailArray as $email) {
					$toEMail[] = $email;
				}
			}

			/** @var $mail t3lib_mail_Message */
			$mailMessage = t3lib_div::makeInstance('t3lib_mail_Message');
			$mailMessage->setTo($toEMail)
				->setFrom(array($fromEMail => $fromName))
				->setSubject($subject)
				->setBody($html, 'text/html', $GLOBALS['TSFE']->renderCharset)
				->addPart($message, 'text/plain', $GLOBALS['TSFE']->renderCharset);

/*
			if ($html != '') {
				$mailMessage->setBody($html, 'text/html', 'iso-8859-1');
			} else if ($message != '') {
				$mailMessage->addPart($message, 'text/plain', 'iso-8859-1');
			}*/

			if (isset($attachment)) {
				if (is_array($attachment)) {
					$attachmentArray = $attachment;
				} else {
					$attachmentArray = array($attachment);
				}
				foreach ($attachmentArray as $theAttachment) {
					if (file_exists($theAttachment)) {
						$mailMessage->attach(Swift_Attachment::fromPath($theAttachment));
					}
				}
			}
			if ($bcc != '') {
				$mailMessage->addBcc($bcc);
			}
			$mailMessage->send();
		} else {
			include_once (PATH_t3lib.'class.t3lib_htmlmail.php');

			$fromName = tx_div2007_alpha5::slashName($fromName);
			if (is_array($toEMail)) {
				list($email, $name) = each($toEMail);
				$toEMail = tx_div2007_alpha5::slashName($name) . ' <' . $email . '>';
			}

			$Typo3_htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
			$Typo3_htmlmail->start();
			$Typo3_htmlmail->mailer = 'TYPO3 HTMLMail';
			// $Typo3_htmlmail->useBase64(); +++ TODO
			$message = html_entity_decode($message);
			if ($Typo3_htmlmail->linebreak == chr(10))	{
				$message = str_replace(chr(13).chr(10),$Typo3_htmlmail->linebreak,$message);
			}

			$Typo3_htmlmail->subject = $subject;
			$Typo3_htmlmail->from_email = $fromEMail;
			$Typo3_htmlmail->returnPath = $fromEMail;
			$Typo3_htmlmail->from_name = $fromName;
			$Typo3_htmlmail->replyto_email = $Typo3_htmlmail->from_email;
			$Typo3_htmlmail->replyto_name = $fromName;
			$Typo3_htmlmail->organisation = '';

			if ($attachment != '' && file_exists($attachment))	{
				$Typo3_htmlmail->addAttachment($attachment);
			}

			if ($html)  {
				$Typo3_htmlmail->theParts['html']['content'] = $html;
				$Typo3_htmlmail->theParts['html']['path'] = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/';
				$Typo3_htmlmail->extractMediaLinks();
				$Typo3_htmlmail->extractHyperLinks();
				$Typo3_htmlmail->fetchHTMLMedia();
				$Typo3_htmlmail->substMediaNamesInHTML(0);	// 0 = relative
				$Typo3_htmlmail->substHREFsInHTML();
				$Typo3_htmlmail->setHTML($Typo3_htmlmail->encodeMsg($Typo3_htmlmail->theParts['html']['content']));
			}
			if ($message)	{
				$Typo3_htmlmail->addPlain($message);
			}
			$Typo3_htmlmail->setHeaders();
			if ($bcc != '')	{
				$Typo3_htmlmail->add_header('Bcc: '.$bcc);
			}
			if ($attachment != '')	{
				foreach ($Typo3_htmlmail->theParts['attach'] as $k => $media)	{
					$Typo3_htmlmail->theParts['attach'][$k]['filename'] = basename($media['filename']);
				}
			}
			if ($attachment != '')	{
				if (isset($Typo3_htmlmail->theParts) && is_array($Typo3_htmlmail->theParts) && isset($Typo3_htmlmail->theParts['attach']) && is_array($Typo3_htmlmail->theParts['attach'])) {
					foreach ($Typo3_htmlmail->theParts['attach'] as $k => $media)	{
						$Typo3_htmlmail->theParts['attach'][$k]['filename'] = basename($media['filename']);
					}
				}
			}
			$Typo3_htmlmail->setContent();
			$Typo3_htmlmail->setRecipient(explode(',', $toEMail));

			$hookVar = 'sendMail';
			if ($hookVar && is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar])) {
				foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXTkey][$hookVar] as $classRef) {
					$hookObj= &t3lib_div::getUserObj($classRef);
					if (method_exists($hookObj, 'init')) {
						$hookObj->init($Typo3_htmlmail);
					}
					if (method_exists($hookObj, 'sendMail')) {
						$rc = $hookObj->sendMail($Typo3_htmlmail,$toEMail,$subject,$message,$html,$fromEMail,$fromName,$attachment,$bcc);
					}
				}
			}

			if ($rc !== FALSE)	{
				$Typo3_htmlmail->sendTheMail();
			}
		}
	}


	/**
	 * Send notification email for tracking
	 */
	function sendNotifyEmail (
		&$cObj,
		&$conf,
		&$config,
		&$feusersObj,
		$orderNumber,
		$recipient,
		$v,
		$statusCodeArray,
		$tracking,
		$orderRow,
		$templateCode,
		$templateMarker,
		$sendername='',
		$senderemail=''
	) {
		global $TSFE;

			// initialize order data.
		$orderData = unserialize($orderRow['orderData']);
		$sendername = ($sendername ? $sendername : $conf['orderEmail_fromName']);
		$senderemail = ($senderemail ? $senderemail : $conf['orderEmail_from']);

			// Notification email
		$recipients = $recipient;
		$recipients=t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients)) {	// If any recipients, then compile and send the mail.
			$emailContent=trim($cObj->getSubpart($templateCode,'###'.$templateMarker.$config['templateSuffix'].'###'));
			if (!$emailContent)	{
				$emailContent=trim($cObj->getSubpart($templateCode,'###'.$templateMarker.'###'));
			}
			if ($emailContent)  {		// If there is plain text content - which is required!!
				$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
				$globalMarkerArray = &$markerObj->getGlobalMarkerArray();

				$markerArray = $globalMarkerArray;
				$markerArray['###ORDER_STATUS_TIME###'] = $cObj->stdWrap($v['time'],$conf['statusDate_stdWrap.']);
				$markerArray['###ORDER_STATUS###'] = $v['status'];
				$info = $statusCodeArray[$v['status']];
				$markerArray['###ORDER_STATUS_INFO###'] = ($info ? $info : $v['info']);
				$markerArray['###ORDER_STATUS_COMMENT###'] = $v['comment'];
				$markerArray['###PID_TRACKING###'] = $conf['PIDtracking'];
				$markerArray['###PERSON_NAME###'] = $orderData['billing']['name'];
				$markerArray['###DELIVERY_NAME###'] = $orderData['delivery']['name'];

				$variantFieldArray = array();
				$variantMarkerArray = array();
				$feusersObj->getRowMarkerArray ($orderData['billing'], $markerArray, false, 'person');
				$feusersObj->getRowMarkerArray ($orderData['delivery'], $markerArray, false, 'delivery');

				$markerArray['###ORDER_TRACKING_NO###'] = $tracking;
				$markerArray['###ORDER_UID###'] = $orderNumber;
				$emailContent = $cObj->substituteMarkerArrayCached($emailContent, $markerArray);
				$parts = explode(chr(10),$emailContent,2);
				$subject = trim($parts[0]);
				$plain_message = trim($parts[1]);
				self::send_mail(implode($recipients,','), $subject, $plain_message, $tmp='', $senderemail, $sendername);
			}
		}
	}


	/**
	 * Send notification email for gift certificates
	 */
	function sendGiftEmail (&$cObj,&$conf,$recipient,$comment,$giftRow,$templateCode,$templateMarker, $bHtmlMail=false)	{
		global $TSFE;

		$sendername = ($giftRow['personname'] ? $giftRow['personname'] : $conf['orderEmail_fromName']);
		$senderemail = ($giftRow['personemail'] ? $giftRow['personemail'] : $conf['orderEmail_from']);
		$recipients = $recipient;
		$recipients = t3lib_div::trimExplode(',',$recipients,1);

		if (count($recipients)) {	// If any recipients, then compile and send the mail.
			$emailContent=trim($cObj->getSubpart($templateCode,'###'.$templateMarker.'###'));
			if ($emailContent)  {		// If there is plain text content - which is required!!
				$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
				$globalMarkerArray = &$markerObj->getGlobalMarkerArray();

				$parts = explode(chr(10),$emailContent,2);	// First line is subject
				$subject = trim($parts[0]);
				$plain_message = trim($parts[1]);

				$markerArray = $globalMarkerArray;
				$markerArray['###CERTIFICATES_TOTAL###'] = $giftRow['amount'];
				$markerArray['###CERTIFICATES_UNIQUE_CODE###'] = $giftRow['uid'].'-'.$giftRow['crdate'];
				$markerArray['###PERSON_NAME###'] = $giftRow['personname'];
				$markerArray['###DELIVERY_NAME###'] = $giftRow['deliveryname'];
				$markerArray['###ORDER_STATUS_COMMENT###'] = $giftRow['note'].($bHtmlMail?'\n':chr(13)).$comment;
				$emailContent = $cObj->substituteMarkerArrayCached($plain_message, $markerArray);

				$recipients = implode($recipients,',');

				if (class_exists($cls) && $bHtmlMail) {	// If htmlmail lib is included, then generate a nice HTML-email
					$HTMLmailShell = $cObj->getSubpart($this->templateCode,'###EMAIL_HTML_SHELL###');
					$HTMLmailContent = $cObj->substituteMarker($HTMLmailShell,'###HTML_BODY###',$emailContent);
					$markerObj = &t3lib_div::getUserObj('&tx_ttproducts_marker');
					$HTMLmailContent=$cObj->substituteMarkerArray($HTMLmailContent, $markerObj->getGlobalMarkerArray());

					self::send_mail($recipients,  $subject, $emailContent, $HTMLmailContent, $senderemail, $sendername, $conf['GiftAttachment']);
				} else {		// ... else just plain text...
					self::send_mail($recipients, $subject, $emailContent, $tmp='',$senderemail, $sendername, $conf['GiftAttachment']);
				}
			}
		}
	}
}


?>
