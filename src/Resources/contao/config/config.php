<?php

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2024 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Download Statistics Bundle (Dlstats)
 * @link       https://github.com/BugBuster1701/contao-dlstats-bundle
 *
 * @license    LGPL-3.0-or-later
 */

use Contao\System;
use Symfony\Component\HttpFoundation\Request;

define('DLSTATS_VERSION', '1.6');
define('DLSTATS_BUILD', '0');

/*
 * Defaults, you can overwrite this in Backend -> System -> Settings
 */
$GLOBALS['TL_CONFIG']['dlstatTopDownloads']  = 20;
$GLOBALS['TL_CONFIG']['dlstatLastDownloads'] = 20;

/*
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

/*
 * CSS
 */
if (
	System::getContainer()->get('contao.routing.scope_matcher')
	->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))
) {
	$GLOBALS['TL_CSS'][] = 'bundles/bugbusterdlstats/dlstatssystem_be.css';
}
