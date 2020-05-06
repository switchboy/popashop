<?php 
function determineOpenField($screen){
	switch ($screen){
		case 1:
			$openScreen = 'eigenschappen';
			break;
		case 2:
			$openScreen = 'management';
			break;
		case 3:
			$openScreen = 'afbeeldingen';
			break;
		default:
			$openScreen = 'eigenschappen';
			$screen = '1';
			break;
	}
	return tabsJavaCode($openScreen, $screen);
}

function createPropertiesField($id){
	global $PDO;
	$resultDisc = $PDO->prepare("SELECT * FROM productdetails WHERE productId = :id");
	$resultDisc->execute(array(':id' => $id));
	$return = "<div id='eigenschappen' class='tab'><div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Product eigenschappen</h2></div>";
	$eigenschappen = 0;
	foreach ($resultDisc as $rowDisc){
		$return = $return."	<form action='index.php?action=manageProduct&pId=".$id."&disc=2&discId=".$rowDisc['id']."' method='post' class='w3-container'>
									<p><label>Eigenschap</label><input class='w3-input w3-border' type='text' name='naam' value='".$rowDisc['naam']."'></p>
									<p><label>Waarde</label><textarea class='w3-input w3-border' type='text' name='waarde' rows='4'>".$rowDisc['waarde']."</textarea></p>
									<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'> <a class='w3-btn w3-theme-l2' href='index.php?action=manageProduct&pId=".$id."&disc=3&discId=".$rowDisc['id']."'>Eigenschap verwijderen</a></p>
									</form>";
		$eigenschappen++;
	}
	if($eigenschappen == 0){
		$return = $return."<p>Dit product heeft nog geen beschrijving</p>";
	}
	$return =$return."<div class='w3-container w3-theme-l2'><h3>Nieuwe eigenschap:</h3></div>
					<form action='index.php?action=manageProduct&pId=".$id."&disc=1' method='post' class='w3-container'>
					<p><label>Eigenschap</label><input class='w3-input w3-border' type='text' name='naam' value=''></p>
					<p><label>Waarde</label><textarea class='w3-input w3-border' type='text' name='waarde' rows='4'></textarea></p>
					<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Eigenschappen toevoegen'></p>
					</form>
					</div>
					</div>";
	return $return;
}

function createPictureField($id){
	global $PDO;
	$resultPlaatjes = $PDO->prepare("SELECT * FROM productplaatjes WHERE productId = :id");
	$resultPlaatjes->execute(array(':id' => $id));
	$return = "<div id='afbeeldingen' class='tab'><div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Product afbeeldingen</h2></div><form action='index.php?action=manageProduct&pId=".$id."&Pic=1' method='post' class='w3-container' enctype='multipart/form-data'>";
	$afbeeldingen = 0;
	foreach($resultPlaatjes as $rowPlaatjes){
		$return = $return."<p><img src='".$rowPlaatjes['url']."' alt='productplaatje' height='100px' width='100px'> <a href='index.php?action=manageProduct&pId=".$id."&Pic=2&picId=".$rowPlaatjes['id']."' class='w3-btn w3-theme-l2'>afbeelding verwijderen</a>";
		$afbeeldingen++;
	}
	if($afbeeldingen == 0){
		$return = $return."<p>Dit product heeft nog geen afbeeldingen</p>";
	}
	if($afbeeldingen < 4){
		$return =$return."<div class='w3-container w3-theme-l2'><h3>Plaatje toevoegen:</h3></div>
						<p><label>Kies plaatje</label><input class='w3-input w3-border' type='file' name='fileToUpload' value=''></p>
						<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Afbeelding uploaden'></p>";
	} else {
		$return =$return."<p>Dit product heeft het maximale aantal afbeeldingen</p>";
	}
	$return =$return."
					</form>
					</div>
					</div>";
	return $return;
}

