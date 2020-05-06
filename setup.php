<?php
set_time_limit(0);
$errormsg = FALSE;
$noPost = FALSE;

if(!isset($_POST['shopname']) OR $_POST['shopname'] == ''){
	$errormsg = $errormsg."Geen winkelnaam opgegeven.<br>";
} else {
	$shopname = $_POST['shopname'];
}
if(!isset($_POST['fullpath']) OR $_POST['fullpath'] == ''){
	$errormsg = $errormsg."Geen pad naar het script ingevoerd voorbeeld: http://www.voorbeeld.nl/<br>";
} else {
	if(!filter_var($_POST['fullpath'], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){
		$errormsg = $errormsg."Ongeldig pad naar het script ingevoerd http://www.voorbeeld.nl/<br>";
	} else {
		$fullpath = $_POST['fullpath'];
	}
}
if(!isset($_POST['shopdomain']) OR $_POST['shopdomain'] == ''){
	$errormsg = $errormsg."Geen domeinnaam ingevoerd.<br>";
} else {
	if(!is_valid_domain_name($_POST['shopdomain'])){
		$errormsg = $errormsg."Geen geldige domeinnaam ingevoerd: 'voorbeeld.nl'<br>";
	} else {
		$shopdomain = $_POST['shopdomain'];
	}
}
if(isset($_POST['username']) && $_POST['username'] != ''){
	$username = $_POST['username'];
	if (preg_match('/\s/',$username)){
		$errormsg = $errormsg."Een gebruiksersnaam mag geen spaties bevatten.<br>";
	}
} else {
	$errormsg = $errormsg."Geen gebruikersnaam ingevoerd.<br>";
}

if(isset($_POST['email']) && $_POST['email'] != ''){
	$email = $_POST['email'];
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errormsg = $errormsg."Onjuist email adres ingevoerd.<br>";
	}
} else {
	$errormsg = $errormsg."Geen email adres ingevoerd.<br>";
}

if(isset($_POST['password']) AND $_POST['password'] != ''){
	if($_POST['password'] == $_POST['cpassword']){
		$password = $_POST['password'];
		if(strlen ($password) >= '8'){
			$salt = createSalt();
			$hash = sha1($salt . $_POST['password']);
		} else {
			$errormsg = $errormsg."Het beheerderswachtwoord moet minstens 8 tekens bevatten.<br>";
		}
	} else {
		$errormsg = $errormsg."Het beheerderswachtwoord komt niet overeen met het controle wachtwoord.<br>";
	}
} else {
	$errormsg = $errormsg."Geen beheerderswachtwoord ingevoerd.<br>";
}

if(isset($_POST['dbserver']) && $_POST['dbname'] && $_POST['dbusername'] && $_POST['dbpassword']){
	$dbserver = $_POST['dbserver'];
	$dbname = $_POST['dbname'];
	$dbusername = $_POST['dbusername'];
	$dbpassword = $_POST['dbpassword'];	
	$PDO = connectPDO($dbserver, $dbname, $dbusername, $dbpassword);
} else {
	$noPost = TRUE;
}

if(!$noPost){
	if(!isset($_POST['package']) OR $_POST['package'] == ''){
		$errormsg = $errormsg."Geen pakket gekozen.<br>";
	}
}


