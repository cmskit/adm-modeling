<?php
/********************************************************************************
*  Copyright notice
*
*  (c) 2014 Christoph Taubmann (info@cms-kit.org)
*  All rights reserved
*
*  This script is part of cms-kit Framework. 
*  This is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License Version 3 as published by
*  the Free Software Foundation, or (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/licenses/gpl.html
*  A copy is found in the textfile GPL.txt and important notices to other licenses
*  can be found found in LICENSES.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
************************************************************************************/

/**
* collect embed-codes from available Wizards and Extensions
* 
* @param string Project-Name
* @return mixed array with Embed-Codes
*/
function collectExtensionInfos($project)
{

	$backendPath = dirname(dirname(dirname(__DIR__)));

	// collect filelist
	$dirs = 					glob($backendPath . '/wizards/*',	GLOB_ONLYDIR);
	$dirs = array_merge($dirs, 	glob($backendPath . '/extensions/*',	GLOB_ONLYDIR));
	$dirs = array_merge($dirs, 	glob(dirname($backendPath) . '/projects/' . $project . '/extensions/*',	GLOB_ONLYDIR));

    // storage for wizards + hooks
	$embeds = array(
        'wizards' => array(),
        'hooks' => array(),
    );

	// collect Informations from Extensions & Wizards
	foreach($dirs as $dir)
	{
		if(@$str = file_get_contents($dir . '/composer.json'))
		{
			
			//
			if(@$json = json_decode($str, true))
			{

                if(isset($json['extra']['wizards'])) {

                    foreach($json['extra']['wizards'] as $k => $v) {

							if(!isset($embeds['wizards'][$k])) {
                                $embeds['wizards'][$k] = array();
                            }
							
							$embeds['wizards'][$k][] = $v;
					}
				}
				
				// fill hook-embeds
				if(isset($json['extra']['hooks']))
				{
					foreach($json['extra']['hooks'] as $k => $v)
					{
						$embeds['hooks'][$k] = $v;
					}
				}
				
			}
		}
	}// collect END
	
	return $embeds;

}
