<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Register the CKEditor preset that includes the master data placeholder plugin
$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['sitemasterdata_default'] =
    'EXT:sitemasterdata/Configuration/RTE/Default.yaml';
