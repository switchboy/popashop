
<?php
function determineOpenField($screen){
	switch ($screen){
		case 1:
			$openScreen = 'coupons';
			break;
		case 2:
			$openScreen = 'promoties';
			break;
		case 3:
			$openScreen = 'banner';
			break;
		default:
			$openScreen = 'promoties';
			$screen = '2';
			break;
	}
	return tabsJavaCode($openScreen, $screen);
}

function addOrEditCoupon(){
	global $PDO;
	$edit = FALSE;
	$errormsg = FALSE;
	if(isset($_POST['couponCode']) AND $_POST['couponCode'] != ''){
		if(strlen($_POST['couponCode']) > 20){
			$errormsg = $errormsg."Deze kortinscoupon code is te lang (maximaal 20 tekens).";
		}
	} else {
		$errormsg = $errormsg."Geen kortingscoupon code ingevuld.";
	}
	if(isset($_POST['value']) AND $_POST['value'] != NULL){
		if(!is_numeric($_POST['value'])){
			$errormsg = $errormsg."Waarde moet numeriek zijn.";
		}
	} else {
		$errormsg = $errormsg."Geen waarde ingevuld.";
	}
	if(isset($_POST['type']) AND $_POST['type'] != NULL){
		if(!($_POST['type'] == '0' OR $_POST['type'] == '1')){
			$errormsg = $errormsg."Geen geldig type geselecteerd.";
		}
	} else {
		$errormsg = $errormsg."Geen kortingstype geselecteerd.";
	}
	if(isset($_GET['id']) AND $_GET['id'] != '' AND is_numeric($_GET['id'])){
		$edit = TRUE;
		$result = $PDO->prepare("SELECT * FROM discountcoupons WHERE id = :id");
		$result->execute(array(':id' => $_GET['id']));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg = $errormsg."Deze kortinscoupon bestaat niet.";
		}
	} else {
		$resultCode = $PDO->prepare("SELECT * FROM discountcoupons WHERE couponCode = :couponCode");
		$resultCode->execute(array(':couponCode' => $_POST['couponCode']));
		$rowCode = $resultCode->fetch();
		if($rowCode['id'] != NULL){
			$errormsg = $errormsg."Deze kortinscoupon code bestaat al, kies een andere.";
		}
	}
	if(isset($_POST['startTime']) AND $_POST['startTime'] != NULL){
		$startTime = strtotime($_POST['startTime']);
	} else {
		$errormsg = $errormsg."Geen start datum ingevuld.";
	}
	if(isset($_POST['stopTime']) AND $_POST['stopTime'] != NULL){
		$stopTime = strtotime($_POST['stopTime']);
		if($stopTime <= $startTime){
			$errormsg = $errormsg."De stop datum mag niet voor of op de startdatum liggen.";
		}
	} else {
		$errormsg = $errormsg."Geen stop datum ingevuld.";
	}
	if(!$errormsg){
		if($edit){
			$edit = $PDO->prepare("UPDATE discountcoupons SET couponCode = :couponCode, value = :value, type = :type, startTime = :startTime, stopTime = :stopTime WHERE id = :id");
			$edit->execute(array(':couponCode' => $_POST['couponCode'], ':value' => $_POST['value'], ':type' => $_POST['type'], ':startTime' => $startTime, ':stopTime' => $stopTime ,':id' => $_GET['id']));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De coupon is aangepast!</p></div><br>".
					showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(1);
		} else {
			$insert = $PDO->prepare("INSERT INTO `discountcoupons` (couponCode, startTime, stopTime, type, value) VALUES(:couponCode, :startTime, :stopTime, :type, :value)");
			$insert->execute(array(':couponCode' => $_POST['couponCode'], ':value' => $_POST['value'], ':type' => $_POST['type'], ':startTime' => $startTime, ':stopTime' => $stopTime));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De coupon is aangemaakt!</p></div><br>".
					showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(1);
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".
				showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(1);
	}
}

