<?php

/**
 * 
 * Modul Download Statistics - Eventlistener als postDownload Hook Ersatz
 *
 * Log file downloads done by the content elements Download and Downloads, 
 * and show statistics in the backend. 
 *
 * 
 * @copyright  Glen Langer 2011..2018 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 * @see	       https://github.com/BugBuster1701/contao-dlstats-bundle
 */

namespace BugBuster\DlstatsBundle\EventListener;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\KernelInterface;
use BugBuster\DLStats\DlstatsHelper; 
use Contao\System;
use Contao\Environment;
use Contao\FrontendUser;
use Psr\Log\LogLevel;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Framework\ContaoFramework;

class DownloadResponseListener extends DlstatsHelper
{
    private $connection;
    private $projectDir;
    private $framework;

	/**
	 * tl_dlstats.id
	 * @var integer
	 */
	private $_statId = 0;

    /**
	 * File name for logging
	 * @var string
	 */
	private $_filename = '';

    public function __construct(Connection $connection, KernelInterface $kernel, ContaoFramework $framework)
    {
        $this->connection = $connection;
        $this->projectDir = $kernel->getProjectDir();
        $this->framework = $framework;
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
        System::getContainer()
        ->get('monolog.logger.contao')
        ->log(LogLevel::INFO,
            'DownloadResponseListener invoke: MainRequest',
            ['contao' => new ContaoContext('DownloadResponseListener MainRequest ', ContaoContext::GENERAL)])
      ;

        /** @var \Symfony\Component\HttpKernel\Event\Response $response */
        $file = $response->getFile();
        // $response->getFile() gibt ein Symfony\Component\HttpFoundation\File\File Objekt zurück. 
        // absoluten Pfad zu einem relativen Pfad umwandeln, damit den entsprechenden Eintrag aus tl_files suchen
        // Dazu kernel.project_dir Parameter injecten und dann mit Hilfe von 
        // \Symfony\Component\Filesystem\Path::makeRelative($file->getPathname(), $this->projectDir); 
        // den relativen Pfad erzeugen
        $this->_filename = \Symfony\Component\Filesystem\Path::makeRelative($file->getPathname(), $this->projectDir);
        // TODO?
        // Darüberhinaus ein early out einbauen für den Fall, dass der Dateipfad nicht mit dem 
        // Parameter contao.upload_path beginnt ("files"). 
        // Solche Responses kann es ja auch für Dateien außerhalb von files/ geben.
        // contao.upload_path injecten wie kernel.project_dir. Für die Prüfung dann Path::isBasePath nutzen

        if (isset($GLOBALS['TL_CONFIG']['dlstats']) && 
           (bool) $GLOBALS['TL_CONFIG']['dlstats'] === true)
		{
			if (true  === $this->DL_LOG &&
			    false === $this->checkMultipleDownload($this->_filename) 
               )
			{
				$this->logDLStats();
				$this->logDLStatDetails();
			}
		}
    }

	/**
	 * Helper function log file name
	 * @return void
	 */
	protected function logDLStats()
	{
    //     System::getContainer()
    //     ->get('monolog.logger.contao')
    //     ->log(LogLevel::INFO,
    //         'DownloadResponseListener logDLStats',
    //         ['contao' => new ContaoContext('DownloadResponseListener logDLStats ', ContaoContext::GENERAL)])
    //   ;
        $q = $this->connection->fetchAssociative("SELECT id FROM `tl_dlstats` WHERE `filename`=?", [$this->_filename]);
        if ($q !== false)
        {
            $this->_statId = $q['id'];
            $this->connection->update(
                'tl_dlstats',
                [
                    'tstamp' => time(),
                    'downloads' => 'downloads'+1,
                ],
                [
                    'id' => $this->_statId,
                ]
            );
        }
        else
        {
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
	 * Helper function log details
	 * @return void
	 */
	protected function logDLStatDetails()
	{
    //     System::getContainer()
    //     ->get('monolog.logger.contao')
    //     ->log(LogLevel::INFO,
    //         'DownloadResponseListener logDLStatDetails',
    //         ['contao' => new ContaoContext('DownloadResponseListener logDLStatDetails ', ContaoContext::GENERAL)])
    //   ;
	    //Host / Page ID ermitteln
        $objPage = System::getContainer()->get('request_stack')->getCurrentRequest()->get('pageModel');
        $pageId = $objPage->id;
	    $pageHost = Environment::get('host'); // Host der grad aufgerufenden Seite.

	    if (isset($GLOBALS['TL_CONFIG']['dlstatdets']) 
	           && (bool) $GLOBALS['TL_CONFIG']['dlstatdets'] === true
	       )
	    {
	        //Maximum details for year & month statistic
            $username = '';
// TODO: https://docs.contao.org/dev/reference/services/#security-helper

			$container = System::getContainer();
			$authorizationChecker = $container->get('security.authorization_checker');
			if ($authorizationChecker->isGranted('ROLE_MEMBER'))
			{
				$user = FrontendUser::getInstance();
				$username = $user->username;
			}

            $data = [
                'tstamp'    => time(), 
                'pid'       => $this->_statId, 
                'ip'        => $this->dlstatsAnonymizeIP(), 
                'domain'    => $this->dlstatsAnonymizeDomain(), 
                'username'  => $username,
                'page_host' => $pageHost,
                'page_id'   => $pageId,
                'browser_lang' => $this->dlstatsGetLang(),
            ];
            $this->connection->insert('tl_dlstatdets', $data);
	    }
	    else
	    {
	        //Minimum details for year & month statistic
            $data = [
                'tstamp'    => time(), 
                'pid'       => $this->_statId,
            ];
            $this->connection->insert('tl_dlstatdets', $data);
	    }
	}
}
