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

/**
 * Run in a custom namespace, so the class can be replaced
 */

namespace BugBuster\DLStats;

use BugBuster\BotDetection\ModuleBotDetection;
use Contao\Controller;
use Contao\Database;
use Contao\Environment;
use Contao\System;

/**
 * Class DlstatsHelper
 *
 * @copyright  Glen Langer 2011..2018 <http://contao.ninja>
 * @license    LGPL
 */
class DlstatsHelper extends Controller
{
	/**
	 * The IP address
	 * @var string
	 */
	protected $IP = false;

	/**
	 * The IP version
	 * @var string
	 */
	protected $IP_Version = '';

	/**
	 * The IP filter status
	 * @var boolean
	 */
	protected $IP_Filter = false;

	/**
	 * The BE filter status
	 * @var boolean
	 */
	protected $BE_Filter = false;

	/**
	 * The BOT filter status
	 * @var boolean
	 */
	protected $BOT_Filter = false;

	/**
	 * Status, download logging yes or no
	 * @var boolen
	 */
	protected $DL_LOG = true;

	/**
	 * Browser language
	 * @var string
	 */
	protected $_lang;

	private static $_BackendUser  = false;

	/**
	 * Initialize the object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->checkIP();
		$this->checkBE();
		$this->checkBot();
		$this->setDlLog();
		$this->dlstatsSetLang();
	}

	/**
	 * Set DL_LOG, true: logging OK (default), false not OK
	 */
	public function setDlLog()
	{
		if ($this->IP_Filter === true || $this->BE_Filter === true || $this->BOT_Filter === true)
		{
			$this->DL_LOG = false;
		}
	}

	/**
	 * IP Check
	 * Set IP, detect the IP version and calls the method CheckIPv4 respectively CheckIPv6.
	 *
	 * @param  string  $UserIP User IP, optional for tests
	 * @return boolean true when bot found over IP
	 */
	public function checkIP($UserIP = false)
	{
		// Check if IP present
		if ($UserIP === false)
		{
			$tempIP = $this->dlstatsGetUserIP();
			if ($tempIP !== false)
			{
				$this->IP = $tempIP;
			}
			else
			{
				return false; // No IP, no search.
			}
		}
		else
		{
			$this->IP = $UserIP;
		}

		// IPv4 or IPv6 ?
		switch ($this->checkIPVersion($this->IP))
		{
			case "IPv4":
				if ($this->checkIPv4($this->IP) === true)
				{
					$this->IP_Filter = true;

					return $this->IP_Filter;
				}
				break;
			case "IPv6":
				if ($this->checkIPv6($this->IP) === true)
				{
					$this->IP_Filter = true;

					return $this->IP_Filter;
				}
				break;
			default:
				$this->IP_Filter = false;

				return $this->IP_Filter;
				break;
		}
		$this->IP_Filter = false;

		return $this->IP_Filter;
	}

	/**
	 * BE Login Check
	 * basiert auf Frontend.getLoginStatus
	 *
	 * @return boolean
	 */
	public function checkBE()
	{
		if (false === self::$_BackendUser)
		{
			$objTokenChecker = System::getContainer()->get('contao.security.token_checker');
			if ($objTokenChecker->hasBackendUser())
			{
				self::$_BackendUser = true;
				$this->BE_Filter = true;

				return true;
			}

			return false;
		}
		$this->BE_Filter = true;

		return true;
	}

	/**
	 * Bot Check
	 *
	 * @return mixed true or string if Bot found, false if not
	 */
	public function checkBot()
	{
		$bundles = array_keys(System::getContainer()->getParameter('kernel.bundles')); // old \ModuleLoader::getActive()

		if (!\in_array('BugBusterBotdetectionBundle', $bundles))
		{
			// BotdetectionBundle fehlt, trotzdem zählen, Meldung kommt bereits per Hook
			return false; // fake: no bots found
		}
		if (
			isset($GLOBALS['TL_CONFIG']['dlstatDisableBotdetection'])
			&& (bool) $GLOBALS['TL_CONFIG']['dlstatDisableBotdetection'] === true
		) {
			// botdetection ist disabled for dlstats
			return false; // fake: no bots founds
		}

		$ModuleBotDetection = new ModuleBotDetection();
		if ($ModuleBotDetection->checkBotAllTests())
		{
			$this->BOT_Filter = true;

			return true;
		}

		// No Bots found
		return false;
	}

