<?php

function determineOpenField($screen){
	switch ($screen){
		case 1:
			$openScreen = 'manageUser';
			break;
		case 2:
			$openScreen = 'makeUser';
			break;
		case 3:
			$openScreen = 'shopOptions';
			break;
		case 4:
			$openScreen = 'shopLogo';
			break;
		case 5:
			$openScreen = 'manCat';
			break;
		default:
			$openScreen = 'manageUser';
			$screen = '1';
			break;
	}
	return tabsJavaCode($openScreen, $screen);
}

function manageUserForm($userId){
	global $PDO;
	global $Settings;
	$return = "<div id='manageUser' class='tab'>
	<div class='w3-card-4'>";
	if($userId == ''){
		$return = $return.searchUserForm('admin');
	} else {
		$result = $PDO->prepare("SELECT * FROM klanten WHERE id = :id");
		$result->execute(array(':id' => $userId));
		$row = $result->fetch();
		if($row['id'] != NULL){			
			if($row['active'] == 1){$checkedActief = "checked";}else{$checkedActief ='';}
			if($row['bedrijf'] == 1){$checkedBedrijf = "checked";}else{$checkedBedrijf ='';}
			if($row['admin'] == '1'){$checkedAdmin = "checked";}else{$checkedAdmin ='';}
			if($row['employee'] == 1){$checkedEmployee = "checked";}else{$checkedEmployee ='';}
			if($row['editproduct'] == 1){$checkedEditProduct = "checked";}else{$checkedEditProduct ='';}
			if($row['service'] == 1){$checkedService = "checked";}else{$checkedService ='';}
			$return = $return."<div class='w3-container w3-theme-l2'><h2>Gebruiker beheren</h2></div>
			 <form action='index.php?action=admin&subaction=2&userId=$userId' method='post' class='w3-container'>
			 	<p><label>Gebruikersnaam</label><input class='w3-input w3-border' type='text' name='naam' value='".$row['username']."'></p>
			 	<p><label>Nieuw wachtwoord</label><input class='w3-input w3-border' type='password' name='password' value=''></p>
			 	<p><label>Bevestig het nieuwe wachtwoord</label><input class='w3-input w3-border' type='password' name='cpassword' value=''></p>
			 	<p><label>Kvk Nummer</label><input class='w3-input w3-border' type='text' name='kvknummer' value='".$row['kvknummer']."'></p>
			 	<p><input class='w3-check' name='active' type='checkbox' value='1' $checkedActief><label class='w3-validate'> Dit account is geactiveerd</label></p>
			 	<p><input class='w3-check' name='bedrijf' type='checkbox' value='1' $checkedBedrijf><label class='w3-validate'> Deze gebruiker vertegenwoordigd een bedrijf</label></p>
				<p><input class='w3-check' name='admin' type='checkbox' value='1' $checkedAdmin><label class='w3-validate'> Deze gebruiker is een beheerder</label></p>";
			if($Settings->_get('allowEmployees') == 1){
				$return .= 	"<p><input class='w3-check' name='employee' type='checkbox' value='1' $checkedEmployee><label class='w3-validate'> Deze gebruiker is een medewerker</label></p>
				<p><input class='w3-check' name='editproduct' type='checkbox' value='1' $checkedEditProduct><label class='w3-validate'> Deze gebruiker mag producten toevoegen, verwijderen en aanpassen</label></p>
				<p><input class='w3-check' name='service' type='checkbox' value='1' $checkedService><label class='w3-validate'> Deze gebruiker mag producten omruilen, crediteren en innemen ter reparatie</label></p>";
			}			
			$return .= "<p><a href='index.php?action=admin&subaction=3&userId=$userId' class='w3-btn w3-theme-l2'>Gebruiker verwijderen</a> <input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'></p>
			 </form>";
		} else {
			$return = $return.searchUserForm('admin');
		}
	}
	$return = $return."</div></div>";
	return $return;
}

