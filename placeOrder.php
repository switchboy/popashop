<?php

function registerOrder($voornaam, $achternaam, $tussenvoegsel, $adres, $homeNumber, $homeNumberAdditive, $postcode, $city, $landCode, $betaalMethode, $email){
	global $Settings;
	global $Landen;
	global $myCart;
	global $PDO;
	global $User;
	global $Settings;
	$error = FALSE;
	$errorcode;
	$rekeningnummer = $Settings->_get('rekeningnummer');
	$bedrijfsnaam = $Settings->_get('siteName');
	$remboursFee = $Settings->_get('remboursfee');
	$sitedomain = $Settings->_get('siteDomain');
	//VVVVVVVVVVVVVVVVVVVVVVVVVVV INPUT CHECKING VVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV
	if($User->isLoggedIn()){
		$userId = $User->_get('costumerId');
	} else {
		$userId = '0';
	}
	if($voornaam == NULL){
		$error = TRUE;
		$errorcode = $errorcode."Geen voornaam ingevoerd.<br>";
	}else if(!ctype_alpha(str_replace(array(' ', "'", '-'), '', $voornaam))){
		$error = TRUE;
		$errorcode = $errorcode."Een naam mag alleen uit letters bestaan.<br>";
	}
	
	if($achternaam == NULL){
		$error = TRUE;
		$errorcode = $errorcode."Geen achternaam ingevoerd.<br>";
	}else if(!ctype_alpha(str_replace(array(' ', "'", '-'), '', $achternaam))){
		$error = TRUE;
		$errorcode = $errorcode."Een naam mag alleen uit letters bestaan.<br>";
	}
	
	if($adres == NULL){
		$error = TRUE;
		$errorcode = $errorcode."Geen adres ingevoerd.<br>";
	}else if(!ctype_alpha(str_replace(array(' ', "'", '-', '.'), '', $adres))){
		$error = TRUE;
		$errorcode = $errorcode."Een straatnaam mag alleen uit een combinatie van letters, -, . en ' bestaan.<br>";
	}
	
	if($homeNumber == NULL){
		$error = TRUE;
		$errorcode = $errorcode."Geen huisnummer ingevoerd.<br>";
	} else if(!is_numeric($homeNumber)){
		$error = TRUE;
		$errorcode = $errorcode."Het huisnummer mag alleen cijfers bevatten.<br>";
	}
	
	if($postcode == NULL){
		$error = TRUE;
		$errorcode = $errorcode."Geen postcode ingevuld.<br>";
	} else  if(!checkPostcode($postcode)){
		$error = TRUE;
		$errorcode = $errorcode."Ongeldige postcode ingevuld.<br>";
	} else {
		$postcode = checkPostcode($postcode);
	}
		
	if($city == NULL){
		$error = TRUE;
		$errorcode = $errorcode."Geen stad ingevuld.<br>";
	}else if(!ctype_alpha(str_replace(array(' ', "'", '-'), '', $city))){
		$error = TRUE;
		$errorcode = $errorcode."Een stadsnaam mag alleen uit letters, - en ' bestaan.<br>";
	}
	
	if($landCode == NULL){
		$error = TRUE;
		$errorcode = $errorcode."Geen land geselecteerd.<br>";
	} else if(!$Landen->checkLandCode($landCode)){
		$error = TRUE;
		$errorcode = $errorcode."Geen land geselecteerd.<br>";
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errorCode = $errorCode."Onjuist of leeg email adres ingevoerd.<br>";
		$error = TRUE;
	}
	if($betaalMethode == NULL){
		$error = TRUE;
		$errorcode = $errorcode."Geen betaalmethode geselecteerd.<br>";
	}
	//^^^^^^^^^^^^^^^^^^ INPUT CHECKING ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
	
	if(!$error){
		$shippingPrice = (float)0.00;
		if($Landen->hasShippingFee($landCode)){
			$shippingPrice = (float)$shippingPrice+$Landen->hasShippingFee($landCode);
		} 
		if($betaalMethode == '2'){
			$shippingPrice = (float)$shippingPrice+$Settings->_get(remboursfee);
		}
		$myCart->addShippingFee($shippingPrice);
		$prijs = $myCart->_get('total_value')-$myCart->getDiscount();
		$return = "<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:66%\"><div class=\"w3-center w3-text-white\">66%</div></div></div>";
		$insert = $PDO->prepare("INSERT INTO orders (discountCoupon, userId, email, prijs, voornaam, achternaam, tussenvoegsel, straat, huisnummer, toevoeging, postcode, stad, landCode, betaalmethode, besteldatum) VALUES(:discountCoupon, :userId, :email, :prijs,:voornaam, :achternaam, :tussenvoegsel, :straat, :huisnummer, :toevoeging, :postcode, :stad, :landCode, :betaalmethode, :besteldatum)");
		$discountcoupon = $myCart->_get('discountCoupon');
		if($discountcoupon == NULL){
			$discountcoupon = '';
		}
		$insert->execute(array(':discountCoupon' => $discountcoupon, ':userId'  => $userId, ':email' => $email, ':prijs' => $prijs,':voornaam'  => $voornaam, ':achternaam'  => $achternaam, ':tussenvoegsel'  => $tussenvoegsel,':straat'  => $adres, ':huisnummer' => $homeNumber, ':toevoeging' => $homeNumberAdditive, ':postcode' => $postcode, ':stad' => $city, ':landCode' => $landCode, ':betaalmethode' => $betaalMethode, ':besteldatum' => time()));
		$orderId = $PDO->lastInsertId();
		$insert->closeCursor();
		$return = $return."<br><div id='bestellingAf' class='w3-card-4'>".$myCart->showList(TRUE);
		$emailtext = 
"Geachte klant,\r\n
U heeft een bestelling geplaatst op $bedrijfsnaam, deze bestelling bevat de volgende producten:\r\n";
		foreach ($myCart->_get('productsArray') as $index => $productId){
			$price = $myCart->_get('productPriceArray')[$index];
			$amount = $myCart->_get('productsAmountArray')[$index];
			$subtotal = $price*$amount;
			$productname = $myCart->_get('productsNameArray')[$index];
			$emailtext = $emailtext."$productname $amount stuk(s) stukprijs EUR $price totaalprijs: EUR $subtotal \r\n";
			$insertProducts = $PDO->prepare("INSERT INTO orderproducten (orderid, productid, prijs, aantal) VALUES (:orderid, :productid, :prijs, :aantal)");
			$insertProducts->execute(array(':orderid' => $orderId, ':productid' => $productId, ':prijs' => $price, ':aantal' => $amount));
			$insertProducts->closeCursor();
		}
		$emailtext = $emailtext.
"Subtotaal excl 6% BTW EUR ".$myCart->_get('btwArray')['6']." 
Subtotaal excl 21% BTW EUR ".$myCart->_get('btwArray')['21']."
Totaal bedrag EUR ".$myCart->_get('total_value')."\r\n
Namens het team van $bedrijfsnaam bedankt voor uw bestelling. Voor vragen kunt u terecht bij onze klantenservice. \r\n 
Met vriendelijke groet, \r\n 
$bedrijfsnaam";
		$updateEmailtext = $PDO->prepare("UPDATE orders SET emailtext = :emailtext WHERE id = :id");
		$updateEmailtext->execute(array(':emailtext' => $emailtext, ':id' => $orderId));
	} else {
		$return = "<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:33%\"><div class=\"w3-center w3-text-white\">33%</div></div></div><br>".
				"<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$errorcode."</p></div>".
				orderForm($voornaam, $achternaam, $tussenvoegsel, $adres, $homeNumber, $homeNumberAdditive, $postcode, $city, $landCode, 1, $email);
	}
	$return = $return."<p style='text-align: right;'><a href='#' class='w3-btn w3-theme-l2' onclick=\"document.getElementById('bestellingAf').style.display = 'none'; document.getElementById('bestellingAanpassen').style.display = 'block';\">Bestelling aanpassen</a><a href='index.php?action=placeOrder&step=4&orderId=$orderId' class='w3-btn w3-xlarge w3-round-large w3-theme-l2'>Bestelling afronden</a></p></div>";
	$return = $return."<div id='bestellingAanpassen' style='display: none'>".orderForm($voornaam, $achternaam, $tussenvoegsel, $adres, $homeNumber, $homeNumberAdditive, $postcode, $city, $landCode, 1, $email)."</div>";
	return $return;
}