	/**
	 * Set _lang over Environment::get('httpAcceptLanguage')
	 *
	 * @return boolean true
	 */
	public function dlstatsSetLang()
	{
		$array = Environment::get('httpAcceptLanguage');

		$this->_lang = str_replace('-', '_', $array[0] ?? '');

		if (empty($this->_lang) || \strlen($this->_lang) < 2)
		{
			$this->_lang = 'unknown';
		}

		return true;
	}

	/**
	 * Get _lang
	 *
	 * @return string
	 */
	public function dlstatsGetLang()
	{
		return $this->_lang;
	}

	/**
	 * Get IP_Version
	 *
	 * @return string
	 */
	public function dlstatsGetIpVersion()
	{
		return $this->IP_Version;
	}

	/**
	 * Get IP
	 *
	 * @return string
	 */
	public function dlstatsGetIp()
	{
		return $this->IP;
	}

	public function checkMultipleDownload($fileName)
	{
		return $this->getBlockingStatus($this->IP, $fileName);
	}

	// ////////////////////// protected functions \\\\\\\\\\\\\\\\\\\\\\\\

	/**
	 * IP =  IPv4 or IPv6 ?
	 *
	 * @param  string $UserIP IP Address (IPv4 or IPv6)
	 * @return mixed  false: no valid IPv4 and no valid IPv6
	 *                "IPv4" : IPv4 Address
	 *                "IPv6" : IPv6 Address
	 */
	protected function checkIPVersion($UserIP = false)
	{
		// Test for IPv4
		if (ip2long($UserIP) !== false)
		{
			$this->IP_Version = "IPv4";

			return $this->IP_Version;
		}

		// Test for IPv6
		if (substr_count($UserIP, ":") < 2)
		{
			$this->IP_Version = false;

			return false;
		}
		// ::1 or 2001::0db8
		if (substr_count($UserIP, "::") > 1)
		{
			$this->IP_Version = false;

			return false; // one allowed
		}
		$groups = explode(':', $UserIP);
		$num_groups = \count($groups);
		if (($num_groups > 8) || ($num_groups < 3))
		{
			$this->IP_Version = false;

			return false;
		}
		$empty_groups = 0;

		foreach ($groups as $group)
		{
			$group = trim($group);
			if (!empty($group) && !(is_numeric($group) && ($group == 0)))
			{
				if (!preg_match('#([a-fA-F0-9]{0,4})#', $group))
				{
					$this->IP_Version = false;

					return false;
				}
			}
			else
			{
				++$empty_groups;
			}
		}
		if ($empty_groups < $num_groups)
		{
			$this->IP_Version = "IPv6";

			return $this->IP_Version;
		}
		$this->IP_Version = false;

		return false; // no (valid) IP Address
	}

	/**
	 * IP Check for IPv6
	 *
	 * @param  string  $UserIP User IP
	 * @return boolean true when own IP found in localconfig definitions
	 */
	protected function checkIPv6($UserIP = false)
	{
		// Check if IP present
		if ($UserIP === false)
		{
			return false; // No IP, no search.
		}
		// search for user bot IP-filter definitions in localconfig.php
		if (isset($GLOBALS['DLSTATS']['BOT_IPV6']))
		{
			foreach ($GLOBALS['DLSTATS']['BOT_IPV6'] as $lineleft)
			{
				$network = explode("/", trim($lineleft));
				if (!isset($network[1]))
				{
					$network[1] = 128;
				}
				if ($this->dlstatsIPv6InNetwork($UserIP, $network[0], $network[1]))
				{
					return true; // IP found
				}
			}
		}

		return false;
	}

	/**
	 * IP Check for IPv4
	 *
	 * @param  string  $UserIP User IP
	 * @return boolean true when own IP found in localconfig definitions
	 */
	protected function checkIPv4($UserIP = false)
	{
		// Check if IP present
		if ($UserIP === false)
		{
			return false; // No IP, no search.
		}
		// search for user bot IP-filter definitions in localconfig.php
		if (isset($GLOBALS['DLSTATS']['BOT_IPV4']))
		{
			foreach ($GLOBALS['DLSTATS']['BOT_IPV4'] as $lineleft)
			{
				$network = explode("/", trim($lineleft));
				if (!isset($network[1]))
				{
					$network[1] = 32;
				}
				if ($this->dlstatsIPv4InNetwork($UserIP, $network[0], $network[1]))
				{
					return true; // IP found
				}
			}
		}

		return false;
	}

