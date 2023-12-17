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

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsHook('replaceInsertTags')]
class ReplaceInsertTagsListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly KernelInterface $kernel,
    ) {
    }

    public function __invoke(string $insertTag, bool $useCache, string $cachedValue, array $flags, array $tags, array $cache, int $_rit, int $_cnt)
    {
        $arrTag = StringUtil::trimsplit('::', $insertTag);
        $key = strtolower($arrTag[0]);
        $parameter1 = strtolower($arrTag[1] ?? '');
        $parameter2 = strtolower($arrTag[2] ?? '');

        if ('dlstats' !== $key) {
            if ('cache_dlstats' !== $key) {
                return false; // not for us
            }
        }

        $arrUniqid = StringUtil::trimsplit('.', uniqid('c0n7a0', true));

        $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'Insert-Tag complete        : '.$insertTag);
        // $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'Insert-Tag useCache bool   : '.(int) $useCache);
        // $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'Insert-Tag cachedValue bool: '.(int) $cachedValue);
        // $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'Insert-Tag flags           : '.print_r($flags, true));
        // $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'Insert-Tag tags            : '.print_r($tags, true));
        // $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'Insert-Tag cache           : '.print_r($cache, true));
        // $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'Insert-Tag _rit            : '.(int) $_rit);
        // $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'Insert-Tag _cnt            : '.(int) $_cnt);

        if ('totaldownloads' === $parameter1) {
            $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'Insert-Tag Parameter 1     : '.$parameter1);

            return $this->replaceEventInsertTag($parameter2, $arrUniqid[1]);
        }
        $this->logMonolog($arrUniqid[1], __METHOD__, __LINE__, 'Insert-Tag Parameter 1     : '.$GLOBALS['TL_LANG']['tl_dlstats']['wrong_key'], true);

        return false; // wrong key
    }

    /**
     * Logging via Monolog, debug messages only in debug mode.
     *
     * @param string $uuid
     * @param string $class
     * @param int    $line
     * @param string $message
     */
    public function logMonolog($uuid, $class, $line, $message, $levelIsError = false): void
    {
        if (!$this->kernel->isDebug() && false === $levelIsError) {
            return;
        }

        $timezone = new \DateTimeZone('UTC');
        $datetime = new \DateTime('now', $timezone);
        $strLog = 'dev-'.$datetime->format('Y-m-d').'.log';

        $strLogsDir = null;

        $strLogsDir = $this->kernel->getLogDir();

        if (!$strLogsDir) {
            $strRootDir = $this->kernel->getProjectDir();
            $strLogsDir = $strRootDir.'/var/logs';
        }

        $strMessage = sprintf("%s %s\n", $uuid, $message);

        $logger = new Logger('dlstats');
        $logger->setTimezone($timezone);

        $logger->pushHandler(new StreamHandler($strLogsDir.'/'.$strLog, Logger::DEBUG));

        if (false !== $class) {
            if (false === $levelIsError) {
                $logger->debug($strMessage, ['class' => $class.'::'.$line]);
            } else {
                $logger->error($strMessage, ['class' => $class.'::'.$line]);
            }
        } else {
            if (false === $levelIsError) {
                $logger->debug($strMessage);
            } else {
                $logger->error($strMessage);
            }
        }
        $logger = null;
        unset($logger);
    }

    /**
     * Replace InsertTag with number of downloads.
     *
     * @param string $parameter2 filename
     * @param string $uniqid
     */
    private function replaceEventInsertTag($parameter2, $uniqid): string
    {
        if ('' === $parameter2) {
            $this->logMonolog($uniqid, __METHOD__, __LINE__, 'Insert-Tag Parameter 2     : '.$GLOBALS['TL_LANG']['tl_dlstats']['no_key'], true);

            return '-';
        }
        $q = $this->connection->fetchAssociative('SELECT
                                                            `downloads`
                                                    FROM
                                                            `tl_dlstats`
                                                    WHERE
                                                            `filename` = ?', [urldecode($parameter2)]);
        if (false !== $q) {
            $this->logMonolog($uniqid, __METHOD__, __LINE__, 'Insert-Tag Downloads       : '.(string) $q['downloads']);

            return (string) $q['downloads'];
        }
        $this->logMonolog($uniqid, __METHOD__, __LINE__, 'Insert-Tag Downloads       : '.(string) 0);

        return (string) 0;
    }
}

// Template Anpassung für Ausgabe
// {% block download_link %}
//   {{ parent() }} Downloads: {{ "{{fragment::{{dlstats::totaldownloads::files/%s}}}}"|format(download.file) }}
// {% endblock %}
