<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @package Dlstats
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_dlstats_be_statistics'      => 'vendor/bugbuster/contao-dlstats-bundle/src/Resources/contao/templates',
	'mod_dlstats_be_partial_details' => 'vendor/bugbuster/contao-dlstats-bundle/src/Resources/contao/templates',
	'mod_dlstats_fe_test_ip'         => 'vendor/bugbuster/contao-dlstats-bundle/src/Resources/contao/templates',
));