	/**
	 * Helperfunction, if IPv4 in NET_ADDR/NET_MASK
	 *
	 * @param  string  $ip       IPv4 Address
	 * @param  string  $net_addr Network, optional
	 * @param  int     $net_mask Mask, optional
	 * @return boolean
	 */
	protected function dlstatsIPv4InNetwork($ip, $net_addr = 0, $net_mask = 0)
	{
		if ($net_mask <= 0)
		{
			return false;
		}
		if (ip2long($net_addr) === false)
		{
			return false; // no IP
		}
		// php.net/ip2long : jwadhams1 at yahoo dot com
		$ip_binary_string  = sprintf("%032b", ip2long($ip));
		$net_binary_string = sprintf("%032b", ip2long($net_addr));

		return substr_compare($ip_binary_string, $net_binary_string, 0, $net_mask) === 0;
	}

	/**
	 * Helperfunction, Replace '::' with appropriate number of ':0'
	 *
	 * @param  string $Ip IP Address
	 * @return string IP Address expanded
	 */
	protected function dlstatsIPv6ExpandNotation($Ip)
	{
		if (strpos($Ip, '::') !== false)
		{
			$Ip = str_replace('::', str_repeat(':0', 8 - substr_count($Ip, ':')) . ':', $Ip);
		}
		if (strpos($Ip, ':') === 0)
		{
			$Ip = '0' . $Ip;
		}

		return $Ip;
	}

	/**
	 * Helperfunction, Convert IPv6 address to an integer
	 *
	 * Optionally split in to two parts.
	 *
	 * @see http://stackoverflow.com/questions/420680/
	 * @param  string $Ip            IP Address
	 * @param  int    $DatabaseParts 1 = one part, 2 = two parts (array)
	 * @return mixed  string      / array
	 */
	protected function dlstatsIPv6ToLong($Ip, $DatabaseParts = 1)
	{
		$Ip = $this->dlstatsIPv6ExpandNotation($Ip);
		$Parts = explode(':', $Ip);
		$Ip = array('', '');

		for ($i = 0; $i < 4; $i++)
		{
			$Ip[0] .= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
		}

		for ($i = 4; $i < 8; $i++)
		{
			$Ip[1] .= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
		}

		if ($DatabaseParts == 2)
		{
			return array(base_convert($Ip[0], 2, 10), base_convert($Ip[1], 2, 10));
		}

		return base_convert($Ip[0], 2, 10) + base_convert($Ip[1], 2, 10);
	}

	/**
	 * Helperfunction, if IPv6 in NET_ADDR/PREFIX
	 *
	 * @param  string  $UserIP   IP Address
	 * @param  string  $net_addr NET_ADDR
	 * @param  integer $net_mask PREFIX
	 * @return boolean
	 */
	protected function dlstatsIPv6InNetwork($UserIP, $net_addr = 0, $net_mask = 0)
	{
		if ($net_mask <= 0)
		{
			return false;
		}
		// UserIP to bin
		$UserIP = $this->dlstatsIPv6ExpandNotation($UserIP);
		$Parts = explode(':', $UserIP);
		$Ip = array('', '');

		for ($i = 0; $i < 8; $i++)
		{
			$Ip[0] .= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
		}

		// NetAddr to bin
		$net_addr = $this->dlstatsIPv6ExpandNotation($net_addr);
		$Parts = explode(':', $net_addr);

		for ($i = 0; $i < 8; $i++)
		{
			$Ip[1] .= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
		}

		// compare the IPs
		return substr_compare($Ip[0], $Ip[1], 0, $net_mask) === 0;
	}

	/**
	 * dlstatsAnonymizeIP - Anonymize the last byte(s) of visitors IP addresses
	 *
	 * @return mixed string = IP Address anonymized, false for "no IP"
	 */
	protected function dlstatsAnonymizeIP()
	{
		if ($this->IP_Version === false)
		{
			return '0.0.0.0';
		}
		// Anonymize is enabled (since Contao 4.6 no longer deactivatable.)
		if (!isset($GLOBALS['TL_CONFIG']['dlstatAnonymizeIP4']))
		{
			$GLOBALS['TL_CONFIG']['dlstatAnonymizeIP4'] = 1;
		}
		if (!isset($GLOBALS['TL_CONFIG']['dlstatAnonymizeIP6']))
		{
			$GLOBALS['TL_CONFIG']['dlstatAnonymizeIP6'] = 2;
		}

		switch ($this->IP_Version)
		{
			case "IPv4":
				$arrIP = explode('.', $this->IP); // 0..3
				$arrIP[3] = 0;
				if ($GLOBALS['TL_CONFIG']['dlstatAnonymizeIP4'] == 2)
				{
					$arrIP[2] = 0;
				}

				return implode('.', $arrIP);
				break;
			case "IPv6":
				$arrIP = explode(':', $this->dlstatsIPv6ExpandNotation($this->IP)); // 0..7
				$arrIP[7] = 0;
				$arrIP[6] = 0;
				if ($GLOBALS['TL_CONFIG']['dlstatAnonymizeIP6'] >= 3)
				{
					$arrIP[5] = 0;
				}
				if ($GLOBALS['TL_CONFIG']['dlstatAnonymizeIP6'] == 4)
				{
					$arrIP[4] = 0;
				}

				return implode(':', $arrIP);
				break;
			default:
				return '0.0.0.0';
		}
	}