function editUser($userId){
	global $PDO;
	global $Settings;
	$errormsg = FALSE;
	$result = $PDO->prepare("SELECT * FROM klanten WHERE id = :id");
	$result->execute(array(':id' => $userId));
	$row = $result->fetch();
	$msg = FALSE;
	
	if($row['id'] == NULL){
		$error = TRUE;
		$errormsg = 'Deze gebruiker bestaat niet...<br>';
	}
	
	if(isset($_POST['naam']) AND $_POST['naam'] != ''){
		if($_POST['naam'] != $row['username']){
			//Dit is dus een nieuwe gebruikersnaam.
			$resultU = $PDO->prepare("SELECT * FROM `klanten` WHERE `username` = :username");
			$resultU->execute(array(':username' => $_POST['naam']));
			$rowU = $resultU->fetch();
			if($rowU['id'] == NULL){
				$username = $_POST['naam'];
			} else {
				$errormsg =  $errormsg.'Deze gebruikersnaam bestaat al.<br>';
			}
		} else {
			$username = $_POST['naam'];
		}
	} else{
		$errormsg =  $errormsg.'Er is geen gebruikersnaam ingevoerd.<br>';
	}
	
	//Doe alle checkboxen
	if($_POST['active'] != 1){
		$active = 0;
	} else {
		$active = 1;
	}
	if(!isset($_POST['bedrijf']) OR $_POST['bedrijf'] != 1){
		$bedrijf = 0;
	} else {
		$bedrijf = 1;
	}
	if(!isset($_POST['admin']) OR $_POST['admin'] != 1){
		$admin = 0;
	} else {
		$admin = 1;
	}
	if($Settings->_get('allowEmployees') == 1){
		if(!isset($_POST['employee']) OR $_POST['employee'] != 1){
			$employee = 0;
		} else {
			$employee = 1;
		}
		if(!isset($_POST['editproduct']) OR $_POST['editproduct'] != 1){
			$editproduct = 0;
		} else {
			$editproduct = 1;
		}
		if(!isset($_POST['service']) OR $_POST['service'] != 1){
			$service = 0;
		} else {
			$service = 1;
		}
	} else {
		$service = 0;
		$editproduct = 0;
		$employee = 0;
	}
	
	if(!$errormsg){
		if(isset($_POST['password']) AND $_POST['password'] != ''){
			if($_POST['password'] == $_POST['cpassword']){
				$password = $_POST['password'];
				if(strlen ($password) >= '8'){
					$hash = sha1($row['salt'].$_POST['password']);
					$update = $PDO->prepare("UPDATE klanten SET wachtwoord = :hash WHERE id = :id");
					$update->execute(array(':hash' => $hash, ':id' => $userId));
					$update->closeCursor();
					$msg = $msg."Wachtwoord aangepast.<br>";
				} else {
					$errormsg = $errormsg .'Het wachwoord moet minstens 8 tekens hebben.<br>';
				}
			} else {
				$errormsg = $errormsg.'Het wachwoord en controle wachtwoord komen niet overeen.<br>';
			}
		}
		if(!$errormsg){
			$update = $PDO->prepare("UPDATE klanten SET username = :username , admin = :admin, employee = :employee, editproduct = :editproduct, service = :service, bedrijf = :bedrijf, active = :active WHERE id = :id");
			$update->execute(array(':username' => $username , ':admin' => $admin, ':employee' => $employee, ':editproduct' => $editproduct, ':service' => $service, ':bedrijf' => $bedrijf, ':active' => $active, ':id' => $userId));
			$msg = $msg."User is aangepast.<br>";
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>$msg</p></div><br>".manageUserForm($userId).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(1);
		} else {
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".manageUserForm($userId).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(1);
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".manageUserForm($userId).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(1);
	}
}

function uploadLogoImage(){
	global $PDO;
	$imageLocation = uploadImage('images/', TRUE);
	if(!$imageLocation){
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Er is iets mis gegaan bij het uploaden van het plaatje.</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(4);
	} else{
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Logo is aangepast.</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(4);
	}
}

function makeUserForm(){
	return "<div id='makeUser' class='tab'>
	<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>User cre&euml;ren</h2></div>
		<form action='index.php?action=admin&subaction=4' method='post' class='w3-container'>
			<p><label>Gebruikersnaam:</label><input class='w3-input w3-border' type='text' name='username' value=''></input></p>
			<p><label>E-mail:</label><input class='w3-input w3-border' type='text' name='email' value=''></input></p>
			<p><label>Wachtwoord:*</label><input class='w3-input w3-border' type='password' name='password' value=''></input></p>
			<p><label>Controle:*</label><input class='w3-input w3-border' type='password' name='cpassword' value=''></input></p>
			<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='aanmaken'></input></p>
		</form>
	</div>
</div>";
}

function registerUsername($username, $password, $cpassword, $email){
	global $PDO;
	global $Settings;
	$errorCode = '';
	$error = FALSE;
	$result = $PDO->prepare("SELECT * FROM `klanten` WHERE `username` = :username");
	$result->execute(array(':username' => $username));
	$row = $result->fetch();
	$resultEmail = $PDO->prepare("SELECT * FROM `klanten` WHERE `email` = :email");
	$resultEmail->execute(array(':email' => $email));
	$rowEmail = $resultEmail->fetch();
	if (preg_match('/\s/',$username)){
		$errorCode = $errorCode."Een gebruiksersnaam mag geen spaties bevatten.<br>";
		$error = TRUE;
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errorCode = $errorCode."Onjuist of leeg email adres ingevoerd.<br>";
		$error = TRUE;
	}
	if ($password != $cpassword){
		$errorCode = $errorCode."Wachtwoord komt niet overeen met controle wachtwoord.<br>";
		$error = TRUE;
	}
	if ($password == NULL){
		$errorCode = $errorCode."Geen wachtwoord ingevuld.<br>";
		$error = TRUE;
	}
	if($username == NULL){
		$errorCode = $errorCode."Geen gebruikersnaam ingevuld.<br>";
		$error = TRUE;
	}
	if($row['id'] != NULL){
		$errorCode = $errorCode."Deze gebruikersnaam bestaat al.<br>";
		$error = TRUE;
	}
	if($rowEmail['id'] != NULL){
		$errorCode = $errorCode."Er is al een gebruiker met dit email adres, inplaats hiervan <a href='index.php?action=login'>inloggen</a>?<br>";
		$error = TRUE;
	}
	if(!strlen ($password) >= '8'){
		$errorCode = $errorCode."Een wachtwoord moet minstens 8 tekens lang zijn.<br>";
		$error = TRUE;
	}
	if(!$error){
		$salt = createSalt();
		$hash = sha1($salt . $password );
		$reghash = sha1($salt . time() );
		$insert = $PDO->prepare("INSERT INTO `klanten` (username, email, wachtwoord, salt, reghash, active) VALUES (:username, :email, :wachtwoord, :salt, :reghash , 1)");
		$insert->execute(array(':username' => $username, ':email' => $email, ':wachtwoord' => $hash, ':salt' => $salt, ':reghash' =>$reghash));
		$userId = $PDO->lastInsertId();
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Gebruikersnaam aangemaakt.</p></div><br>".manageUserForm($userId).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(1);
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errorCode."</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(2);
	}
}

function deleteUser($userId){
	global $PDO;
	$errormsg = FALSE;
	$result = $PDO->prepare("SELECT * FROM klanten WHERE id = :id");
	$result->execute(array(':id' => $userId));
	$row = $result->fetch();
	$msg = FALSE;
	$wmsg = FALSE;
	if($row['id'] == NULL){
		$errormsg = $errormsg."Deze gebruiker bestaat niet.<br>";
	} elseif($_GET['zeker'] == '1'){
		$msg = 'Gebruiker verwijderd.<br>';
		$delete = $PDO->prepare("DELETE FROM klanten WHERE id = :id");
		$delete->execute(array(':id' => $userId));
	} else {
		$wmsg = "U staat op het punt deze gebruiker te verwijderen. Weet u dit zeker?<br><a href='index.php?action=admin&subaction=3&userId=$userId&zeker=1'>Ja</a><br>";
	}
	if(!$errormsg){
		if($wmsg){
			return "<div class=\"w3-container w3-yellow\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Waarschuwing!</h3><p>$wmsg</p></div><br>".manageUserForm($userId).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(1);
		} else {
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>$msg</p></div><br>".manageUserForm($userId).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(1);
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".manageUserForm($userId).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(1);
	}
}

function shopOptionsForm(){
	global $PDO;
	$result = $PDO->query("SELECT * FROM shopsettings ORDER BY id DESC limit 0, 1");
	$row = $result->fetch();
	$resultLanden = $PDO->query("SELECT * FROM countries ORDER BY name");
	$optionListLanden = '';
	$optionListLandenShip = '';
	$returnLanden = '';
	foreach($resultLanden as $rowLand){
		$optionListLanden = $optionListLanden."<option value='".$rowLand['countryCode']."'>".$rowLand['name']."</option>";
		if($rowLand['ship'] == 1){
			$optionListLandenShip = $optionListLandenShip."<option value='".$rowLand['countryCode']."'>".$rowLand['name']."</option>";
			$returnLanden = $returnLanden."<p>".$rowLand['name']." verzendkosten: &euro;".number_format($rowLand['charge'], 2, ',', '.')."</p>";
		}
		if($rowLand['countryCode'] == $row['standaardShipCountry']){
			$landNaam = $rowLand['name'];
		}
	}
	if($landNaam == NULL){
		$landNaam = 'Land niet actief - kies een ander land';
		$disabled = 'disabled';
	} else {
		$disabled = '';
	}
	return "<div id='shopOptions' class='tab'>
	<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Webshop eigenschappen</h2></div>
		<form action='index.php?action=admin&subaction=5' method='post' class='w3-container'>
			<p><label>Winkelnaam</label><input class='w3-input w3-border' type='text' name='shopname' value='". $row['shopname']."'></p>
			<p><label>Domeinnaam:</label><input class='w3-input w3-border' type='text' name='shopdomain' value='". $row['shopdomain']."'></p>
			<p><label>Volledig pad naar de webwinkel</label><input class='w3-input w3-border' type='text' name='fullpath' value='". $row['fullpath']."'></p>
			<p><label>Mollie API Key (nodig om Mollie betalingen te kunnen verwerken. <a href='https://www.mollie.com/'>Mollie website</a>)</label><input class='w3-input w3-border' type='text' name='mollieapikey' value='". $row['mollieapikey']."'></p>
			<p><label>Rekening nummer voor overboekingen</label><input class='w3-input w3-border' type='text' name='rekeningnummer' value='". $row['rekeningnummer']."'></p>
			<p><label>Kosten van rembours zending (bovenop de verzendkosten)</label><input class='w3-input w3-border' type='text' name='remboursfee' value='". $row['remboursfee']."'></p>
			<p><label>Standaard afleverland</label><select class='w3-input w3-border' type='text' name='standaardShipCountry'><option value='".$row['standaardShipCountry']."' $disabled>$landNaam</option>$optionListLandenShip</select></p>
			<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'></p>
		</form>
	</div>
	<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Landeigenschappen</h2></div><form action='index.php?action=admin&subaction=6' method='post' class='w3-container'>
			<h3>Naar de volgende landen worden op dit moment producten verzonden:</h3>"
			.$returnLanden
			."<p><label>Land</label><select class='w3-input w3-border' type='text' name='shipCountry'>$optionListLanden</select></p>
			<p><label>Verzendkosten naar dit land</label><input class='w3-input w3-border' type='text' name='charge' value=''></p>
			<p><input class='w3-check' name='actief' type='checkbox' value='1'><label class='w3-validate'>Naar dit land mogen producten verzonden worden</label></p>
			<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'></p>
		</form>
	</div>
</div>";
	
}

function alterCountryShipStatus(){
	global $PDO;
	$errormsg = FALSE;
	
	//sanitychecks
	if($_POST['shipCountry'] == '' OR strlen ($_POST['shipCountry']) != 2){
		$errormsg = $errormsg."Geen land geselecteerd.";
	} else {
		$shipCountry = $_POST['shipCountry'];
	}
	if($_POST['charge'] == '' OR !is_numeric($_POST['charge'])){
		$errormsg = $errormsg."Geen verzendkosten ingevuld.";
	} else {
		$charge = $_POST['charge'];
	}
	if($_POST['actief'] == '1'){
		$actief = 1;
	} else {
		$actief = 0;
	}
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE countries SET ship = :actief, charge = :charge WHERE countryCode = :countryCode");
		$update->execute(array(':actief' => $actief, ':charge' => $charge, ':countryCode' => $shipCountry));
		$msg = "De landleveringsstatus is succesvol aangepast.";
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>$msg</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(3);
	} else {
		// er is iets misgegaan
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(3);
	}
}

function alterSiteSettings(){
	global $PDO;
	$errormsg = FALSE;
	$wmsg = FALSE;
	//sanity checks
	if(!isset($_POST['shopname']) OR $_POST['shopname'] == ''){
		$errormsg = $errormsg."Geen winkelnaam opgegeven.<br>";
	} else {
		$shopname = $_POST['shopname'];
	}
	if(!isset($_POST['standaardShipCountry']) OR $_POST['standaardShipCountry'] == ''){
		$errormsg = $errormsg."Ongeldig standaard afleverland ingevuld.<br>";
	}  else {
		if(strlen ($_POST['standaardShipCountry']) != 2){
			$errormsg = $errormsg."Ongeldig standaard afleverland ingevuld.<br>";
		} else {
			$standaardShipCountry = $_POST['standaardShipCountry'];
		}
	}
	if(!filter_var($_POST['fullpath'], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){
		$errormsg = $errormsg."Ongeldig pad naar het script ingevoerd http://www.voorbeeld.nl/<br>";
	} else {
		$fullpath = $_POST['fullpath'];
	}
	if(!is_valid_domain_name($_POST['shopdomain'])){
		$errormsg = $errormsg."Geen geldige domeinnaam ingevoerd: 'voorbeeld.nl'<br>";
	} else {
		$shopdomain = $_POST['shopdomain'];
	}
	if($_POST['mollieapikey'] == ''){
		$wmsg = $wmsg."Geen mollie API key ingesteld, mollie betalingen zullen niet werken.<br>";
	} else {
		$mollieapikey = $_POST['mollieapikey'];
	}
	if(!checkIBAN($_POST['rekeningnummer'])){
		$errormsg = $errormsg."Ongeldig rekeningnummer ingevoerd <br>";
	} else {
		$rekeningnummer = $_POST['rekeningnummer'];
	}
	if($_POST['remboursfee'] == '' OR !is_numeric($_POST['remboursfee'])){
		$errormsg = $errormsg."Geen of ongeldige rembourskosten ingevoerd<br>";
	} else {
		$remboursfee = $_POST['remboursfee'];
	}
	//Bij geen error toepassen
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE shopsettings SET shopname = :shopname, shopdomain = :shopdomain, fullpath = :fullpath, mollieapikey = :mollieapikey, remboursfee = :remboursfee, rekeningnummer = :rekeningnummer, standaardShipCountry = :standaardShipCountry WHERE id = '1'");
		$update->execute(array(':shopname' => $shopname, ':shopdomain' => $shopdomain, ':fullpath' => $fullpath, ':mollieapikey' => $mollieapikey, ':remboursfee' => $remboursfee, ':rekeningnummer' => $rekeningnummer, ':standaardShipCountry' => $standaardShipCountry));
		$msg = "Website eigenschappen zijn succesvol aangepast.";
		if($wmsg){
			//waarschuwing weergeven
			return "<div class=\"w3-container w3-yellow\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Waarschuwing!</h3><p>$wmsg</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(3);
		} else {
			// alles is goed gegaan
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>$msg</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(3);
		}
	} else {
		// er is iets misgegaan
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(3);
	}
}

function is_valid_domain_name($domain_name){
	return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
			&& preg_match("/^.{1,253}$/", $domain_name) //overall length check
			&& preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
}

function checkIBAN($iban)
{
	$iban = strtolower(str_replace(' ','',$iban));
	$Countries = array('al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24);
	$Chars = array('a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35);

	if(strlen($iban) == $Countries[substr($iban,0,2)]){

		$MovedChar = substr($iban, 4).substr($iban,0,4);
		$MovedCharArray = str_split($MovedChar);
		$NewString = "";

		foreach($MovedCharArray AS $key => $value){
			if(!is_numeric($MovedCharArray[$key])){
				$MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
			}
			$NewString .= $MovedCharArray[$key];
		}

		if(bcmod($NewString, '97') == 1)
		{
			return TRUE;
		}
		else{
			return FALSE;
		}
	}
	else{
		return FALSE;
	}
}

function shopLogoForm(){
	return "<div id='shopLogo' class='tab'>
	<div class='w3-card-4'>
	<div class='w3-container w3-theme-l2'><h2>Logo aanpassen</h2></div>
	<form action='index.php?action=admin&subaction=7' method='post' class='w3-container' enctype='multipart/form-data'>
	<p><label>Kies de afbeelding die u als logo wilt gebruiken. (250*60) pixels als '.jpg'.</label><input class='w3-input' type='file' name='fileToUpload' value=''></p>
	<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'></p>
	</form>
	</div>
	</div>";
}

function addOrEditCategory(){
	global $PDO;
	global $Settings;
	$errormsg = FALSE;
	$insertArray = array();
	$updateArray = array();
	$result = $PDO->query("SELECT count(*) as count FROM categorie");
	$count = $result->fetchColumn('count');
	if($Settings->_get('catLimit') != 0){
		if(!$count < $Settings->_get('catLimit')){
			$errormsg = $errormsg."Het maximale aantal is bereikt.";
		}
	}
	if(!isset($_POST['naam']) OR $_POST['naam'] == ''){
		$errormsg = $errormsg."Geen categorienaam ingevoerd.";
	} else {
		$naam = $_POST['naam'];
		$updateArray[':naam'] = $naam;
		$insertArray[':naam'] = $naam;
		$updateQuery = "UPDATE categorie SET naam = :naam, subfromcatid = NULL";
		$inserrQuery = "INSERT INTO categorie (naam) VALUES (:naam)";
	}
	if(isset($_POST['subfromcatid']) AND $_POST['subfromcatid'] != ''){
		$subfromcatid = $_POST['subfromcatid'];
		$updateArray[':subfromcatid'] = $subfromcatid;
		$insertArray[':subfromcatid'] = $subfromcatid;
		$updateQuery = "UPDATE categorie SET naam = :naam, subfromcatid = :subfromcatid";
		$inserrQuery = "INSERT INTO categorie (naam, subfromcatid) VALUES (:naam, :subfromcatid)";
	}
	if(!$errormsg){
		if(!isset($_GET['catId']) OR $_GET['catId'] == '' ){
			//maak een nieuwe categorie
			$insert = $PDO->prepare($inserrQuery);
			$insert->execute($insertArray);
			//gelukt tekst
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De categorie is aangemaakt.</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(5);
		} else {
			//update een bestaande categorie
			$catId = $_GET['catId'];
			$resultCheck = $PDO->prepare("SELECT * FROM categorie WHERE id = :id");
			$resultCheck->execute(array(':id' => $catId));
			$rowCheck = $resultCheck->fetch();
			if($rowCheck['id'] == NULL){
				//dit id bestaat niet geef foutmelding
				$errormsg = "Deze categorie bestaat niet.";
				return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(5);
			} else {
				$updateQuery = $updateQuery." WHERE id = :id";
				$updateArray[':id'] = $catId;
				$update = $PDO->prepare($updateQuery);
				$update->execute($updateArray);
				//update gelukt tekst
				return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De categorie is bijgewerkt.</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(5);
			}
		}
	} else {
		//geef de foutmelding
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(5);
	}
}

function shopCategoryForm(){
	global $PDO;
	global $Settings;
	$result = $PDO->query("SELECT * FROM categorie ORDER BY subfromcatid, naam");
	$optionMC = createCatOptionDropDownList(1);
	$subcategorie = NULL;
	$hoofdcategorienaam = array(NULL => NULL);
	$return = ''; 
	$catcount = 0;
	foreach ($result as $row){
		$catcount++;
		if($subcategorie != $row['subfromcatid']){
			$return = $return."<div class='w3-container w3-pale-blue'><h3>Subcategorie&euml;n van: ".$hoofdcategorienaam[$row['subfromcatid']]."</h3></div>";
			$subcategorie = $row['subfromcatid'];
		}
		$hoofdcategorienaam[$row['id']] = $row['naam'];
		$return = $return."<form action='index.php?action=admin&subaction=8&catId=".$row['id']."' method='post' class='w3-container'>
			<p><label>Categorie naam</label><input class='w3-input w3-border' type='text' name='naam' value='".$row['naam']."'></p>
			<p><label>Is een subcategorie van:</label><select class='w3-input w3-border' name='subfromcatid' ><option value='".$row['subfromcatid']."'>".$hoofdcategorienaam[$row['subfromcatid']]."</option>$optionMC</select></p>
			<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'>
			<a class='w3-btn w3-theme-l2' href='index.php?action=admin&subaction=9&catId=".$row['id']."'>Categorie verwijderen</a></p>
		</form>";
	}
	if($catcount < $Settings->_get('catLimit') OR $Settings->_get('catLimit') == 0){
		$return = "<div id='manCat' class='tab'>
		<div class='w3-card-4'>
		<div class='w3-container w3-theme-l2'><h2>Nieuwe categorie maken</h2></div>
		<form action='index.php?action=admin&subaction=8' method='post' class='w3-container'>
		<p><label>Categorie naam</label><input class='w3-input w3-border' type='text' name='naam' value=''></p>
		<p><label>Is een subcategorie van:</label><select class='w3-input w3-border' name='subfromcatid'>$optionMC</select></p>
		<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Nieuwe categorie maken'></p>
		</form>
		<div class='w3-container w3-theme-l2'><h2>Categorie&euml;n beheren</h2></div><div class='w3-container w3-pale-blue'><h3>Hoofdcategorie&euml;n:</h3></div>".$return;
	} else {
		$return = "<div id='manCat' class='tab'><div class=\"w3-container w3-yellow\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Waarschuwing!</h3><p>Het maximaal aantal categorie&euml;n is bereikt.</p></div><br>
		<div class='w3-card-4'>
		<div class='w3-container w3-theme-l2'><h2>Categorie&euml;n beheren</h2></div><div class='w3-container w3-pale-blue'><h3>Hoofdcategorie&euml;n:</h3></div>".$return;
	}
	$return = $return."</div></div>";
	return $return;
}

function removeCategory(){
	global $PDO;
	if($_GET['catId'] != ''){
		$catId = $_GET['catId'];
		$result = $PDO->prepare("SELECT * FROM categorie WHERE id = :id");
		$result->execute(array(':id' => $catId));
		$row = $result->fetch();
		if($row != NULL){
			$resultChild = $PDO->prepare("SELECT COUNT(*) FROM categorie WHERE subfromcatid = :id");
			$resultChild->execute(array(':id' => $catId));
			$numberOfChildren = $resultChild->fetchColumn();
			if($numberOfChildren == 0){
				$resultProduct = $PDO->prepare("SELECT COUNT(*) FROM producten WHERE categorieId = :id");
				$resultProduct->execute(array(':id' => $catId));
				$numberOfProducts = $resultProduct->fetchColumn();
				If($numberOfProducts == 0){
					$delete = $PDO->prepare("DELETE FROM categorie WHERE id = :id");
					$delete->execute(array(':id' => $catId));
					$msg = "Categorie Verwijderd";
					return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>$msg</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(5);
				} else {
					$errormsg = "Kan geen categorie verwijderen die nog producten bevat.<br>";
				}
			} else {
				$errormsg = "Kan geen categorie verwijderen die nog subcategorie&euml;n heeft.<br>";
			}
		} else {
			$errormsg = "De categorie bestaat niet.<br>";
		}
	} else {
		$errormsg = "Geen categorie ingevuld.<br>";
	}
	return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".manageUserForm(NULL).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(5);
}


function getYearlyOverview(){
	global $PDO;
	global $printThisPage;
	if(!isset($_GET['year']) or $_GET['year'] == ''){
		return '<div class="w3-card-4">
					<div class="w3-container w3-theme-l2"><h2>Bekijk de omzet uit:</h2></div>
					<form class="w3-container" action="index.php" method="GET">
						<input type="hidden" name="action" value="admin"></input>
						<input type="hidden" name="subaction" value="10"></input>
						<p><label>Jaartal:</label><input class="w3-input w3-border" type="text" name="year" value=""></input></p>
						<p><input class="w3-btn w3-theme-l2" type="submit"></p>
					</form>';
	} elseif($_GET['year'] > 1800 AND $_GET['year'] < 3000) {
		$result = $PDO->prepare("SELECT orders.id, orders.prijs as totaalprijs, orders.besteldatum, orderproducten.productid, orderproducten.prijs as productprijs, orderproducten.aantal, producten.btwtarief, producten.productNaam, producten.btwtarief, discountcoupons.value as discount, discountcoupons.type as discounttype,
								(SELECT sum(orders.prijs) FROM orders WHERE FROM_UNIXTIME(besteldatum, '%Y') = :datum1 AND voldaan = 1) as jaartotaal , (SELECT count(*) FROM orders WHERE FROM_UNIXTIME(besteldatum, '%Y') = :datum3 AND voldaan = 1) as totaalorders
								FROM orders
								LEFT JOIN orderproducten ON orders.id = orderproducten.orderid
								LEFT JOIN producten on orderproducten.productid = producten.id
								LEFT JOIN discountcoupons ON orders.discountCoupon = discountcoupons.couponCode
								WHERE FROM_UNIXTIME(besteldatum, '%Y') = :datum2 AND voldaan = 1
								order by id");
		$result->execute(array(':datum1' => $_GET['year'], ':datum2' => $_GET['year'], ':datum3' => $_GET['year']));
		$fisrtrun = TRUE;
		$lastrunorder = '';
		$return = '<div class="w3-container w3-theme-l2"><h2>Omzet overzicht van '.$_GET['year'].'</h2><p>';
		$totalBTW[6] = 0;
		$totalBTW[21] = 0;
		$discount = FALSE;
		$notext = FALSE;
		foreach($result as $row){
			if($fisrtrun){
				$return .= '</p></div><div class="w3-container"><p>In '.$_GET['year'].' was de jaaromzet &euro;'.number_format($row['jaartotaal'],2, ',', '.').'<br>Er zijn in totaal '.$row['totaalorders'].' orders in dit jaar verricht.</div>
						<div class="w3-container w3-theme-l2"><h2>Alle orders met hun producten.</h2><p>';
				$fisrtrun = FALSE;
				$notext = TRUE;
			}
			if($lastrunorder != $row['id']){
				if($discount){
					$percentageLaag = (float) $totalBTW[6] / ($totalBTW[6]  + $totalBTW[21]);
					$percentageHoog = (float) $totalBTW[21] / ($totalBTW[6]  + $totalBTW[21]);
					$discounHoog = $discount*$percentageHoog;
					$discountLaag = $discount*$percentageLaag;
					$totalBTW[6] = $totalBTW[6]-$discountLaag;
					$totalBTW[21] = $totalBTW[21]-$discounHoog;
				}
				if(!$notext){
					$return .= '<br>Totaal 21% btw categorie (na evt korting) &euro;'.number_format($totalBTW[21]/1.21 ,2, ',', '.').'<br>Totaal 6% btw categorie (na evt korting) &euro;'.number_format($totalBTW[6]/1.06 ,2, ',', '.').'';
				} else {
					$notext = FALSE;
				}
				$totalBTW[6] = 0;
				$totalBTW[21] = 0;
				if($row['discounttype'] == "0"){
					$korting = $row['discount'];
				} elseif($row['discounttype'] == 1){
					$kortingpercentage = $row['discount'];
					(100-$kortingpercentage)/100;
					$origineleprijs = $row['totaalprijs']/((100-$kortingpercentage)/100);
					$korting = $origineleprijs - $row['totaalprijs'];
				} else {
					$korting = FALSE;
				}
				$return .= '</p></div><div class="w3-container"><h3>Order: '.$row['id'].' geplaatst op '.date('d-m-Y',$row['besteldatum'] ).' totale waarde: &euro;'.number_format($row['totaalprijs'],2, ',', '.').' </h3><p>';
				if($korting){
					$return .='Korting van &euro;'.number_format($korting ,2, ',', '.').' incl btw<br>';
				}
				$lastrunorder = $row['id'];
			}
			if($row['productid'] == 0){
				$return .= 'Verzend/rembourskosten '.$row['aantal'].'x &euro;'.number_format($row['productprijs'],2, ',', '.').'<b> totaal: '.number_format(($row['productprijs']*$row['aantal']),2, ',', '.').'</b> incl 21% btw<br>';
				$totalBTW[21] = $totalBTW[21] + $row['productprijs'];
			} else {
				$totalBTW[$row['btwtarief']] = $totalBTW[$row['btwtarief']] + $row['productprijs'];
				$return .= $row['productNaam'].' '.$row['aantal'].'x &euro;'.number_format($row['productprijs'],2, ',', '.').'<b> totaal: '.number_format(($row['productprijs']*$row['aantal']),2, ',', '.').'</b> incl '.$row['btwtarief'].'% btw<br>';
			}
		}
		if($fisrtrun == TRUE){
			$return .= '</p></div><div class="w3-container"><p>In '.$_GET['year'].' was de jaaromzet &euro;'.number_format(0 ,2, ',', '.').'<br>Er zijn in totaal 0 orders in dit jaar verricht.</div>';
		} else {
			$return .= '<br>Totaal 21% btw categorie (na evt korting) &euro;'.number_format($totalBTW[21]/1.21 ,2, ',', '.').'<br>Totaal 6% btw categorie (na evt korting) &euro;'.number_format($totalBTW[6]/1.06 ,2, ',', '.').'';
		}
		if($printThisPage == 1){
			$return .= '</p></div>';
		} else {
			$return .= '</p><br><a href="index.php?action=admin&subaction=10&year='.$_GET['year'].'&print=1" class="w3-btn w3-theme-l2">Afdrukken</a> <a href="index.php?action=admin&subaction=10" class="w3-btn w3-theme-l2">Overzicht van ander jaar</a></div>';
		}
		return $return;
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Het jaartal is ongeldig.</p></div><br>";
	}
}

function messageForm(){
	global $PDO;
	global $Page;
	$errormsg = FALSE;
	$edit = '';
	$title = 'Website pagina maken';
	if(isset($_GET['id']) && $_GET['id'] != ''){
		$result = $PDO->prepare("SELECT * FROM shopmessages WHERE id = :id");
		$result->execute(array(':id' => $_GET['id']));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg = 'Dit bericht bestaat niet.<br>';
		} else {
			$edit = '&id='.$row['id'];
			$title = 'Website pagina bewerken';
		}
	} else {
		$row = array('title' => NULL, 'text' => NULL, 'publish' => NULL, 'linkmenu' => NULL, 'linkfooter' => NULL);
	}
	if(isset($_POST['title']) && $_POST['title'] != NULL){
		$row['title'] = $_POST['title'];
	}
	if(isset($_POST['text']) && $_POST['text'] != NULL){
		$row['text'] = $_POST['text'];
	}
	if(isset($_POST['publish']) && $_POST['publish'] == 1){
		$row['publish'] = 1;
	}
	if(isset($_POST['link']) && $_POST['link'] == 1){
		$row['link'] = 1;
	} 
	if($row['publish'] == '1'){
		$publish = 'checked';
	} else {
		$publish = '';
	}
	if($row['linkmenu'] == '1'){
		$linkmenu = 'checked';
	} else {
		$linkmenu = '';
	}
	if($row['linkfooter'] == '1'){
		$linkfooter = 'checked';
	} else {
		$linkfooter = '';
	}
	if(!$errormsg){
		$Page->changePageTitle($title);
		return "<div class='w3-card-4'>
					<div class='w3-container w3-theme-l2'><h2>$title</h2></div>
					<script type='text/javascript' src='tinymce/tinymce.min.js'></script>
<script type='text/javascript'>
tinymce.init({
  selector: 'textarea',
  height: 400,
  theme: 'modern',
  language: 'nl',
  menubar:false,
  browser_spellcheck : true,
  plugins: [
    'advlist lists link image charmap print pagebreak table',
    'searchreplace wordcount fullscreen autolink',
    'insertdatetime save table directionality',
    'paste textcolor colorpicker textpattern imagetools'
  ],
  font_size_classes : 'fontSize1, fontSize2, fontSize3, fontSize4, fontSize5, fontSize6',
  fontsize_formats: '8pt 10pt 12pt 14pt 18pt 24pt 36pt',
  toolbar1: 'save undo redo | print | bold italic underline | fontselect fontsizeselect | link | table',
  toolbar2: 'forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image | charmap',
  save_enablewhendirty: false,
  image_advtab: true,
 });
</script>
	<form action='index.php?action=admin&subaction=12$edit' method='post' class='w3-container'>
		<p><label>Titel:<label><input type='text' value='".$row['title']."' name='title' class='w3-input w3-border'></input></p>
		<p><input class='w3-check' type='checkbox' value='1' name='publish' $publish></input> <label class='w3-validate'>Publiceer</label></p>
		<p><input class='w3-check' type='checkbox' value='1' name='linkmenu' $linkmenu></input> <label class='w3-validate'>Plaats link in menu</label></p>
		<p><input class='w3-check' type='checkbox' value='1' name='linkfooter' $linkfooter></input> <label class='w3-validate'>Plaats link footer</label></p>
		<textarea name='text' style='width: 100%; height: 400px'>".$row['text']."</textarea>
	</form></div>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>";
	}
}

function postOrEditMessage(){
	global $PDO;
	global $Page;
	global $User;
	$errormsg = FALSE;
	$edit = FALSE;
	if(isset($_GET['id']) && $_GET['id'] != ''){
		$result = $PDO->prepare("SELECT * FROM shopmessages WHERE id = :id");
		$result->execute(array(':id' => $_GET['id']));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg = 'Dit bericht bestaat niet.<br>';
		}  else {
			$edit = TRUE;
		}
	}
	if(!isset($_POST['title']) OR $_POST['title'] == ''){
		$errormsg = 'Geen titel ingevoerd<br>';
	}
	if(!isset($_POST['text']) OR $_POST['text'] == ''){
		$errormsg = 'Geen tekst ingevoerd<br>';
	}
	if(isset($_POST['publish']) && $_POST['publish'] == 1){
		$publish = 1;
	} else {
		$publish = 0;
	}
	if(isset($_POST['linkmenu']) && $_POST['linkmenu'] == 1){
		$linkmenu = 1;
	} else {
		$linkmenu = 0;
	}
	if(isset($_POST['linkfooter']) && $_POST['linkfooter'] == 1){
		$linkfooter = 1;
	} else {
		$linkfooter = 0;
	}
	if(!$errormsg){
		if($edit){
			$update = $PDO->prepare("UPDATE shopmessages SET title = :title, text = :text, publish = :publish, linkmenu = :linkmenu,linkfooter = :linkfooter, lasteditdate = :lasteditdate WHERE id = :id");
			$update->execute(array(':title' => $_POST['title'], ':text' => $_POST['text'], ':publish' => $publish, ':linkmenu' => $linkmenu, ':linkfooter' => $linkfooter,':lasteditdate' => time(), ':id' => $_GET['id']));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De pagina is aangepast.</p></div><br>".messageForm();
		} else {
			$insert = $PDO->prepare("INSERT INTO shopmessages (title, text, date, publish, linkmenu, linkfooter, lasteditdate, uid) VALUES (:title, :text, :date, :publish, :linkmenu, :linkfooter, :lasteditdate, :uid)");
			$insert->execute(array(':title' => $_POST['title'], ':text' => $_POST['text'],':date' => time(),':publish' => $publish, ':linkmenu' => $linkmenu, ':linkfooter' => $linkfooter, ':lasteditdate' => time(), ':uid' => $User->_get('costumerId')));
			$_GET['id'] = $PDO->lastInsertId();
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De pagina is aangemaakt.</p></div><br>".messageForm();
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>".messageForm();
	}
}

function messageOverview(){
	global $PDO;
	$result = $PDO->query("SELECT shopmessages.*, klanten.username FROM shopmessages LEFT JOIN klanten ON uid = klanten.id");
	$return =  "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Overzicht van pagina's</h2></div>".'<div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 35%;">Titel</div>
					<div class="tableCell">gemaakt op</div>
					<div class="tableCell">aangepast op</div>
					<div class="tableCell">geplaatst door</div>
					<div class="tableCell">Zichtbaar</div>
					<div class="tableCell">Link</div>
					<div class="tableCell"></div>
				</div>';
	$articleCount = 0;
	foreach($result as $row){
		$return .= '<div class="tableRow">
					<div class="tableCell"><a href="index.php?action=admin&subaction=11&id='.$row['id'].'">'.$row['title'].'</a></div>
					<div class="tableCell">'.date('d-m-Y', $row['date']).'</div>
					<div class="tableCell">'.date('d-m-Y', $row['lasteditdate']).'</div>
					<div class="tableCell">'.$row['username'].'</div>
					<div class="tableCell">'.janee($row['publish']).'</div>
					<div class="tableCell"><a href="index.php?action=showMessage&id='.$row['id'].'">Permalink</a></div>
					<div class="tableCell"><a href="index.php?action=admin&subaction=15&id='.$row['id'].'" class="w3-btn w3-theme-l2">verwijder</a></div>
				</div>';
		$articleCount++;
	}
	if($articleCount == 0){
		$return .= '</div><div class="w3-container">Er zijn nog geen webpagina\'s aangemaakt.<br><br><a href="index.php?action=admin&subaction=11" class="w3-btn w3-theme-l2">Nieuwe webpagina aanmaken</a><br><br></div>';
	} else {
		$return .= '</div><div class="w3-container"><br><br><a href="index.php?action=admin&subaction=11" class="w3-btn w3-theme-l2">Nieuwe webpagina aanmaken</a><br><br></div>';
	}
	return $return;
}

function deleteMessage(){
	global $PDO;
	$errormsg = FALSE;
	$zekerweten = FALSE;
	if(!isset($_GET['id']) OR $_GET['id'] == ''){
		$errormsg = 'Geen pagina geslecteerd.<br>';
	}
	if(!isset($_GET['zeker']) OR $_GET['zeker'] == ''){
		$zekerweten = TRUE;
	}
	if(!$errormsg){
		if(!$zekerweten){
			$delete = $PDO->prepare("DELETE FROM shopmessages WHERE id = :id");
			$delete->execute(array(':id' => $_GET['id']));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De pagina is verwijderd.</p></div><br>".messageOverview();
		} else {
			return "<div class=\"w3-container w3-yellow\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Weet u het zeker?</h3>
					<p>U staat op het punt deze pagina te verwijderen, weet u dit zeker? <a href='index.php?action=admin&subaction=15&id=".$_GET['id']."&zeker=1'>Ja</a></p></div><br>".showMessage();
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>".messageOverview();
	}
}

function openrabtes(){
	global $PDO;
	$result = $PDO->query("SELECT * FROM orders WHERE prijs < 0 AND rebatedone = 0 AND 	costumercomfirmed = 1");
	$return =  "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Overzicht van openstaande terugbetalingen</h2></div>".'<div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 20%;">Order</div>
					<div class="tableCell" >Bedrag</div>
					<div class="tableCell">rekeningnummer</div>
					<div class="tableCell">geplaatst op</div>
					<div class="tableCell"></div>
				</div>';
	$articleCount = 0;
	foreach ($result as $row){
		$return .= '<div class="tableRow">
					<div class="tableCell">ordernummer: '.$row['id'].'</div>
					<div class="tableCell">&euro; '.number_format(abs($row['prijs']), 2, ',', '.').'</div>
					<div class="tableCell">'.$row['rekeningnummer'].'</div>
					<div class="tableCell">'.date('d-m-Y', $row['besteldatum']).'</div>
					<div class="tableCell"><a href="index.php?action=admin&subaction=16&id='.$row['id'].'" class="w3-btn w3-theme-l2">verwerkt</a></div>
				</div>';
		$articleCount++;
	}
	if($articleCount == 0){
		$return .= '</div><div class="w3-container">Er zijn geen openstaande terugbetalingen.<br><br></div>';
	} else {
		$return .= '</div><div class="w3-container"><br><br></div>';
	}
	return $return;
}

function finishrebates(){
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_GET['id']) OR $_GET['id'] == ''){
		$errormsg = 'Geen order geselecteerd';
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>".openrabtes();
	} else {
		$update = $PDO->prepare("UPDATE orders SET rebatedone = 1 WHERE id = :id");
		$update->execute(array(':id' => $_GET['id']));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De betaling aan de klant is verwerkt.</p></div><br>".openrabtes();
	}
}

$Page->addToBody(createNavBar(array('manageUser' => 'Gebruikers beheren','makeUser' => 'User cre&euml;ren','shopOptions' => 'Webshop eigenschappen','shopLogo' => 'Logo aanpassen','manCat' => 'Categori&euml;n beheren')));

if(isset($_GET['subaction'])){
	$subaction = $_GET['subaction'];
} else {
	$subaction = '';
}

if(isset($_GET['screen'])){
	$screen = $_GET['screen'];
} else {
	$screen = 3;
}
if(isset($_GET['userId'])){
	$userId = $_GET['userId'];
} else {
	$userId = NULL;
}


if($User->isAdmin()){
	switch ($subaction){
	
		case 1:
			$Page->addToBody(searchUser('admin').makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField(1));
			break;
	
		case 2:
			//user aanpassen
			$Page->addToBody(editUser($userId));
			break;
	
		case 3:
			//user verwijderen
			$Page->addToBody(deleteUser($userId));
			break;
	
		case 4:
			$Page->addToBody(registerUsername($_POST['username'], $_POST['password'], $_POST['cpassword'], $_POST['email']));
			break;
	
		case 5:
			$Page->addToBody(alterSiteSettings());
			break;
	
		case 6:
			$Page->addToBody(alterCountryShipStatus());
			break;
	
		case 7:
			$Page->addToBody(uploadLogoImage());
			break;
	
		case 8:
			$Page->addToBody(addOrEditCategory());
			break;
	
		case 9:
			$Page->addToBody(removeCategory());
			break;
			
		case 10:
			$Page->changePageTitle("Omzet overzichten");
			$Page->clearBody();
			$Page->addToBody(getYearlyOverview());
			break;
			
		case 11:
			$Page->clearBody();
			$Page->addToBody(messageForm());
			break;
			
		case 12:
			$Page->clearBody();
			$Page->addToBody(postOrEditMessage());
			break;
			
		case 13:
			$Page->changePageTitle("Overzicht van webpagina's");
			$Page->clearBody();
			$Page->addToBody(messageOverview());
			break;
		
		case 14:
			$Page->changePageTitle("Overzicht van openstaande terugbetalingen");
			$Page->clearBody();
			$Page->addToBody(openrabtes());
			break;
	
		case 15:
			$Page->changePageTitle("Pagina verwijderen");
			$Page->clearBody();
			$Page->addToBody(deleteMessage());
			break;
			
		case 16:
			$Page->changePageTitle("Overzicht van openstaande terugbetalingen");
			$Page->clearBody();
			$Page->addToBody(finishrebates());
			break;
				
		default:
			$Page->addToBody(manageUserForm($userId).makeUserForm().shopOptionsForm().shopLogoForm().shopCategoryForm().determineOpenField($screen));
			break;
	}
} else {
	$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>U bezit niet de juiste rechten voor deze actie, mogelijk bent u niet ingelogd.</p></div><br>");
}
?>




