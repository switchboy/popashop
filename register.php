<?php
function createUserForm($username, $email){
	return "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Registreren</h2></div><form class='w3-container' action='index.php?action=register&subaction=1' method='post'>
					<p><label>Gebruikersnaam:</label><input class='w3-input w3-border' type='text' name='username' value='".$username."'></input></p>
					<p><label>E-mail:</label><input class='w3-input w3-border' type='text' name='email' value='".$email."'></input></p>
					<p><label>Wachtwoord:*</label><input class='w3-input w3-border' type='password' name='password' value=''></input></p>
					<p><label>Controle:*</label><input class='w3-input w3-border' type='password' name='cpassword' value=''></input></p>
					<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='aanmaken'></input></p>
					<p>Al een account? <a href='index.php?action=login'>Log dan in</a>.<br>
					*Beide moeten overeen komen.<br>Het wachtwoord moet minstens 8 tekens zijn.</p>
					</form>
					</div>";
}

function userdetailsForm($voornaam, $achternaam,$tussenvoegsel, $straatnaam, $huisnummer, $toevoeging, $postcode, $stad, $geboortedatum, $kvknummer, $bedrijfsnaam){
	return "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Klantgegevens</h2></div>
			<form class='w3-container' action='index.php?action=register&subaction=2' method='post'>
			<p><label>Voornaam:</label><input class='w3-input w3-border' type='text' name='voornaam' value='$voornaam'></p>
			<p><label>Achternaam:</label><input class='w3-input w3-border' type='text' name='achternaam' value='$achternaam'></p>
			<p><label>tussenvoegsel:</label><input class='w3-input w3-border' type='text' name='tussenvoegsel' value='$tussenvoegsel'></p>
			<p><label>Straatnaam:</label><input class='w3-input w3-border' type='text' name='straatnaam' value='$straatnaam'></p>
			<p><label>Huisnummer:</label><input class='w3-input w3-border' type='text' name='huisnummer' value='$huisnummer'></p>
			<p><label>Toevoeging:</label><input class='w3-input w3-border' type='text' name='toevoeging' value='$toevoeging'></p>
			<p><label>Postcode:</label><input class='w3-input w3-border' type='text' name='postcode' value='$postcode'></p>
			<p><label>Stad:</label><input class='w3-input w3-border' type='text' name='stad' value='$stad'></p>
			<p><label>Geboortedatum</label><input class='w3-input w3-border' type='date' name='geboortedatum' value='$geboortedatum'></p>
			<div class='w3-container w3-theme-l2'>
				<h3>Maak een bedrijf aan:</h3>
				<p>Indien u voor uw bedrijf bestellingen wilt plaatsen via dit account voert u hieronder de benodigde gegvens in. Bedrijven worden geverifieerd dit proces gaat handmatig en kan enkele dagen duren. Indien u geen bedrijf aan dit account wenst te koppellen laat u dan de velden leeg.</p>
			</div>
			<p><label>KvK-nummer</label><input class='w3-input w3-border' type='text' name='kvknummer' value='$kvknummer'></p>
			<p><label>Bedrijfsnaam</label><input class='w3-input w3-border' type='text' name='bedrijfsnaam' value='$bedrijfsnaam'></p>
			<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Invoeren'></input></p>
			</form></div>";
}

function registerUsername($username, $password, $cpassword, $email){
	global $PDO;
	global $Settings;
	$errorCode;
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
	if(strlen ($password) < '8'){
		$errorCode = $errorCode."Een wachtwoord moet minstens 8 tekens lang zijn.<br>";
		$error = TRUE;
	}
	if(!$error){
		$salt = createSalt();
		$hash = sha1($salt . $password );
		$reghash = sha1($salt . time() );
		$insert = $PDO->prepare("INSERT INTO `klanten` (username, email, wachtwoord, salt, reghash) VALUES (:username, :email, :wachtwoord, :salt, :reghash)");
		$insert->execute(array(':username' => $username, ':email' => $email, ':wachtwoord' => $hash, ':salt' => $salt, ':reghash' =>$reghash));
		$_SESSION['reghash'] = $reghash;
		$_SESSION['username'] = $username;
		$message = "Beste klant, \r\n \r\n Deze mail is verzonden omdat er op ".$Settings->_get(siteDomain)." een account is aangemaakt met dit email adres. Om de registratie van uw account te bevestigen vragen wij u om op onderstaande link te klikken: \r\n ".$Settings->_get(fullPath)."index.php?action=register&subaction=3&username=$username&userkey=$reghash \r\n \r\n Heeft u geen account geregistreerd? Dan kunt u deze mail als niet verzonden beschouwen. \r\n \r\n Met vriendelijke groet,\r\n Het team van ".$Settings->_get(siteName);
		$subject = "Bevestig uw registratie". $Settings->_get('siteDomain');
		sendEmail($email, 'noreply@'.$Settings->_get(siteDomain), $subject, $message);
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Gebruikersnaam aangemaakt.</p></div><br>".userdetailsForm(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errorCode."</p></div>".createUserForm($username, $email);
	}
}