	/**
	 * dlstatsAnonymizeDomain - Anonymize the Domain of visitors
	 *
	 * @return string Domain anonymized, if DNS entry exists
	 */
	protected function dlstatsAnonymizeDomain()
	{
		if ($this->IP_Version === false || $this->IP === '0.0.0.0')
		{
			return '';
		}
		// Anonymize is enabled (since Contao 4.6 no longer deactivatable.)
		$domain = gethostbyaddr($this->IP);
		if ($domain != $this->IP) // bei Fehler/keiner Aufloesung kommt IP zurueck
		{
			$arrURL = explode('.', $domain);
			$tld  = array_pop($arrURL);
			$host = array_pop($arrURL);

			return (\strlen($host)) ? $host . '.' . $tld : $tld;
		}

		return '';
	}

	/**
	 * Get User IP
	 *
	 * @return string
	 */
	protected function dlstatsGetUserIP()
	{
		$UserIP = Environment::get('ip');
		if (strpos($UserIP, ',') !== false) // first IP
		{
			$UserIP = trim(substr($UserIP, 0, strpos($UserIP, ',')));
		}
		if (
			true === $this->dlstatsIsPrivateIP($UserIP)
			 && false === empty($_SERVER['HTTP_X_FORWARDED_FOR'])
		) {
			// second try
			$HTTPXFF = $_SERVER['HTTP_X_FORWARDED_FOR'];
			$_SERVER['HTTP_X_FORWARDED_FOR'] = '';

			$UserIP = Environment::get('ip');
			if (strpos($UserIP, ',') !== false) // first IP
			{
				$UserIP = trim(substr($UserIP, 0, strpos($UserIP, ',')));
			}
			$_SERVER['HTTP_X_FORWARDED_FOR'] = $HTTPXFF;
		}

		return $UserIP;
	}

	/**
	 * Check if an IP address is from private or reserved ranges.
	 *
	 * @param  string  $UserIP
	 * @return boolean true = private/reserved
	 */
	protected function dlstatsIsPrivateIP($UserIP = false)
	{
		return !filter_var($UserIP, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
	}

	protected function setBlockingIP($UserIP = false, $filename = false)
	{
		if ($UserIP === false)
		{
			$UserIP = $this->IP;
		}
		if ($filename === false)
		{
			$filename = 'no_filename';
		}
		$IPHash = bin2hex(sha1($UserIP, true)); // sha1 20 Zeichen, bin2hex 40 zeichen

		// Insert
		$arrSet = array
		(
			'dlstats_tstamp'   => date('Y-m-d H:i:s'),
			'dlstats_ip'       => $IPHash,
			'dlstats_filename' => $filename
		);
		Database::getInstance()
					->prepare("INSERT IGNORE INTO tl_dlstats_blocker %s")
					->set($arrSet)
					->execute();
	}

	protected function getBlockingStatus($UserIP = false, $filename = false)
	{
		if ($UserIP === false)
		{
			$UserIP = $this->IP;
		}
		if ($filename === false)
		{
			$filename = 'no_filename';
		}

		// Delete All Old Blocker Entries (>10s)
		Database::getInstance()
					->prepare("DELETE FROM
                                    tl_dlstats_blocker
                                WHERE
                                    CURRENT_TIMESTAMP - INTERVAL ? SECOND > dlstats_tstamp
                                ")
					->execute(10);

		$IPHash = bin2hex(sha1($UserIP, true)); // sha1 20 Zeichen, bin2hex 40 zeichen
		// Test ob Blocking gesetzt ist
		$objBlockingIP = Database::getInstance()
							->prepare("SELECT
                                            id
                                        FROM
                                            tl_dlstats_blocker
                                        WHERE
                                            dlstats_ip = ?
                                        AND
                                            dlstats_filename = ?
                                        ")
							->execute($IPHash, $filename);
		if ($objBlockingIP->numRows < 1)
		{
			return false;
		}

		return true;
	}
}
