<?php

function addBankNumberToOrder(){
	global $PDO;
	$errormsg = FALSE;
	
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen klant geslecteerd.</br>';
	} else {
		$cid = $_GET['uid'];
	}
	if(isset($_GET['soid']) AND $_GET['soid'] != NULL){
		$soid = $_GET['soid'];
		$result = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
		$result->execute(array(':id' => $soid));
		$row = $result->fetch();
		if($row['service'] != 1){
			$errormsg .= 'De geselecteerde order is geen service order.</br>';
		}
		if($row['userId'] != $cid){
			$errormsg .= 'De geselecteerde order hoort niet bij deze klant.</br>';
		}
		if($row['costumercomfirmed'] == 1){
			$errormsg .= 'De geselecteerde order is al definitief.</br>';
		}
	} else {
		$errormsg .= 'Geen order geslecteerd.</br>';
	}
	if(!isset($_POST['rekeningnummer']) && $_POST['rekeningnummer'] != ''){
		$errormsg .= 'Geen rekeningnummer ingevoerd.</br>';
	}
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE orders SET rekeningnummer = :rekeningnummer WHERE id = :id");
		$update->execute(array(':rekeningnummer' => $_POST['rekeningnummer'], ':id' => $soid));
		return  "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De order is aangepast.</p></div><br>".serviceOrderForm();
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br><a href='index.php?action=service&subaction=2&uid=$cid' class='w3-btn w3-theme-l2'>Terug naar het service center</a>";
	}
}

function finishServiceOrder(){
	global $PDO;
	$errormsg = FALSE;
	
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen klant geslecteerd.</br>';
	} else {
		$cid = $_GET['uid'];
	}
	if(isset($_GET['soid']) AND $_GET['soid'] != NULL){
		$soid = $_GET['soid'];
		$result = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
		$result->execute(array(':id' => $soid));
		$row = $result->fetch();
		if($row['service'] != 1){
			$errormsg .= 'De geselecteerde order is geen service order.</br>';
		}
		if($row['userId'] != $cid){
			$errormsg .= 'De geselecteerde order hoort niet bij deze klant.</br>';
		}
		if($row['costumercomfirmed'] == 1){
			$errormsg .= 'De geselecteerde order is al definitief.</br>';
		}
	} else {
		$errormsg .= 'Geen order geslecteerd.</br>';
	}
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE orders SET costumercomfirmed = '1', voldaan = '1' WHERE id = :id");
		$update->execute(array(':id' => $soid));
		return  "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De order is definitief.</p></div><br>
		<a href='index.php?action=service&subaction=2&uid=$cid' class='w3-btn w3-theme-l2'>Terug naar het service center</a>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br><a href='index.php?action=service&subaction=2&uid=$cid' class='w3-btn w3-theme-l2'>Terug naar het service center</a>";
	}
}

function deleteServiceOrder(){
	global $PDO;
	$errormsg = FALSE;

	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen klant geslecteerd.</br>';
	} else {
		$cid = $_GET['uid'];
	}
	if(isset($_GET['soid']) AND $_GET['soid'] != NULL){
		$soid = $_GET['soid'];
		$result = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
		$result->execute(array(':id' => $soid));
		$row = $result->fetch();
		if($row['service'] != 1){
			$errormsg .= 'De geselecteerde order is geen service order.</br>';
		}
		if($row['userId'] != $cid){
			$errormsg .= 'De geselecteerde order hoort niet bij deze klant.</br>';
		}
		if($row['costumercomfirmed'] == 1){
			$errormsg .= 'De geselecteerde order is al definitief.</br>';
		}
	} else {
		$errormsg .= 'Geen order geslecteerd.</br>';
	}
	if(!$errormsg){
		$delete = $PDO->prepare("DELETE FROM orders WHERE id = :id");
		$delete->execute(array(':id' => $soid));
		return  "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De order is verwijderd.</p></div><br>
				<a href='index.php?action=service&subaction=2&uid=$cid' class='w3-btn w3-theme-l2'>Terug naar het service center</a>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>
		<a href='index.php?action=service&subaction=2&uid=$cid' class='w3-btn w3-theme-l2'>Terug naar het service center</a>";
	}
}

