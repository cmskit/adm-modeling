<?php


function processBrackets(&$str)
{
    $out = array();
    // Documentation-File <in angle brackets> (eg. doc??)
    if (preg_match('/\<([^\)]+)\>/', $str, $doc))
    {
        $str = trim(preg_replace('/\s*\<[^)]*\>/', '', $str));
        $out['angle'] = $doc[1];
    }

    // Tooltip (in round brackets) (eg. tooltips)
    if (preg_match('/\(([^\)]+)\)/', $str, $ttip))
    {
        $str = trim(preg_replace('/\s*\([^)]*\)/', '', $str));
        $out['round'] = $ttip[1];
    }

    // Placeholder [in square brackets] (eg. placeholders)
    if (preg_match('/\[(.*?)\]/', $str, $pa))
    {
        $str = trim(preg_replace('/\[(.*?)\]/', '', $str));
        $out['square'] = $pa[1];
    }
    return $out;
}

function testForBrackets($str)
{
    $arr = processBrackets($str);
    if(!empty($arr)) {
        $arr['label'] = $str;
        return $arr;
    }
    return $str;
}

/**
* 
*/
function processLabel ($arr)
{
	$out = array();
	
	foreach ($arr as $k => $str)
	{
		
		$out[$k] = processBrackets($str);

		// Accordion-Limiter "--"
		$arr = explode('--', $str);
		if (count($arr) === 2)
		{
			$str = trim($arr[1]);
			$out[$k]['accordionhead'] = trim($arr[0]);
		}
		
		// Tab-Limiter "||"
		$arr = explode('||', $str);
		if (count($arr) === 2)
		{
			$str = trim($arr[1]);
			$out[$k]['tabhead'] = trim($arr[0]);
		}
		
		// define the pure Label
		$out[$k]['label'] = $str;
		
	}
	
	return $out;
}