if($noPost OR $errormsg){
	if($noPost){
		$errormsg = FALSE;
	}
	echo showHeader().$errormsg.showSetupForm().showFooter();
} else {
	createDatabase();
	file_put_contents('variables.php', '<?php $dbserver="'.$dbserver.'"; $dbname="'.$dbname.'"; $dbusername="'.$dbusername.'"; $dbpassword="'.$dbpassword.'"; ?>');
	$insert = $PDO->prepare("INSERT INTO klanten (username, email, wachtwoord, salt, active, admin) VALUES (:username, :email, :password, :salt, 1, 1) ");
	$insert->execute(array(':username' => $username, ':email' => $email, ':password' => $hash, ':salt' => $salt));
	switch($_POST['package']){
		case 1:
			$siteSettings = $PDO->prepare("INSERT INTO shopsettings (shopname, shopdomain, fullpath, productLimit, catLimit, allowEmployees, allowReviews, themeLevel, allowServiceOrders, allowServiceCenter, supplyTracking, productHistory, allowPromotions)
															values (:shopname, :shopdomain, :fullpath, 20, 2, 0, 0, 0, 0, 0, 0, 0, 0)");
			break;
	
		case 2:
			$siteSettings = $PDO->prepare("INSERT INTO shopsettings (shopname, shopdomain, fullpath, productLimit, catLimit, allowEmployees, allowReviews, themeLevel, allowServiceOrders, allowServiceCenter, supplyTracking, productHistory, allowPromotions)
															values (:shopname, :shopdomain, :fullpath, 200, 10, 0, 1, 1, 1, 0, 0, 0, 1)");
			break;
	
		case 3:
			$siteSettings = $PDO->prepare("INSERT INTO shopsettings (shopname, shopdomain, fullpath, productLimit, catLimit, allowEmployees, allowReviews, themeLevel, allowServiceOrders, allowServiceCenter, supplyTracking, productHistory, allowPromotions)
															values (:shopname, :shopdomain, :fullpath, 1000, 50, 1, 1, 2, 1, 1, 1, 0, 1)");
			break;
	
		case 4:
			$siteSettings = $PDO->prepare("INSERT INTO shopsettings (shopname, shopdomain, fullpath, productLimit, catLimit, allowEmployees, allowReviews, themeLevel, allowServiceOrders, allowServiceCenter, supplyTracking, productHistory, allowPromotions)
															values (:shopname, :shopdomain, :fullpath, 0, 0, 1, 1, 2, 1, 1, 1, 1, 1)");
			break;
	}
	$siteSettings->execute(array(':shopname' => $shopname, ':shopdomain' => $shopdomain, ':fullpath' => $fullpath));
	$PDO->query("INSERT INTO frontpage (title, text) VALUES ('Standaard welkomstbericht', '<p>Dit is de standaard welkomst tekst, pas deze aan bij product opties.</p>')");
	echo showHeader()."<h3>Gelukt!</h3>Webshop is aangemaakt. Delete 'setup.php' om verder te gaan.".showFooter();
}


function createSalt()
{
	$string = md5(uniqid(rand(), true));
	return substr($string, 0, 3);
}

function is_valid_domain_name($domain_name){
	return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
			&& preg_match("/^.{1,253}$/", $domain_name) //overall length check
			&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
}

function connectPDO($dbserver, $dbname, $dbusername, $dbpassword){
	global $errormsg;
	try{
		$db = new PDO("mysql:host=".$dbserver.";dbname=".$dbname.";charset=UTF8", $dbusername, $dbpassword);
		$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
	}
	catch(PDOException $e) {
		$errormsg .= "De verbinding met de database kon niet worden gemaakt! Controleer uw inlog gegevens en probeer het opnieuw.<br>";
		return FALSE;
	}
	return $db;
}

function showHeader(){
	return "<!DOCTYPE html>
<html>
<head>
	<meta name=\"viewport\" content=\"width=device-width\">
	<link rel=\"shortcut icon\" href=\"favicon.ico\" type=\"image/x-icon\">
	<link rel=\"stylesheet\" href=\"w3.css\"><link rel=\"stylesheet\" href=\"themes/w3-theme-blue.css\">
			<link rel=\"stylesheet\" type=\"text/css\" href=\"site.css\" media=\"screen, handheld, projection\">
	<link rel=\"stylesheet\" href=\"sitem.css\" type=\"text/css\" media=\"(max-width: 900px),(max-device-width: 767px) and (orientation: portrait),(max-device-width: 499px) and (orientation: landscape)\"><link rel=\"shortcut icon\" href=\"favicon.ico\" type=\"image/x-icon\">
	<title>eShop</title>
</head>
<body>
	<div id=logowrapper class=\"w3-theme\">
		<div id=logo>
			<div id='desktop'>
				<a href='index.php' id='index'></a>
			</div>
		</div>
	</div>
	<div id='container'>";
}

function showFooter(){
	return "</div><div id=footer><br><br>Copyright 2015-".date('Y')." - Pop-a-Shop</div></body></html>";
}

function showSetupForm(){
	return "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Pop-a-Shop installeren</h2></div><form class='w3-container' action='setup.php' method='post'>
				<h3>Database gegevens:</h3>
				<p><label>Database server</label><input class='w3-input w3-border' type='text' name='dbserver' value=''></input></p>
				<p><label>Database naam</label><input class='w3-input w3-border' type='text' name='dbname' value=''></input></p>
				<p><label>Database gebruikersnaam</label><input class='w3-input w3-border' type='text' name='dbusername' value=''></input></p>
				<p><label>Database wachtwoord</label><input class='w3-input w3-border' type='password' name='dbpassword' value=''></input></p>
				<h3>Website gegevens</h3>
				<p><label>Winkel naam</label><input class='w3-input w3-border' type='text' name='shopname' value=''></input></p>
				<p><label>Winkel domein bijvoorbeeld 'pop-a-shop.nl'</label><input class='w3-input w3-border' type='text' name='shopdomain' value=''></input></p>
				<p><label>Pas naar de webwinkel bijvoorbeeld: 'https://www.pop-a-shop.nl/'</label><input class='w3-input w3-border' type='text' name='fullpath' value=''></input></p>
				<p><label>Webwinkel pakket</label><select class='w3-input w3-border' name='package'><option selected value='' disabled>Kies een pakket</option><option value='1'>gratis</option><option value='2'>core</option><option value='3'>professional</option><option value='4'>enterprise</option></select></p>
				<h3>Beheerders account</h3>
				<p><label>Gebruikersnaam</label><input class='w3-input w3-border' type='text' name='username' value=''></input></p>
				<p><label>E-mail</label><input class='w3-input w3-border' type='text' name='email' value=''></input></p>
				<p><label>Wachtwoord</label><input class='w3-input w3-border' type='password' name='password' value=''></input></p>
				<p><label>Controle wachtwoord</label><input class='w3-input w3-border' type='password' name='cpassword' value=''></input></p>
				<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Webwinkel starten'></p>
			</form></div>";
}

function createDatabase(){
	global $PDO;
	$databaseStructure = 
"CREATE TABLE `betalingsopties` (`id` int(11) NOT NULL, `betaalmethode` text NOT NULL,`mollie` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `categorie` (`id` int(11) NOT NULL, `naam` text NOT NULL, `subfromcatid` int(11) DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `countries` (`countryCode` varchar(2) NOT NULL, `name` text NOT NULL, `ship` int(11) NOT NULL DEFAULT '0', `charge` float NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `discountcoupons` (`id` int(11) NOT NULL, `couponCode` varchar(20) NOT NULL, `startTime` int(11) NOT NULL, `stopTime` int(11) NOT NULL, `type` int(11) NOT NULL, `value` float NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `frontpage` (`id` int(11) NOT NULL, `text` text NOT NULL, `title` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `innames` (`id` int(11) NOT NULL, `uid` int(11) NOT NULL, `cid` int(11) NOT NULL, `pid` int(11) NOT NULL, `innamedatum` int(11) NOT NULL, `retourdatum` int(11) DEFAULT NULL, `conditie` TEXT NOT NULL, `statusid` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `innamestatus` (`id` int(11) NOT NULL, `status` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `klanten` (`id` int(11) NOT NULL, `username` text NOT NULL, `voornaam` text NOT NULL, `achternaam` text NOT NULL, `tussenvoegsel` text NOT NULL, `straatnaam` text NOT NULL, `huisnummer` text NOT NULL, `toevoeging` text NOT NULL, `postcode` text NOT NULL, `stad` text NOT NULL, `geboortedatum` text NOT NULL, `bedrijfsnaam` text NOT NULL, `email` text NOT NULL, `wachtwoord` text NOT NULL, `salt` text NOT NULL, `admin` int(11) NOT NULL DEFAULT '0', `employee` int(11) NOT NULL DEFAULT '0', `editproduct` int(11) NOT NULL DEFAULT '0', `service` int(11) NOT NULL DEFAULT '0', `login_ip` text NOT NULL, `last_attempt` int(11) NOT NULL, `login_attempts` int(11) NOT NULL, `attempt_ip` text NOT NULL, `kvknummer` int(11) DEFAULT NULL, `bedrijf` int(11) NOT NULL, `reghash` text NOT NULL, `newsletter` INT NOT NULL DEFAULT '1', `active` int(11) NOT NULL DEFAULT '0') ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `newsletters` (`id` int(11) NOT NULL, `date` int(11) NOT NULL, `uid` int(11) NOT NULL, `lasteditdate` int(11) NOT NULL, `subject` text NOT NULL, `text` text NOT NULL, `publish` int(11) NOT NULL DEFAULT '0') ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `orderproducten` (`id` int(11) NOT NULL, `orderid` int(11) NOT NULL, `productid` int(11) NOT NULL, `prijs` float NOT NULL, `aantal` int(11) NOT NULL, `ordergereserveerd` int(11) NOT NULL DEFAULT '0', `orderbesteld` int(11) NOT NULL, `TOS` int(11) DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `orders` (`id` int(11) NOT NULL, `userId` int(11) NOT NULL, `email` text NOT NULL, `prijs` float NOT NULL, `voornaam` text NOT NULL, `achternaam` text NOT NULL, `tussenvoegsel` text NOT NULL, `straat` text NOT NULL, `huisnummer` text NOT NULL, `toevoeging` text NOT NULL, `postcode` text NOT NULL, `stad` text NOT NULL, `landCode` varchar(2) NOT NULL, `betaalmethode` int(11) NOT NULL, `voldaan` tinyint(1) NOT NULL DEFAULT '0', `aborted` int(11) NOT NULL, `compleet` tinyint(1) NOT NULL DEFAULT '0', `geraapt` int(11) NOT NULL DEFAULT '0', `verzonden` int(11) NOT NULL DEFAULT '0', `mollie` text NOT NULL, `emailtext` text NOT NULL,`costumercomfirmed` int(11) NOT NULL DEFAULT '0', `besteldatum` int(11) NOT NULL, `discountCoupon` varchar(20) NOT NULL, `shop` int(11) NOT NULL, `service` int(11) DEFAULT '0', `siud` int(11) NOT NULL, `firstCheckComplete` int(11) NOT NULL DEFAULT '0', `rebatedone` int(11) NOT NULL DEFAULT '0', `rekeningnummer` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `productdetails` (`id` int(11) NOT NULL, `productId` int(11) NOT NULL, `naam` text NOT NULL, `waarde` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `producten` (`id` int(11) NOT NULL, `productNaam` text NOT NULL, `prijs` float NOT NULL, `verkocht` int(11) NOT NULL DEFAULT '0', `voorraad` int(11) NOT NULL, `gereserveerd` int(11) NOT NULL, `externBesteld` int(11) NOT NULL, `uitstaandeBestelling` int(11) NOT NULL, `categorieId` int(11) NOT NULL, `voorraad_leverancier` int(11) NOT NULL, `actief` int(11) NOT NULL DEFAULT '1', `btwtarief` int(11) NOT NULL DEFAULT '21', `rek` int(11) NOT NULL, `vak` int(11) NOT NULL, `plank` int(11) NOT NULL,  `service` int(11) NOT NULL DEFAULT '0') ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `productplaatjes` (`id` int(11) NOT NULL, `productId` int(11) NOT NULL, `url` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `productreview` (`id` int(11) NOT NULL, `pid` int(11) NOT NULL, `uid` int(11) NOT NULL, `rating` int(11) NOT NULL, `review` text) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `producttracking` (`id` int(11) NOT NULL, `pid` int(11) NOT NULL, `oid` int(11) NOT NULL, `rek` int(11) NOT NULL, `vak` int(11) NOT NULL, `plank` int(11) NOT NULL, `history` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `promobanner` (`id` int(11) NOT NULL, `imageurl` text NOT NULL, `pid` int(11) NOT NULL, `active` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `promotion` (`id` int(11) NOT NULL, `catid` int(11) NOT NULL, `productid` int(11) NOT NULL, `active` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `servicecalls` (`id` int(11) NOT NULL, `uid` int(11) NOT NULL, `cid` int(11) NOT NULL, `eid` int(11) NOT NULL, `date` int(11) NOT NULL, `text` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `serviceepisodes` (`id` int(11) NOT NULL, `uid` int(11) NOT NULL, `cid` int(11) NOT NULL, `date` int(11) NOT NULL, `title` text NOT NULL, `open` int(11) NOT NULL DEFAULT '1') ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `shopmessages` (`id` int(11) NOT NULL, `uid` int(11) NOT NULL, `title` text NOT NULL, `text` text NOT NULL, `date` int(11) NOT NULL, `publish` int(11) NOT NULL, `linkmenu` int(11) NOT NULL, `linkfooter` int(11) NOT NULL, `lasteditdate` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `shopsettings` (`id` int(11) NOT NULL,  `shopname` text NOT NULL,  `shopdomain` text NOT NULL,  `fullpath` text NOT NULL,  `mollieapikey` text NOT NULL,  `remboursfee` float NOT NULL,  `rekeningnummer` text NOT NULL,  `standaardShipCountry` varchar(2) NOT NULL,  `productLimit` int(11) NOT NULL,  `catLimit` int(11) NOT NULL DEFAULT '2',  `allowEmployees` int(11) NOT NULL DEFAULT '0',  `allowReviews` int(11) NOT NULL DEFAULT '0',  `themeLevel` int(11) NOT NULL DEFAULT '0',  `allowServiceOrders` int(11) NOT NULL DEFAULT '0',  `allowServiceCenter` int(11) NOT NULL DEFAULT '0',  `supplyTracking` int(11) NOT NULL DEFAULT '0',  `productHistory` int(11) NOT NULL DEFAULT '0',  `allowPromotions` int(11) NOT NULL DEFAULT '0') ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `winkelwagen` (`id` bigint(20) NOT NULL,  `userId` int(11) DEFAULT NULL,  `sessionId` text NOT NULL,  `lastEditTime` int(11) NOT NULL,  `discountCoupon` varchar(20) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE `winkelwagen_producten` (`id` int(11) NOT NULL,  `cartId` int(11) NOT NULL,  `productId` int(11) NOT NULL,  `aantal` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `betalingsopties` ADD PRIMARY KEY (`id`);
ALTER TABLE `categorie` ADD PRIMARY KEY (`id`);
ALTER TABLE `countries` ADD PRIMARY KEY (`countryCode`);
ALTER TABLE `discountcoupons` ADD PRIMARY KEY (`id`);
ALTER TABLE `frontpage` ADD PRIMARY KEY (`id`);
ALTER TABLE `innames` ADD PRIMARY KEY (`id`);
ALTER TABLE `innamestatus` ADD PRIMARY KEY (`id`);
ALTER TABLE `klanten` ADD PRIMARY KEY (`id`);
ALTER TABLE `newsletters`  ADD PRIMARY KEY (`id`);
ALTER TABLE `orderproducten` ADD PRIMARY KEY (`id`);
ALTER TABLE `orders` ADD PRIMARY KEY (`id`);
ALTER TABLE `productdetails` ADD PRIMARY KEY (`id`), ADD KEY `productId` (`productId`);
ALTER TABLE `producten` ADD PRIMARY KEY (`id`), ADD KEY `categorieId` (`categorieId`);
ALTER TABLE `productplaatjes` ADD PRIMARY KEY (`id`);
ALTER TABLE `productreview` ADD PRIMARY KEY (`id`);
ALTER TABLE `producttracking` ADD PRIMARY KEY (`id`);
ALTER TABLE `promobanner` ADD PRIMARY KEY (`id`);
ALTER TABLE `promotion`  ADD PRIMARY KEY (`id`);
ALTER TABLE `servicecalls` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `id` (`id`), ADD KEY `id_2` (`id`), ADD KEY `uid` (`uid`), ADD KEY `cid` (`cid`);
ALTER TABLE `serviceepisodes` ADD PRIMARY KEY (`id`);
ALTER TABLE `shopmessages` ADD PRIMARY KEY (`id`);
ALTER TABLE `shopsettings` ADD PRIMARY KEY (`id`);
ALTER TABLE `winkelwagen` ADD PRIMARY KEY (`id`);
ALTER TABLE `winkelwagen_producten`  ADD PRIMARY KEY (`id`),   ADD KEY `id` (`id`),  ADD KEY `cartId` (`cartId`);
ALTER TABLE `betalingsopties` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `categorie` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `discountcoupons` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `frontpage` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `innames` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `innamestatus` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `klanten` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `newsletters` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `orderproducten` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `orders` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `productdetails` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `producten` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `productplaatjes` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `productreview` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `producttracking` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `promobanner` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `promotion` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `servicecalls` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `serviceepisodes` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `shopmessages` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `shopsettings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `winkelwagen` MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `winkelwagen_producten`  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";
	foreach(preg_split("/[;]+/", $databaseStructure) as $line){
		$PDO->query($line);
	}
	$PDO->query("INSERT INTO `countries` (`countryCode`, `name`, `ship`, `charge`) VALUES('AD', 'Andorra', 0, 0),('AE', 'Verenigde Arabische Emiraten', 0, 0),('AF', 'Afghanistan', 0, 0),('AG', 'Antigua en Barbuda', 0, 0),('AI', 'Anguilla', 0, 0),('AL', 'Albani&euml;', 0, 0),('AM', 'Armeni&euml;', 0, 0),('AO', 'Angola', 0, 0),('AQ', 'Antarctica', 0, 0),('AR', 'Argentini&euml;', 0, 0),('AS', 'Amerikaans-Samoa', 0, 0),('AT', 'Oostenrijk', 0, 0),('AU', 'Australi&euml;', 0, 0),('AW', 'Aruba', 0, 0),('AX', '&Aring;land', 0, 0),('AZ', 'Azerbeidzjan', 0, 0),('BA', 'Bosni&euml; en Herzegovina', 0, 0),('BB', 'Barbados', 0, 0),('BD', 'Bangladesh', 0, 0),('BE', 'Belgi&euml;', 0, 0),('BF', 'Burkina Faso', 0, 0),('BG', 'Bulgarije', 0, 0),('BH', 'Bahrein', 0, 0),('BI', 'Burundi', 0, 0),('BJ', 'Benin', 0, 0),('BL', 'Saint-Barth&eacute;lemy', 0, 0),('BM', 'Bermuda', 0, 0),('BN', 'Brunei', 0, 0),('BO', 'Bolivia', 0, 0),('BQ', 'Bonaire Sint Eustatius en Saba', 0, 0),('BR', 'Brazili&euml;', 0, 0),('BS', 'Bahama''s', 0, 0),('BT', 'Bhutan', 0, 0),('BV', 'Bouveteiland', 0, 0),('BW', 'Botswana', 0, 0),('BY', 'Wit-Rusland', 0, 0),('BZ', 'Belize', 0, 0),('CA', 'Canada', 0, 0),('CC', 'Cocoseilanden', 0, 0),('CD', 'Congo-Kinshasa', 0, 0),('CF', 'Centraal-Afrikaanse Republiek', 0, 0),('CG', 'Congo-Brazzaville', 0, 0),('CH', 'Zwitserland', 0, 0),('CI', 'Ivoorkust', 0, 0),('CK', 'Cookeilanden', 0, 0),('CL', 'Chili', 0, 0),('CM', 'Kameroen', 0, 0),('CN', 'China', 0, 0),('CO', 'Colombia', 0, 0),('CR', 'Costa Rica', 0, 0),('CU', 'Cuba', 0, 0),('CV', 'Kaapverdi&euml;', 0, 0),('CW', 'Cura&ccedil;ao', 0, 0),('CX', 'Christmaseiland', 0, 0),('CY', 'Cyprus', 0, 0),('CZ', 'Tsjechi&euml;', 0, 0),('DE', 'Duitsland', 0, 0),('DJ', 'Djibouti', 0, 0),('DK', 'Denemarken', 0, 0),('DM', 'Dominica', 0, 0),('DO', 'Dominicaanse Republiek', 0, 0),('DZ', 'Algerije', 0, 0),('EC', 'Ecuador', 0, 0),('EE', 'Estland', 0, 0),('EG', 'Egypte', 0, 0),('EH', 'Westelijke Sahara', 0, 0),('ER', 'Eritrea', 0, 0),('ES', 'Spanje', 0, 0),('ET', 'Ethiopi&euml;', 0, 0),('FI', 'Finland', 0, 0),('FJ', 'Fiji', 0, 0),('FK', 'Falklandeilanden', 0, 0),('FM', 'Micronesia', 0, 0),('FO', 'Faer&ouml;er', 0, 0),('FR', 'Frankrijk', 0, 0),('GA', 'Gabon', 0, 0),('GB', 'Verenigd Koninkrijk', 0, 0),('GD', 'Grenada', 0, 0),('GE', 'Georgi&euml;', 0, 0),('GF', 'Frans-Guyana', 0, 0),('GG', 'Guernsey', 0, 0),('GH', 'Ghana', 0, 0),('GI', 'Gibraltar', 0, 0),('GL', 'Groenland', 0, 0),('GM', 'Gambia', 0, 0),('GN', 'Guinee', 0, 0),('GP', 'Guadeloupe', 0, 0),('GQ', 'Equatoriaal-Guinea', 0, 0),('GR', 'Griekenland', 0, 0),('GS', 'Zuid-Georgia en de Zuidelijke Sandwicheilanden', 0, 0),('GT', 'Guatemala', 0, 0),('GU', 'Guam', 0, 0),('GW', 'Guinee-Bissau', 0, 0),('GY', 'Guyana', 0, 0),('HK', 'Hongkong', 0, 0),('HM', 'Heard en McDonaldeilanden', 0, 0),('HN', 'Honduras', 0, 0),('HR', 'Kroati&euml;', 0, 0),('HT', 'Ha&iuml;ti', 0, 0),('HU', 'Hongarije', 0, 0),('ID', 'Indonesi&euml;', 0, 0),('IE', 'Ierland', 0, 0),('IL', 'Isra&euml;l', 0, 0),('IM', 'Man', 0, 0),('IN', 'India', 0, 0),('IO', 'Brits Indische Oceaanterritorium', 0, 0),('IQ', 'Irak', 0, 0),('IR', 'Iran', 0, 0),('IS', 'IJsland', 0, 0),('IT', 'Itali&euml;', 0, 0),('JE', 'Jersey', 0, 0),('JM', 'Jamaica', 0, 0),('JO', 'Jordani&euml;', 0, 0),('JP', 'Japan', 0, 0),('KE', 'Kenia', 0, 0),('KG', 'Kirgizi&euml;', 0, 0),('KH', 'Cambodja', 0, 0),('KI', 'Kiribati', 0, 0),('KM', 'Comoren', 0, 0),('KN', 'Saint Kitts en Nevis', 0, 0),('KP', 'Noord-Korea', 0, 0),('KR', 'Zuid-Korea', 0, 0),('KW', 'Koeweit', 0, 0),('KY', 'Kaaimaneilanden', 0, 0),('KZ', 'Kazachstan', 0, 0),('LA', 'Laos', 0, 0),('LB', 'Libanon', 0, 0),('LC', 'Saint Lucia', 0, 0),('LI', 'Liechtenstein', 0, 0),('LK', 'Sri Lanka', 0, 0),('LR', 'Liberia', 0, 0),('LS', 'Lesotho', 0, 0),('LT', 'Litouwen', 0, 0),('LU', 'Luxemburg', 0, 0),('LV', 'Letland', 0, 0),('LY', 'Libi&euml;', 0, 0),('MA', 'Marokko', 0, 0),('MC', 'Monaco', 0, 0),('MD', 'Moldavi&euml;', 0, 0),('ME', 'Montenegro', 0, 0),('MF', 'Sint-Maarten', 0, 0),('MG', 'Madagaskar', 0, 0),('MH', 'Marshalleilanden', 0, 0),('MK', 'Macedoni&euml;', 0, 0),('ML', 'Mali', 0, 0),('MM', 'Myanmar', 0, 0),('MN', 'Mongoli&euml;', 0, 0),('MO', 'Macau', 0, 0),('MP', 'Noordelijke Marianen', 0, 0),('MQ', 'Martinique', 0, 0),('MR', 'Mauritani&euml;', 0, 0),('MS', 'Montserrat', 0, 0),('MT', 'Malta', 0, 0),('MU', 'Mauritius', 0, 0),('MV', 'Maldiven', 0, 0),('MW', 'Malawi', 0, 0),('MX', 'Mexico', 0, 0),('MY', 'Maleisi&euml;', 0, 0),('MZ', 'Mozambique', 0, 0),('NA', 'Namibi&euml;', 0, 0),('NC', 'Nieuw-Caledoni&euml;', 0, 0),('NE', 'Niger', 0, 0),('NF', 'Norfolk', 0, 0),('NG', 'Nigeria', 0, 0),('NI', 'Nicaragua', 0, 0),('NL', 'Nederland', 0, 0),('NO', 'Noorwegen', 0, 0),('NP', 'Nepal', 0, 0),('NR', 'Nauru', 0, 0),('NU', 'Niue', 0, 0),('NZ', 'Nieuw-Zeeland', 0, 0),('OM', 'Oman', 0, 0),('PA', 'Panama', 0, 0),('PE', 'Peru', 0, 0),('PF', 'Frans-Polynesi&euml;', 0, 0),('PG', 'Papoea-Nieuw-Guinea', 0, 0),('PH', 'Filipijnen', 0, 0),('PK', 'Pakistan', 0, 0),('PL', 'Polen', 0, 0),('PM', 'Saint-Pierre en Miquelon', 0, 0),('PN', 'Pitcairneilanden', 0, 0),('PR', 'Puerto Rico', 0, 0),('PS', 'Palestina', 0, 0),('PT', 'Portugal', 0, 0),('PW', 'Palau', 0, 0),('PY', 'Paraguay', 0, 0),('QA', 'Qatar', 0, 0),('RE', 'R&eacute;union', 0, 0),('RO', 'Roemeni&euml;', 0, 0),('RS', 'Servi&euml;', 0, 0),('RU', 'Rusland', 0, 0),('RW', 'Rwanda', 0, 0),('SA', 'Saoedi-Arabi&euml;', 0, 0),('SB', 'Salomonseilanden', 0, 0),('SC', 'Seychellen', 0, 0),('SD', 'Soedan', 0, 0),('SE', 'Zweden', 0, 0),('SG', 'Singapore', 0, 0),('SH', 'Sint-Helena Ascension en Tristan da Cunha', 0, 0),('SI', 'Sloveni&euml;', 0, 0),('SJ', 'Spitsbergen en Jan Mayen', 0, 0),('SK', 'Slowakije', 0, 0),('SL', 'Sierra Leone', 0, 0),('SM', 'San Marino', 0, 0),('SN', 'Senegal', 0, 0),('SO', 'Somali&euml;', 0, 0),('SR', 'Suriname', 0, 0),('SS', 'Zuid-Soedan', 0, 0),('ST', 'Sao Tom&eacute; en Principe', 0, 0),('SV', 'El Salvador', 0, 0),('SX', 'Sint Maarten', 0, 0),('SY', 'Syri&euml;', 0, 0),('SZ', 'Swaziland', 0, 0),('TC', 'Turks- en Caicoseilanden', 0, 0),('TD', 'Tsjaad', 0, 0),('TF', 'Franse Zuidelijke en Antarctische Gebieden', 0, 0),('TG', 'Togo', 0, 0),('TH', 'Thailand', 0, 0),('TJ', 'Tadzjikistan', 0, 0),('TK', 'Tokelau', 0, 0),('TL', 'Oost-Timor', 0, 0),('TM', 'Turkmenistan', 0, 0),('TN', 'Tunesi&euml;', 0, 0),('TO', 'Tonga', 0, 0),('TR', 'Turkije', 0, 0),('TT', 'Trinidad en Tobago', 0, 0),('TV', 'Tuvalu', 0, 0),('TW', 'Taiwan', 0, 0),('TZ', 'Tanzania', 0, 0),('UA', 'Oekra&iuml;ne', 0, 0),('UG', 'Oeganda', 0, 0),('UM', 'Kleine Pacifische eilanden van de V.S.', 0, 0),('US', 'Verenigde Staten', 0, 0),('UY', 'Uruguay', 0, 0),('UZ', 'Oezbekistan', 0, 0),('VA', 'Vaticaanstad', 0, 0),('VC', 'Saint Vincent en de Grenadines', 0, 0),('VE', 'Venezuela', 0, 0),('VG', 'Britse Maagdeneilanden', 0, 0),('VI', 'Amerikaanse Maagdeneilanden', 0, 0),('VN', 'Vietnam', 0, 0),('VU', 'Vanuatu', 0, 0),('WF', 'Wallis en Futuna', 0, 0),('WS', 'Samoa', 0, 0),('YE', 'Jemen', 0, 0),('YT', 'Mayotte', 0, 0),('ZA', 'Zuid-Afrika', 0, 0),('ZM', 'Zambia', 0, 0),('ZW', 'Zimbabwe', 0, 0);");
	$PDO->query("INSERT INTO `innamestatus` (`id`, `status`) VALUES (1, 'wacht op test'), (2, 'niet defect'), (3, 'defect'), (4, 'retour leverancier'), (5, 'credit levernacier'), (6, 'omruiling leverancier'), (7, 'schade aan product');");
}
?>