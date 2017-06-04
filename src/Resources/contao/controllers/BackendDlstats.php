<?php

/**
 * @copyright  Glen Langer 2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Dlstats
 * @license    LGPL-3.0+
 * @see	       https://github.com/BugBuster1701/contao-dlstats-bundle
 */

namespace BugBuster\DLStats;

use Symfony\Component\HttpFoundation\Response;
use BugBuster\DLStats\ModuleDlstatsStatisticsHelper;


/**
 * Back end dlstats wizard.
 *
 * @author     Glen Langer (BugBuster)
 */
class BackendDlstats extends ModuleDlstatsStatisticsHelper
{

	/**
	 * Initialize the controller
	 *
	 * 1. Import the user
	 * 2. Call the parent constructor
	 * 3. Authenticate the user
	 * 4. Load the language files
	 * DO NOT CHANGE THIS ORDER!
	 */
	public function __construct()
	{
		$this->import('BackendUser', 'User');
		parent::__construct();

		$this->User->authenticate();

		\System::loadLanguageFile('default');
		\System::loadLanguageFile('modules');
		\System::loadLanguageFile('tl_dlstatstatistics_stat');
	}


	/**
	 * Run the controller and parse the template
	 *
	 * @return Response
	 */
	public function run()
	{
		/** @var BackendTemplate|object $objTemplate */
		$objTemplate = new \BackendTemplate('mod_dlstats_be_stat_details');
		$objTemplate->theme         = \Backend::getTheme();
		$objTemplate->base          = \Environment::get('base');
		$objTemplate->language      = $GLOBALS['TL_LANGUAGE'];
		$objTemplate->title         = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['systemMessages']);
		$objTemplate->charset       = \Config::get('characterSet');

		
		if ( is_null( \Input::get('action'   ,true) ) ||
		     is_null( \Input::get('dlstatsid',true) ) )
		{
		    $objTemplate->messages = '<p class="tl_error">'.$GLOBALS['TL_LANG']['tl_dlstatstatistics_stat']['wrong_parameter'].'</p>';
		    return $objTemplate->getResponse();
		}
		
		
		switch (\Input::get('action',true))
		{
		    case 'TopLastDownloads' :
		        $DetailFunction = 'getDlstatsDetails'.\Input::get('action',true);
		        $objTemplate->messages = $this->$DetailFunction( \Input::get('action',true), \Input::get('dlstatsid',true) );
		        break;
		    default:
		        $objTemplate->messages = '<p class="tl_error">'.$GLOBALS['TL_LANG']['tl_dlstatstatistics_stat']['wrong_parameter'].'</p>';
		        break;
		}
		
		return $objTemplate->getResponse();
	}
}
