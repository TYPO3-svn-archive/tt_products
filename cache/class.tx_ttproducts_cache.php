<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Franz Holzinger (franz@ttproducts.de)
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
 * function for the TYPO3 caching framework needed from TYPO3 4.3 to 4.5
 *
 * $Id$
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_cache {

    /**
     * @var t3lib_cache_frontend_AbstractFrontend
     */
    protected $cacheInstance;


    /**
     * Constructor
     */
    public function __construct () {
        $this->initializeCache();
    }


    /**
     * Initialize cache instance to be ready to use
     *
     * @return void
     */
    protected function initializeCache () {
        t3lib_cache::initializeCachingFramework();
        try {
            $this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('tt_products_cache');
        }
        catch (t3lib_cache_exception_NoSuchCache $e) {
            $this->cacheInstance = $GLOBALS['typo3CacheFactory']->create(
                'tt_products_cache',
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['frontend'],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['backend'],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['tt_products_cache']['options']
            );
        }
    }


	// Todo. See http://wiki.typo3.org/Caching_framework
    protected function getCachedMagic () {
		$cacheIdentifier = $this->calculateCacheIdentifier();

        // If $entry is null, it hasn't been cached. Calculate the value and store it in the cache:
		if (is_null($entry = $GLOBALS['typo3CacheManager']->getCache('myCache')->get($cacheIdentifier))) {
				$entry = $this->calculateMagic();

				// [calculate lifetime and assigned tags]

				// Save value in cache
				$GLOBALS['typo3CacheManager']->getCache('myCache')->set($cacheIdentifier, $entry, $tags, $lifetime);
			}
        return $entry;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/cache/class.tx_ttproducts_cache.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/cache/class.tx_ttproducts_cache.php']);
}


?>