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

use Contao\DC_Table;

/*
 * Table tl_dlstats
 */
$GLOBALS['TL_DCA']['tl_dlstats'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'			=> DC_Table::class,
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
