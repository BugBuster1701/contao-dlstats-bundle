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
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Bundle\SecurityBundle\Security; // Symfony\Component\Security\Core\Security ist deprecated ab 6.2
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
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !($response = $event->getResponse()) instanceof BinaryFileResponse) {
            return;
        }
        $arrUniqid = StringUtil::trimsplit('.', uniqid('c0n7a0', true));

        /** @var PageModel $pageModel */
        $pageModel = $event->getRequest()->attributes->get('pageModel');
        if (!$pageModel instanceof PageModel) {
            $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'DownloadResponseListener invoke: no PageModel');

            return;
        }
        $pageHost = $event->getRequest()->getHost();

        $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'DownloadResponseListener invoke: '.$pageHost);

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

        $force = (bool) ($GLOBALS['TL_CONFIG']['dlstatsforce'] ?? false); // undocumented feature
        $active = (bool) ($GLOBALS['TL_CONFIG']['dlstats'] ?? false);

        if ($active) {
            if (
                (true === $this->DL_LOG || true === $force)
                && false === $this->checkMultipleDownload($this->_filename)
            ) {
                $this->logDLStats($arrUniqid[1]);
                $this->logDLStatDetails($pageModel, $pageHost, $arrUniqid[1]);
            }
        }
    }

    /**
     * Logging via Monolog, only in debug mode.
     *
     * @param string $uuid
     * @param string $class
     * @param int    $line
     * @param string $message
     */
    public function logMonolog($uuid, $class, $line, $message): void
    {
        if (!$this->kernel->isDebug()) {
            return;
        }

        $timezone = new \DateTimeZone('UTC');
        $datetime = new \DateTime('now', $timezone);
        $strLog = 'dev-'.$datetime->format('Y-m-d').'.log';

        $strLogsDir = null;

        $strLogsDir = $this->kernel->getLogDir();

        if (!$strLogsDir) {
            $strLogsDir = $this->projectDir.'/var/logs';
        }

        $strMessage = sprintf("%s %s\n", $uuid, $message);

        $logger = new Logger('dlstats');
        $logger->setTimezone($timezone);
        $logger->pushHandler(new StreamHandler($strLogsDir.'/'.$strLog, Logger::DEBUG));
        if (false !== $class) {
            $logger->debug($strMessage, ['class' => $class.'::'.$line]);
        } else {
            $logger->debug($strMessage);
        }
        $logger = null;
        unset($logger);
    }

    /**
     * Helper function log file name.
     */
    protected function logDLStats($Uniqid): void
    {
        $this->logMonolog($Uniqid, __METHOD__, __LINE__, 'DownloadResponseListener logDLStats');
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
    protected function logDLStatDetails(PageModel $pageModel, $pageHost, $Uniqid): void
    {
        $this->logMonolog($Uniqid, __METHOD__, __LINE__, 'DownloadResponseListener logDLStatDetails');
        // Host / Page ID ermitteln
        $pageId = $pageModel->id;
        $details = (bool) ($GLOBALS['TL_CONFIG']['dlstatdets'] ?? false);

        $q = $this->connection->fetchAssociative('SELECT id FROM `tl_dlstats` WHERE `filename`=?', [$this->_filename]);
        $this->_statId = $q['id'];

        if ($details) {
            $this->logMonolog($Uniqid, __METHOD__, __LINE__, 'DownloadResponseListener logDLStatDetails true');
            // Maximum details for year & month statistic
            $username = '';

            if ($this->security->isGranted('ROLE_MEMBER')) {
                $this->logMonolog($Uniqid, __METHOD__, __LINE__, 'DownloadResponseListener logDLStatDetails ROLE_MEMBER');
                if (($user = $this->security->getUser()) instanceof FrontendUser) {
                    $this->logMonolog($Uniqid, __METHOD__, __LINE__, 'DownloadResponseListener logDLStatDetails FE-User: '.$user);
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
