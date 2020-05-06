<?php

if(isset($_GET['subaction'])){
	$subaction = $_GET['subaction'];
} else {
	$subaction = '';
}

function serviceStartScreen($doWhat){
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen gebruiker geselecteerd<br>'; 
	} else {
		$uid = $_GET['uid'];
		$resultKlant = $PDO->prepare("SELECT * FROM klanten WHERE id = :id");
		$resultKlant->execute(array(':id' => $uid));
		$rowKlant = $resultKlant->fetch();
		if($rowKlant['id'] == NULL){
			$errormsg .= 'Dit klantnummer bestaat niet.<br>';
		} else {
			if($rowKlant['tussenvoegsel'] != ''){
				$tussenvoegsel = ", ".$rowKlant['tussenvoegsel'];
			} else {
				$tussenvoegsel = '';
			}
		}
	}
	if(!$errormsg){
		$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Service center:</h2></div>
				<div class='w3-container'><h3>Klantgegevens:</h3><p><p>".$rowKlant['voornaam']." ".$rowKlant['achternaam'].$tussenvoegsel."<br>".$rowKlant['straatnaam']." ".$rowKlant['huisnummer']." ".
				$rowKlant['toevoeging']."<br>".$rowKlant['postcode']." ".$rowKlant['stad']."</p></div>";
		switch($doWhat){
			
			default:
				$return .= showLastTenCalls($uid).showEpisodes($uid, TRUE).showEpisodes($uid, FALSE).showServiceOrderList($uid).showInnamesKlanr($uid).showOpenServiceOrders($uid);
				break;
		}
		$return .= '</div>';
		return $return;
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".searchUser('service');
	}
}


function limitedServiceStartScreen(){
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen gebruiker geselecteerd<br>';
	} else {
		$uid = $_GET['uid'];
		$resultKlant = $PDO->prepare("SELECT * FROM klanten WHERE id = :id");
		$resultKlant->execute(array(':id' => $uid));
		$rowKlant = $resultKlant->fetch();
		if($rowKlant['id'] == NULL){
			$errormsg .= 'Dit klantnummer bestaat niet.<br>';
		} else {
			if($rowKlant['tussenvoegsel'] != ''){
				$tussenvoegsel = ", ".$rowKlant['tussenvoegsel'];
			} else {
				$tussenvoegsel = '';
			}
		}
	}
	if(!$errormsg){
		return "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Service center:</h2></div>
				<div class='w3-container'><h3>Klantgegevens:</h3><p><p>".$rowKlant['voornaam']." ".$rowKlant['achternaam'].$tussenvoegsel."<br>".$rowKlant['straatnaam']." ".$rowKlant['huisnummer']." ".
				$rowKlant['toevoeging']."<br>".$rowKlant['postcode']." ".$rowKlant['stad']."</p></div>".showServiceOrderList($uid).showOpenServiceOrders($uid).'</div>';
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".searchUser('service');
	}
}

function makeOrEditServiceEpisode(){
	global $PDO;
	$errormsg = FALSE;
	$post = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen gebruiker geselecteerd<br>';
	} else {
		$uid = $_GET['uid'];
		$resultKlant = $PDO->prepare("SELECT * FROM klanten WHERE id = :id");
		$resultKlant->execute(array(':id' => $uid));
		$rowKlant = $resultKlant->fetch();
		if($rowKlant['id'] == NULL){
			$errormsg .= 'Dit klantnummer bestaat niet.<br>';
		}
	}
	if(!$errormsg){
		if(isset($_GET['post']) && $_GET['post'] == 1){
			if(isset($_GET['edit']) && $_GET['edit'] != NULL) {
				return postEditServiceEpisode($uid);
			}
			return postNewServiceEpisode($uid);
		} else {
			if(isset($_GET['edit']) && $_GET['edit'] != NULL) {
				$resultEdit = $PDO->prepare("SELECT * FROM serviceepisodes WHERE cid = :cid AND id = :id");
				$resultEdit->execute(array(':cid' => $uid, ':id' => $_GET['edit']));
				$rowEdit = $resultEdit->fetch();
				$editpost = '&edit='.$edit;
				if($rowEdit['id'] == NULL){
					$errormsg = 'Deze service episode bestaat niet of is niet gekoppeld aan deze klant.';
				}
			} else {
				$rowEdit['title'] = '';
				$editpost = '';
			}
			return "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Service center - Maak een episode</h2></div>
			<form class='w3-container' action='index.php?action=service&subaction=3&uid=$uid&post=1$editpost' method ='post'>
				<p><label>Naam episode</label><input class='w3-input w3-border' type='text' name='title' value='".$rowEdit['title']."'></input></p>
				<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Episode aanmaken'></input></p>
			</form></div>";
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".searchUser('service');
	}
}

