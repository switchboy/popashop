<?php

function bookInForm(){
	global $PDO;
	global $Settings;
	$errormsg = FALSE;
	if(!isset($_GET['pId']) OR $_GET['pId'] == ''){
		$errormsg = $errormsg."Geen product geselecteerd";
	} elseif(!is_numeric($_GET['pId'])){
		$errormsg = $errormsg."Geen geldig productnummer";
	} else{
		$pid = $_GET['pId'];
		$result = $PDO->prepare("SELECT * FROM producten WHERE id = :id");
		$result->execute(array(':id' => $pid));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg = $errormsg."Dit product bestaat niet";			
		}
	}
	if(!$errormsg){
		$return = "<div class='w3-card-4'>
					<div class='w3-container w3-theme-l2'><h2>Pruduct inboeken</h2></div>
					<form action='index.php?action=employee&subaction=3' method='post' class='w3-container'>
					<p>Product: ".$row['productNaam']."</p>
					<input type='hidden' name='pid' value='$pid'></input>
					<p><label>Aantal (maximaal 100 per keer):</label><input class='w3-input w3-border' type='text' name='hoeveelheid' value=''></p>";
		if($Settings->_get('supplyTracking') != 0){
				$return .= "<p><label>Rek:</label><input class='w3-input w3-border' type='text' name='rek' value='".$row['rek']."'></p>
							<p><label>Vak:</label><input class='w3-input w3-border' type='text' name='vak' value='".$row['vak']."'></p>
							<p><label>Plank:</label><input class='w3-input w3-border' type='text' name='plank' value='".$row['plank']."'></p>";
		}
		$return .= "<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Producten inboeken'></p>
					</form>
					</div>";
		return $return;
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}

function bookInProductes(){
	global $PDO;
	global $Settings;
	$errormsg = FALSE;
	if(!isset($_POST['pid']) OR $_POST['pid'] == ''){
		$errormsg = $errormsg."Geen product geselecteerd";
	} elseif(!is_numeric($_POST['pid'])){
		$errormsg = $errormsg."Geen geldig productnummer";
	} else{
		$pid = $_POST['pid'];
		$result = $PDO->prepare("SELECT * FROM producten WHERE id = :id");
		$result->execute(array(':id' => $pid));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg = $errormsg."Dit product bestaat niet";			
		}
	}
	if(!isset($_POST['hoeveelheid']) OR $_POST['hoeveelheid'] == ''){
		$errormsg = $errormsg."Geen aantal ingevuld";
	} elseif(!is_numeric($_POST['hoeveelheid'])){
		$errormsg = $errormsg."Geen geldige hoeveelheid";
	} elseif($_POST['hoeveelheid'] > 100){
		$errormsg = $errormsg."U kunt maximaal 100 producten tegelijkertijd toevoegen";
	} else {
		$hoeveelheid = $_POST['hoeveelheid'];
	}
	if($Settings->_get('supplyTracking') != 0){
		if(!isset($_POST['rek']) OR $_POST['rek'] == ''){
			$errormsg = $errormsg."Geen rek";
		} elseif(!is_numeric($_POST['rek'])){
			$errormsg = $errormsg."Geen gedlig reknummer";
		} else{
			$rek = $_POST['rek'];
		}
		if(!isset($_POST['vak']) OR $_POST['vak'] == ''){
			$errormsg = $errormsg."Geen vak ingevuld";
		} elseif(!is_numeric($_POST['vak'])){
			$errormsg = $errormsg."Geen geldig vaknummer";
		} else{
			$vak = $_POST['vak'];
		}
		if(!isset($_POST['plank']) OR $_POST['plank'] == ''){
			$errormsg = $errormsg."Geen planknummer";
		} elseif(!is_numeric($_POST['plank'])){
			$errormsg = $errormsg."Geen geldig planknummer";
		} else{
			$plank = $_POST['plank'];
		}
	} else {
		$rek = 0;
		$vak =0;
		$plank =0;
	}
	if(!$errormsg){
		$voorraad = $row['voorraad'] + $hoeveelheid;
		$updateProduct = $PDO->prepare("UPDATE producten SET voorraad = :voorraad, rek = :rek, vak = :vak, plank = :plank WHERE id = :id");
		$updateProduct->execute(array(':voorraad' => $voorraad, ':rek' => $rek, ':vak' => $vak, ':plank' => $plank, ':id' => $pid ));
		$insert = $PDO->prepare("INSERT INTO producttracking (pid, rek, vak, plank) VALUES (:pid, :rek, :vak, :plank)");
		$list = '<ul>';
		for($a = 0; $a < $hoeveelheid; $a++){
			$insert->execute(array(':pid' => $pid, ':rek' => $rek, ':vak' => $vak, ':plank' => $plank));
			$lastId = $PDO->lastInsertId();
			$list .= "<li>Product met identificatienummer: $lastId  als ".$row['productNaam']." ingepoekt op $rek-$vak-$plank <a href='#' onClick=\"window.open('genbarcode.php?productnummer=$lastId','overzicht printen','width=600,height=240')\">print label</a></li>";
		}
		$list .= '</ul>';
		$return = "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het product is ingeboekt.</p></div><br>";
		$return .= $list;
		return $return;	
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".bookInForm();
	}
	
}

