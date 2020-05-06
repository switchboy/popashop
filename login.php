<?php
function showLoginForm(){
	return "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Inloggen</h2></div><form action='index.php?action=login' method='post' class='w3-container'>
	<p><label>Gebruikersnaam:</label><input class='w3-input w3-border' type='text' name='username' value=''></p>
	<p><label>Wachtwoord:</label><input class= 'w3-input w3-border' type='password' name='password' value=''></p>
	<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Inloggen'></p>
	<p><a href='index.php?action=register&subaction=4'>Wachwoord vergeten?</a><br>
	<a href='index.php?action=register'>Account aanmaken</a></p>
	</form>
	</div>";
}

$Page->changePageTitle('Inloggen');

if(!isset($_POST['username']) AND  !isset($_POST['password'])){
	$Page->addToBody(showLoginForm());
} else {
	if ($_POST['username'] == NULL){
		$errorCode = $errorCode."Geen gebruikersnaam ingevuld.<br>";
		$error = TRUE;
	}
	if ($_POST['password'] == NULL){
		$errorCode = $errorCode."Geen wachtwoord ingevuld.<br>";
		$error = TRUE;
	}
	$result = $PDO->prepare("SELECT * FROM `klanten` WHERE username = :username");
	$result->execute(array(':username' => $_POST['username']));
	$row = $result->fetch();
	$updateAttempt = $PDO->prepare("UPDATE `klanten` SET attempt_ip = :attempt_ip, login_attempts = login_attempts +1, last_attempt = :last_attempt WHERE username = :username");
	$updateAttempt->execute(array(':attempt_ip' => $_SERVER['REMOTE_ADDR'], ':last_attempt' => time(), ':username' => $_POST['username']));
	if(sha1($row['salt'] . $_POST['password'] ) == $row['wachtwoord']){
		if($row['login_attempts'] > 4 && $row[last_attempt]+900 > time()){
			$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Teveel mislukte inlogpogingen, wacht 15 minuten en probeer het dan opnieuw.</p></div><br>");
		} else {
			$reghash = sha1($row['salt'] . time());
			$updateAttempt = $PDO->prepare("UPDATE `klanten` SET login_ip = :attempt_ip, login_attempts = '0', last_attempt = :last_attempt, reghash = :reghash WHERE username = :username");
			$updateAttempt->execute(array('attempt_ip' => $_SERVER['REMOTE_ADDR'], ':last_attempt' => time(), ':reghash' => $reghash, ':username' => $_POST['username']));
			$_SESSION['login_ip'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['userId'] = $row['id'];
			$_SESSION['userName'] = $row['username'];
			$_SESSION['sessionhash'] = $reghash;
			retainSession($reghash);
			$User = NULL;
			$User = new user();
			$Page->addToBody("<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Inloggen is gelukt.</p></div><br>");
			$Page->addToBody(imageSlider().showPromotions('0', '3'));
		}
	} else {
		$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Verkeerde username of wachtwoord ingevoerd!</p></div><br>".showLoginForm());
	}
}
?>