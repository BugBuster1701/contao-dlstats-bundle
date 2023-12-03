<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2018 Leo Feyer
 * 
 * Module Download Statistics
 *
 * Log file downloads done by the content elements Download and Downloads, and 
 * show statistics in the backend. 
 *
 * Extends module tl_settings.
 * 
 * PHP version 5
 * @copyright  Glen Langer 2011..2020 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @license    LGPL
 * @filesource
 * @see	       https://github.com/BugBuster1701/contao-dlstats-bundle
 */

/**
 * Add to palette
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['__selector__'][]	= 'dlstats'; 
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default']        .= ';{dlstats_legend},dlstats'; 
$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['dlstats']	= 'dlstatdets,dlstatDisableBotdetection
                                                                    ,dlstatAnonymizeIP4,dlstatAnonymizeIP6
                                                                    ,dlstatTopDownloads,dlstatLastDownloads
                                                                    ,dlstatStatresetProtected
                                                                    ,dlstatStatresetGroups,dlstatStatresetAdmins
                                                                    '; 

/**
 * Add field
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['dlstats'] = array
(
        'label'		=> &$GLOBALS['TL_LANG']['tl_settings']['dlstats'],
        'inputType'	=> 'checkbox',
        'eval'		=> array('submitOnChange'=>true)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dlstatdets'] = array
(
        'label'         => &$GLOBALS['TL_LANG']['tl_settings']['dlstatdets'],
        'inputType'     => 'checkbox',
        'eval'          => array('tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dlstatDisableBotdetection'] = array
(
        'label'		=> &$GLOBALS['TL_LANG']['tl_settings']['dlstatDisableBotdetection'],
        'inputType'	=> 'checkbox',
        'eval'          => array('tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dlstatAnonymizeIP4'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_settings']['dlstatAnonymizeIP4'],
    'inputType' => 'select',
    'default'   => 1,
    'options'   => array(1, 2),
    'reference' => &$GLOBALS['TL_LANG']['tl_settings']['dlstats']['anonip4'],
    'eval'      => array('tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dlstatAnonymizeIP6'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_settings']['dlstatAnonymizeIP6'],
    'inputType' => 'select',
    'default'   => 2,
    'options'   => array(2, 3, 4),
    'reference' => &$GLOBALS['TL_LANG']['tl_settings']['dlstats']['anonip6'],
    'eval'      => array('tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dlstatTopDownloads'] = array
(
        'label'		=> &$GLOBALS['TL_LANG']['tl_settings']['dlstatTopDownloads'],
        'inputType'	=> 'text',
        'default'	=> '20',
        'eval'		=> array('mandatory'=>true, 'rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dlstatLastDownloads'] = array
(
        'label'		=> &$GLOBALS['TL_LANG']['tl_settings']['dlstatLastDownloads'],
        'inputType'	=> 'text',
        'default'	=> '20',
        'eval'		=> array('mandatory'=>true, 'rgxp'=>'digit', 'nospace'=>true, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dlstatStatresetProtected'] = array
(
        'label'		=> &$GLOBALS['TL_LANG']['tl_settings']['dlstatStatresetProtected'],
        'inputType'	=> 'checkbox',
        'eval'		=> array('submitOnChange'=>true, 'tl_class'=>'clr')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dlstatStatresetGroups'] = array
(
        'label'		 => &$GLOBALS['TL_LANG']['tl_settings']['dlstatStatresetGroups'],
        'inputType'	 => 'checkbox',
        'foreignKey'     => 'tl_user_group.name',
        'eval'           => array('multiple'=>true, 'tl_class'=>'dlstats_left20')
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dlstatStatresetAdmins'] = array
(
        'label'		 => &$GLOBALS['TL_LANG']['tl_settings']['dlstatStatresetAdmins'],
        'inputType'	 => 'checkbox',
        'eval'           => array('disabled'=>true, 'tl_class'=>'dlstats_left20'),
        'load_callback'  => array
        (
            function ($data) { return '1'; }
        )
);