function makeOrEditServiceCall(){
	global $PDO;
	global $User;
	$errormsg = FALSE;
	$post = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen gebruiker geselecteerd<br>';
	} else {
		$uid = $_GET['uid'];
		$resultKlant = $PDO->prepare("SELECT * FROM klanten WHERE id = :id");
		$resultKlant->execute(array(':id' => $uid));
		$rowKlant = $resultKlant->fetch();
		if($rowKlant['id'] == NULL){
			$errormsg .= 'Dit klantnummer bestaat niet.<br>';
		}
	}
	if(!isset($_GET['eid']) OR $_GET['eid'] == NULL){
		$errormsg .= 'Geen episode opgegeven.<br>';
	} else {
		$resultEpisode = $PDO->prepare("SELECT * FROM serviceepisodes WHERE id = :eid AND cid = :uid");
		$resultEpisode->execute(array(':eid' => $_GET['eid'], ':uid' => $uid));
		$rowEpisode = $resultEpisode->fetch();
		if($rowEpisode['id'] == NULL){
			$errormsg .= 'Deze episode bestaat niet of is niet aan deze klant gekoppeld.<br>';
		}
	}
	if(!isset($_POST['text']) OR $_POST['text'] == NULL){
		$errormsg .= 'De call mag niet leeg zijn!<br>';
	}
	if(!$errormsg){
		if(isset($_GET['edit']) && $_GET['edit'] != NULL) {
			//TODO: EDIT code. <-- achteraf gezien geen support want zeer fraude gevoelig.
		} else {
			$insert = $PDO->prepare("INSERT INTO servicecalls (uid, cid, eid, date, text) VALUES (:uid, :cid, :eid, :date, :text)");
			$insert->execute(array(':uid' => $User->_get('costumerId'), ':cid' => $uid, ':eid' => $_GET['eid'], ':date' => time(), ':text' => htmlspecialchars($_POST['text'])));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De call is gemaakt.</p></div><br>".showServiceEpisode($_GET['eid']);
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".searchUser('service');
	}
}

function postNewServiceEpisode($cid){
	global $PDO;
	global $User;
	$errormsg = FALSE;
	if(!isset($_POST['title']) OR $_POST['title'] == ''){
		$_GET['post'] = 0;
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Geen titel ingevoerd.</p></div><br>".makeOrEditServiceEpisode();
	} else {
		$insert = $PDO->prepare("INSERT INTO serviceepisodes (title, date, cid, uid) VALUES(:title, :date, :cid, :uid)");
		$insert->execute(array(':title' => $_POST['title'], ':date' => time(), ':cid' => $cid, ':uid' => $User->_get('costumerId')));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De episode is aangemaakt.</p></div><br>".showServiceEpisode($PDO->lastInsertId());
	}
}

function postEditServiceEpisode($cid){
	global $PDO;
	global $User;
	$errormsg = FALSE;
	if(!isset($_POST['title']) OR $_POST['title'] == ''){
		$_GET['post'] = 0;
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Geen titel ingevoerd.</p></div><br>".makeOrEditServiceEpisode();
	} else {
		$resultEdit = $PDO->prepare("SELECT * FROM serviceepisodes WHERE cid = :cid AND id = :id");
		$resultEdit->execute(array(':cid' => $uid, ':id' => $_GET['edit']));
		$rowEdit = $resultEdit->fetch();
		if($rowEdit['id'] == NULL){
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Deze episode bestaat niet</p></div><br>".serviceStartScreen(NULL);
		}
		//Uitgeschakeld want fraude gevoelig.
		//$update = $PDO->prepare("UPDATE serviceepisodes SET title = :title, date = :date, uid = :uid WHERE id = :id)");
		//$update->execute(array(':title' => htmlspecialchars($_POST['title']), ':date' => time(), ':uid' => $User->_get('costumerId'), ':id' => $_GET['edit']));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De episode is aangepast.</p></div><br>".showServiceEpisode($_GET['edit']);
	}
}

