<?php

declare(strict_types=1);

/*
 * This file is part of a BugBuster Contao Bundle.
 *
 * @copyright  Glen Langer 2023 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    Contao Download Statistics Bundle (Dlstats)
 * @link       https://github.com/BugBuster1701/contao-dlstats-bundle
 *
 * @license    LGPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

namespace BugBuster\DlstatsBundle\EventListener;

use BugBuster\DLStats\DlstatsHelper;
use Contao\CoreBundle\Framework\ContaoFramework;
// use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FrontendUser;
use Contao\PageModel;
// use Contao\System;
use Doctrine\DBAL\Connection;
// use Psr\Log\LogLevel;
use Symfony\Bundle\SecurityBundle\Security; // deprecated ab 6.2 ist Symfony\Component\Security\Core\Security;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Event\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class DownloadResponseListener extends DlstatsHelper
{
    private $projectDir;

    /**
     * tl_dlstats.id.
     *
     * @var int
     */
    private $_statId = 0;

    /**
     * File name for logging.
     *
     * @var string
     */
    private $_filename = '';

    public function __construct(
        private readonly Connection $connection,
        private readonly KernelInterface $kernel,
        private readonly ContaoFramework $framework,
        private readonly Security $security,
    ) {
        $this->projectDir = $kernel->getProjectDir();
        $this->framework->initialize();

        parent::__construct(); // DlstatsHelper check methods
        // System::getContainer()
        //     ->get('monolog.logger.contao')
        //     ->log(LogLevel::INFO,
        //         'DownloadResponseListener Info: construct',
        //         ['contao' => new ContaoContext('DownloadResponseListener construct ', ContaoContext::GENERAL)])
        //   ;
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !($response = $event->getResponse()) instanceof BinaryFileResponse) {
            return;
        }
        /** @var PageModel $pageModel */
        $pageModel = $event->getRequest()->attributes->get('pageModel');
        if (!$pageModel instanceof PageModel) {
            // e.g. /favicon.ico
            // System::getContainer()
            //     ->get('monolog.logger.contao')
            //     ->log(LogLevel::INFO,
            //     'DownloadResponseListener invoke: kein PageModel',
            //     ['contao' => new ContaoContext('DownloadResponseListener MainRequest ', ContaoContext::GENERAL)])
            // ;
            return;
        }
        $pageHost = $event->getRequest()->getHost();
        // System::getContainer()
        //     ->get('monolog.logger.contao')
        //     ->log(LogLevel::INFO,
        //         'DownloadResponseListener invoke: ' . $pageHost,
        //         ['contao' => new ContaoContext('DownloadResponseListener MainRequest ', ContaoContext::GENERAL)])
        // ;

        /** @var Response $response */
        $file = $response->getFile();
        // $response->getFile() gibt ein Symfony\Component\HttpFoundation\File\File Objekt zurück.
        // absoluten Pfad zu einem relativen Pfad umwandeln, damit den entsprechenden Eintrag aus tl_files suchen
        // Dazu kernel.project_dir Parameter injecten und dann mit Hilfe von
        // \Symfony\Component\Filesystem\Path::makeRelative($file->getPathname(), $this->projectDir);
        // den relativen Pfad erzeugen
        $this->_filename = Path::makeRelative($file->getPathname(), $this->projectDir);
        // TODO?
        // Darüberhinaus ein early out einbauen für den Fall, dass der Dateipfad nicht mit dem
        // Parameter contao.upload_path beginnt ("files").
        // Solche Responses kann es ja auch für Dateien außerhalb von files/ geben.
        // contao.upload_path injecten wie kernel.project_dir. Für die Prüfung dann Path::isBasePath nutzen

        if (
            isset($GLOBALS['TL_CONFIG']['dlstats'])
           && true === (bool) $GLOBALS['TL_CONFIG']['dlstats']
        ) {
            if (
                true === $this->DL_LOG
                && false === $this->checkMultipleDownload($this->_filename)
            ) {
                $this->logDLStats();
                $this->logDLStatDetails($pageModel, $pageHost);
            }
        }
    }

    /**
     * Helper function log file name.
     */
    protected function logDLStats(): void
    {
        //     System::getContainer()
        //     ->get('monolog.logger.contao')
        //     ->log(LogLevel::INFO,
        //         'DownloadResponseListener logDLStats',
        //         ['contao' => new ContaoContext('DownloadResponseListener logDLStats ', ContaoContext::GENERAL)])
        //   ;
        $q = $this->connection->fetchAssociative('SELECT id FROM `tl_dlstats` WHERE `filename`=?', [$this->_filename]);
        if (false !== $q) {
            $this->_statId = $q['id'];
            $this->connection->executeQuery('UPDATE `tl_dlstats`
                                            SET `tstamp`=?, `downloads`=`downloads`+1
                                            WHERE `id`=?',
                [time(), $this->_statId],
            );
        } else {
            $data = [
                'tstamp' => time(),
                'filename' => $this->_filename,
                'downloads' => 1,
            ];
            $this->connection->insert('tl_dlstats', $data);
        }
        $this->setBlockingIP($this->IP, $this->_filename);
    }

    /**
     * Helper function log details.
     */
    protected function logDLStatDetails(PageModel $pageModel, $pageHost): void
    {
        //     System::getContainer()
        //     ->get('monolog.logger.contao')
        //     ->log(LogLevel::INFO,
        //         'DownloadResponseListener logDLStatDetails',
        //         ['contao' => new ContaoContext('DownloadResponseListener logDLStatDetails ', ContaoContext::GENERAL)])
        //   ;
        // Host / Page ID ermitteln
        $pageId = $pageModel->id;

        if (
            isset($GLOBALS['TL_CONFIG']['dlstatdets'])
               && true === (bool) $GLOBALS['TL_CONFIG']['dlstatdets']
        ) {
            // Maximum details for year & month statistic
            $username = '';

            if ($this->security->isGranted('ROLE_MEMBER')) {
                if (($user = $this->security->getUser()) instanceof FrontendUser) {
                    $username = $user;
                }
            }

            $data = [
                'tstamp' => time(),
                'pid' => $this->_statId,
                'ip' => $this->dlstatsAnonymizeIP(),
                'domain' => $this->dlstatsAnonymizeDomain(),
                'username' => $username,
                'page_host' => $pageHost,
                'page_id' => $pageId,
                'browser_lang' => $this->dlstatsGetLang(),
            ];
            $this->connection->insert('tl_dlstatdets', $data);
        } else {
            // Minimum details for year & month statistic
            $data = [
                'tstamp' => time(),
                'pid' => $this->_statId,
            ];
            $this->connection->insert('tl_dlstatdets', $data);
        }
    }
}
