<?php

defined('TYPO3') or die();

// Register Hook for AccountDeleteListener
// Called by DataHandler when a tx_blogsync_config record is deleted via TYPO3 backend
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]
    = \AgencyPowerstack\BlogSync\EventListener\AccountDeleteListener::class;
