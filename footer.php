<?php 
global $time_start;
$time_end = microtime(true);
$time = round(($time_end - $time_start)*1000, 4);
if($printThisPage != '1'){
	$return .= '</div></div>
<div id=footer>'.$footerLinkBuffer.'<br><br>Copyright 2015-'.date('Y').' - '.$Settings->_get('siteName');
	//$return .= '<br><br>Page statistics: '.$PDO->GetCount()." queries used and it took $time ms and ".round(memory_get_peak_usage ()/1000000, 2)." megabyte of ram to generate this page.";
	$return .= '</div></body></html>';
}
?>