function deleteServiceOrderProduct(){
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen klant geslecteerd.</br>';
	} else {
		$cid = $_GET['uid'];
	}
	if(isset($_GET['soid']) AND $_GET['soid'] != NULL){
		$soid = $_GET['soid'];
		$result = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
		$result->execute(array(':id' => $soid));
		$row = $result->fetch();
		if($row['service'] != 1){
			$errormsg .= 'De geselecteerde order is geen service order.</br>';
		}
		if($row['userId'] != $cid){
			$errormsg .= 'De geselecteerde order hoort niet bij deze klant.</br>';
		}
		if($row['costumercomfirmed'] == 1){
			$errormsg .= 'De geselecteerde order is al definitief.</br>';
		}
	} else {
		$errormsg .= 'Geen order geslecteerd.</br>';
	}
	if(!isset($_GET['opid']) OR $_GET['opid'] == NULL){
		$errormsg .= 'Geen product uit de orde geselecteerd.</br>';
	} else {
		$opid = $_GET['opid'];
		$resultOpid = $PDO->prepare("SELECT * FROM orderproducten WHERE id = :id");
		$resultOpid->execute(array(':id' => $opid));
		$rowOpid = $resultOpid->fetch();
		if($rowOpid['orderid'] != $soid){
			$errormsg .= 'Dit orderproduct hoort niet bij deze order.</br>';
		}
	}
	if(!$errormsg){
		$delete = $PDO->prepare("DELETE FROM orderproducten WHERE id = :id");
		$delete->execute(array(':id' => $opid));
		return  "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het product is verwijderd.</p></div><br>".serviceOrderForm();
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>
		<a href='index.php?action=service&subaction=2&uid=$cid' class='w3-btn w3-theme-l2'>Terug naar het service center</a>";
	}
}

function editServiceOrderProduct(){
	global $PDO;
	$errormsg = FALSE;
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen klant geslecteerd.</br>';
	} else {
		$cid = $_GET['uid'];
	}
	if(isset($_GET['soid']) AND $_GET['soid'] != NULL){
		$soid = $_GET['soid'];
		$result = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
		$result->execute(array(':id' => $soid));
		$row = $result->fetch();
		if($row['service'] != 1){
			$errormsg .= 'De geselecteerde order is geen service order.</br>';
		}
		if($row['userId'] != $cid){
			$errormsg .= 'De geselecteerde order hoort niet bij deze klant.</br>';
		}
		if($row['costumercomfirmed'] == 1){
			$errormsg .= 'De geselecteerde order is al definitief.</br>';
		}
	} else {
		$errormsg .= 'Geen order geslecteerd.</br>';
	}
	if(!isset($_GET['opid']) OR $_GET['opid'] == NULL){
		$errormsg .= 'Geen product uit de orde geselecteerd.</br>';
	} else {
		$opid = $_GET['opid'];
		$resultOpid = $PDO->prepare("SELECT * FROM orderproducten WHERE id = :id");
		$resultOpid->execute(array(':id' => $opid));
		$rowOpid = $resultOpid->fetch();
		if($rowOpid['orderid'] != $soid){
			$errormsg .= 'Dit orderproduct hoort niet bij deze order.</br>';
		}
	}
	if(isset($_POST['TOS']) && $_POST['TOS'] != NULL){
		$TOS = 1;
	} else {
		$TOS = NULL;
	}
	if(!isset($_POST['pid']) OR $_POST['pid'] == NULL){
		$errormsg .= 'Geen productnummer ingevoerd.</br>';
	} else {
		$pid = $_POST['pid'];
		if($pid != 0){
			$resultpid = $PDO->prepare("SELECT * FROM producten WHERE id = :pid");
			$resultpid->execute(array(':pid' => $pid));
			$rowpid = $resultpid->fetch();
			if($rowpid['id'] == NULL){
				$errormsg .= 'Dit product nummer is ongeledig, het product bestaat niet.</br>';
			}
		}
	}
	if(!isset($_POST['prijs']) OR $_POST['prijs'] == NULL OR !is_numeric($_POST['prijs'])){
		$errormsg .= 'Geen of ongeldige prijs ingevoerd.</br>';
	} else {
		$prijs = $_POST['prijs'];
	}
	
	if(!isset($_POST['aantal']) OR $_POST['aantal'] == NULL OR !is_numeric($_POST['aantal'])){
		$errormsg .= 'Geen of ongeldig aantal ingevoerd.</br>';
	} else {
		$aantal = $_POST['aantal'];
	}
	
	if(!$errormsg){
		$update = $PDO->prepare("UPDATE orderproducten SET productid = :pid, prijs = :prijs, aantal = :aantal, TOS = :TOS WHERE id = :opid");
		$update->execute(array(':pid' => $pid, ':prijs' => $prijs, ':aantal' => $aantal,':TOS' => $TOS, ':opid' => $opid));
		$updateOrders = $PDO->prepare("update orders, (SELECT SUM(prijs * aantal) as totaal FROM orderproducten WHERE orderid = :soid) as totaal SET prijs = totaal WHERE id = :soid1");
		$updateOrders->execute(array(':soid' => $soid,':soid1' => $soid));
		return  "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>De order is aangepast.</p></div><br>".serviceOrderForm();
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>
		<a href='index.php?action=service&subaction=2&uid=$cid' class='w3-btn w3-theme-l2'>Terug naar het service center</a>";
	}
}

