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

/*
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
