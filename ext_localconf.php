<?php
if (!defined ("TYPO3_MODE"))    die ("Access denied.");
t3lib_extMgm::addUserTSConfig('
        options.saveDocNew.tt_products=1
');

t3lib_extMgm::addUserTSConfig('
        options.saveDocNew.tt_products_cat=1
');

$TYPO3_CONF_VARS['EXTCONF']['tt_products']['pageAsCategory'] = 0; //for page as categories = 1

?>