function showOrdersToPick(){
	global $PDO;
	$checkForNewOrders = checkOrdersForCompleteness();
	$debug = FALSE;
	$result = $PDO->query("SELECT * FROM orders WHERE compleet = 1 AND geraapt = 0 ORDER BY shop DESC, service DESC");
	$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Te rapen orders</h2></div><div class='w3-container'><br><div class='table'><div class='tableRow'><div class=tableCell>Order</div><div class=tableCell>Shop</div><div class=tableCell>Service</div><div class=tableCell>Besteldatum</div><div class=tableCell>Rapen</div></div>";
	$a = 0;
	foreach($result as $row){
		$a++;
		$return .= "<div class='tableRow'>
				<div class=tableCell><a href='index.php?action=employee&subaction=5&orderId=".$row['id']."'>Ordernummer ".$row['id']."</a></div>
				<div class=tableCell>".$row['shop']."</div>
						<div class=tableCell>".$row['service']."</div>
				<div class=tableCell>".date('m-d-Y', $row['besteldatum'])."</div>
				<div class=tableCell><a href='index.php?action=employee&subaction=6&orderId=".$row['id']."'>Order rapen</a></div>
				</div>";
	}
	if($a == 0){
		$return .= "<p>Geen orders te rapen.</p>";
	}
	$return .= "<br></div></div></div><br>";
	if($debug){
		$return .= "<br>Debugging:<br>".$checkForNewOrders;
	}
	return $return;
}

function showOrdersToSendShop(){
	global $PDO;
	$result = $PDO->query("SELECT * FROM orders WHERE compleet = 1 AND geraapt = 1 AND verzonden = 0 AND shop = 1 AND service = 0 ORDER BY shop, id");
	$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Klaar voor afleveren in de shop</h2></div><div class='w3-container'><br><div class='table'><div class='tableRow'><div class=tableCell>Order</div><div class=tableCell>Besteldatum</div><div class=tableCell>Verzenden</div></div>";
	$a = 0;
	foreach($result as $row){
		$a++;
		$return .= "<div class='tableRow'>
				<div class=tableCell><a href='index.php?action=employee&subaction=5&orderId=".$row['id']."'>Ordernummer ".$row['id']."</a></div>
				<div class=tableCell>".date('m-d-Y', $row['besteldatum'])."</div>
				<div class=tableCell><a href='index.php?action=employee&subaction=15&orderId=".$row['id']."'>uitgeleverd</a></div>
				</div>";
	}
	if($a == 0){
		$return .= "<p>Geen wachtende shoporders.</p>";
	}
	$return .= "<br></div></div></div><br>";
	return $return;
}

function showOrdersToSendMail(){
	global $PDO;
	$result = $PDO->query("SELECT * FROM orders WHERE compleet = 1 AND geraapt = 1 AND verzonden = 0 AND shop = 0 AND service = 0 ORDER BY shop, id");
	$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Klaar om te verzenden per post</h2></div><div class='w3-container'><br><div class='table'><div class='tableRow'><div class=tableCell>Order</div><div class=tableCell>Besteldatum</div><div class=tableCell>Verzenden</div></div>";
	$a = 0;
	foreach($result as $row){
		$a++;
		$return .= "<div class='tableRow'>
				<div class=tableCell><a href='index.php?action=employee&subaction=5&orderId=".$row['id']."'>Ordernummer ".$row['id']."</a></div>
				<div class=tableCell>".date('m-d-Y', $row['besteldatum'])."</div>
				<div class=tableCell><a href='index.php?action=employee&subaction=15&orderId=".$row['id']."'>verzonden</a></div>
				</div>";
	}
	if($a == 0){
		$return .= "<p>Geen complete orders te verzenden.</p>";
	}
	$return .= "<br></div></div></div><br>";
	return $return;
}