function addAdditionalInfo($voornaam, $achternaam,$tussenvoegsel, $straatnaam, $huisnummer, $toevoeging, $postcode, $stad, $geboortedatum, $kvknummer, $bedrijfsnaam){
	global $PDO;
	$errorCode;
	$error = FALSE;
	$result = $PDO->prepare("SELECT reghash FROM `klanten` WHERE username = :username");
	$result->execute(array(':username' => $_SESSION['username']));
	$row = $result->fetch();
	if($row['reghash'] !=  $_SESSION['reghash']){
		$errorCode = $errorCode."Geen rechten om deze gebruiker aan te passen.<br>";
		$error = TRUE;
	}
	if($postcode != NULL){
		if(!checkPostcode($postcode)){
			$errorCode = $errorCode."Deze postcode is ongeldig.<br>";
			$error = TRUE;
		}
	}
	if(!is_numeric($huisnummer) AND $huisnummer != NULL){
		$errorCode = $errorCode."Huisnummer mag alleen uit cijfers bestaan.<br>";
		$error = TRUE;
	}
	if(!$error){
		$update = $PDO->prepare("UPDATE `klanten` SET 
				voornaam = :voornaam, 
				achternaam = :achternaam, 
				tussenvoegsel = :tussenvoegsel, 
				straatnaam = :straatnaam, 
				huisnummer = :huisnummer, 
				toevoeging = :toevoeging, 
				postcode = :postcode, 
				stad = :stad, 
				geboortedatum = :geboortedatum, 
				kvknummer = :kvknummer, 
				bedrijfsnaam = :bedrijfsnaam 
				WHERE username = :username");
		$update->execute(array(
				':voornaam' => $voornaam,
				':achternaam' => $achternaam,
				':tussenvoegsel' => $tussenvoegsel,
				':straatnaam' => $straatnaam,
				':huisnummer' => $huisnummer,
				':toevoeging' => $toevoeging,
				':postcode' => checkPostcode($postcode),
				':stad' => $stad,
				':geboortedatum' => strtotime($geboortedatum),
				':kvknummer' => $kvknummer,
				':bedrijfsnaam' => $bedrijfsnaam,
				':username' => $_SESSION['username']
		));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Uw account is aangemaakt, u kunt inloggen na activatie van uw account via de link welke verzonden is naar het door u ingevoerde e-mail adres.</p></div><br>".imageSlider().showPromotions('0', '3');
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errorCode."</p></div>".userdetailsForm($voornaam, $achternaam,$tussenvoegsel, $straatnaam, $huisnummer, $toevoeging, $postcode, $stad, $geboortedatum, $kvknummer, $bedrijfsnaam);
	}
}

function activateAccount($username, $hash){
	global $Page;
	$Page->changePageTitle('Acount activeren');
	global $PDO;
	global $User;
	$result = $PDO->prepare("SELECT id, username, salt, reghash FROM `klanten` WHERE `username` = :username");
	$result->execute(array(':username' => $_GET['username']));
	$row = $result->fetch();
	$control = $row['reghash'];
	$hashedtime = sha1($row['salt'] . time());
	if($hash == $control){
		$update = $PDO->prepare("UPDATE `klanten` SET active = '1', login_ip = :login_ip , reghash = :reghash WHERE username = :username");
		$update->execute(array(':reghash' => $hashedtime,  ':login_ip' => $_SERVER['REMOTE_ADDR'], ':username' => $username));
		//log de gebruiker meteen in
		$_SESSION['login_ip'] = $_SERVER['REMOTE_ADDR'];
		$_SESSION['userId'] = $row['id'];
		$_SESSION['userName'] = $row['username'];
		$_SESSION['sessionhash'] = $hashedtime;
		retainSession($hashedtime);
		$User = NULL;
		$User = new user();
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Uw account is nu geactiveerd en u bent nu tevens ingelogd.</p></div><br>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Deze activatiecode is ongeldig.</p></div><br>";
	}
	
}
$Page->changePageTitle('Registreren');

if(!isset($_GET['subaction'])){
	$_GET['subaction'] = 'default';
}

function forgotPasswordMailer(){
	global $Page;
	$Page->changePageTitle('Wachtwoord vergeten');
	global $PDO;
	global $Settings;
	$formulier = FALSE;
	if(!isset($_POST['email']) OR $_POST['email'] == ''){
		$formulier = ' ';
	} else {
		$resultEmail = $PDO->prepare("SELECT * FROM klanten WHERE email = :email");
		$resultEmail->execute(array(':email' => $_POST['email']));
		$rowEmail = $resultEmail->fetch();
		if($rowEmail['id'] == NULL){
			$formulier = "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Onbekend email adres.</p></div><br>";
		} else {
			$email = $_POST['email'];
			$message = "Beste klant, \r\n \r\n Deze mail is verzonden omdat er op ".$Settings->_get(siteDomain)." een verzoek is gedaan om het wachtwoord van het account wat aan dit email adres verbonden is te restten. Indien u dit inderdaad wenst klink dan op de volgende link:
					".$Settings->_get(fullPath)."index.php?action=register&subaction=5&email=$email&hash=".$rowEmail['reghash']." \r\n Heeft u hier niet op geklikt? Dan kunt u deze mail als niet verzonden beschouwen. \r\n \r\n Met vriendelijke groet,\r\n Het team van ".$Settings->_get(siteName);
			$subject = "Wachtwoord resetten op ". $Settings->_get('siteDomain');
			sendEmail($email, 'noreply@'.$Settings->_get(siteDomain), $subject, $message);
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Er is een mail gestuurd naar het opgegeven email adres met verdere instructies om uw wachtoord te resetten.</p></div><br>";
		}
	}
	if($formulier){
		return $formulier."<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Wachtwoord vergeten</h2></div>
			<form class='w3-container' action='index.php?action=register&subaction=4' method ='post'>
				<p>Om uw wachtwoord te resetten vult u hieronder het emailadres in waarmee u uw account hebt gemaakt.</p>
				<p><label>E-mail:</label><input class='w3-input w3-border' type='text' name='email' value=''></input></p>
				<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='wachtwoord resetten'></input></p>
				</form></div>";
	}
}

function resetPasswordForm(){
	global $Page;
	$Page->changePageTitle('Wachtwoord resetten');
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_GET['email']) OR $_GET['email'] == ''){
		$errormsg=$errormsg."Geen email adres.";
	} else {
		$resultEmail = $PDO->prepare("SELECT * FROM klanten WHERE email = :email");
		$resultEmail->execute(array(':email' => $_GET['email']));
		$rowEmail = $resultEmail->fetch();
		if($rowEmail['id'] == NULL){
			$errormsg = $errormsg."Dit email adres bestaat niet.";
		} else {
			$email = $_GET['email'];
		}
	}
	if(!isset($_GET['hash']) OR $_GET['hash'] == ''){
		$errormsg=$errormsg."Geen (geldige) hash.";
	} else {
		$hash = $_GET['hash']; 
		if($hash != $rowEmail['reghash']){
		 $errormsg=$errormsg."Geen geldige hash.";
		}
	}
	if(!$errormsg){
		return "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Wachtwoord vergeten</h2></div>
			<form class='w3-container' action='index.php?action=register&subaction=6' method ='post'>
				<p>Om uw wachtwoord te resetten vult u hieronder een nieuw wachtoowrd in.</p>
				<input type='hidden' name='email' value='$email'></input><input type='hidden' name='hash' value='$hash'></input>
				<p><label>nieuw wachtwoord:</label><input class='w3-input w3-border' type='password' name='password' value=''></input></p>
				<p><label>herhaal het nieuwe wachtwoord:</label><input class='w3-input w3-border' type='password' name='cpassword' value=''></input></p>
				<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='wachtwoord resetten'></input></p>
				</form></div>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}