function addOrRemoveImage($pId, $Pic, $picId){
	global $PDO;
	global $ErrorHandler;
	$error = FALSE;
	if($pId == ''){
			$error = TRUE;
			$errormsg = $errormsg."Geen product code<br>";
	} else {
		$resultpId = $PDO->prepare("SELECT * from producten WHERE id = :id");
		$resultpId->execute(array('id' => $pId));
		$testrow = $resultpId->fetch();
		if($testrow['id'] == NULL){
			$error = TRUE;
			$errormsg = $errormsg."De productcode is ongeldig.<br>";
		}
	}
	if($Pic == 1){
		if(!$error){
			$imageLocation = uploadImage('productImages/', FALSE);
			if(!$imageLocation){
				//Het uploaden is misgegaan d'oh!
				return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$ErrorHandler->getErrorMessages()."</p></div><br>"
				.productField($pId, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 3, NULL);
			} else {
				$insert = $PDO->prepare("INSERT INTO productplaatjes (productId, url) VALUES (:id, :url)");
				$insert->execute(array(':id' => $pId, ':url' => $imageLocation));				
				return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het plaatje is toegevoed.</p></div><br>"
						.productField($pId, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 3, NULL);
			}
		} else{
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>"
			.productField($pId, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 3);
		}
	} else if($Pic == 2){
		//remove pic
		$errormsg = FALSE;
		if($picId == ''){
			$error = TRUE;
			$errormsg = $errormsg."Geen afbeeldingscode<br>";
		} else {
			$resultpicId = $PDO->prepare("SELECT * FROM productplaatjes WHERE id = :id");
			$resultpicId->execute(array('id' => $picId));
			$row = $resultpicId->fetch();
			if($row['id'] == NULL){
				$errormsg = $errormsg."De afbeeldingscode is ongeldig.<br>";
				$error = TRUE;
			}
		}
		if(!$error){
			//verwijder het plaatje van de server en de database
			unlink($row['url']);
			$delete = $PDO->prepare("DELETE FROM productplaatjes WHERE id = :id");
			$delete->execute(array('id' => $picId));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het plaatje is verwijderd.</p></div><br>"
					.productField($pId, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 3, NULL);
		} else {
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>"
					.productField($pId, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 3, NULL);
		}
		
	}
}

function btwTariefDropdownList($btwtarief){
	if($btwtarief == '' OR !($btwtarief == '6' OR $btwtarief == '21')){
		$btwtarief = '21';
	}
	return "<select class='w3-input w3-border' name='btwtarief'><option value='$btwtarief'>$btwtarief</option><option value='6'>6</option><option value='21'>21</option></select>";
	
}


function createProductField($row, $checked, $isNew){
	if($isNew){
		$add = 'Product toevoegen';
	} else {
		$add = 'Product aanpassen';
	}
	return 
		"<p><label>Productnaam</label><input class='w3-input w3-border' type='text' name='productNaam' value='".$row['productNaam']."'></p>
		<p><label>Categorie<label>".getCatDropdownList($row['categorieId'])."</p>
		<p><label>Product prijs in euro's (incl BTW) </label><input class='w3-input w3-border' type='number' name='prijs' value='".$row['prijs']."' step='0.01'></p>
		<p><label>BTW tarief:</label>".btwTariefDropdownList($row['btwtarief'])."</p>
		<p><label>Status leverancier (Wordt op de website weergegeven indien uw voorraad is uitgeput)</label>".voorraadLeverancierDropdownList($row['voorraad_leverancier'])."</p>
		<p><label>Externe bestelling (optioneel veld indien u dit product bij de levernacier besteld heeft en binnenkort verwacht)</label><input class='w3-input w3-border' type='number' name='externBesteld' value='".$row['externBesteld']."'></p>
		<p><input class='w3-check' name='actief' type='checkbox' value='1' $checked><label class='w3-validate'>Product is actief (dit houd in dat het product door klanten vindbaar is en besteld kan worden)</label></p>
		<div class='w3-container w3-theme-l2'>
			<h3>Geavanceerde opties</h3>
			<p>Gelieve deze waardes niet zomaar aan te passen gezien dit de voorraad tracking in de war kan maken. Indien u een nieuw product toevoegd volstaat het deze waarden leeg te laten of op 0 te zetten.</p>
		</div>
		<p><label>Eigen voorraad</label><input class='w3-input w3-border' type='number' name='voorraad' value='".$row['voorraad']."'></p>
		<p><label>Gereserveerd voor betaalde bestellingen uit eigen voorraad (De bestelling moet nog geraapt worden, al wel van voorraad af)</label><input class='w3-input w3-border' type='number' name='gereserveerd' value='".$row['gereserveerd']."'></p>
		<p><label>Aantal door klanten in bestelling vanwege niet toereikende voorraad</label><input class='w3-input w3-border' type='number' name='uitstaandeBestelling' value='".$row['uitstaandeBestelling']."'></p>
		<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='$add'></p>
	
		</form>
		</div>
		</div>";
}

