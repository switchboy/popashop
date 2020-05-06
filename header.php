<?php
$return .= '<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="w3.css">';

if($printThisPage != '1'){
	$return .= "<link rel=\"stylesheet\" href=\"themes/w3-theme-blue.css\">
			<link rel=\"stylesheet\" type=\"text/css\" href=\"site.css\" media=\"screen, handheld, projection\">
	<link rel=\"stylesheet\" href=\"sitem.css\" type=\"text/css\" media=\"(max-width: 900px),(max-device-width: 767px) and (orientation: portrait),(max-device-width: 499px) and (orientation: landscape)\">";		 
}

$return .= '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<title>'.$this->pageTitle.'</title>
</head>
<body>';
if($printThisPage != '1'){
	$return .= "<div id=logowrapper class=\"w3-theme\">
			<div id=logo>
				<div id='desktop'>
					<a href='#' id='mobile' onclick=\"if(document.getElementById('menu').style.display == 'table-cell'){document.getElementById('menu').style.display='none';return false;} else {document.getElementById('menu').style.display='table-cell';return false;}\"></a>
					<a href='index.php' id='index'></a>
					</div>
			<a href='index.php?action=showCart' id='cart'><span id='cartnumber'>".$myCart->cartTextNumberOfItems()."</span></a>";
	if($User->isLoggedIn()){
		$return .= "<a href='index.php?action=logout' id='logout'></a>";
	} else {
		$return .= "<a href='index.php?action=login' id='login'></a><a href='index.php?action=register' id='register'></a>";
	}
	$return .="<div id='search' style='text-align: center'><br>
					<form action='index.php' method='GET'><input type='hidden' name='action' value='showSearch'>
						<input style='width: 40%;height:34px;margin-top:0px;padding:2px;vertical-align:middle' type='text' name='q' placeholder='Zoeken...'>
						<button class=\"w3-btn w3-theme-l2\" type='submit'>Zoeken</button>
					</form>
				</div>
				</div>
			</div>
			
";
 }        
	$return .="<div id=container><div id=subcontainer>";
		
?>