function resetPaswordCommit(){
	global $Page;
	$Page->changePageTitle('Wachtwoord resetten');
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_POST['email']) OR $_POST['email'] == ''){
		$errormsg=$errormsg."Geen email adres.";
	} else {
		$resultEmail = $PDO->prepare("SELECT * FROM klanten WHERE email = :email");
		$resultEmail->execute(array(':email' => $_POST['email']));
		$rowEmail = $resultEmail->fetch();
		if($rowEmail['id'] == NULL){
			$errormsg = $errormsg."Dit email adres bestaat niet.";
		} else {
			$email = $_POST['email'];
		}
	}
	if(!isset($_POST['hash']) OR $_POST['hash'] == ''){
		$errormsg=$errormsg."Geen (geldige) hash.";
	} else {
		$hash = $_POST['hash'];
		if($hash != $rowEmail['reghash']){
			$errormsg=$errormsg."Geen geldige hash.";
		}
	}
	if(!isset($_POST['password']) OR $_POST['password'] == ''){
		$errormsg=$errormsg."Geen wachtwoord ingevuld.";
	} else {
		if(strlen($_POST['password']) < 8){
			$errormsg=$errormsg."Het wachtwoord moet minstens 8 tekens lang zijn.";
		} else {
			if(isset($_POST['cpassword'])){
				if($_POST['password'] != $_POST['cpassword']){
					$errormsg=$errormsg."Het wachtwoord en controle wachtwoord moeten overeenkomen.";
				}
			} else {
				$errormsg=$errormsg."Geen controle wachtwoord ingevuld.";
			}
		}
	}
	if(!$errormsg){
		$newhash = sha1($rowEmail['salt'] . $_POST['password'] );
		$update = $PDO->prepare("UPDATE klanten SET wachtwoord = :wachtwoord WHERE email = :email");
		$update->execute(array(':wachtwoord' => $newhash, ':email' => $email));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Uw wachtwoord is opniew ingesteld. U kunt nu inloggen.</p></div><br>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}

