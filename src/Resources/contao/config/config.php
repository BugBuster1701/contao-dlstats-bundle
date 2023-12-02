<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2020 Leo Feyer
 * 
 * Module Download Statistics
 *
 * Log file downloads done by the content elements Download and Downloads 
 * and show statistics in the backend. 
 *
 *
 * Module configuration file.
 * 
 * PHP version 5
 * @copyright  Glen Langer 2011..2020 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-dlstats-bundle
 */

use Contao\System;
use Symfony\Component\HttpFoundation\Request;

\define('DLSTATS_VERSION', '1.3');
\define('DLSTATS_BUILD', '7');

/**
 * Defaults, you can overwrite this in Backend -> System -> Settings
 */
$GLOBALS['TL_CONFIG']['dlstatTopDownloads']  = 20;
$GLOBALS['TL_CONFIG']['dlstatLastDownloads'] = 20;

/**
 * -------------------------------------------------------------------------
 * BACK END MODULES
 * -------------------------------------------------------------------------
 */
$GLOBALS['BE_MOD']['system']['dlstats'] = array
(
        'callback'   => 'BugBuster\DLStats\ModuleDlstatsStatistics',
        'icon'       => 'bundles/bugbusterdlstats/icon.png',
        'stylesheet' => 'bundles/bugbusterdlstats/mod_dlstatsstatistics_be.css',
);

/**
 * -------------------------------------------------------------------------
 * HOOKS
 * -------------------------------------------------------------------------
 */
$GLOBALS['TL_HOOKS']['postDownload'][]          = array('BugBuster\DLStats\Dlstats',          'logDownload');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][]     = array('BugBuster\DLStats\ModuleDlstatsTag', 'dlstatsReplaceInsertTags');

/**
 * CSS
 */
if (System::getContainer()->get('contao.routing.scope_matcher')
    ->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))
    )
{
    $GLOBALS['TL_CSS'][] = 'bundles/bugbusterdlstats/dlstatssystem_be.css';
}
