<?php 

/**
 * Contao Open Source CMS, Copyright (C) 2005-2015 Leo Feyer
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
 * @copyright  Glen Langer 2011..2015 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    GLDLStats
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/dlstats
 */

define('DLSTATS_VERSION', '1.0');
define('DLSTATS_BUILD'  , '0');

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
$GLOBALS['BE_MOD']['content']['dlstats'] = array
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
$GLOBALS['TL_HOOKS']['parseBackendTemplate'][]  = array('BugBuster\DLStats\DlstatsCheck', 'checkExtensions');
$GLOBALS['TL_HOOKS']['postDownload'][] = array('BugBuster\DLStats\Dlstats', 'logDownload');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('BugBuster\DLStats\ModuleDlstatsTag', 'dlstatsReplaceInsertTags');


/**
 * -------------------------------------------------------------------------
 * FRONT END MODULES ONLY FOR DEBUGGING 
 * -------------------------------------------------------------------------
 */
//$GLOBALS['FE_MOD']['DlstatTestDebug']['testip'] = 'DLStats\DlstatsTestIP';