function editCostumerForm(){
	global $Page;
	$Page->changePageTitle('Klantgegevens aanpassen');
	global $User;
	global $PDO;
	$result = $PDO->prepare("SELECT * FROM klanten WHERE username = :username");
	$result->execute(array(':username' => $User->_get('username')));
	$row = $result->fetch();
	return "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Klantgegevens</h2></div>
	<form class='w3-container' action='index.php?action=register&subaction=8' method='post'>
	<p><label>Voornaam:</label><input class='w3-input w3-border' type='text' name='voornaam' value='".$row['voornaam']."'></p>
	<p><label>Achternaam:</label><input class='w3-input w3-border' type='text' name='achternaam' value='".$row['achternaam']."'></p>
	<p><label>tussenvoegsel:</label><input class='w3-input w3-border' type='text' name='tussenvoegsel' value='".$row['tussenvoegsel']."'></p>
	<p><label>Straatnaam:</label><input class='w3-input w3-border' type='text' name='straatnaam' value='".$row['straatnaam']."'></p>
	<p><label>Huisnummer:</label><input class='w3-input w3-border' type='text' name='huisnummer' value='".$row['huisnummer']."'></p>
	<p><label>Toevoeging:</label><input class='w3-input w3-border' type='text' name='toevoeging' value='".$row['toevoeging']."'></p>
	<p><label>Postcode:</label><input class='w3-input w3-border' type='text' name='postcode' value='".$row['postcode']."'></p>
	<p><label>Stad:</label><input class='w3-input w3-border' type='text' name='stad' value='".$row['stad']."'></p>
	<p><label>Geboortedatum</label><input class='w3-input w3-border' type='date' name='geboortedatum' value='".date("Y-m-d", $row['geboortedatum'])."'></p>
	<div class='w3-container w3-theme-l2'>
	<h3>Maak een bedrijf aan:</h3>
	<p>Indien u voor uw bedrijf bestellingen wilt plaatsen via dit account voert u hieronder de benodigde gegvens in. Bedrijven worden geverifieerd dit proces gaat handmatig en kan enkele dagen duren. Indien u geen bedrijf aan dit account wenst te koppellen laat u dan de velden leeg.</p>
	</div>
	<p><label>KvK-nummer</label><input class='w3-input w3-border' type='text' name='kvknummer' value='".$row['kvknummer']."'></p>
	<p><label>Bedrijfsnaam</label><input class='w3-input w3-border' type='text' name='bedrijfsnaam' value='".$row['bedrijfsnaam']."'></p>
	<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Invoeren'></input></p>
	</form></div>";
}