function showOrdersToSendService(){
	global $PDO;
	global $Settings;
	if($Settings->_get('allowServiceOrders') != 0){
		$result = $PDO->query("SELECT * FROM orders WHERE compleet = 1 AND geraapt = 1 AND verzonden = 0 AND shop = 0 AND service = 1 ORDER BY shop, id");
		$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Klaar voor service</h2></div><div class='w3-container'><br><div class='table'><div class='tableRow'><div class=tableCell>Order</div><div class=tableCell>Besteldatum</div><div class=tableCell>Verzenden</div></div>";
		$a = 0;
		foreach($result as $row){
			$a++;
			$return .= "<div class='tableRow'>
				<div class=tableCell><a href='index.php?action=employee&subaction=5&orderId=".$row['id']."'>Ordernummer ".$row['id']."</a></div>
				<div class=tableCell>".date('m-d-Y', $row['besteldatum'])."</div>
				<div class=tableCell><a href='index.php?action=employee&subaction=15&orderId=".$row['id']."'>verzonden</a></div>
				</div>";
		}
		if($a == 0){
			$return .= "<p>Geen complete orders te verzenden.</p>";
		}
		$return .= "<br></div></div></div><br>";
		return $return;
	} else {
		return;
	}
}

function showOrdersToComplete(){
	global $PDO;
	$result = $PDO->query("SELECT * FROM orders WHERE compleet = 0 AND voldaan = 1 ORDER BY shop, id");
	$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Orders welke incompleet zijn.</h2></div><div class='w3-container'><br><div class='table'><div class='tableRow'><div class=tableCell>Order</div><div class=tableCell>Shop</div><div class=tableCell>Service</div><div class=tableCell>Besteldatum</div><div class=tableCell>Verzenden</div></div>";
	$a = 0;
	foreach($result as $row){
		$a++;
		$return .= "<div class='tableRow'>
				<div class=tableCell><a href='index.php?action=employee&subaction=5&orderId=".$row['id']."'>Ordernummer ".$row['id']."</a></div>
				<div class=tableCell>".$row['shop']."</div>
				<div class=tableCell>".$row['service']."</div>
				<div class=tableCell>".date('m-d-Y', $row['besteldatum'])."</div>
				<div class=tableCell><a href=''>Controleer opnieuw</a></div>
				</div>";
	}
	if($a == 0){
		$return .= "<p>Geen incomplete orders.</p>";
	}
	$return .= "<br></div></div></div><br>";
	return $return;
}

function showOrdersToPay(){
	global $PDO;
	global $User;
	if($User->hasServiceRights() OR $User->isAdmin()){
	$result = $PDO->query("SELECT * FROM orders WHERE voldaan = 0 AND costumercomfirmed = 1");
	$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Orders die nog niet betaald zijn.</h2></div><div class='w3-container'><br><div class='table'><div class='tableRow'><div class=tableCell>Order</div><div class=tableCell>Shop</div><div class=tableCell>Besteldatum</div><div class=tableCell></div></div>";
	$a = 0;
	foreach($result as $row){
		$a++;
		$return .= "<div class='tableRow'>
				<div class=tableCell><a href='index.php?action=employee&subaction=5&orderId=".$row['id']."'>Ordernummer ".$row['id']."</a></div>
				<div class=tableCell>".$row['shop']."</div>
				<div class=tableCell>".date('m-d-Y', $row['besteldatum'])."</div>
				<div class=tableCell><a href='index.php?action=employee&subaction=16&orderId=".$row['id']."'>Betaling akkoord</a></div>
				</div>";
	}
	if($a == 0){
		$return .= "<p>Geen wachtende betalingauthorisaties.</p>";
	}
	$return .= "<br></div></div></div><br>";
	return $return;
	} else {
		return;
	}
}

