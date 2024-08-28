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

namespace BugBuster\DLStats;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Response;

/**
 * Back end dlstats wizard.
 */
class BackendDlstats extends ModuleDlstatsStatisticsHelper
{
	/**
	 * Initialize the controller
	 */
	public function __construct()
	{
		parent::__construct();

		if (false === System::getContainer()->get('contao.security.token_checker')->hasBackendUser())
		{
			throw new AccessDeniedException('Access denied');
		}

		System::loadLanguageFile('default');
		System::loadLanguageFile('modules');
		System::loadLanguageFile('tl_dlstatstatistics_stat');
	}

	/**
	 * Run the controller and parse the template
	 *
	 * @return Response
	 */
	public function run()
	{
		/** @var BackendTemplate|object $objTemplate */
		$objTemplate            = new BackendTemplate('mod_dlstats_be_stat_details');
		$objTemplate->theme     = Backend::getTheme();
		$objTemplate->base      = Environment::get('base');
		$objTemplate->language  = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title     = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['systemMessages']);
		$objTemplate->charset   = System::getContainer()->getParameter('kernel.charset');
		$objTemplate->contaoversion = ContaoCoreBundle::getVersion();

		if (
			null === Input::get('action', true)
			 || null === Input::get('dlstatsid', true)
		) {
			$objTemplate->messages = '<p class="tl_error">' . $GLOBALS['TL_LANG']['tl_dlstatstatistics_stat']['wrong_parameter'] . '</p>';

			return $objTemplate->getResponse();
		}

		switch (Input::get('action', true))
		{
			case 'TopLastDownloads':
				$DetailFunction = 'getDlstatsDetails' . Input::get('action', true);
				$objTemplate->messages = $this->$DetailFunction(Input::get('action', true), Input::get('dlstatsid', true));
				break;
			default:
				$objTemplate->messages = '<p class="tl_error">' . $GLOBALS['TL_LANG']['tl_dlstatstatistics_stat']['wrong_parameter'] . '</p>';
				break;
		}

		return $objTemplate->getResponse();
	}
}