function editCostumer(){
	global $Page;
	$Page->changePageTitle('Klantgegevens aanpassen');
	global $PDO;
	global $User;
	$errormsg = FALSE;
	$username = $User->_get('username');
	
	//sanitycheck
	if(isset($_POST['voornaam']) AND $_POST['voornaam'] != NULL){
		$voornaam = $_POST['voornaam'];
		if(!ctype_alpha(str_replace(array(' ', "'", '-'), '', $voornaam))){
			$errormsg = $errormsg."Een voornaam mag alleen uit een combinatie van letters, -, . en ' bestaan.<br>";
		}
	} else {
		$voornaam = '';
	}
	if(isset($_POST['achternaam']) AND $_POST['achternaam'] != NULL){
		$achternaam = $_POST['achternaam'];
		if(!ctype_alpha(str_replace(array(' ', "'", '-'), '', $achternaam))){
			$errormsg = $errormsg."Een achternaam mag alleen uit een combinatie van letters, -, . en ' bestaan.<br>";
		}
	} else {
		$achternaam = '';
	}
	if(isset($_POST['tussenvoegsel']) AND $_POST['tussenvoegsel'] != NULL){
		$tussenvoegsel = $_POST['tussenvoegsel'];
		if(!ctype_alpha(str_replace(array(' ', "'", '-'), '', $tussenvoegsel))){
			$errormsg = $errormsg."Een tussenvoegsel mag alleen uit een combinatie van letters, -, . en ' bestaan.<br>";
		}
	} else {
		$tussenvoegsel = '';
	}
	if(isset($_POST['straatnaam']) AND $_POST['straatnaam'] != NULL){
		$straatnaam = $_POST['straatnaam'];
		if(!ctype_alpha(str_replace(array(' ', "'", '-'), '', $straatnaam))){
			$errormsg = $errormsg."Een straatnaam mag alleen uit een combinatie van letters, -, . en ' bestaan.<br>";
		}
	} else {
		$straatnaam = '';
	}
	if(isset($_POST['huisnummer']) AND $_POST['huisnummer'] != NULL){
		$huisnummer = $_POST['huisnummer'];
		if(!is_numeric($huisnummer)){
			$errormsg = $errormsg."Een huisnummer mag allen uit cijfers bestaan.<br>";
			
		}
	} else {
		$huisnummer = '';
	}
	if(isset($_POST['toevoeging']) AND $_POST['toevoeging'] != NULL){
		$toevoeging = $_POST['toevoeging'];
	} else {
		$toevoeging = '';
	}
	if(isset($_POST['postcode']) AND $_POST['postcode'] != NULL){
		$postcode = checkPostcode( $_POST['postcode']);
		 if(!$postcode){
		 	$errormsg = $errormsg."Ongeldige postcode ingevuld.<br>";
		 }
	} else {
		$postcode = '';
	}
	if(isset($_POST['stad']) AND $_POST['stad'] != NULL){
		$stad = $_POST['stad'];
		if(!ctype_alpha(str_replace(array(' ', "'", '-'), '', $stad))){
			$errormsg = $errormsg."Een plaatsnaam mag alleen uit een combinatie van letters, -, . en ' bestaan.<br>";
		}
	} else {
		$stad = '';
	}
	if(isset($_POST['geboortedatum']) AND $_POST['geboortedatum'] != NULL){
		$tempdate = $_POST['geboortedatum'];
		$geboortedatum = strtotime($tempdate);
	} else {
		$geboortedatum = '';
	}
	if(isset($_POST['kvknummer']) AND $_POST['kvknummer'] != NULL){
		$kvknummer = $_POST['kvknummer'];
	} else {
		$kvknummer = '';
	}
	if(isset($_POST['bedrijfsnaam']) AND $_POST['bedrijfsnaam'] != NULL){
		$bedrijfsnaam = $_POST['bedrijfsnaam'];
		if(!ctype_alpha(str_replace(array(' ', "'", '-'), '', $bedrijfsnaam))){
			$errormsg = $errormsg."Een bedrijfsnaam mag alleen uit een combinatie van letters, -, . en ' bestaan.<br>";
		}
	} else {
		$bedrijfsnaam = '';
	}
	
	//data naar de database
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE `klanten` SET
				voornaam = :voornaam,
				achternaam = :achternaam,
				tussenvoegsel = :tussenvoegsel,
				straatnaam = :straatnaam,
				huisnummer = :huisnummer,
				toevoeging = :toevoeging,
				postcode = :postcode,
				stad = :stad,
				geboortedatum = :geboortedatum,
				kvknummer = :kvknummer,
				bedrijfsnaam = :bedrijfsnaam
				WHERE username = :username");
		$update->execute(array(
				':voornaam' => $voornaam,
				':achternaam' => $achternaam,
				':tussenvoegsel' => $tussenvoegsel,
				':straatnaam' => $straatnaam,
				':huisnummer' => $huisnummer,
				':toevoeging' => $toevoeging,
				':postcode' => $postcode,
				':stad' => $stad,
				':geboortedatum' => $geboortedatum,
				':kvknummer' => $kvknummer,
				':bedrijfsnaam' => $bedrijfsnaam,
				':username' => $username
		));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Uw account gegevens zijn aangepast.</p></div><br>".editCostumerForm();
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>".editCostumerForm();
	}
}

