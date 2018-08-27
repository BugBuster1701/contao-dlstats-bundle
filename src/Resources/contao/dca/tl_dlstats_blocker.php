<?php 

/**
 * Contao Open Source CMS, Copyright (C) 2005-2018 Leo Feyer
 * 
 * Module Download Statistics
 *
 * Log file downloads done by the content elements Download and Downloads, and 
 * show statistics in the backend. 
 *
 *
 * This is the data container array for table tl_dlstats_blocker.
 * 
 * PHP version 5
 * @copyright  Glen Langer 2011..2018 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    DLStats
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-dlstats-bundle
 */

/**
 * Table tl_dlstats_blocker
 */
$GLOBALS['TL_DCA']['tl_dlstats_blocker'] = array
(
        // Config
        'config' => array
        (
                'sql' => array
                (
                        'keys' => array
                        (
                                'id'  => 'primary'
                        )
                )
        ),

        // Fields
        'fields' => array
        (
                'id' => array
                (
                        'sql'       => "int(10) unsigned NOT NULL auto_increment"
                ),
                'dlstats_tstamp' => array
                (
                        'sql'       => "timestamp NULL"
                ),
                'dlstats_ip' => array
                (
                        'sql'       => "varchar(40) NOT NULL default '0.0.0.0'"
                ),
                'dlstats_filename' => array
        		(
        			'sql'           => "varchar(255) NOT NULL default ''"
        		)
        )
);