function showCouponsForm(){
	global $PDO;
	$result = $PDO->query("SELECT * FROM discountcoupons");
	$resultStart = "<div id='coupons' class='tab'><div class='w3-card-4'>";
	$resultActief = "<div class='w3-container w3-theme-l2'><h2>Actieve coupons</h2></div>";
	$resultNietActief = "<div class='w3-container w3-theme-l2'><h2>Verlopen coupons</h2></div>";
	$newFrom = "<div class='w3-container w3-theme-l2'><h2>Maak een nieuwe coupon</h2></div><form action='index.php?action=doPromotions&subaction=1' method='post' class='w3-container'>
		<p><label>Couponcode mag maximaal 20 karakters lang zijn.</label><input class='w3-input w3-border' type='text' name='couponCode' value=''></p>
		<p><label>Waarde of percentage korting:</label><input class='w3-input w3-border' type='number' name='value' value=''></p>
		<p><label>type actie</label><select name='type' class='w3-input w3-border'>
			<option disabled selected>Selecteer een type...</option>
			<option value='0'>Een vast bedrag</option>
			<option value='1'>Een percentage</option>
		</select>
		<p><label>Deze actie start op:</label><input class='w3-input w3-border' type='date' name='startTime' value=''></p>
		<p><label>Deze actie eindigd op:</label><input class='w3-input w3-border' type='date' name='stopTime' value=''></p>
		<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'></p>
		</form>";
	$tabEnd = "</div></div>";
	foreach($result as $row){
		if($row['type'] == 0){$type = 'Een vast bedrag';}else{$type = 'Een percentage';}
		$form = "<form action='index.php?action=doPromotions&subaction=1&id=".$row['id']."' method='post' class='w3-container'>
		<p><label>Couponcode mag maximaal 20 karakters lang zijn.</label><input class='w3-input w3-border' type='text' name='couponCode' value='".$row['couponCode']."'></p>
		<p><label>Waarde of percentage korting:</label><input class='w3-input w3-border' type='number' name='value' value='".$row['value']."'></p>
		<p><label>type actie</label><select name='type' class='w3-input w3-border'>
			<option value='".$row['type']."'>$type</option>
			<option value='0'>Een vast bedrag</option>
			<option value='1'>Een percentage</option>
			</select>
			<p><label>Deze actie start op:</label><input class='w3-input w3-border' type='date' name='startTime' value='".date('Y-m-d', $row['startTime'])."'></p>
		<p><label>Deze actie eindigd op:</label><input class='w3-input w3-border' type='date' name='stopTime' value='".date('Y-m-d', $row['stopTime'])."'></p>
		<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'></p>
		</form>";
		if(time() < $row['stopTime'] && time() >$row['startTime']){
			$resultActief = $resultActief.$form;
		} else {
			$resultNietActief = $resultNietActief.$form;
		}
	}
	return $resultStart.$resultActief.$resultNietActief.$newFrom.$tabEnd;
}