function productField($id, $productNaam, $prijs, $voorraad, $gereserveerd, $externBesteld, $uitstaandeBestelling, $categorieId, $voorraad_leverancier, $actief, $isNew, $screen, $btwtarief){
	global $PDO;
	global $Settings;
	$checked;
	$errormessage = FALSE;
	$return = FALSE;
	if($isNew){
		if($Settings->_get('productLimit') != '0'){
			if($row['actief'] != '1'){
				$resultActiveProducts = $PDO->query("SELECT COUNT(*) FROM producten WHERE actief = '1'");
				$numberOfProducts = $resultActiveProducts->fetchColumn();
				if($numberOfProducts >= $Settings->_get('productLimit')){
					$errormessage = "<div class=\"w3-container w3-yellow\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Let op!</h3><p>Het maximale aantal actieve producten is bereikt.<br>Indien u een product wilt toevoegen dient u eerst een ander product te deactiveren. Of u kunt uw Shoppakket wijzigen zodat u meer producten tegelijk actief kunt hebben.</p></div><br>";
				}
			}
		}
		$return = $errormessage."<div><div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Nieuw product toevoegen</h2></div><form action='index.php?action=manageProduct&addNew=1' method='post' class='w3-container'>"; 
		$row = NULL;
	} else {
		$result = $PDO->prepare("SELECT * FROM producten WHERE id = :id");
		$result->execute(array(':id' => $id));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$return = "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Dit product bestaat niet.</p></div><br>".search(0);
			return $return;
		} else {
			$return = $return.
				createNavBar(array('eigenschappen' => 'Product eigenschappen', 'management' => 'Product management', 'afbeeldingen' => 'Product afbeeldingen')).
				createPropertiesField($id).
				createPictureField($id).
				"<div id='management' class='tab'><div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Product wijzigen</h2></div><form action='index.php?action=manageProduct&edit=1&pId=$id' method='post' class='w3-container'>";
			
		}
	}
	//Zet de doorgegeven waarden in $row
	$inputRow = array(
			'productNaam' => $productNaam, 
			'prijs' => $prijs, 
			'voorraad' => $voorraad, 
			'gereserveerd' => $gereserveerd, 
			'externBesteld' => $externBesteld, 
			'uitstaandeBestelling' => $uitstaandeBestelling, 
			'categorieId' => $categorieId, 
			'voorraad_leverancier' => $voorraad_leverancier, 
			'actief' => $actief,
			'btwtarief' => $btwtarief
	);
	foreach($inputRow as $naam => $waarde){
		if($waarde != ''){
			$row[$naam] = $waarde;
		}
	}
	
	//Kijk of de checkbox aan of uit moet staan
	if(isset($row['actief']) && $row['actief'] == 1){ 
		$checked = 'checked'; 
	} else {
		$checked = NULL;
	}
	
	//maak het veld en sluit.
	return $return.createProductField($row, $checked, $isNew).determineOpenField($screen);
}