function addServiceOrderProduct(){
	global $PDO;
	$errormsg = FALSE;
	
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen klant geslecteerd.</br>';
	} else {
		$cid = $_GET['uid'];
	}
	if(isset($_GET['soid']) AND $_GET['soid'] != NULL){
		$soid = $_GET['soid'];
		$result = $PDO->prepare("SELECT * FROM orders WHERE id = :id");
		$result->execute(array(':id' => $soid));
		$row = $result->fetch();
		if($row['service'] != 1){
			$errormsg .= 'De geselecteerde order is geen service order.</br>';
		}
		if($row['userId'] != $cid){
			$errormsg .= 'De geselecteerde order hoort niet bij deze klant.</br>';
		}
		if($row['costumercomfirmed'] == 1){
			$errormsg .= 'De geselecteerde order is al definitief.</br>';
		}
	} else {
		$errormsg .= 'Geen order geslecteerd.</br>';
	}
	if(isset($_POST['TOS']) && $_POST['TOS'] != NULL){
		$TOS = 1;
	} else {
		$TOS = NULL;
	}
	if(!isset($_POST['pid']) OR $_POST['pid'] == NULL){
		$errormsg .= 'Geen productnummer ingevoerd.</br>';
	} else {
		$pid = $_POST['pid'];
		if($pid != 0){
			$resultpid = $PDO->prepare("SELECT * FROM producten WHERE id = :pid");
			$resultpid->execute(array(':pid' => $pid));
			$rowpid = $resultpid->fetch();
			if($rowpid['id'] == NULL){
				$errormsg .= 'Dit product nummer is ongeledig, het product bestaat niet.</br>';
			}
		}
	}
	if(!isset($_POST['prijs']) OR $_POST['prijs'] == NULL OR !is_numeric($_POST['prijs'])){
		$errormsg .= 'Geen of ongeldige prijs ingevoerd.</br>';
	} else {
		$prijs = $_POST['prijs'];
	}
	
	if(!isset($_POST['aantal']) OR $_POST['aantal'] == NULL OR !is_numeric($_POST['aantal'])){
		$errormsg .= 'Geen of ongeldig aantal ingevoerd.</br>';
	} else {
		$aantal = $_POST['aantal'];
	}
		
	if(!$errormsg){
		$insert = $PDO->prepare("INSERT INTO orderproducten (orderid, productid, prijs, aantal, TOS) VALUES (:orderid, :pid, :prijs, :aantal, :TOS)");
		$insert->execute(array(':orderid' => $soid, ':pid' => $pid, ':prijs' => $prijs, ':aantal' => $aantal, ':TOS' => $TOS));
		$updateOrders = $PDO->prepare("update orders, (SELECT SUM(prijs * aantal) as totaal FROM orderproducten WHERE orderid = :soid) as totaal SET prijs = totaal WHERE id = :soid1");
		$updateOrders->execute(array(':soid' => $soid, ':soid1' => $soid));
		return  "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Het product is toegevoegd.</p></div><br>".serviceOrderForm();
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>
		<a href='index.php?action=service&subaction=2&uid=$cid' class='w3-btn w3-theme-l2'>Terug naar het service center</a>";
	}
}