function finalStepOfCheckout($orderId){
	global $PDO;
	global $myCart;
	global $Settings;
	$rekeningnummer = $Settings->_get('rekeningnummer');
	$bedrijfsnaam = $Settings->_get('siteName');
	$remboursFee = $Settings->_get('remboursfee');
	$sitedomain = $Settings->_get('siteDomain');
	$error = FALSE;
	$result = $PDO->prepare("SELECT * FROM orders WHERE id = :id AND costumercomfirmed = '0'");
	$result->execute(array(':id' => $orderId));
	$row = $result->fetch();
	if($row['id'] == NULL){
		$error = TRUE;
	}
	$email = $row['email'];
	$emailtext = $row['emailtext'];
	$totalPrice = $row['prijs'];
	$betaalMethode = $row['betaalmethode'];
	
	if(!$error){
		$updateOrder = $PDO->prepare("UPDATE orders SET costumercomfirmed = '1' WHERE id = :id");
		$updateOrder->execute(array(':id' => $orderId));
		if($betaalMethode == '1'){
			//Overboeking
			emptyCart($myCart->_get('cartId'));
			$return = "<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:100%\"><div class=\"w3-center w3-text-white\">100%</div></div></div><br>".
					"<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Bestelling geplaatst</h3><p>Uw bestelling is geplaatst. Als betalingoptie heeft u gekozen voor overboeking.<br>Het verschildigde bedrag van &euro; $totalPrice dient te worden overgemaakt op rekeningnummer: $rekeningnummer te name van $bedrijfsnaam ondervermelding van 'ordernummer: $orderId'.<br>Na ontvanging en bevestiging van betaling zullen wij zo snel mogelijk tot levering overgaan.</p></div>".
					orderPlaced();
					sendEmail($email, 'no-reply@'.$sitedomain, $emailtext, 'Uw bestelling met order-id: '.$orderId);
			return $return;
		} else if($betaalMethode == '2'){
			//rembours betaling
			emptyCart($myCart->_get('cartId'));
			$return = "<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:100%\"><div class=\"w3-center w3-text-white\">100%</div></div></div><br>".
					"<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Bestelling geplaatst</h3><p>Uw bestelling is geplaatst. Als betalingoptie heeft u gekozen voor rembourszending.<br>Het verschildigde bedrag van &euro; $totalPrice dient bij aflevering te worden voldaan.</p></div>".
					orderPlaced();
					sendEmail($email, 'no-reply@'.$sitedomain, $emailtext, 'Uw bestelling met order-id: '.$orderId);
			return $return;
		} else {
			//mollie betaling
			$_SESSION['emailtext'] = $emailtext;
			$_SESSION['email'] = $email;
			try
			{
				include "initialize.php";
				$protocol = isset($_SERVER['HTTPS']) && strcasecmp('off', $_SERVER['HTTPS']) !== 0 ? "https" : "http";
				$hostname = $_SERVER['HTTP_HOST'];
				$path     = dirname(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF']);
				$payment = $mollie->payments->create(array(
						"amount"       => $totalPrice,
						"description"  => "Betaling voor order-nr: $orderId",
						"redirectUrl"  => "{$protocol}://{$hostname}{$path}/index.php?action=placeOrder&step=2&orderId={$orderId}",
						"webhookUrl"   => "{$protocol}://{$hostname}{$path}/index.php?action=placeOrder&step=3",
						"metadata"     => array("orderId" => $orderId)
				));
				header("Location: " . $payment->getPaymentUrl());
				return;
			}
			catch (Mollie_API_Exception $e)
			{
				$deleteOrder = $PDO->prepare("DELETE FROM orders WHERE id = :id");
				$deleteOrder->execute(array(':id' => $orderId));
				$deleteOrderProducts = $PDO->prepare("DELETE FROM orderproducten WHERE orderid = :id");
				$deleteOrderProducts->execute(array(':id' => $orderId));
				return"<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:33%\"><div class=\"w3-center w3-text-white\">33%</div></div></div><br>".
						"<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>API call failed: " . htmlspecialchars($e->getMessage())."</p><p>Uit veiligheidsoverweging is het bestelproces gestopt. De producten bevinden zich nog wel in uw winkelwagen. Probeert u het alsublieft opnieuw.</p></div>";
			}
				
		}
	} else {
		return"<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:33%\"><div class=\"w3-center w3-text-white\">33%</div></div></div><br>".
				"<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Ongeldig order-nr.</p></div>";
	}
}

function orderPlaced(){
	return "<h3>Hartelijk bedankt voor uw bestelling!</h3><p>Een bevestiging van uw bestelling is verzonden naar het door u opgegeven e-mail adres. Bij vragen kunt u contact opnemen met onze klantenservice.</p>
			<p style='text-align: right'><a class='w3-btn w3-xlarge w3-round-large w3-theme-l2' href='index.php'>Verder winkelen</a></p>";
}

function orderForm($voornaam, $achternaam, $tussenvoegsel, $adres, $homeNumber, $homeNumberAdditive, $postcode, $city, $landCode, $percentageBarShown, $email){
	global $myCart;
	global $User;
	global $Landen;
	if($voornaam == NULL){
		$voornaam = $User->_get('surname');
	}
	if($achternaam == NULL){
		$achternaam = $User->_get('name');
	}
	if($tussenvoegsel == NULL){
		$tussenvoegsel = $User->_get('additive');
	}
	if($adres == NULL){
		$adres = $User->_get('adres');
	} 
	if($homeNumber == NULL){
		$homeNumber = $User->_get('homeNumber');
	}
	if($homeNumberAdditive == NULL){
		$homeNumberAdditive = $User->_get('homeNumberAdditive');
	}
	if($postcode == NULL){
		$postcode = $User->_get('postcode');
	}
	if($city == NULL){
		$city = $User->_get('city');
	}
	if($landCode == NULL){
		$landCode = $User->_get('countryCode');
	}
	if($email == NULL){
		$email = $User->_get('email');
	}
	if($myCart->cartTextNumberOfItems() != NULL && $myCart->_get('total_value') >= 0){
		if($percentageBarShown == NULL){
			$return = "<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:33%\"><div class=\"w3-center w3-text-white\">33%</div></div></div>";
		} else {
			$return = '';
		}
		$return = $return."<br><div class='w3-card-4'>".$myCart->showList(TRUE)."</div><br>".
		"<div class='w3-card-4'>
		<div class=\"w3-container w3-theme-l2\">
			<h3>Bestelling opties</h3>
		</div>
		<form class='w3-container' action='index.php?action=placeOrder&step=1'method='POST'>
			<h4>Aflever adres:</h4>
			<p><label>E-mailadres voor correspondentie:</label><input class='w3-input  w3-border' type='text' name='email' value='$email'></input></p>
			<p><label>Voornaam:</label><input class='w3-input w3-border' type='text' name='voornaam' value='$voornaam'></input></p>
			<p><label>Achternaam:</label><input class='w3-input w3-border' type='text' name='achternaam' value='$achternaam'></input></p>
			<p><label>tussenvoegsel:</label><input class='w3-input w3-border' type='text' name='tussenvoegsel' value='$tussenvoegsel'></input></p>
			<p><label>Straatnaam:</label><input class='w3-input w3-border' type='text' name='straatnaam' value='$adres'></input></p>
			<p><label>Huisnummer:</label><input class='w3-input w3-border'type='text' name='huisnummer' value='$homeNumber'></input></p>
			<p><label>Toevoeging:</label><input class='w3-input w3-border'type='text' name='toevoeging' value='$homeNumberAdditive'></input></p>
			<p><label>Postcode:</label><input class='w3-input w3-border' type='text' name='postcode' value='$postcode'></input></p>
			<p><label>Stad:</label><input class='w3-input w3-border' type='text' name='stad' value='$city'></input></p>
			<p><label>Land:</label>".$Landen->landenLijst($landCode)."</p>
			<div class='w3-container w3-yellow w3-card-8'>Let op: Voor bestellingen in het buitenland worden extra kosten in rekening gebracht.<br>Bij verzending buiten de EU kunnen er eventueel importheffingen door uw overheid in rekening worden gebracht.</div>
			<h4>Betalingsmethode:</h4>
			<p><select class='w3-select w3-border' name='betaalMethode'>
 				<option value='' disabled selected>Kies een betalingsoptie</option>
  				<option value='1'>Overboeking</option>";
		if($myCart->_get('total_value') > 0){
  			$return .= "<option value='2'>Rembours</option><option value='3'>iDeal/Creditcard/Paypall betaling</option>";
		}
		$return .= "</select></p>
			<div class='w3-container w3-yellow w3-card-8'>Let op: Voor rembours bestellingen worden extra kosten in rekening gebracht.</div>
			<p style='text-align: right;'><input class='w3-btn w3-xlarge w3-round-large w3-theme-l2' type='submit' name='submit' value='Bestelling plaatsen'></p>
		</div>";
		return $return;
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Uw winkelwagen is leeg, of heeft een totale waarde van 0 euro.<br>Plaats eerst producten in uw winkelwagen voor u een order plaatst.</p></div><br>".
		imageSlider().
		showPromotions('0', '3');
	}
}

function mollieWebhook(){
	global $PDO;
	global $Settings; 
	try{
		include "initialize.php";
		if (!empty($_GET['testByMollie'])){
			die('OK');
		}
		$payment  = $mollie->payments->get($_POST["id"]);
		$orderId = $payment->metadata->orderId;
		mollie_database_write($orderId, $payment->status);
		if ($payment->isPaid() == TRUE){
			$write = $PDO->prepare("UPDATE orders SET voldaan = 1 WHERE id = :orderId");
			$write->execute(array(':orderId' => $orderId));
			checkOrdersForCompleteness();
		}
		elseif ($payment->isOpen() == FALSE){
			$write = $PDO->prepare("UPDATE orders SET aborted = 1 WHERE id = :orderId");
			$write->execute(array(':orderId' => $orderId));
		}
	}
	catch (Mollie_API_Exception $e){
		echo "API call failed: " . htmlspecialchars($e->getMessage());
	}
	return ;
}

function mollie_database_write ($orderId, $status){
	global $PDO;
	$write = $PDO->prepare("UPDATE orders SET mollie = :status WHERE id = :orderId");
	$write->execute(array(':status' => $status, ':orderId' => $orderId));
	return;
}

function mollieReturnPage($orderId){
	global $PDO;
	global $myCart;
	$result = $PDO->prepare("SELECT * FROM orders WHERE id = :orderId");
	$result->execute(array(':orderId' => $orderId));
	$row = $result->fetch();
	$emailtext = $_SESSION['emailtext'];
	$email = $_SESSION['email'];
	$sitedomain = 'daritek.nl';
	if($row['voldaan'] == '1' AND $row['mollie'] == 'paid'){
		sendEmail($email, 'no-reply@'.$sitedomain, $emailtext, 'Uw bestelling met order-id: '.$orderId);
		emptyCart($myCart->_get(cartId));
		return "<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:100%\"><div class=\"w3-center w3-text-white\">100%</div></div></div><br>".
					"<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Bestelling geplaatst</h3><p>Uw bestelling is met succes geplaatst, tevens is uw betaling verwerkt.</p></div>".orderPlaced();
	} else if($row['aborted'] == '1'){
		return "<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:100%\"><div class=\"w3-center w3-text-white\">100%</div></div></div><br>".
				"<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Bestelling mislukt!</h3><p>Uw betaling is helaas mislukt. Hiermee is de bestelling geannuleerd. Start het bestelprocces opnieuw.</p></div>";			
	} else if($row['mollie'] == 'cancelled' OR 'failed' OR 'expired'){
		return "<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:100%\"><div class=\"w3-center w3-text-white\">100%</div></div></div><br>".
				"<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Bestelling mislukt!</h3><p>Uw betaling is helaas mislukt. Hiermee is de bestelling geannuleerd. Start het bestelprocces opnieuw.</p></div>";		
	} else {
		sendEmail($email, 'no-reply@'.$sitedomain, $emailtext, 'Uw bestelling met order-id: '.$orderId);
		emptyCart($myCart->_get(cartId));
		return "<div class=\"w3-progress-container\"><div class=\"w3-progressbar w3-theme-l2\" style=\"width:100%\"><div class=\"w3-center w3-text-white\">100%</div></div></div><br>".
				"<div class=\"w3-container w3-yellow\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Bestelling geplaatst</h3><p>Uw bestelling is geplaatst, bij verwerking van uw betaling zal deze worden verzonden.</p></div>".orderPlaced();;
	}
}

$Page->changePageTitle('Bestelling plaatsen');
if(isset($_GET['step'])){
	$currentStep = $_GET['step'];
} else {
	$currentStep = '';
}
switch ($currentStep){
	case 1:
		$Page->addToBody(registerOrder($_POST['voornaam'], $_POST['achternaam'], $_POST['tussenvoegsel'], $_POST['straatnaam'], $_POST['huisnummer'], $_POST['toevoeging'], $_POST['postcode'], $_POST['stad'], $_POST['land'], $_POST['betaalMethode'], $_POST['email']));
		break;
		
	case 2:
		$Page->addToBody(mollieReturnPage($_GET['orderId']));
		break;
	
	case 3:
		mollieWebhook();
		break;
		
	case 4:
		$Page->addToBody(finalStepOfCheckout($_GET['orderId']));
		break;
		
	default:
		$Page->addToBody(orderForm(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL));
		break;
}

?>