function orderEmployeeProductList($orderId){
	global $PDO;
	global $printThisPage;
	global $Settings;
	$return = '';
	$resultOrder = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
	$resultOrder->execute(array(':id' => $orderId));
	$rowOrder = $resultOrder->fetch();
	$a = 0;
	$result = $PDO->prepare("SELECT orderproducten.prijs , orderproducten.productid, orderproducten.aantal, producten.productNaam, producten.btwtarief FROM orderproducten LEFT JOIN producten ON productid = producten.id WHERE orderid = :orderId");
	$result->execute(array(':orderId' => $orderId ));
	$compleet = TRUE;
	foreach($result as $row){
		if($row['productid'] == 0 OR $row['aantal'] <= 0){
			$return = $return . '';
		} else {
			$resultPlace=$PDO->prepare("SELECT * FROM producttracking WHERE pid = :pid AND oid = 0 LIMIT 0, :aantal");
			$resultPlace->execute(array(':pid' => $row['productid'], ':aantal' => $row['aantal']));
			$plek= array();
			$a = 0;
			$b = 0;
			$ligtOpSchap = TRUE;
			foreach ($resultPlace as $rowPlace){
				if($rowPlace['id'] != NULL){
					if ($a == 0){
						$plek[$b]['rek'] = $rowPlace['rek'];
						$plek[$b]['vak'] = $rowPlace['vak'];
						$plek[$b]['plank'] = $rowPlace['plank'];
					} elseif($plek[$b]['rek'] != $rowPlace['rek'] OR $plek[$b]['vak'] != $rowPlace['vak'] OR $plek[$b]['plank'] != $rowPlace['plank']){
						$b++;
						$plek[$b]['rek'] = $rowPlace['rek'];
						$plek[$b]['vak'] = $rowPlace['vak'];
						$plek[$b]['plank'] = $rowPlace['plank'];
					}
				} else {
					$ligtOpSchap = FALSE;
				}
				$a++;
			}
			$return = $return . "<li>".$row['aantal']."x ".$row['productNaam']." ligt op:<b>";
			if($rowOrder['geraapt'] == 1){
				$return .= ' <b>Al geraapt!</b>';
			} elseif($ligtOpSchap AND $a == $row['aantal']){
				if($Settings->_get('supplyTracking') != 0){
					for($c=0; $c <= $b; $c++){
						$return .= ' rek '.$plek[$c]['rek'].' vak '.$plek[$c]['vak'].' plank '.$plek[$c]['plank'].' ';
					}
				} else {
					$return .= ' op voorraad';
				}
			} else {
				$compleet = FALSE;
				$return .= ' <b>Niet op voorraad</b>';
			}
			$return .="</b></li>";
		}
		$a++;
	}
	if($rowOrder['geraapt'] == '0' AND $compleet){
		if($printThisPage){
			$return .= "</ul><div style='text-align: center;'>Ordernummer barcode:<br><br><img src=\"barcode.php?text=".$orderId."\" alt=\"".$orderId."\" /><br>".$orderId."</div>";
		} else{
			$return .= "</ul><a href='index.php?action=employee&subaction=5&orderId=$orderId&print=1'>Printen</a><br><br>".pickUpOrderForm().'<br></div>';
		}
	} else {
		$return .= "</ul><br></div>";
	}
	return $return;
}

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