function showServiceEpisode($eid){
	global $PDO;
	$errormsg = FALSE;
	$post = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen gebruiker geselecteerd<br>';
	} else {
		$uid = $_GET['uid'];
	}
	if($eid == NULL){
		$resultKlant = $PDO->prepare("SELECT * FROM klanten WHERE id = :id");
		$resultKlant->execute(array(':id' => $uid));
		$rowKlant = $resultKlant->fetch();
		if($rowKlant['id'] == NULL){
			$errormsg .= 'Dit klantnummer bestaat niet.<br>';
		}
		if(isset($_GET['eid']) && $_GET['eid'] != NULL){
			$eid = $_GET['eid'];
		} else {
			$errormsg .= 'Geen episode geselecteerd<br>';
		}
	}
	if(!$errormsg){
		$result = $PDO->prepare("SELECT serviceepisodes.*, servicecalls.id AS scid, servicecalls.uid AS cuid, servicecalls.date AS cdate, servicecalls.text,  klanten.userName, callklanten.userName as calluserName
									FROM serviceepisodes
									LEFT JOIN servicecalls ON  serviceepisodes.id = servicecalls.eid
									LEFT JOIN klanten ON serviceepisodes.uid = klanten.id
									LEFT JOIN klanten AS callklanten ON servicecalls.uid = klanten.id
									WHERE serviceepisodes.id = :id AND serviceepisodes.cid = :uid
									GROUP BY servicecalls.id 
									ORDER BY servicecalls.id DESC");
		$result->execute(array(':id' => $eid, ':uid' => $uid));
		$counter = 0;
		$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Episode: ";
		foreach ($result as $row){
			if($counter == 0){
				if($row['id'] == NULL){
					return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Deze episode hoort niet bij deze klant of deze episode bestaat niet.</p></div><br>";
				} else{
					if($row['open'] == 1){
						$newCall = "<form class='w3-container' action='index.php?action=service&subaction=5&uid=$uid&eid=$eid&post=1' method ='post'>
						<p><label>Nieuwe call:</label><textarea class='w3-input w3-border' name='text'></textarea></p>
						<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Maak call'></input> <a href='index.php?action=service&subaction=9&uid=$uid&eid=$eid' class='w3-btn w3-theme-l2'>Sluit episode</a></p>
						</form>";
					} else {
						$newCall = '';
					}
					$return .= $row['title']." aangemaakt op ".date('d-m-Y', $row['date'])." door ".$row['userName']."</h2></div><br>".$newCall.'
						<div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell"><b>Medewerker</b></div>
					<div class="tableCell"><b>Datum</b></div>
					<div class="tableCell" style="width: 70%;"><b>call</b></div>
				</div>';
				}
			}
			if($row['scid'] != NULL){
				$return .= '<div class="tableRow">
					<div class="tableCell" style="vertical-align: top;">'.$row['calluserName'].'</div>
					<div class="tableCell" style="vertical-align: top;">'.date('d-m-Y H:m', $row['cdate']).'</div>
					<div class="tableCell" style="vertical-align: top;">'.$row['text'].'</div>
				</div>';
				$counter++;
			}
		
		}
		if($counter == 0){
			$return .= '</div><div class="w3-container">Er zijn nog geen calls bij deze episode.</div><br>';
		} else {
			$return .= '</div><br>';
		}
		$return .= '<div class="w3-container"><a href="index.php?action=service&subaction=2&uid='.$uid.'" class="w3-btn w3-theme-l2">Terug naar het service center</a></div><br>';
		return $return;
		
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}

function showServiceOrderList($cid){
	global $PDO;
	global $User;
	$result = $PDO->prepare("SELECT * FROM orders WHERE userId = :userId ");
	$result->execute(array(':userId' => $cid));
	$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Orders van klant</h2></div><div class='w3-container'><p>";
	$a = 0;
	foreach ($result as $row){
		if($row['verzonden'] == '1'){
			$verzonden = "Verzonden";
		} elseif($row['geraapt'] == '1'){
			$verzonden = "Geraapt";
		}elseif($row['compleet'] == '1'){
			$verzonden = "Op voorraad";
		}elseif($row['voldaan'] == '1' && $row['shop'] == '0' && $row['service'] != '1'){
			$verzonden = "Betaling voldaan";
		} elseif($row['shop'] == 1) {
			$verzonden = "Shop order";
		}elseif($row['service'] == 1) {
			$verzonden = "Service order";
		}else {
			$verzonden = "Betaling niet voldaan";
		}
		$return = $return . "Order nummer: <a href='index.php?action=showOrder&orderId=".$row['id']."'>".$row['id']."</a> geplaatst op: ".date("d-m-Y", $row['besteldatum'])." status: $verzonden <br>";
		$a++;
	}
	if($a == 0){
		$return = $return . "Nog geen orders bij deze klant.";
	}
	$return = $return."</p></div></div>";
	return $return;
}

function showEpisodes($cid, $open){
	global $PDO;
	if($open){
		$result = $PDO->prepare("SELECT serviceepisodes.*, count(servicecalls.id) AS callcount, servicecalls.date as lastcall, klanten.userName, cklanten.userName as cuserName
				FROM serviceepisodes
				LEFT JOIN servicecalls ON serviceepisodes.id = servicecalls.eid
				LEFT JOIN klanten ON serviceepisodes.uid = klanten.id
				LEFT JOIN klanten AS cklanten ON servicecalls.uid = klanten.id
				WHERE serviceepisodes.cid = :cid
				AND open = 1
				GROUP BY serviceepisodes.id 
				ORDER BY lastcall DESC");
		$text = 'Open episodes';
		$etext = 'open';
		$button = '<div class="w3-container"><a href="index.php?action=service&subaction=3&uid='.$cid.'" class="w3-btn w3-theme-l2">Maak nieuwe episode</a></div><br>';
	} else {
		$result = $PDO->prepare("SELECT serviceepisodes.*, count(servicecalls.id) AS callcount, servicecalls.date as lastcall, klanten.userName, cklanten.userName as cuserName
				FROM serviceepisodes
				LEFT JOIN servicecalls ON serviceepisodes.id = servicecalls.eid
				LEFT JOIN klanten ON serviceepisodes.uid = klanten.id
				LEFT JOIN klanten AS cklanten ON servicecalls.uid = klanten.id
				WHERE serviceepisodes.cid = :cid
				AND open = 0
				GROUP BY serviceepisodes.id 
				ORDER BY lastcall DESC");
		$text = 'Gesloten episodes';
		$etext = 'gesloten';
		$button = '';
	}
	$result->execute(array(':cid' => $cid));
	$return = '<div class="w3-container w3-theme-l2"><h2>'.$text.' bij klant:</h2></div>
			<br><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell"><b>Medewerker</b></div>
					<div class="tableCell"><b>Episode datum</b></div>
					<div class="tableCell" style="width: 40%;"><b>Naam</b></div>
					<div class="tableCell"><b>calls</b></div>
					<div class="tableCell" style="width: 12%;"><b>laatste call</b></div>
					<div class="tableCell"><b>door</b></div>
				</div>';
	$counter = 0;
	foreach($result as $row){
		if($row['id'] != NULL){
			$counter++;
			if($row['date'] == NULL){
				$date = ' - ';
			} else {
				$date = date('m-d-Y', $row['date']);
			}
			if($row['lastcall'] == NULL){
				$lastcall = ' - ';
			} else {
				$lastcall = date('m-d-Y', $row['lastcall']);
			}
			$return .= '<div class="tableRow">
							<div class="tableCell" style="vertical-align: top;">'.$row['userName'].'</div>
							<div class="tableCell" style="vertical-align: top;">'.$date.'</div>
							<div class="tableCell" style="vertical-align: top;"><a href="index.php?action=service&subaction=4&uid='.$cid.'&eid='.$row['id'].'">'.$row['title'].'</a></div>
							<div class="tableCell" style="vertical-align: top;">'.($row['callcount']/2).'</div>
							<div class="tableCell" style="vertical-align: top;">'.$lastcall.'</div>
							<div class="tableCell" style="vertical-align: top;">'.$row['cuserName'].'</div>
						</div>';
		}
	}
	if($counter == 0){
		$return .= '</div><div class="w3-container">Er zijn nog geen '.$etext.' episodes bij deze klant.';
	}
	$return .= '</div><br>'.$button;
	return $return;
}

function showLastTenCalls($cid){
	global $PDO;
	$result = $PDO->prepare("SELECT servicecalls.text, servicecalls.date, serviceepisodes.id as id, serviceepisodes.title, serviceepisodes.date AS edate, klanten.username 
							FROM servicecalls 
							LEFT JOIN serviceepisodes ON servicecalls.eid = serviceepisodes.id 
							LEFT JOIN klanten ON servicecalls.uid = klanten.id
							WHERE servicecalls.cid = :cid 
							ORDER BY servicecalls.id DESC 
							LIMIT 0, 10");
	$result->execute(array(':cid'=> $cid));
	$return = '<div class="w3-container w3-theme-l2"><h2>Laatste calls bij klant:</h2></div>
			<br><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 12%;"><b>Episode van</b></div>
					<div class="tableCell" style="width: 17.5%;"><b>Episode</b></div>
					<div class="tableCell" style="width: 11%;"><b>Medewerker</b></div>
					<div class="tableCell" style="width: 12%;"><b>Call datum</b></div>
					<div class="tableCell" style="width: 47.5%;"><b>Call</b></div>
				</div>';
	$counter = 0;
	foreach($result as $row){
		$return .= '<div class="tableRow">
						<div class="tableCell" style="vertical-align: top;">'.date('m-d-Y', $row['edate']).'</div>
						<div class="tableCell" style="vertical-align: top;"><a href="index.php?action=service&subaction=4&uid='.$cid.'&eid='.$row['id'].'">'.$row['title'].'</a></div>
						<div class="tableCell" style="vertical-align: top;">'.$row['username'].'</div>
						<div class="tableCell" style="vertical-align: top;">'.date('m-d-Y', $row['date']).'</div>
						<div class="tableCell" style="vertical-align: top;">'.$row['text'].'</div>
					</div>';
		$counter++;
	}
	if($counter == 0){
		$return .= '</div><div class="w3-container">Er zijn nog geen calls bij deze klant.</div><br>';
	} else {
		$return .= '</div><br>';
	}
	return $return;
}

function showInnamesKlanr($cid){
	global $PDO;
	$result = $PDO->prepare("SELECT innames.*, innamestatus.status, klanten.userName, producten.productNaam
							FROM innames
							LEFT JOIN innamestatus ON innames.statusid = innamestatus.id
							LEFT JOIN klanten ON innames.uid = klanten.id
							lEFT JOIN producttracking ON innames.pid = producttracking.id
							LEFT JOIN producten ON producttracking.pid = producten.id
							WHERE innames.cid = :cid
							ORDER BY innames.id DESC");
	$result->execute(array(':cid' =>$cid));
	$returnActive = '<div class="w3-container w3-theme-l2"><h2>Huidige ingenomen producten van klant:</h2></div>
			<br><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 15%;"><b>Ingenomen op</b></div>
					<div class="tableCell" style="width: 47%;"><b>Product</b></div>
					<div class="tableCell" style="width: 18%;"><b>Ingenomen door</b></div>
					<div class="tableCell" style="width: 20%;"><b>Status</b></div>
				</div>';
	$returnReturned = '<div class="w3-container w3-theme-l2"><h2>Aferonde ingenomen producten van klant:</h2></div>
			<br><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 15%;"><b>Terug op</b></div>
					<div class="tableCell" style="width: 47%;"><b>Product</b></div>
					<div class="tableCell" style="width: 18%;"><b>Ingenomen door</b></div>
					<div class="tableCell" style="width: 20%;"><b>Status</b></div>
				</div>';
	$activeCounter = 0;
	$returnedCount = 0;
	foreach ($result as $row){
		if($row['retourdatum'] == NULL){
			$activeCounter++;
			$returnActive .= '<div class="tableRow">
					<div class="tableCell">'.date('d-m-Y', $row['innamedatum']).'</div>
					<div class="tableCell"><a href="index.php?action=service&subaction=6&uid='.$row['cid'].'&iid='.$row['id'].'">'.$row['productNaam'].'</a></div>
					<div class="tableCell">'.$row['userName'].'</div>
					<div class="tableCell">'.$row['status'].'</div>
				</div>';
		} else {
			$returnedCount++;
			$returnReturned .= '<div class="tableRow">
					<div class="tableCell">'.date('d-m-Y', $row['retourdatum']).'</div>
					<div class="tableCell"><a href="index.php?action=service&subaction=6&uid='.$row['cid'].'&iid='.$row['id'].'">'.$row['productNaam'].'</a></div>
					<div class="tableCell">'.$row['userName'].'</div>
					<div class="tableCell">'.$row['status'].'</div>
				</div>';
		}
	}
	if($activeCounter == 0){
		$returnActive  .= '</div><div class="w3-container">Er zijn geen actieve innames bij deze klant.</div><br><div class="w3-container"><a href="index.php?action=service&subaction=6&uid='.$cid.'" class="w3-btn w3-theme-l2">Product innemen</a></div></br>';
	} else {
		$returnActive  .= '</div><br><div class="w3-container"><a href="index.php?action=service&subaction=6&uid='.$cid.'" class="w3-btn w3-theme-l2">Product innemen</a></div></br>';
	}
	if($returnedCount == 0){
		$returnReturned  .= '</div><div class="w3-container">Er zijn nog geen afgeronde innames bij deze klant.</div><br>';
	} else {
		$returnReturned  .= '</div><br>';
	}
	return $returnActive.$returnReturned;
}

function generateInnameStatusSelectorList($id){
	global $PDO;
	$result = $PDO->query("SELECT * FROM innamestatus");
	$returnBegin = '<select class="w3-input w3-border" type="text" name="innameStatus">';
	$selectedStatus = '';
	$returnMiddle = '';
	$returnEnd = '</select>';
	foreach ($result as $row){
		$returnMiddle .= '<option value="'.$row['id'].'">'.$row['status'].'</option>';
		if($row['id'] == $id){
			$selectedStatus = '<option selected value="'.$row['id'].'">'.$row['status'].'</option>'; 
		}
	}
	return $returnBegin.$selectedStatus.$returnMiddle.$returnEnd;
}


function innameForm(){
	global $PDO;
	$errormsg = FALSE;
	$post = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen gebruiker geselecteerd<br>';
	} else {
		$uid = $_GET['uid'];
	}
	if(isset($_GET['iid']) && $_GET['iid'] != NULL){
		$iid = $_GET['iid'];
		$result = $PDO->prepare("SELECT * FROM innames WHERE id = :iid");
		$result->execute(array(':iid' => $iid));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg .= 'Deze inname Bestaat niet<br>';
		}
		$title = 'Inname aanpassen';
	} else {
		$row = array('pid' => '', 'statusid' => '', 'conditie' => '', 'retourdatum' => NULL);
		$iid = '';
		$title = 'nieuwe product innemen';
	}
	if($row['retourdatum'] != NULL){
		$sluiten = "<a href='index.php?action=service&subaction=2&uid=$uid' class='w3-btn w3-theme-l2'>Terug naar het service center</a> dit product is reeds terug naar de klant.";
	} else {
		$sluiten = "<input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Toepassen'></input> <a href='index.php?action=service&subaction=2&uid=$uid' class='w3-btn w3-theme-l2'>Terug naar het service center</a> <a href='index.php?action=service&subaction=8&uid=$uid&iid=$iid' class='w3-btn w3-theme-l2'>Product retourneren aan klant</a>";
	}
	if(!$errormsg){
		return "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>$title</h2></div>
			<form class='w3-container' action='index.php?action=service&subaction=7&uid=$uid&iid=$iid' method ='post'>
				<p><label>Shop serienummer</label><input class='w3-input w3-border' type='text' name='pid' value='".$row['pid']."'></input></p>
				<p><label>Status van inname</label>".generateInnameStatusSelectorList($row['statusid'])."</p>
				<p><label>Product conditie</label><input class='w3-input w3-border' type='text' name='conditie' value='".$row['conditie']."'></input></p>
				<p>$sluiten</p>
			</form></div>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}

function closeInname(){
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen gebruiker geselecteerd<br>';
	} else {
		$uid = $_GET['uid'];
	}
	if(isset($_GET['iid']) && $_GET['iid'] != NULL){
		$iid = $_GET['iid'];
		$result = $PDO->prepare("SELECT * FROM innames WHERE id = :iid");
		$result->execute(array(':iid' => $iid));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg .= 'Deze inname Bestaat niet<br>';
		}
		if($row['cid'] != $uid){
			$errormsg .= 'Deze inname hoort niet bij deze klant<br>';
		}
	} else {
		$errormsg .= 'Geen inname gespecificeerd.<br>';
	}
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE innames SET retourdatum = :retourdatum WHERE id =:iid");
		$update->execute(array(':retourdatum' => time(), ':iid' => $iid));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Prouct inname afgesloten</p></div><br>".innameForm();
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}

function closeEpisode(){
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen gebruiker geselecteerd<br>';
	} else {
		$uid = $_GET['uid'];
	}
	if(isset($_GET['eid']) && $_GET['eid'] != NULL){
		$eid = $_GET['eid'];
		$result = $PDO->prepare("SELECT * FROM serviceepisodes WHERE id = :eid");
		$result->execute(array(':eid' => $eid));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg .= 'Deze episode Bestaat niet<br>';
		}
		if($row['cid'] != $uid){
			$errormsg .= 'Deze episodehoort niet bij deze klant<br>';
		}
	} else {
		$errormsg .= 'Geen episode gespecificeerd.<br>';
	}
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE serviceepisodes SET open = '0' WHERE id =:eid");
		$update->execute(array(':eid' => $eid));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Episode afgesloten</p></div><br>".showServiceEpisode($eid);
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}