function getCatDropdownList($id){
	global $PDO;
	if($id == '0'){
		$naam = 'Prullenbak';
	} else if($id != NULL){
		$resultCurrent = $PDO->prepare("SELECT * FROM categorie WHERE id = :id AND subfromcatid is NOT NULL");
		$resultCurrent->execute(array(':id' => $id));
		$row = $resultCurrent->fetch();
		if($row['id'] != NULL){
			$naam = $row['naam'];
		} else {
			$id = "' disabled selected '";
			$naam = 'kies een categorie';
		}
	} else {
		$id = "' disabled selected '";
		$naam = 'kies een categorie';
	}
	$return = "<select class='w3-select w3-border' name='categorieId'>
			<option value='$id'>$naam</option><option value='0'>Prullenbak</value>";

	return $return.createCatOptionDropDownList(NULL)."<select>";
}

function voorraadLeverancierDropdownList($voorraad_leverancier){

	switch($voorraad_leverancier){
		case '1':
			$textCurrent = "op voorraad leverancier";
			break;
		
		case 2:
			$textCurrent = "Leverbaar binnen een week";
			break;
				
		case 3:
			$textCurrent = "Leverbaar binnen drie weken";
			break;
		
		case 4:
			$textCurrent = "Leverbaar 1-2 maanden";
			break;
		
		default:
			$textCurrent = "Levertijd onbekend";
			break;
	}
	return "<select class='w3-select w3-border' name='voorraad_leverancier'>
			<option value='$voorraad_leverancier'>$textCurrent</option>
			<option value='1'>Direct leverbaar leverancier</option>
			<option value='2'>Leverbaar binnen een week</option>
			<option value='3'>Leverbaar binnen drie weken</option>
			<option value='4'>Leverbaar 1-2 maanden</option>
			<option value='0'>Levertijd onbekend</option>
			</select>";
}

function editProductProperties($disc, $pId, $id, $waarde, $naam){
	global $PDO;
	$error = FALSE;
	if($naam == '' AND $disc != 3){
		$error = TRUE;
		$errormessage = $errormessage."Geen eigenschapnaam ingevuld.<br>";
	}
	if($waarde == '' AND $disc != 3){
		$error = TRUE;
		$errormessage = $errormessage."Geen eigenschapwaarde ingevuld.<br>";
	}
	if($pId == ''){
		$error = TRUE;
		$errormessage = $errormessage."Geen productcode meegestuurd.<br>";
	}
	if($id == '' && $disc == 2){
		$error = TRUE;
		$errormessage = $errormessage."Geen eigenschap id meegestuurd.<br>";
	}
	if($disc == 1){
		//nieuwe eigenschap
		$query = $PDO->prepare("INSERT INTO productdetails (naam, waarde, productId) VALUES (:naam, :waarde, :productId)");
		$array = array(':naam' => $naam, ':waarde' => $waarde, ':productId' => $pId);
	} else if($disc == 2){
		$query = $PDO->prepare("UPDATE productdetails SET naam = :naam, waarde = :waarde WHERE id=:id AND productId = :productId");
		$array = array(':naam' => $naam, ':waarde' => $waarde, ':id' => $id, ':productId' => $pId);
	}else if($disc == 3){
		$query = $PDO->prepare("DELETE FROM productdetails WHERE id=:id AND productId = :productId");
		$array = array(':id' => $id, ':productId' => $pId);
	}else {
		$error = TRUE;
		$errormessage = $errormessage."Actie niet besschikbaar.<br>";
	}
	if(!$error){
		$query->execute($array);
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De producteigenschappen zijn aangepast.</p></div><br>".productField($pId, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 1, NULL);
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormessage</p></div><br>".productField($pId, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 1, NULL);
	}
}