function paymentRecieved(){
	global $PDO;
	global $User;
	if($User->isAdmin()){
		$errormsg = FALSE;
		if (isset($_GET['orderId']) && $_GET['orderId'] != NULL ){
			$oId = $_GET['orderId'];
			$resultOrder = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
			$resultOrder->execute(array(':id' => $oId));
			$rowOrder = $resultOrder->fetch();
			if($rowOrder['id'] == NULL){
				$errormsg .= 'Deze order bestaat niet.<br>';
			}
			if($rowOrder['costumercomfirmed'] == 0){
				$errormsg .= 'Deze order is niet door de klant geaccordeerd.<br>';
			}
			if($rowOrder['voldaan'] == 1){
				$errormsg .= 'Deze order is al betaald.<br>';
			}
			if($rowOrder['betaalmethode'] != 1){
				$errormsg .= 'Dit is geen overboeking order.<br>';
			}
		} else {
			$errormsg .= 'Geen order geselecteerd.<br>';
		}
		if(!$errormsg){
			$update = $PDO->prepare("UPDATE orders SET voldaan = 1 WHERE id = :id");
			$update->execute(array(':id' => $oId));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Deze order is aangemerkt als betaald</p></div><br>".logistics();
		} else {
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>U heeft onvoldoende rechten om een betaling te authoriseren./p></div><br>";
	}
}

function pickUpOrderForm(){
	global $PDO;
	$errormsg = FALSE;
	if (isset($_GET['orderId']) && $_GET['orderId'] != NULL ){
		$oId = $_GET['orderId'];
		$resultOrder = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
		$resultOrder->execute(array(':id' => $oId));
		$rowOrder = $resultOrder->fetch();
		if($rowOrder['id'] == NULL){
			$errormsg .= 'Deze order bestaat niet.<br>';
		}
	} else {
		$errormsg .= 'Geen order geselecteerd.<br>';
	}
	$begin = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Order rapen met order-nr: $oId</h2></div>
			<form class='w3-container' action='index.php?action=employee&subaction=7&orderId=$oId' method ='post'>";
	$eind = "<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Producten koppellen'></input></p>
				</form></div>";
	$productList = FALSE;
	if(!$errormsg){
		$resultProducten = $PDO->prepare("SELECT orderproducten.productid, orderproducten.aantal, producten.productNaam, producten.service FROM orderproducten LEFT JOIN producten ON productid = producten.id WHERE orderid = :orderid");
		$resultProducten->execute(array(':orderid' => $oId));
		foreach($resultProducten as $rowP){
			if($rowP['productid'] != NULL){
				for($a = 0; $a < $rowP['aantal']; $a++){
					if($rowP['service'] != 0){
						$productList .= "<p>".$rowP['productNaam']." is een service en heeft dus geen serienummer. <input class='w3-input w3-border' type='hidden' name='product[".$rowP['productid']."][$a]' value='service'></input></p>";
					} else {
						$productList .= "<p><label>".$rowP['productNaam']." serienummer:</label><input class='w3-input w3-border' type='text' name='product[".$rowP['productid']."][$a]' value=''></input></p>";
					}
				}
			}
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
	if($productList){
		return $begin.$productList.$eind;
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Geen producten op deze lijst.</p></div><br>";
	}
}

function pickUpOrder(){
	global $PDO;
	$errormsg = FALSE;
	if(isset($_GET['orderId']) && $_GET['orderId'] != NULL){
		$oId = $_GET['orderId'];
		$resultOrder = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
		$resultOrder->execute(array(':id' => $oId));
		$rowOrder = $resultOrder->fetch();
		if($rowOrder['id'] == NULL){
			$errormsg .= 'Deze order bestaat niet.<br>';
		}
	} else {
		$errormsg .= 'Geen order meegegeven<br>';
	}
	if(isset($_POST['product']) && $_POST['product'] != NULL){
		$productArray = $_POST['product'];
	} else {
		$errormsg .= 'Geen serienummers ingevuld';
	}
	if(!$errormsg){
		$transactionActive = TRUE;
		$PDO->beginTransaction();
		$a = 0;
		$update = $PDO->prepare("UPDATE producttracking SET oid = :oid WHERE id = :id");
		$updateProduct = $PDO->prepare("UPDATE producten SET gereserveerd = gereserveerd - 1, verkocht = verkocht + 1 WHERE id = :id");
		foreach($productArray as $productId => $productSerialArray){
			foreach($productSerialArray as $productSerial){
				if($transactionActive){
					$a++;
					if($productSerial != 'service'){
						$result = $PDO->prepare("SELECT * FROM producttracking WHERE id = :id");
						$result->execute(array(':id' => $productSerial));
						$row = $result->fetch();
						if($row['id'] == NULL){
							$errormsg .= "Het serienummer '$productSerial' van product $a bestaat niet.";
							$PDO->rollBack();
							$transactionActive = FALSE;
						}elseif($row['oid'] != '0'){
							$errormsg .= 'Dit product is al aan een andere order gekoppeld.';
							$PDO->rollBack();
							$transactionActive = FALSE;
						}elseif($row['pid'] != $productId){
							$errormsg .= 'Dit sernienummer is aan een ander product gekoppeld, controleer of u het goede product hebt.';
							$PDO->rollBack();
							$transactionActive = FALSE;
						}
						if($transactionActive){
							$update->execute(array(':oid' => $oId,':id' => $productSerial));
							$update->closeCursor();
							$updateProduct->execute(array(':id' => $productId));
							$updateProduct->closeCursor();
						}
					}
				}
			}
		}
		if($transactionActive){
			$updateOrder  = $PDO->prepare("UPDATE orders SET geraapt = 1 WHERE id = :id");
			$updateOrder->execute(array(':id' => $oId));
			$PDO->commit();
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De order is geraapt.</p></div><br>".showOrdersToPick();
		} else {
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".showOrderDetails(FALSE);
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>".showOrderDetails(FALSE);
	}
}

function placeShopOrderForm(){
	global $PDO;
	global $myCart;
	$errormsg = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen klant geselecteerd.<br>';
	} else {
		$uid = $_GET['uid'];
		$result = $PDO->prepare("SELECT * FROM klanten WHERE id = :id");
		$result->execute(array(':id' => $uid));
		$row = $result->fetch();
		if( $row['id'] == NULL){
			$errormsg .= 'Deze klant bestaat niet.<br>';
		}
		if($row['tussenvoegsel'] != ''){
			$tussenvoegsel = ", ".$row['tussenvoegsel'];
		} else {
			$tussenvoegsel = '';
		}
	}
	if(!$errormsg){
		$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Shop order maken</h2></div>".
				"<div class='w3-container'><h3>Klantgegevens:</h3><p>".$row['voornaam']." ".$row['achternaam'].$tussenvoegsel."<br>".$row['straatnaam']." ".$row['huisnummer']." ".
				$row['toevoeging']."<br>".$row['postcode']." ".$row['stad']."</p></div>";
		$return .= $myCart->showList(TRUE);
		$return .= "<div class='w3-container'><a href='index.php?action=employee&subaction=11&uid=$uid' class='w3-btn w3-theme-l2'>Maak een shoporder voor klant</a><br><br></div></div>";
		return $return;
	}else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}

function placeShopOrder(){
	global $PDO;
	global $myCart;
	$errormsg = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen klant geselecteerd.<br>';
	} else {
		$uid = $_GET['uid'];
		$result = $PDO->prepare("SELECT * FROM klanten WHERE id = :id");
		$result->execute(array(':id' => $uid));
		$row = $result->fetch();
		if( $row['id'] == NULL){
			$errormsg .= 'Deze klant bestaat niet.<br>';
		}
	}
	if($myCart->_get('numberOfItems') < 1){
		$errormsg .= 'Er zitten geen producten in de winkelwagen.<br>';
	}
	if(!$errormsg){
		$prijs = $myCart->_get('total_value')-$myCart->getDiscount();
		$insertOrder = $PDO->prepare("INSERT INTO orders (userid, email, prijs, voornaam, achternaam, tussenvoegsel, straat, huisnummer, toevoeging, postcode, stad, voldaan, costumercomfirmed, besteldatum, discountcoupon, shop)
				VALUES (:userid, :email, :prijs, :voornaam, :achternaam, :tussenvoegsel, :straat, :huisnummer, :toevoeging, :postcode, :stad, :voldaan, :costumercomfirmed, :besteldatum, :dicountcoupon, :shop)");
		$insertOrder->execute(array(
				':userid' => $uid, 
				':email' => $row['email'],
				':prijs' => $prijs,
				':voornaam' => $row['voornaam'],
				':achternaam' => $row['achternaam'],
				':tussenvoegsel' => $row['tussenvoegsel'],
				':straat' => $row['straatnaam'],
				':huisnummer' => $row['huisnummer'],
				':toevoeging' => $row['toevoeging'],
				':postcode' => $row['postcode'],
				':stad' => $row['stad'],
				':voldaan' => 1,
				':costumercomfirmed' => 1,
				':besteldatum' => time(),
				':dicountcoupon' => $myCart->_get('discountCoupon') ,
				':shop' => 1
		));
		$orderId = $PDO->lastInsertId();
		foreach ($myCart->_get('productsArray') as $index => $productId){
			$price = $myCart->_get('productPriceArray')[$index];
			$amount = $myCart->_get('productsAmountArray')[$index];
			$insertProducts = $PDO->prepare("INSERT INTO orderproducten (orderid, productid, prijs, aantal) VALUES (:orderid, :productid, :prijs, :aantal)");
			$insertProducts->execute(array(':orderid' => $orderId, ':productid' => $productId, ':prijs' => $price, ':aantal' => $amount));
			$insertProducts->closeCursor();
		}
		emptyCart($myCart->_get('cartId'));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De shopbestelling is gemaakt.</p></div><br>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}


function productTOSForm(){
	global $PDO;
	if(isset($_POST['serial']) AND $_POST['serial'] != NULL){
		$result = $PDO->prepare("SELECT * FROM producttracking WHERE id = :id");
		$result->execute(array(':id' => $_POST['serial']));
		$row = $result->fetch();
		if($row['id'] != NULL){
			return '<div class="w3-card-4">
					<div class="w3-container w3-theme-l2"><h2>Product met serienummer '.$_POST['serial'].' terug op schap leggen</h2></div>
					<form class="w3-container" action="index.php?action=employee&subaction=13" method="post">
							<input type="hidden" name="serial" value="'.$_POST['serial'].'"></input>
						<p><label>Rek:</label><input class="w3-input w3-border" type="text" name="rek" value="'.$row['rek'].'"></input></p>
						<p><label>Vak:</label><input class="w3-input w3-border" type="text" name="vak" value="'.$row['vak'].'"></input></p>
						<p><label>Plank:</label><input class="w3-input w3-border" type="text" name="plank" value="'.$row['plank'].'"></input></p>
						<p><input class="w3-btn w3-theme-l2" type="submit" name="submit" value="toepassen"></p>
					</form>';
		} else {
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Dit product bestaat niet.</p></div><br>";
		}
	} else {
		return '<div class="w3-card-4">
					<div class="w3-container w3-theme-l2"><h2>Producten terug op schap leggen</h2></div>
					<form class="w3-container" action="index.php?action=employee&subaction=12" method="post">
						<p><label>Product serienummer:</label><input class="w3-input w3-border" type="text" name="serial" value=""></input></p>
						<p><input class="w3-btn w3-theme-l2" type="submit" name="submit" value="toepassen"></p>
					</form>';
	}
}

function productTOS(){
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_POST['serial']) OR $_POST['serial'] == NULL){
		$errormsg .= 'Geen product serienummer bekend<br>';
	} else {
		$result = $PDO->prepare("SELECT * FROM producttracking WHERE id = :id");
		$result->execute(array(':id' => $_POST['serial']));
		$row = $result->fetch();
		if($row['id'] == NULL){
			$errormsg .= 'Dit productserienummer bestaat niet.<br>';
		}
		if($row['oid'] == NULL){
			$errormsg .= 'Dit product ligt al op schap!<br>';
		}
		$row['history'] .= $row['oid'].', ';
	}
	if(!isset($_POST['rek']) OR $_POST['rek'] == ''){
		$errormsg = $errormsg."Geen rek ingevuld<br>";
	} elseif(!is_numeric($_POST['rek'])){
		$errormsg = $errormsg."Geen gedlig reknummer<br>";
	} 
	if(!isset($_POST['vak']) OR $_POST['vak'] == ''){
		$errormsg = $errormsg."Geen vak ingevuld<br>";
	} elseif(!is_numeric($_POST['vak'])){
		$errormsg = $errormsg."Geen geldig vaknummer<br>";
	}
	if(!isset($_POST['plank']) OR $_POST['plank'] == ''){
		$errormsg = $errormsg."Geen planknummer<br>";
	} elseif(!is_numeric($_POST['plank'])){
		$errormsg = $errormsg."Geen geldig planknummer<br>";
	}
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE producttracking SET oid = 0, rek = :rek, vak = :vak, plank = :plank, history = :history WHERE id = :id");
		$update->execute(array(':rek' => $_POST['rek'], ':vak' => $_POST['vak'], ':plank' => $_POST['plank'],':history' => $row['history'], 'id' => $_POST['serial']));
		$_POST['serial'] == NULL;
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Product ligt op schap.</p></div><br>".productTOSForm();
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>";
	}
}


function productStatus(){
	global $PDO;
	global $Settings;
	if($Settings->_get('productHistory') != 0){
		if(isset($_GET['serial']) AND $_GET['serial'] != NULL){
			$result = $PDO->prepare("SELECT producttracking.* , producten.productNaam FROM producttracking LEFT JOIN producten ON producttracking.pid = producten.id WHERE producttracking.id = :id");
			$result->execute(array(':id' => $_GET['serial']));
			$row = $result->fetch();
			if($row['id'] != NULL){
				$return = '<div class="w3-card-4">
					<div class="w3-container w3-theme-l2"><h2>Bekijk de status en geschiedenis van een product</h2></div>
					<div class="w3-container"><h3>Huidige status van product: '.$row['productNaam'].'</h3>';
				if($row['oid'] == 0){
					$return .= '<p>Product ligt op schap.<br>rek: '.$row['rek'].' vak: '.$row['vak'].' plank: '.$row['plank'].'</p>';
				} else {
					$return .= '<p>Product is onderdeel van <a href="index.php?action=showOrder&orderId='.$row['oid'].'">ordernummer '.$row['oid'].'</a><br>En lag op schap:<br>rek: '.$row['rek'].' vak: '.$row['vak'].' plank: '.$row['plank'].'</p>';
				}
				$return .= '</div><div class="w3-container w3-theme-l2"><h2>Geschiedenis van product</h2></div><div class="w3-container"><p>';
				$history = explode(',', $row['history']);
				$historyCounter = 0;
				foreach($history as $ordernummer){
					if($ordernummer != NULL AND $ordernummer != ' '){
						$return .= "<a href='index.php?action=showOrder&orderId=$ordernummer'>Ordernummer $ordernummer</a><br>";
						$historyCounter++;
					}
				}
				if($historyCounter == 0){
					$return .= 'Dit product heeft geen verdere geschiedenis.<br>';
				}
				$return .= '</p><br><a class="w3-btn w3-theme-l2" href="index.php?action=employee&subaction=14">Bekijk een ander product</a></div><br></div>';
				return $return.'<br><div class="w3-card-4">
					<div class="w3-container w3-theme-l2"><h2>Bekijk de status en geschiedenis van een product</h2></div>
					<form class="w3-container" action="index.php" method="GET">
						<input type="hidden" name="action" value="employee"></input>
						<input type="hidden" name="subaction" value="14"></input>
						<p><label>Product serienummer:</label><input class="w3-input w3-border" type="text" name="serial" value=""></input></p>
						<p><input class="w3-btn w3-theme-l2" type="submit"></p>
					</form>
				</div>';
			} else {
				return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Dit product bestaat niet.</p></div><br>";
			}
		} else {
			return '<div class="w3-card-4">
					<div class="w3-container w3-theme-l2"><h2>Bekijk de status en geschiedenis van een product</h2></div>
					<form class="w3-container" action="index.php" method="GET">
						<input type="hidden" name="action" value="employee"></input>
						<input type="hidden" name="subaction" value="14"></input>
						<p><label>Product serienummer:</label><input class="w3-input w3-border" type="text" name="serial" value=""></input></p>
						<p><input class="w3-btn w3-theme-l2" type="submit"></p>
					</form>
				</div>';
		}
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>De product status functie is uigeschakeld.</p></div><br>";
	}
}

function sentOutOrder(){
	global $PDO;
	$errormsg = FALSE;
	if(isset($_GET['orderId']) && $_GET['orderId'] != NULL){
		$oId = $_GET['orderId'];
		$resultOrder = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
		$resultOrder->execute(array(':id' => $oId));
		$rowOrder = $resultOrder->fetch();
		if($rowOrder['id'] == NULL){
			$errormsg .= 'Deze order bestaat niet.<br>';
		}
		if($rowOrder['geraapt'] != '1'){
			$errormsg .= 'Deze is nog niet geraapt.<br>';
		}
		if($rowOrder['verzonden'] == '1'){
			$errormsg .= 'Deze order is al verzondenj.<br>';
		}
	} else {
		$errormsg .= 'Geen order meegegeven<br>';
	}
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE orders SET verzonden = 1 WHERE id =:id");
		$update->execute(array(':id' => $oId));
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Bestelling uitgeleverd.</p></div><br>".logistics();
	} else{
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errormsg."</p></div><br>";
	}
}