function addOrEditInname(){
	global $PDO;
	global $User;
	$errormsg = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen gebruiker geselecteerd<br>';
	} else {
		$uid = $_GET['uid'];
	}
	if(isset($_GET['iid']) && $_GET['iid'] != NULL){
		$iid = $_GET['iid'];
		$result = $PDO->prepare("SELECT * FROM innames WHERE id = :iid");
		$result->execute(array(':iid' => $iid));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg .= 'Deze inname Bestaat niet<br>';
		}
		if($row['cid'] != $uid){
			$errormsg .= 'Deze inname hoort niet bij deze klant<br>';
		}
		$new = FALSE;
	} else {
		$new = TRUE;
	}
	if(!isset($_POST['pid']) OR $_POST['pid'] == NULL){
		$errormsg .= 'Geen product gespelecteerd<br>';
	} else {
		$pid = $_POST['pid'];
		$resultCheckSerial = $PDO->prepare("SELECT * FROM producttracking WHERE id = :pid AND oid <> 0");
		$resultCheckSerial->execute(array(':pid' => $pid));
		$rowCheckSerial = $resultCheckSerial->fetch();
		if($rowCheckSerial['id'] == NULL){
			$errormsg .= 'Dit shop serienummer is niet bekend of niet verkocht.<br>';
		}
		$resultCheckSeriald = $PDO->prepare("SELECT * FROM innames WHERE pid = :pid AND retourdatum IS NULL");
		$resultCheckSeriald->execute(array(':pid' => $pid));
		$rowCheckSeriald = $resultCheckSeriald->fetch();
		if($rowCheckSeriald['id'] != NULL && $new){
			$errormsg .= 'Dit product is nog actief ingenomen<br>';
		}
	}
	if(!isset($_POST['conditie']) OR $_POST['conditie'] == NULL){
		$errormsg .= 'Geen status van de inname gespelecteerd<br>';
	} else {
		$conditie = $_POST['conditie'];
	}
	if(!isset($_POST['innameStatus']) OR $_POST['innameStatus'] == NULL){
		$errormsg .= 'Geen status van de inname gespelecteerd<br>';
	} else {
		$innameStatus = $_POST['innameStatus'];
	}
	if(!$errormsg){
		if($new){
			$insert = $PDO->prepare("INSERT INTO innames (uid, cid, pid, innamedatum, statusid, conditie) VALUES (:uid, :cid, :pid, :innamedatum, :statusid, :conditie)");
			$insert->execute(array(':uid' => $User->_get('costumerId'), ':cid' => $uid, ':pid' => $pid, ':innamedatum' => time(), ':statusid' => $innameStatus, ':conditie' => $conditie));
			$_POST['iid'] = $PDO->lastInsertId();
		} else {
			$update = $PDO->prepare("UPDATE innames SET uid = :uid, cid = :cid, pid = :pid, innamedatum = :innamedatum, statusid = :statusid conditie = :conditie WHERE id = :id");
			$update->execute(array(':uid' => $User->_get('costumerId'), ':cid' => $uid, ':pid' => $pid, ':innamedatum' => time(), ':statusid' => $innameStatus, ':conditie' => $conditie, ':id' => $iid));
		}
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Prouct inname toegepast</p></div><br>".innameForm();
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}