function addOrEditCatPromo(){
	global $PDO;
	$edit = FALSE;
	$errormsg = FALSE;
	if(!isset($_POST['productid']) OR $_POST['productid'] == ''){
		$errormsg = $errormsg."Geen product gekozen.";
	}
	if(!isset($_GET['catId']) OR $_GET['catId'] == ''){
		$errormsg = $errormsg."Geen categorie gekozen";
	} else {
		$resultPromo = $PDO->prepare("SELECT COUNT(*) FROM promotion WHERE catid = :id");
		$resultPromo->execute(array(':id' => $_GET['catId']));
		$promoCount = $resultPromo->fetchColumn();
		if(!isset($_GET['cid']) OR $_GET['cid'] == ''){
			if($_GET['catId'] == '0'){
				if($promoCount >= 8){
					$errormsg = $errormsg."Maximale aantal promoties reeds bereikt.";
				}
			} elseif ($promoCount >= 4){
				$errormsg = $errormsg."Maximale aantal promoties reeds bereikt.";
			}
		} else {
			$resultPromoId = $PDO->prepare("SELECT * FROM promotion WHERE id = :id");
			$resultPromoId->execute(array(':id' => $_GET['cid']));
			$rowId = $resultPromoId->fetch();
			if($rowId['id'] == NULL){
				$errormsg = $errormsg."Deze promotie bestaat niet.";
			}
			$edit = TRUE;
		}
	}
	if(!$errormsg){
		if($edit){
			$update = $PDO->prepare("UPDATE promotion SET catid = :catid , productid = :productid, active = 1 WHERE id = :id");
			$update->execute(array(':catid' => $_GET['catId'], ':productid' => $_POST['productid'], ':id' => $_GET['cid']));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Promo aangepast.</p></div><br>".
					showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(2);
		} else {
			$insert = $PDO->prepare("INSERT INTO promotion (catid, productid, active) values(:catid, :productid, 1) ");
			$insert->execute(array(':catid' => $_GET['catId'], ':productid' => $_POST['productid']));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Promo aangemaakt.</p></div><br>".
					showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(2);
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".
				showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(2);
	}
}

function getProductOptionList($id){
	global $PDO;
	if($id == 0){
		$result = $PDO->query("SELECT * FROM producten WHERE actief = 1");
	} else {
		$query = "SELECT * FROM producten WHERE categorieId = :id";
		$queryArray = array(':id' => $id);
		$resultCat = $PDO->prepare("SELECT * FROM categorie WHERE subfromcatid = :id");
		$resultCat->execute(array(':id' => $id));
		foreach ($resultCat as $rowCat){
			$query = $query." OR categorieId = '".$rowCat['id']."'"; //<-ToDo: Fix potentialy dangerous hack (non prepared statement)
		}
		$query = $query." ORDER BY productNaam";
		$result = $PDO->prepare($query);
		$result->execute($queryArray);
	}
	$return ='';
	foreach ($result as $row){
		$return = $return."<option value='".$row['id']."'>".$row['productNaam']."</option>";
	}
	return $return;
}

function deleteCatPromo(){
	global $PDO;
	$errormsg = FALSE;
	if(isset($_GET['cid']) AND $_GET['cid'] != ''){
		$id = $_GET['cid']; 
	} else {
		$errormsg = $errormsg."Geen promotie geselecteerd.";
	}
	if(!$errormsg){
		if(isset($_GET['zeker']) AND $_GET['zeker'] == 1){
			$delete = $PDO->prepare("DELETE FROM promotion WHERE id = :id");
			$delete->execute(array(':id' => $id));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Promotie verwijderd.</p></div><br>".
					showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(2);
		} else {
			return "<div class=\"w3-container w3-yellow\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Let op!</h3><p>U staat op het punt een promotie te verwijderen weet u dit zeker? <a href='index.php?action=doPromotions&subaction=3&catId=".$_GET['catId']."&cid=$id&zeker=1'>Ja</a></p></div><br>".
					showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(2);
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".
				showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(2);
	}
}

function showCatPromotionsForm(){
	global $PDO;
	$error = '';
	$return = "<div id='promoties' class='tab'><div class='w3-card-4'>
			<div class='w3-container w3-theme-l2'><h2>Selecteer een een categorie</h2></div>
				<form class='w3-container'>
				<input type=hidden name='action' value='doPromotions'></input>
				<p><select name='catId' class='w3-input w3-border' onchange='if(options[selectedIndex].value){var temp = \"index.php?action=doPromotions&catId=\";var temp1 = options[selectedIndex].value;location = temp + temp1;}'><option disabled selected>Selecteer een categorie</option>".createCatOptionDropDownList(2)."</select></p>
				</form>";
	if(isset($_GET['catId']) && $_GET['catId'] != ''){
		$id = $_GET['catId'];
		$showCatSelector = FALSE;
		$result = $PDO->prepare("SELECT * FROM categorie WHERE id = :id");
		$result->execute(array(':id' => $id));
		$row = $result->fetch();
		if($row['id'] == NULL AND $id != '0'){
			$showCatSelector = TRUE;
			$error = "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Deze cotegorie bestaat niet</p></div><br>";
		} else {
			$resultPromo = $PDO->prepare("SELECT * FROM promotion WHERE catid = :id");
			$resultPromo->execute(array(':id' => $id));
			if($id == 0){
				$return = $return."<div class='w3-container w3-theme-l2'><h2>Bestaande promoties op de hoofdpagina:</h2></div>";
			} else {
				$return = $return."<div class='w3-container w3-theme-l2'><h2>Bestaande promoties in de categorie ".$row['naam'].":</h2></div>";
			}
			$a = 0;
			foreach ($resultPromo as $rowPromo){
				$resultProduct = $PDO->prepare("SELECT productNaam FROM producten WHERE id = :id");
				$resultProduct->execute(array(':id' => $rowPromo['productid']));
				$productnaam = $resultProduct->fetchColumn();
				$return = $return
				."<form action='index.php?action=doPromotions&subaction=2&catId=".$id."&cid=".$rowPromo['id']."' method='post' class='w3-container' enctype='multipart/form-data'>
				<p><label>Product</label><select name='productid' class='w3-input w3-border'><option value='".$rowPromo['productid']."'>$productnaam</option>".getProductOptionList($id)."</select></p>
				<p><a class='w3-btn w3-theme-l2' href='index.php?action=doPromotions&subaction=3&catId=".$id."&cid=".$rowPromo['id']."'>verwijder promotie</a> <input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'></p>
				</form>";
				$a++;
			}
			if($a < 4 OR ($a < 8 AND $id == '0')){
				$return = $return."<div class='w3-container w3-theme-l2'><h2>Nieuwe promotie:</h2></div>
				<form action='index.php?action=doPromotions&subaction=2&catId=".$id."' method='post' class='w3-container' enctype='multipart/form-data'>
				<p><label>Product</label><select name='productid' class='w3-input w3-border'>".getProductOptionList($id)."</select></p>
				<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'></p>
				</form>";
			} else {
				"<div class='w3-container w3-yellow'><h2>Let op: Maximale aantal promoties voor deze categorie is bereikt:</h2>Verwijder hierboven oude promoties om ruimte te maken voor nieuwe promoties.</div>";
			}
		}
	}
	$return = $return."</div></div>";
	return $error.$return;
}

function addPromoBannerPic(){
	global $PDO;
	$errormsg = FALSE;
	if(isset($_POST['pid']) && $_POST['pid'] != ''){
		$pid = $_POST['pid'];
		$resultProduct = $PDO->prepare("SELECT * FROM producten WHERE id = :id");
		$resultProduct->execute(array(':id' => $pid));
		$rowProduct = $resultProduct->fetch();
		if($rowProduct['id'] == NULL){
			$errormsg = $errormsg."Het geselecteerde product bestaat niet.";
		}
	} else {
		$errormsg = $errormsg."Geen product aan banner gekoppeld.";
	}
	list($imgwidth, $imgheight) = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
	if($imgwidth != 980 OR $imgheight != 300){
		$errormsg = $errormsg."Een plaatje moet 980 pixels breed en 300 pixels hoog zijn.";
	}
	if(!$errormsg){ 
		$imageLocation = uploadImage('promobannerimages/', FALSE);
		if($imageLocation){
			$insert = $PDO->prepare("INSERT INTO promobanner (imageurl, pid, active) VALUES (:imageurl, :pid, 1)");
			$insert->execute(array('imageurl' => $imageLocation, ':pid' => $pid));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het plaatje is aan de promotiebanner toegevoegd.</p></div><br>".
					showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(3);
		} else {
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Er is iets misgegaan bij het uploaden van het plaatje.</p></div><br>".
					showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(3);
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".
				showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(3);
	}
}

function showBannerPromotionsForm(){
	global $PDO;
	$returnBegin = "<div id='banner' class='tab'><div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Plaatjes in huidige banner:</h2></div>";
	$returnNieuwPlaatje = "<div class='w3-container w3-theme-l2'><h2>Plaatje aan banner toevoegen</h2></div>
	<form action='index.php?action=doPromotions&subaction=4' method='post' class='w3-container' enctype='multipart/form-data'>
	<p><label>Kies de afbeelding die in de banner wilt zetten. (980*300px b*h ).</label><input class='w3-input w3-border' type='file' name='fileToUpload' value=''></p>
	<p><label>Product waar naar wordt gelinkt:</label><select class='w3-input w3-border'  name='pid'>".getProductOptionList(0)."</select></p>
	<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'></p>
	</form>";
	$returnEind = "</div></div>";
	$result = $PDO->query("SELECT * FROM promobanner");
	$return = '';
	foreach($result as $row){
		$resultProduct = $PDO->prepare("SELECT productNaam FROM producten WHERE id = :id");
		$resultProduct->execute(array(':id' => $row['pid']));
		$productnaam = $resultProduct->fetchColumn();
		$return = $return."<form action='index.php?action=doPromotions&subaction=5&id=".$row['id']."' method='POST' class='w3-container'>
				<p><label>Product waar naar wordt gelinkt</label><select name='pid' class='w3-input w3-border'><option value='".$row['pid']."'>".$productnaam."</option>".getProductOptionList(0)."</select></p>
				<p><img src='".$row['imageurl']."' width=300px;> <a href='index.php?action=doPromotions&subaction=6&id=".$row['id']."' class='w3-btn w3-theme-l2'>Plaatje verwijderen</a> <input class='w3-btn w3-theme-l2' type='submit' name='submit' value='verandering toepassen'></p>				
			</form>";
	}
	return $returnBegin.$return.$returnNieuwPlaatje.$returnEind;
}

function deletePromobannerPic(){
	global $PDO;
	$errormsg = FALSE;
	if(isset($_GET['id']) && $_GET['id'] != ''){
		$id = $_GET['id'];
		$resultBanner = $PDO->prepare("SELECT * FROM promobanner WHERE id = :id");
		$resultBanner->execute(array(':id' => $id));
		$rowBanner = $resultBanner->fetch();
		if($rowBanner['id'] == NULL){
			$errormsg = $errormsg."Dit promobanner plaatje bestaat niet.";
		}
	} else {
		$errormsg = $errormsg."Geen plaatje van de promobanner geselecteerd.";
	}
	if(!$errormsg){
		if($_GET['zeker'] == 1){
			$delete = $PDO->prepare("DELETE FROM promobanner WHERE id = :id");
			$delete->execute(array(':id' => $id));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het plaatje van de promobanner is verwijderd.</p></div><br>".
					showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(3);
		} else {
			return "<div class=\"w3-container w3-yellow\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Let op!</h3><p>U staat op het punt een promotie te verwijderen weet u dit zeker? 
					<a href='index.php?action=doPromotions&subaction=6&id=$id&zeker=1'>Ja</a></p></div><br>".
					showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(3);
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".
				showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(3);
	}
}

function editPromobannerPic(){
	global $PDO;
	$errormsg = FALSE;
	if(isset($_POST['pid']) && $_POST['pid'] != ''){
		$pid = $_POST['pid'];
		$resultProduct = $PDO->prepare("SELECT * FROM producten WHERE id = :id");
		$resultProduct->execute(array(':id' => $pid));
		$rowProduct = $resultProduct->fetch();
		if($rowProduct['id'] == NULL){
			$errormsg = $errormsg."Het geselecteerde product bestaat niet.<br>";
		}
		if($rowProduct['actief'] == 0){
			$errormsg = $errormsg."Dit product is niet actief.<br>";
		}
	} else {
		$errormsg = $errormsg."Geen product aan banner gekoppeld.<br>";
	}
	if(isset($_GET['id']) && $_GET['id'] != ''){
		$id = $_GET['id'];
		$resultBanner = $PDO->prepare("SELECT * FROM promobanner WHERE id = :id");
		$resultBanner->execute(array(':id' => $id));
		$rowBanner = $resultBanner->fetch();
		if($rowBanner['id'] == NULL){
			$errormsg = $errormsg."Dit promobanner plaatje bestaat niet.<br>";
		}
	} else {
		$errormsg = $errormsg."Geen plaatje van de promobanner geselecteerd.";
	}
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE promobanner SET pid = :pid WHERE id = :id");
		$update->execute(array(':pid' =>$pid, ':id' => $id));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het plaatje is aan de promotiebanner toegevoegd.</p></div><br>".
				showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(3);
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".
				showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField(3);
	}
}

function newsletterForm(){
	global $PDO;
	global $Page;
	$errormsg = FALSE;
	$edit = '';
	$title = 'Nieuwsbrief maken';
	$sent = '';
	if(isset($_GET['id']) && $_GET['id'] != ''){
		$result = $PDO->prepare("SELECT * FROM newsletters WHERE id = :id");
		$result->execute(array(':id' => $_GET['id']));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg = 'Dit bericht bestaat niet.<br>';
		} else {
			$edit = '&id='.$row['id'];
			$title = 'Nieuwsbrief bewerken';
			if($row['publish'] == 1){
				$sent = 'readonly : true,';
			} 
		}
	} else {
		$row = array('subject' => NULL, 'text' => NULL, 'publish' => NULL);
	}
	if(isset($_POST['title']) && $_POST['title'] != NULL){
		$row['subject'] = $_POST['title'];
	}
	if(isset($_POST['text']) && $_POST['text'] != NULL){
		$row['text'] = $_POST['text'];
	}
	if(isset($_POST['publish']) && $_POST['publish'] == 1){
		$row['publish'] = 1;
	}
	if($row['publish'] == '1'){
		$publish = 'checked';
	} else {
		$publish = '';
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
		".$sent."
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
	<form action='index.php?action=doPromotions&subaction=9$edit' method='post' class='w3-container'>
	<p><label>Onderwerp:<label><input type='text' value='".$row['subject']."' name='title' class='w3-input w3-border'></input></p>
	<p><input class='w3-check' type='checkbox' value='1' name='publish' $publish></input> <label class='w3-validate'>Nieuwsbrief verzenden</label></p>
	<textarea name='text' style='width: 100%; height: 400px'>".$row['text']."</textarea>
	</form></div>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>";
	}
}

function postOrEditNewsletter(){
	global $PDO;
	global $Page;
	global $User;
	global $Settings;
	$errormsg = FALSE;
	$edit = FALSE;
	if(isset($_GET['id']) && $_GET['id'] != ''){
		$result = $PDO->prepare("SELECT * FROM newsletters WHERE id = :id");
		$result->execute(array(':id' => $_GET['id']));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg = 'Dit bericht bestaat niet.<br>';
		}  else {
			if($row['publish'] == 1){
				$errormsg = 'Dit bericht is al verzonden.<br>';
			}
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
	if(!$errormsg){
		if($edit){
			$update = $PDO->prepare("UPDATE newsletters SET subject = :title, text = :text, publish = :publish, lasteditdate = :lasteditdate WHERE id = :id");
			$update->execute(array(':title' => $_POST['title'], ':text' => $_POST['text'], ':publish' => $publish, ':lasteditdate' => time(), ':id' => $_GET['id']));
			if($publish == 1){
				$resultemails = $PDO->query("SELECT email FROM klanten");
				foreach ($resultemails as $rowemails){
					sendEmail($rowemails['email'], 'no-reply@'.$Settings->_get('siteDomain'), $_POST['text'], $_POST['title']);
				}
				$Page->addToBody("<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Nieuwsbrief verzonden.</p></div><br>");
			}
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De nieuwsbrief is aangepast.</p></div><br>".newsletterForm();
		} else {
			$insert = $PDO->prepare("INSERT INTO newsletters (subject, text, date, publish, lasteditdate, uid) VALUES (:title, :text, :date, :publish, :lasteditdate, :uid)");
			$insert->execute(array(':title' => $_POST['title'], ':text' => $_POST['text'],':date' => time(),':publish' => $publish, ':lasteditdate' => time(), ':uid' => $User->_get('costumerId')));
			$_GET['id'] = $PDO->lastInsertId();
			if($publish == 1){
				$resultemails = $PDO->query("SELECT email FROM klanten WHERE newsletter = 1");
				foreach ($resultemails as $rowemails){
					$text = $_POST['text'].'<br><a href="index.php?action=newsletter&email='.$rowemails['email'].'">Ik wil geen nieuwsbrieven meer ontvangen</a>';
					sendEmail($rowemails['email'], 'no-reply@'.$Settings->_get('siteDomain'), $text, $_POST['title']);
				}
				$Page->addToBody("<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Nieuwsbrief verzonden.</p></div><br>");
			}
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De nieuwsbrief is aangemaakt.</p></div><br>".newsletterForm();
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>".newsletterForm();
	}
}

function newslettersOverview(){
	global $PDO;
	$result = $PDO->query("SELECT newsletters.*, klanten.username FROM newsletters LEFT JOIN klanten ON uid = klanten.id");
	$return =  "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Overzicht van nieuwsbrieven</h2></div>".'<div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 35%;">Onderwerp</div>
					<div class="tableCell">gemaakt op</div>
					<div class="tableCell">aangepast op</div>
					<div class="tableCell">geplaatst door</div>
					<div class="tableCell">verzonden</div>
					<div class="tableCell"></div>
				</div>';
	$articleCount = 0;
	foreach($result as $row){
		if($row['publish'] == 1){
			$delete = '';
		} else {
			$delete = '<a href="index.php?action=doPromotions&subaction=10&id='.$row['id'].'" class="w3-btn w3-theme-l2">verwijder</a>';
		}
		$return .= '<div class="tableRow">
					<div class="tableCell"><a href="index.php?action=doPromotions&subaction=8&id='.$row['id'].'">'.$row['subject'].'</a></div>
					<div class="tableCell">'.date('d-m-Y', $row['date']).'</div>
					<div class="tableCell">'.date('d-m-Y', $row['lasteditdate']).'</div>
					<div class="tableCell">'.$row['username'].'</div>
					<div class="tableCell">'.janee($row['publish']).'</div>
					<div class="tableCell">'.$delete.'</div>
				</div>';
		$articleCount++;
	}
	if($articleCount == 0){
		$return .= '</div><div class="w3-container">Er zijn nog geen nieuwsbrieven aangemaakt.<br><br><a href="index.php?action=doPromotions&subaction=8" class="w3-btn w3-theme-l2">Nieuwe nieuwsbrief maken</a><br><br></div>';
	} else {
		$return .= '</div><div class="w3-container"><br><br><a href="index.php?action=doPromotions&subaction=8" class="w3-btn w3-theme-l2">Nieuwe nieuwsbrief maken</a><br><br></div>';
	}
	return $return;
}

function deleteNewsletter(){
	global $PDO;
	$errormsg = FALSE;
	$zekerweten = FALSE;
	if(!isset($_GET['id']) OR $_GET['id'] == ''){
		$errormsg = 'Geen nieuwsbrief geslecteerd.<br>';
	} else {
		$result = $PDO->prepare("SELECT * FROM newsletters WHERE id = :id");
		$result->execute(array(':id' => $_GET['id']));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg = 'Deze nieuwsbrief bestaat niet.<br>';
		}
		if($row['publish'] != 0){
			$errormsg = 'Kan geen nieuwsbrieven verwijderen die reeds verzonden zijn.<br>';
		}
	}
	if(!isset($_GET['zeker']) OR $_GET['zeker'] == ''){
		$zekerweten = TRUE;
	}
	if(!$errormsg){
		if(!$zekerweten){
			$delete = $PDO->prepare("DELETE FROM newsletterss WHERE id = :id AND publish = 0");
			$delete->execute(array(':id' => $_GET['id']));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De nieuwsbrief is verwijderd.</p></div><br>".newsletterOverview();
		} else {
			return "<div class=\"w3-container w3-yellow\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Weet u het zeker?</h3>
					<p>U staat op het punt deze pagina te verwijderen, weet u dit zeker? <a href='index.php?action=doPromotions&subaction=10&id=".$_GET['id']."&zeker=1'>Ja</a></p></div><br>";
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>".newsletterOverview();
	}
}

function uploadPromoImage(){
	global $Settings;
	global $ErrorHandler; 
	if(empty($_FILES["fileToUpload"]["tmp_name"])){
		//Geen upload gedetecteers laat het formulier zien
		return  "<div class='w3-container w3-theme-l2'><h2>Plaatje aan banner toevoegen</h2></div>
	<form action='index.php?action=doPromotions&subaction=11' method='post' class='w3-container' enctype='multipart/form-data'>
	<p><label>Kies de afbeelding:</label><input class='w3-input w3-border' type='file' name='fileToUpload' value=''></p>
	<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Afbeelding uploaden'></p>
	</form>";
	} else {
		$imageLocation = uploadImage('uploadedImages/', FALSE);
		if($imageLocation){
			//upload succesfull
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het plaatje is geupload. Link:<br>".$Settings->_get('fullPath').'/'.$imageLocation."</p></div><br>
					<img src='$imageLocation' alt='uploaded image'>";
		} else {
			//upload failed
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$ErrorHandler->getErrorMessages()."</p></div><br>".$ErrorHandler->getDebugMessages();
		}
	}
}


function editLanderFrom(){
	global $PDO;
	$result = $PDO->query("SELECT * FROM frontpage ORDER BY id DESC LIMIT 0, 1");
	$row = $result->fetch();
	return "<div class='w3-card-4'>
	<div class='w3-container w3-theme-l2'><h2>Welkomstbericht aanpassen</h2></div>
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
	<form action='index.php?action=doPromotions&subaction=13' method='post' class='w3-container'>
	<p><label>Onderwerp:<label><input type='text' value='".$row['title']."' name='title' class='w3-input w3-border'></input></p>
	<textarea name='text' style='width: 100%; height: 400px'>".$row['text']."</textarea>
	</form></div>";
}


function editLander(){
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_POST['title']) OR $_POST['title'] == ''){
		$errormsg = 'Geen titel ingevoerd<br>';
	}
	if(!isset($_POST['text']) OR $_POST['text'] == ''){
		$errormsg = 'Geen tekst ingevoerd<br>';
	}
	if(!$errormsg){
		$insert = $PDO->prepare("INSERT INTO frontpage (title, text) VALUES (:title, :text)");
		$insert->execute(array(':title' => $_POST['title'], ':text' => $_POST['text']));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het welkomstbericht is aangepast.</p></div><br>".editLanderFrom();
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>".newsletterForm();
	}
}

if(isset($_GET['subaction'])){
	$subaction = $_GET['subaction'];
} else {
	$subaction = 'default';
}
if(isset($_GET['screen'])){
	$screen = $_GET['screen'];
} else {
	$screen = '2';
}

if($Settings->_get('allowPromotions') != 0){
	if($User->isAdmin() or $User->canEditProducts()){
		$Page->addToBody(createNavBar(array('coupons' => 'Actie coupons beheren','promoties' => 'Categorie promoties beheren','banner' => 'Banner promotie beheren')));
		switch ($subaction){
	
			case 1:
				$Page->addToBody(addOrEditCoupon());
				break;
	
			case 2:
				$Page->addToBody(addOrEditCatPromo());
				break;
	
			case 3:
				$Page->addToBody(deleteCatPromo());
				break;
	
			case 4:
				$Page->addToBody(addPromoBannerPic());
				break;
	
			case 5:
				$Page->addToBody(editPromobannerPic());
				break;
	
			case 6:
				$Page->addToBody(deletePromobannerPic());
				break;
	
			case 7:
				$Page->clearBody();
				$Page->changePageTitle("Nieuwsbrieven overzicht");
				$Page->addToBody(newslettersOverview());
				break;
					
			case 8:
				$Page->clearBody();
				$Page->changePageTitle("Nieuwsbrieven maken");
				$Page->addToBody(newsletterForm());
				break;
	
			case 9:
				$Page->clearBody();
				$Page->changePageTitle("Nieuwsbrieven maken");
				$Page->addToBody(postOrEditNewsletter());
				break;
					
			case 10:
				$Page->clearBody();
				$Page->changePageTitle("Nieuwsbrieven verwijderen");
				$Page->addToBody(deleteNewsletter());
				break;
	
			case 11:
				$Page->clearBody();
				$Page->changePageTitle("Plaatje uploaden");
				$Page->addToBody(uploadPromoImage());
				break;
					
			case 12:
				$Page->clearBody();
				$Page->changePageTitle("Welkomst bericht aanpassen");
				$Page->addToBody(editLanderFrom());
				break;
	
			case 13:
				$Page->clearBody();
				$Page->changePageTitle("Welkomst bericht aanpassen");
				$Page->addToBody(editLander());
				break;
	
			default:
				$Page->addToBody(showCouponsForm().showCatPromotionsForm().showBannerPromotionsForm().determineOpenField($screen));
				break;
	
		}
	
	} else {
		$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>U bezit niet de juiste rechten voor deze actie, mogelijk bent u niet ingelogd.</p></div><br>");
	}
} elseif($subaction == 12) {
	$Page->changePageTitle("Welkomst bericht aanpassen");
	$Page->addToBody(editLanderFrom());
} elseif($subaction == 13) {
	$Page->changePageTitle("Welkomst bericht aanpassen");
	$Page->addToBody(editLander());
} else {
	$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Promoties zijn uitgeschakeld.</p></div><br>");
}
	
?>