function logistics(){
	return showOrdersToPick().showOrdersToSendShop().showOrdersToSendMail().showOrdersToSendService().showOrdersToComplete().showOrdersToPay();
}

if($User->isAdmin() OR $User->hasServiceRights() OR $User->isEmployee()){
	switch($subaction){
		case 1:
			$Page->changePageTitle('Product zoeken');
			$Page->addToBody("<div class='w3-container w3-theme-l2'><h2>Product inboeken</h2></div><br>".search(2));
			break;
	
		case 2:
			$Page->changePageTitle('Product inboeken');
			$Page->addToBody(bookInForm());
			break;
	
		case 3:
			$Page->changePageTitle('Product inboeken');
			$Page->addToBody(bookInProductes());
			break;
	
		case 4:
			$Page->changePageTitle('Orders rapen');
			$Page->addToBody(showOrdersToPick());
			break;
	
		case 5:
			$Page->changePageTitle('Order details');
			$Page->addToBody(showOrderDetails(FALSE));
			break;
	
		case 6:
			$Page->changePageTitle('Orders rapen');
			$Page->addToBody(pickUpOrderForm());
			break;
	
		case 7:
			$Page->changePageTitle('Orders rapen');
			$Page->addToBody(pickUpOrder());
			break;
	
		case 8:
			$Page->changePageTitle('Klant zoeken voor shoporder');
			$Page->addToBody(searchUserForm('employee'));
			break;
	
		case 9:
			$Page->changePageTitle('Klant zoeken voor shoporder');
			$Page->addToBody(searchUser('employee'));
			break;
	
		case 10:
			$Page->changePageTitle('Shoporder maken');
			$Page->addToBody(placeShopOrderForm());
			break;
	
		case 11:
			$Page->changePageTitle('Shoporder');
			$Page->addToBody(placeShopOrder());
			break;
			
		case 12:
			$Page->changePageTitle('Product terug op schap');
			$Page->addToBody(productTOSForm());
			break;
			
		case 13:
			$Page->changePageTitle('Product terug op schap');
			$Page->addToBody(productTOS());
			break;
			
		case 14:
			$Page->changePageTitle('Bekijk productstatus');
			$Page->addToBody(productStatus());
			break;
			
		case 15:
			$Page->changePageTitle('Order verzenden');
			$Page->addToBody(sentOutOrder());
			break;
		
		case 16:
			$Page->changePageTitle('Shop logistiek');
			$Page->addToBody(paymentRecieved());
				
		default:
			$Page->changePageTitle('Shop logistiek');
			$Page->addToBody(logistics());
			break;
	
	}
} else {
	$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>U bezit niet de juiste rechten voor deze actie, mogelijk bent u niet ingelogd.</p></div><br>");
}

?>