function showMyEpisodes(){
	global $PDO;
	global $User;
	$result = $PDO->prepare("SELECT serviceepisodes.*, servicecalls.date as cdate, count(servicecalls.id) as count FROM serviceepisodes 
							LEFT JOIN servicecalls ON serviceepisodes.id = servicecalls.eid
							WHERE serviceepisodes.uid = :uid
							GROUP BY serviceepisodes.id
							ORDER BY serviceepisodes.id DESC");
	$result->execute(array(':uid' => $User->_get('costumerId')));
	$return = "<div class='w3-card-4'>";
	$open = '<div class="w3-container w3-theme-l2"><h2>Uw lopende episodes:</h2></div>
			<br><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 15%;"><b>Klantnummer</b></div>
					<div class="tableCell" style="width: 45%;"><b>Episode</b></div>
					<div class="tableCell" style="width: 15%;"><b>Geopend op</b></div>
					<div class="tableCell" style="width: 15%;"><b>laatste call</b></div>
					<div class="tableCell" style="width: 10%;"><b>Calls</b></div>
				</div>';
	$closed = '<div class="w3-container w3-theme-l2"><h2>Uw gesloten episodes:</h2></div>
			<br><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 15%;"><b>Klantnummer</b></div>
					<div class="tableCell" style="width: 45%;"><b>Episode</b></div>
					<div class="tableCell" style="width: 15%;"><b>Geopend op</b></div>
					<div class="tableCell" style="width: 15%;"><b>laatste call</b></div>
					<div class="tableCell" style="width: 10%;"><b>Calls</b></div>
				</div>';
	$opencount = 0;
	$closedcount = 0;
	foreach ($result as $row){
		if($row['open'] == 1){
			$opencount++;
			$open .= '<div class="tableRow">
					<div class="tableCell">'.$row['cid'].'</div>
					<div class="tableCell"><a href="index.php?action=service&subaction=4&uid='.$row['cid'].'&eid='.$row['id'].'">'.$row['title'].'</a></div>
					<div class="tableCell">'.date('d-m-Y', $row['date']).'</div>
					<div class="tableCell">'.date('d-m-Y', $row['cdate']).'</div>
					<div class="tableCell">'.$row['count'].'</div>
				</div>';
		} else {
			$closedcount++;
			$closed .= '<div class="tableRow">
					<div class="tableCell">'.$row['cid'].'</div>
					<div class="tableCell"><a href="index.php?action=service&subaction=4&uid='.$row['cid'].'&eid='.$row['id'].'">'.$row['title'].'</a></div>
					<div class="tableCell">'.date('d-m-Y', $row['date']).'</div>
					<div class="tableCell">'.date('d-m-Y', $row['cdate']).'</div>
					<div class="tableCell">'.$row['count'].'</div>
				</div>';
		}
	}
	if($opencount == 0){
		$open .= '</div><div class="w3-container">U heeft geen open episodes.</div><br>';
	} else {
		$open .= '</div></br>';
	}
	if($closedcount == 0){
		$closed .= '</div><div class="w3-container">U heeft geen gesloten episodes.</div><br>';
	} else {
		$closed .= '</div></br>';
	}
	$return .= $open.$closed.'</div>';
	return $return;
}



function showMyInnames(){
	global $PDO;
	global $User;
	$result = $PDO->prepare("SELECT innames.*, innamestatus.status, klanten.userName, producten.productNaam
							FROM innames
							LEFT JOIN innamestatus ON innames.statusid = innamestatus.id
							LEFT JOIN klanten ON innames.uid = klanten.id
							lEFT JOIN producttracking ON innames.pid = producttracking.id
							LEFT JOIN producten ON producttracking.pid = producten.id
							WHERE innames.uid = :uid
							ORDER BY innames.id DESC");
	$result->execute(array(':uid' => $User->_get('costumerId')));
	$return = "<div class='w3-card-4'>";
	$returnActive = '<div class="w3-container w3-theme-l2"><h2>Producten die door u zijn ingenomen:</h2></div>
			<br><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 15%;"><b>Ingenomen op</b></div>
					<div class="tableCell" style="width: 47%;"><b>Product</b></div>
					<div class="tableCell" style="width: 18%;"><b>Ingenomen door</b></div>
					<div class="tableCell" style="width: 20%;"><b>Status</b></div>
				</div>';
	$returnReturned = '<div class="w3-container w3-theme-l2"><h2>Innames die zijn afgerond:</h2></div>
			<br><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 15%;"><b>Terug op</b></div>
					<div class="tableCell" style="width: 47%;"><b>Product</b></div>
					<div class="tableCell" style="width: 18%;"><b>Ingenomen door</b></div>
					<div class="tableCell" style="width: 20%;"><b>Status</b></div>
				</div>';
	$activeCounter = 0;
	$returnedCount = 0;
	foreach ($result as $row){
		if($row['retourdatum'] == NULL){
			$activeCounter++;
			$returnActive .= '<div class="tableRow">
					<div class="tableCell">'.date('d-m-Y', $row['innamedatum']).'</div>
					<div class="tableCell"><a href="index.php?action=service&subaction=6&uid='.$row['cid'].'&iid='.$row['id'].'">'.$row['productNaam'].'</a></div>
					<div class="tableCell">'.$row['userName'].'</div>
					<div class="tableCell">'.$row['status'].'</div>
				</div>';
		} else {
			$returnedCount++;
			$returnReturned .= '<div class="tableRow">
					<div class="tableCell">'.date('d-m-Y', $row['retourdatum']).'</div>
					<div class="tableCell"><a href="index.php?action=service&subaction=6&uid='.$row['cid'].'&iid='.$row['id'].'">'.$row['productNaam'].'</a></div>
					<div class="tableCell">'.$row['userName'].'</div>
					<div class="tableCell">'.$row['status'].'</div>
				</div>';
		}
	}
	if($activeCounter == 0){
		$returnActive  .= '</div><div class="w3-container">U heeft geen actieve innames.</div></br>';
	} else {
		$returnActive  .= '</div></br>';
	}
	if($returnedCount == 0){
		$returnReturned  .= '</div><div class="w3-container">U heeft nog geen afgeronde innames.</div><br>';
	} else {
		$returnReturned  .= '</div><br>';
	}
	$return .= $returnActive.$returnReturned."</div>";
	return $return;
}

function showOpenServiceOrders($cid){
	global $PDO;
	$result = $PDO->prepare("SELECT * FROM orders WHERE userId = :cid AND service = 1 AND costumercomfirmed = 0");
	$result->execute(array(':cid' => $cid));
	$return = '<div class="w3-container w3-theme-l2"><h2>Openstaande service orders:</h2></div>
			<br><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 20%;"><b>Order id</b></div>
					<div class="tableCell" style="width: 20%;"><b>aangemaakt op</b></div>
					<div class="tableCell" style="width: 20%;"><b>Prijs</b></div>
					<div class="tableCell" style="width: 20%;"><b>Delete</b></div>
					<div class="tableCell" style="width: 20%;"><b>Maak definitief</b></div>
				</div>';
	
	$orderscount = 0;
	foreach ($result as $row){
		$return .= '<div class="tableRow">
					<div class="tableCell" style="width: 20%;"><a href="index.php?action=serviceOrders&uid='.$cid.'&soid='.$row['id'].'">'.$row['id'].'</a></div>
					<div class="tableCell" style="width: 20%;">'.date('d-m-Y', $row['besteldatum']).'</div>
					<div class="tableCell" style="width: 20%;">'.$row['prijs'].'</div>
					<div class="tableCell" style="width: 20%;"><a href="index.php?action=serviceOrders&subaction=4&uid='.$cid.'&soid='.$row['id'].'" class="w3-btn w3-theme-l2">Order verwijderen</a></div>
					<div class="tableCell" style="width: 20%;"><a href="index.php?action=serviceOrders&subaction=5&uid='.$cid.'&soid='.$row['id'].'" class="w3-btn w3-theme-l2">Order definitief maken</a></div>
				</div>';
		$orderscount ++;
	}
	if($orderscount == 0){
		$return .= '</div><div class="w3-container">Deze klant heeft geen openstaande service orders.</div><br><div class="w3-container"><a href="index.php?action=serviceOrders&uid='.$cid.'&newOrder=1" class="w3-btn w3-theme-l2">Maak nieuwe service order</a></div><br>';
	} else {
		$return .= '</div><br><div class="w3-container"><a href="index.php?action=serviceOrders&uid='.$cid.'&newOrder=1" class="w3-btn w3-theme-l2">Maak nieuwe service order</a></div><br>';
	}
	
	return $return;
}
if($Settings->_get('allowServiceCenter') != 0){
if($User->isAdmin() OR $User->hasServiceRights()){
	switch($subaction){
	
		case 1:
			$Page->changePageTitle('Resultaten klant zoeken voor service');
			$Page->addToBody(searchUser('service'));
			break;
	
		case 2:
			$Page->changePageTitle('Hoofdscherm service voor geselecteerde klant.');
			$Page->addToBody(serviceStartScreen(NULL));
			break;
	
		case 3:
			$Page->changePageTitle('Hoofdscherm service voor geselecteerde klant.');
			$Page->addToBody(makeOrEditServiceEpisode());
			break;
				
		case 4:
			$Page->changePageTitle('Maak of bewerk een episode.');
			$Page->addToBody(showServiceEpisode(NULL));
			break;
	
		case 5:
			$Page->changePageTitle('Maak of bewerk een episode.');
			$Page->addToBody(makeOrEditServiceCall());
			break;
	
		case 6:
			$Page->changePageTitle('Maak of bewerk een inname.');
			$Page->addToBody(innameForm());
			break;
	
		case 7:
			$Page->changePageTitle('Maak of bewerk een inname.');
			$Page->addToBody(addOrEditInname());
			break;
	
		case 8:
			$Page->changePageTitle('Retourneer een inname.');
			$Page->addToBody(closeInname());
			break;
	
		case 9:
			$Page->changePageTitle('Sluit episode.');
			$Page->addToBody(closeEpisode());
			break;
	
		case 10:
			$Page->changePageTitle('Mijn episodes');
			$Page->addToBody(showMyEpisodes());
			break;
	
		case 11:
			$Page->changePageTitle('Mijn innames');
			$Page->addToBody(showMyInnames());
			break;
	
		default:
			$Page->changePageTitle('Klanten zoeken voor service');
			$Page->addToBody(searchUserForm('service'));
			break;
	}
} else {
	$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>U bezit niet de juiste rechten voor deze actie, mogelijk bent u niet ingelogd.</p></div><br>");
}
}elseif($Settings->_get('allowServiceOrders') != 0){
	if($User->isAdmin() OR $User->hasServiceRights()){
		switch($subaction){
	
			case 1:
				$Page->changePageTitle('Resultaten klant zoeken voor service');
				$Page->addToBody(searchUser('service'));
				break;
	
			case 2:
				$Page->changePageTitle('Hoofdscherm service voor geselecteerde klant.');
				$Page->addToBody(limitedServiceStartScreen());
				break;
	
			default:
				$Page->changePageTitle('Klanten zoeken voor service');
				$Page->addToBody(searchUserForm('service'));
				break;
		}
	} else {
		$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>U bezit niet de juiste rechten voor deze actie, mogelijk bent u niet ingelogd.</p></div><br>");
	}
} else {
	$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Het Service Center en en Service Orders zijn uigeschakeld.</p></div><br>");
}


?>