function addOrEditProduct($id, $productNaam, $prijs, $voorraad, $gereserveerd, $externBesteld, $uitstaandeBestelling, $categorieId, $voorraad_leverancier, $actief, $edit, $btwtarief){
	global $PDO;
	global $Settings;
	//sanity check on variables and cleanup
	$error = FALSE;
	if($id == '' AND $edit){
		$error = TRUE;
		$errormessage = $errormessage."Geen productid megestuurd voor een edit.<br>";
	} else if($edit){
		$result = $PDO->prepare("SELECT * FROM producten WHERE id = :id");
		$result->execute(array(':id' => $id));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$error == NULL;
			$errormessage = "Het product wat u probeerde aan te passen bestaat niet.<br>";
		}
	} else {
		$row['active'] == 0;
	}
	if(!isset($productNaam) OR $productNaam == ''){
		$error = TRUE;
		$errormessage = $errormessage."Geen productnaam ingevoerd.<br>";
	} else if(!ctype_alnum(str_replace(array(' ', "'", '-'), '',$productNaam))){
		$error = TRUE;
		$errormessage = $errormessage."Een naam van een product mag allen alfanumerieke karakters, spaties ' en - bevatten.<br>";
	}
	
	if(!isset($prijs) OR $prijs == ''){
		echo "De prijs is: ".$prijs;
		if($prijs != '0'){
			$error = TRUE;
			$errormessage = $errormessage."Geen productprijs opgegeven.<br>";
		}
	} elseif(!is_numeric($prijs)){
		$error = TRUE;
		$errormessage = $errormessage."De prijs mag alleen uit cijfers bestaan. bijvoorbeeld: 52,36 voor &euro; 52,36<br>";
	}
	
	if(!isset($categorieId) or $categorieId == ''){
		$error = TRUE;
		$errormessage = $errormessage."Een product moet gekoppeld worden aan een categorie. Anders is deze niet vindbaar.<br>";
	}
	
	if(!isset($voorraad_leverancier) OR $voorraad_leverancier == ''){
		$voorraad_leverancier = 0;
	}
	
	if(!isset($voorraad) OR $voorraad == ''){
		$voorraad = 0;
	}
	
	if(!isset($gereserveerd) OR $gereserveerd == ''){
		$gereserveerd = 0;
	}
	
	if(!isset($externBesteld) OR $externBesteld ==''){
		$externBesteld = 0;
	}
	
	if(!isset($btwtarief) OR $btwtarief =='' OR !($btwtarief == '6' OR $btwtarief ==  '21')){
		$error = TRUE;
		$errormessage = 'Geen geldig BTW tarief opgegeven.<br>';
	}
	
	if(!isset($uitstaandeBestelling) OR $uitstaandeBestelling ==''){
		$uitstaandeBestelling = 0;
	}
	if($actief != 1){
		$actief = 0;
	} else {
		if($Settings->_get('productLimit') != '0'){
			if($actief == '1' && !$edit){
				$resultActiveProducts = $PDO->query("SELECT COUNT(*) FROM producten WHERE actief = '1'");
				$numberOfProducts = $resultActiveProducts->fetchColumn();
				if($numberOfProducts >= $Settings->_get('productLimit')){
					$error = TRUE;
					$errormessage = 'Het maximale aantal actieve producten is bereikt.<br>';
				}
			}
		}
		
	}
	if(!$error){
		// alles is in orde tijd voor een insert
		if(!$edit){
			$insert = $PDO->prepare("INSERT INTO producten (productNaam, prijs, voorraad, gereserveerd, externBesteld, uitstaandeBestelling, categorieId, voorraad_leverancier, actief, btwtarief)
				VALUES(:productNaam, :prijs, :voorraad, :gereserveerd, :externBesteld, :uitstaandeBestelling, :categorieId, :voorraad_leverancier, :actief, :btwtarief)");
			$insert->execute(array(':productNaam' => $productNaam, ':prijs' => $prijs, ':voorraad' => $voorraad, ':gereserveerd' => $gereserveerd, ':externBesteld' => $externBesteld, ':uitstaandeBestelling' => $uitstaandeBestelling, ':categorieId' => $categorieId, ':voorraad_leverancier' => $voorraad_leverancier, ':actief' => $actief, ':btwtarief' => $btwtarief));
			$id = $PDO->lastInsertId();
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het product is aangemaakt.</p></div><br>".productField($id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, NULL, NULL);
		} else {
			$update = $PDO->prepare("UPDATE producten SET productNaam = :productNaam, prijs = :prijs, voorraad = :voorraad, gereserveerd = :gereserveerd, externBesteld = :externBesteld,
					uitstaandeBestelling = :uitstaandeBestelling, categorieId = :categorieId, voorraad_leverancier = :voorraad_leverancier, actief = :actief, btwtarief = :btwtarief WHERE id = :id");
			$update->execute(array(':productNaam' => $productNaam, ':prijs' => $prijs, ':voorraad' => $voorraad, ':gereserveerd' => $gereserveerd, ':externBesteld' => $externBesteld,
					':uitstaandeBestelling' => $uitstaandeBestelling, ':categorieId' => $categorieId, ':voorraad_leverancier' => $voorraad_leverancier, ':actief' => $actief, ':btwtarief' => $btwtarief, ':id' => $id));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Productdetails aangepast.</p></div><br>".productField($id, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, 2, NULL);
		}
		
	} else {
		if(!$edit){
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormessage</p></div><br>"
			.productField(NULL, $productNaam, $prijs, $voorraad, $gereserveerd, $externBesteld, $uitstaandeBestelling, $categorieId, $voorraad_leverancier, $actief, TRUE, 2, $btwtarief);
		} else {
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormessage</p></div><br>"
			.productField(NULL, $productNaam, $prijs, $voorraad, $gereserveerd, $externBesteld, $uitstaandeBestelling, $categorieId, $voorraad_leverancier, $actief, FALSE, 2, $btwtarief);
		}
	}
}



if($User->isAdmin() or $User->canEditProducts()){
	if(!isset($_GET['scr'])){
		$screen = NULL;
	}else {
		$screen = $_GET['scr'];
	}
	if(isset($_GET['disc'])){
		$disc = $_GET['disc'];
	} else {
		$disc = NULL;
	}
	if(isset($_GET['discId'])){
		$discId = $_GET['discId'];
	} else {
		$discId= NULL;
	}
	if(isset($_GET['picId'])){
		$picId = $_GET['picId'];
	} else {
		$picId = NULL;
	}
	if(!isset($_GET['pId']) OR $_GET['pId'] == NULL){
		if(!isset($_GET['addNew']) OR $_GET['addNew'] == NULL){
			$Page->addToBody(productField(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, TRUE, NULL, NULL));
		} else {
			//nieuw product toevoegen
			$Page->addToBody(
			addOrEditProduct(NULL, $_POST['productNaam'], (float)$_POST['prijs'], $_POST['voorraad'], $_POST['gereserveerd'], $_POST['externBesteld'], $_POST['uitstaandeBestelling'], $_POST['categorieId'], $_POST['voorraad_leverancier'], $_POST['actief'], FALSE, $_POST['btwtarief']));
		}
	} else {
		if(!isset($_GET['edit']) OR $_GET['edit'] == NULL){
			if(!isset($_GET['disc']) OR $_GET['disc'] == NULL){
				if(!isset($_GET['Pic']) OR $_GET['Pic'] == NULL){
					$Page->addToBody(productField($_GET['pId'], NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, $screen, NULL));
				} else {
					//Upload of verwijder plaatje en voeg aan database toe of haal hem eruit.
					$Page->addToBody(addOrRemoveImage($_GET['pId'], $_GET['Pic'], $picId));
				}
			} else {
				//pas eigenschappen van product aan	
				$Page->addToBody(editProductProperties($disc, $_GET['pId'], $discId, $_POST['waarde'], $_POST['naam']));
			}
		}else {
			//pas wijzegingen toe
			$Page->addToBody(addOrEditProduct($_GET['pId'], $_POST['productNaam'], $_POST['prijs'], $_POST['voorraad'], $_POST['gereserveerd'], $_POST['externBesteld'], $_POST['uitstaandeBestelling'], $_POST['categorieId'], $_POST['voorraad_leverancier'], $_POST['actief'], TRUE, $_POST['btwtarief']));
		}
	}
} else {
	$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>U bezit niet de juiste rechten voor deze actie, mogelijk bent u niet ingelogd.</p></div><br>");
}

?>