function serviceOrderForm(){
	global $PDO;
	global $User;
	$errormsg = FALSE;
	
	if(isset($_GET['soid']) AND $_GET['soid'] != NULL){
		$soid = $_GET['soid'];
		$newOrder = FALSE;
	} else {
		if(isset($_GET['newOrder']) AND $_GET['newOrder'] == '1'){
			$newOrder = TRUE;
		} else {
			$newOrder = FALSE;
			$errormsg .= 'Geen order geslecteerd.</br>';
		}
	}
	if(!isset($_GET['uid']) OR $_GET['uid'] == NULL){
		$errormsg .= 'Geen klant geslecteerd.</br>';
	} else {
		$cid = $_GET['uid'];
	}
	
	if(!$errormsg){
		if($newOrder){
			$insert = $PDO->prepare("	INSERT INTO orders (userId, email, voornaam, achternaam, tussenvoegsel, straat, huisnummer, toevoeging, postcode, stad, betaalmethode, besteldatum, service, siud) 
										SELECT id as userId, email, voornaam, achternaam, tussenvoegsel, straatnaam as straat, huisnummer, toevoeging, postcode, stad, 0 AS betaalmethode, :date AS besteldatum, 1 AS service, :siud AS siud
										FROM klanten WHERE id = :cid");
			$insert->execute(array(':date' => time(), ':siud' => $User->_get('costumerId'), ':cid' => $cid ));
			$soid = $PDO->lastInsertId();
		}
		$result = $PDO->prepare("	SELECT orders.*, orderproducten.TOS, orderproducten.id AS opid, orderproducten.productid, orderproducten.prijs as pprijs, orderproducten.aantal, producten.productNaam
									FROM orders
									LEFT JOIN orderproducten ON orders.id = orderproducten.orderid
									LEFT JOIN producten ON orderproducten.productid = producten.id
									WHERE orders.id =  :id");
		$result->execute(array(':id' => $soid));
		$return = "<div class='w3-card-4'>";
		$returnOrderTable = '<div class="w3-container w3-theme-l2"><h2>Producten in de order</h2></div>
			<br><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 15%;"><b>Product id</b></div>
					<div class="tableCell" style="width: 30%;"><b>Product</b></div>
					<div class="tableCell" style="width: 15%;"><b>Aantal</b></div>
					<div class="tableCell" style="width: 15%;"><b>Prijs</b></div>
					<div class="tableCell" style="width: 15%;"><b>Delete</b></div>
					<div class="tableCell" style="width: 10%;"><b>Toepassen</b></div>
				</div>
			</div>';
		$productCount = 0;
		foreach($result as $row){
			if($row['costumercomfirmed'] == 1){
				return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Deze order is al definitief.</p></div><br>";
			}
			if($row['service'] != 1){
				return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Dit is geen service order.</p></div><br>";
			}
			if($row['productid'] != NULL OR $row['aantal'] != NULL){
				if($row['TOS'] == 1){
					$checked = 'checked';
				} else {
					$checked = '';
				}
				if($row['productid'] == 0){
					$row['productNaam'] = 'Verzend/rembourskosten';
				}
				$returnOrderTable .= '<form action="index.php?action=serviceOrders&subaction=2&uid='.$cid.'&soid='.$soid.'&opid='.$row['opid'].'" method="post"><div class="tableRow">
						<div class="table w3-container">
					<div class="tableCell" style="width: 15%;"><input class="w3-input w3-border" type="text" name="pid" value="'.$row['productid'].'"></input></div>
					<div class="tableCell" style="max-width: 0;width: 30%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">'.$row['productNaam'].'</div>
					<div class="tableCell" style="width: 15%;"><input class="w3-input w3-border" type="text" name="aantal" value="'.$row['aantal'].'"></input></div>
					<div class="tableCell" style="width: 15%;"><input class="w3-input w3-border" type="text" name="prijs" value="'.$row['pprijs'].'"></input></div>
					<div class="tableCell" style="width: 15%;"><a href="index.php?action=serviceOrders&subaction=3&uid='.$cid.'&soid='.$soid.'&opid='.$row['opid'].'" class="w3-btn w3-theme-l2">verwijderen</a></div>
					<div class="tableCell" style="width: 10%;"><input class="w3-btn w3-theme-l2" type="submit" name="submit" value="toepassen"></input></div>
				</div></div></form>';
				$productCount++;
			}
		}
		if($productCount > 0){
			$returnOrderTable .= '<div class="w3-container" style="text-align:right;"><h1>Totale waarde van order &euro;'.number_format($row['prijs'], 2, ',', '.').'</h1></div><br>';
		} else {
			$returnOrderTable .= '<div class="w3-container">Deze service order heeft nog geen producten.</div><br>';
		}
		$return .= $returnOrderTable.'<div class="w3-container w3-theme-l2"><h2>Product aan de order toevoegen:</h2></div>
			<br><form action="index.php?action=serviceOrders&subaction=1&uid='.$cid.'&soid='.$soid.'" method="post"><div class="table w3-container">
				<div class="tableRow">
					<div class="tableCell" style="width: 15%;"><b>Product id</b></div>
					<div class="tableCell" style="width: 30%;"><b></b></div>
					<div class="tableCell" style="width: 15%;"><b>Aantal</b></div>
					<div class="tableCell" style="width: 15%;"><b>Prijs</b></div>
					<div class="tableCell" style="width: 15%;"><b></b></div>
					<div class="tableCell" style="width: 10%;"><b>Toevoegen</b></div>
				</div>
				
					<div class="tableRow">
						<div class="tableCell" style="width: 15%;"><input class="w3-input w3-border" type="text" name="pid" value=""></input></div>
						<div class="tableCell" style="width: 30%;"></input></div>
						<div class="tableCell" style="width: 15%;"><input class="w3-input w3-border" type="text" name="aantal" value=""></input></div>
						<div class="tableCell" style="width: 15%;"><input class="w3-input w3-border" type="text" name="prijs" value=""></input></div>
						<div class="tableCell" style="width: 15%;"></div>
						<div class="tableCell" style="width: 10%;"><input class="w3-btn w3-theme-l2" type="submit" name="submit" value="toepassen"></input></div>
					</div>
				</div></form>
				<form action="index.php?action=serviceOrders&subaction=6&uid='.$cid.'&soid='.$soid.'" method="post" class="w3-container">
						<p><label>Rekeningnummer voor terugbetaling (voer \'shop\' in voor een contante betaling)</label><input class="w3-input w3-border" type="text" name="rekeningnummer" value="'.$row['rekeningnummer'].'"></input></p>
						<p><input class="w3-btn w3-theme-l2" type="submit" name="submit" value="Rekeningnummer toevoegen"></p>
				</form>
				<br><div class="w3-container"><a href="index.php?action=serviceOrders&subaction=5&uid='.$cid.'&soid='.$soid.'" class="w3-btn w3-theme-l2">Order definitief maken</a>
				<a href="index.php?action=serviceOrders&subaction=4&uid='.$cid.'&soid='.$soid.'" class="w3-btn w3-theme-l2">Order verwijderen</a> 
				<a href="index.php?action=service&subaction=2&uid='.$cid.'" class="w3-btn w3-theme-l2">Terug naar het service center</a></div><br></div>';
		return $return;
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errormsg</p></div><br>
		<a href='index.php?action=service&subaction=2&uid=$cid' class='w3-btn w3-theme-l2'>Terug naar het service center</a>";
	}
	
}

if(isset($_GET['subaction'])){
	$subaction = $_GET['subaction'];
} else {
	$subaction = '';
}

if($Settings->_get('allowServiceOrders') != 0){
	if($User->isAdmin() OR $User->hasServiceRights()){
		switch($subaction){
	
			case 1:
				$Page->changePageTitle('Service order');
				$Page->addToBody(addServiceOrderProduct());
				break;
					
			case 2:
				$Page->changePageTitle('Service order');
				$Page->addToBody(editServiceOrderProduct());
				break;
					
			case 3:
				$Page->changePageTitle('Service order');
				$Page->addToBody(deleteServiceOrderProduct());
				break;
	
			case 4:
				$Page->changePageTitle('Service order');
				$Page->addToBody(deleteServiceOrder());
				break;
					
			case 5:
				$Page->changePageTitle('Service order');
				$Page->addToBody(finishServiceOrder());
				break;
	
			case 6:
				$Page->changePageTitle('Service order');
				$Page->addToBody(addBankNumberToOrder());
				break;
	
			default:
				$Page->changePageTitle('Service order');
				$Page->addToBody(serviceOrderForm());
				break;
		}
	} else {
		$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Onvoldoende rechten. Mogelijk bent u niet ingelogd.</p></div><br>");
	}
} else {
	$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Serviceorders zijn niet besschikbaar.</p></div><br>");
}
?>
