<?php

/**
 * @copyright  Glen Langer 2008..2017 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Dlstats
 * @license    LGPL-3.0+
 * @see	       https://github.com/BugBuster1701/contao-dlstats-bundle
 *
 */

namespace BugBuster\DlstatsBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

/**
 * Plugin for the Contao Manager.
 *
 * @author Glen Langer (BugBuster)
 */
class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create('BugBuster\DlstatsBundle\BugBusterDlstatsBundle')
                ->setLoadAfter(['Contao\CoreBundle\ContaoCoreBundle'])
                ->setReplace(['dlstats']),
        ];
    }
}
