<?php 

/**
 * Contao Open Source CMS, Copyright (C) 2005-2018 Leo Feyer
 *
 * Modul Dlstats Tag - Frontend for InsertTags
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
 * Run in a custom namespace, so the class can be replaced
 */
namespace BugBuster\DLStats;
use Psr\Log\LogLevel;
use Contao\CoreBundle\Monolog\ContaoContext;

/**
 * Class ModuleDlstatsTag 
 *
 * @copyright  Glen Langer 2011..2018
 * @author     Glen Langer 
 * @package    DLStats
 * @license    LGPL 
 */
class ModuleDlstatsTag extends \Frontend
{

	/**
	 * replaceInsertTags
	 * 
	 * From TL 2.8 you can use prefix "cache_". Thus the InserTag will be not cached. (when "cache" is enabled)
	 * 
	 *       dlstats::totaldownloads::filename - Total downloads for filename
	 * cache_dlstats::totaldownloads::filename - Total downloads for filename (not cached)
	 * 
	 * <code>
	 * {{cache_dlstats::totaldownloads::tl_files/cdc2010.pdf}}
	 * {{cache_dlstats::totaldownloads::CDC_2010.html?file=tl_files/cdc2010.pdf}}
	 * // in the ce_download template:
	 * {{cache_dlstats::totaldownloads::<?php echo $this->href; ?>}}
	 * // in the ce_downloads template:
	 * {{cache_dlstats::totaldownloads::<?php echo $file['href']; ?>}}
	 * </code>
	 * 
	 * @param string    $strTag Insert-Tag
	 * @return mixed    integer on downloads, false on wrong Insert-Tag or wrong parameters
	 * @access public
	 */
	public function dlstatsReplaceInsertTags($strTag)
	{
		$arrTag = \StringUtil::trimsplit('::', $strTag);
		if ($arrTag[0] != 'dlstats')
		{
			if ($arrTag[0] != 'cache_dlstats')
			{
				return false; // not for us
			}
		}
		$this->loadLanguageFile('tl_dlstats');
		if (!isset($arrTag[2]))
		{
			\System::getContainer()
    			->get('monolog.logger.contao')
    			->log(LogLevel::ERROR,
    			    $GLOBALS['TL_LANG']['tl_dlstats']['no_key'],
    			    array('contao' => new ContaoContext('ModuleDlstatsTag ReplaceInsertTags ', TL_ERROR)));
			
			return false; // da fehlt was
		}
		// filename with article alias?
		if (strpos($arrTag[2], 'file=') !== false)
		{
			$arrTag[2] = substr($arrTag[2], strpos($arrTag[2], 'file=') + 5);
		}
		if ($arrTag[1] == 'totaldownloads')
		{
			$objDlstats = \Database::getInstance()->prepare("SELECT 
                                                                    `downloads`
                                                             FROM
                                                                    `tl_dlstats`
                                                             WHERE
                                                                    `filename` = ?")
                                    ->execute(urldecode($arrTag[2]));
			if ($objDlstats->numRows < 1)
			{
				return 0;
			}
			$objDlstats->next();
			return $objDlstats->downloads;
		}
		// Tag is wrong 
		\System::getContainer()
    		->get('monolog.logger.contao')
    		->log(LogLevel::ERROR,
    		    $GLOBALS['TL_LANG']['tl_dlstats']['wrong_key'],
    		    array('contao' => new ContaoContext('ModuleDlstatsTag ReplaceInsertTags ', TL_ERROR)));
		
		return false; // wrong tag
	} //function
} // class
