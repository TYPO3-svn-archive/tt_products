<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


$table = 'pages_language_overlay';

$orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
if (
	!empty($orderBySortingTablesArray) &&
	in_array($table, $orderBySortingTablesArray)
) {
	$result['ctrl']['sortby'] = 'sorting';
}

