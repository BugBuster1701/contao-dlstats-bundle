<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2018 Leo Feyer
 * 
 * Module Download Statistics
 *
 * Log file downloads done by the content elements Download and Downloads, and 
 * show statistics in the backend. 
 *
 * This is the data container array for table tl_dlstats.
 * 
 * PHP version 5
 * @copyright  Glen Langer 2011..2018 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-dlstats-bundle
 */

/**
 * Table tl_dlstats
 */
$GLOBALS['TL_DCA']['tl_dlstats'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'			=> Contao\DC_Table::class,
		'ctable'				=> array('tl_dlstatdets'),
		'closed'				=> true,
        'sql' => array
        (
            'keys' => array
            (
                'id'       => 'primary',
                'filename' => 'index'
            )
        )
	),

	// Fields
	'fields' => array
	(
    	'id' => array
    	(
            'sql'           => "int(10) unsigned NOT NULL auto_increment"
    	),
    	'tstamp' => array
    	(
	        'sql'           => "int(10) unsigned NOT NULL default '0'"
    	),
		'filename' => array
		(
			'sql'           => "varchar(255) NOT NULL default ''"
		),
		'downloads' => array
		(
			'sql'           => "int(10) unsigned NOT NULL default '0'"
		)
	)
);