function changePassword(){
	global $PDO;
	global $User;
	$errormsg = FALSE;
	if(!isset($_POST['password']) AND !isset($_POST['cpassword'])){
		$errormsg = ' ';
	} else {
		if(!strlen($_POST['password']) >= 8){
			$errormsg=$errormsg."Het wachtwoord moet minstens 8 tekens lang zijn.";
		} elseif($_POST['password'] != $_POST['cpassword']){
			$errormsg=$errormsg."Het wachtwoord en controle wachtwoord moeten overeenkomen.";
		} elseif(strlen($_POST['password']) < 8){
			$errormsg=$errormsg."Het wachtwoordmoet minstens 8 tekens hebben.";
		} else {
			$row = $User->_get('rawRow');
			$newhash = sha1( $row['salt']. $_POST['password'] );
		}		
	}
	if($errormsg){
		if($errormsg != ' '){
			$errormsg = "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>";
		}
		return $errormsg."<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Wachtwoord aanpassen</h2></div>
			<form class='w3-container' action='index.php?action=register&subaction=9' method ='post'>
				<p>Om uw wachtwoord te verranderen vult u hieronder een nieuw wachtoowrd in.</p>
				<p><label>nieuw wachtwoord:</label><input class='w3-input w3-border' type='password' name='password' value=''></input></p>
				<p><label>herhaal het nieuwe wachtwoord:</label><input class='w3-input w3-border' type='password' name='cpassword' value=''></input></p>
				<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='wachtwoord resetten'></input></p>
				</form></div>";
	} else {
		$update = $PDO->prepare("UPDATE klanten SET wachtwoord = :wachtwoord WHERE username = :username");
		$update->execute(array(':wachtwoord' => $newhash, ':username' => $row['username']));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Uw wachtwoord is aangepast.</p></div><br>";
	}
}

if(!$User->isLoggedIn() OR $_GET['subaction'] >= 7){
	switch ($_GET['subaction']){
		case '1':
			$Page->addToBody(registerUsername($_POST['username'], $_POST['password'], $_POST['cpassword'], $_POST['email']));
			break;
	
		case '2':
			$Page->addToBody(addAdditionalInfo($_POST['voornaam'],  $_POST['achternaam'], $_POST['tussenvoegsel'],  $_POST['straatnaam'],  $_POST['huisnummer'],  $_POST['toevoeging'],  $_POST['postcode'],  $_POST['stad'],  $_POST['geboortedatum'],  $_POST['kvknummer'],  $_POST['bedrijfsnaam']));
			break;
			
		case '3':
			$Page->addToBody(activateAccount($_GET['username'], $_GET['userkey']));
			$Page->addToBody(imageSlider().showPromotions('0', '3'));
			break;
			
		case '4':
			$Page->addToBody(forgotPasswordMailer());
			break;
			
		case '5':
			$Page->addToBody(resetPasswordForm());
			break;
			
		case '6':
			$Page->addToBody(resetPaswordCommit());
			break;
		
		case '7':
			$Page->addToBody(editCostumerForm());
			break;
		
		case '8':
			$Page->addToBody(editCostumer());
			break;
		
		case '9':
			$Page->addToBody(changePassword());
			break;
			
		default:
			$Page->addToBody(createUserForm(NULL,NULL));
			break;
	}
} else {
	$Page->addToBody("U bent reeds ingelogd als: ".$User->_get(username));
}

?>