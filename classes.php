<?php

class user {
	private $name;
	private $surname;
	private $additive;
	private $loggedIn;
	private $sessionHash;
	private $username;
	private $costumerId;
	private $Admin;
	private $Employee;
	private $editProduct;
	private $service;
	private $adres;
	private $homeNumber;
	private $homeNumberAdditive;
	private $postcode;
	private $city;
	private $dateOfBirth;
	private $companyName;
	private $email;
	private $company;
	private $kvknummer;
	private $countryCode;
	private $rawRow;
	
	public function isLoggedIn(){
		return $this->loggedIn;
	}
	
	public function isAdmin(){
		if($this->Admin == 1){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function isEmployee(){
		if($this->Employee == 1){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function canEditProducts(){
		if($this->editProduct == 1){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function hasServiceRights(){
		if($this->service == 1){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function isCompany(){
		if($this->company == 1){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function _get($variabele){
		return $this->$variabele;
	}
	
	function __construct(){
		global $PDO;
		if(isset($_SESSION['userId'])){
			$result = $PDO->prepare("SELECT * FROM klanten WHERE id = :id AND username = :username");
			$result->execute(array(':id' => $_SESSION['userId'], ':username' => $_SESSION['userName']));
			$row = $result->fetch();
			if($row['login_ip'] == $_SESSION['login_ip'] && $row['login_ip'] == $_SERVER['REMOTE_ADDR'] && $_SESSION['sessionhash'] == $row['reghash']){
				//login is legitiem
				$this->name = $row['achternaam'];
				$this->surname = $row['voornaam'];
				$this->additive = $row['tussenvoegsel'];
				$this->loggedIn = TRUE;
				$this->username = $row['username'];
				$this->costumerId = $row['id'];
				$this->Admin = $row['admin'];
				$this->Employee = $row['employee'];
				$this->editProduct = $row['editproduct'];
				$this->service = $row['service'];
				$this->adres = $row['straatnaam'];
				$this->homeNumber = $row['huisnummer'];
				$this->homeNumberAdditive = $row['toevoeging'];
				$this->postcode = $row['postcode'];
				$this->city = $row['stad'];
				$this->dateOfBirth = $row['geboortedatum'];
				$this->companyName = $row['bedrijfsnaam'];
				$this->email = $row['email'];
				$this->kvknummer = $row['kvknummer'];
				$this->company = $row['bedrijf'];
				$this->sessionHash = $row['reghash'];
				$this->rawRow = $row;
			} else {
				//login klopt niet (hacking attempt of cookie is corrupt)
				$this->loggedIn = FALSE;
				session_destroy();
				session_start();
				$hashedtime = sha1(createSalt() . time());
				$this->sessionHash = $hashedtime;
				$_SESSION['sessionhash'] = $hashedtime;
			}
		} else {
			//gebruiker is niet ingelogd
			$this->loggedIn = FALSE;
			if(isset($_SESSION['sessionhash']) AND $_SESSION['sessionhash'] != NULL){
				$this->sessionHash = $_SESSION['sessionhash'];
			} else {
				$hashedtime = sha1(createSalt() . time());
				$this->sessionHash = $hashedtime;
				$_SESSION['sessionhash'] = $hashedtime;
			}
		}
	}
	
}

class cart {
	private $cartId;
	private $productsArray;
	private $productsNameArray;
	private $productsAmountArray;
	private $productPriceArray;
	private $productBtwArray;
	private $productServiceArray;
	private $total_value;
	private $productAvailableArray;
	private $productAvailableShipArray;
	private $numberOfItems;
	private $btwArray;
	private $discountCoupon;
	
	
	public function getDiscount(){
		global $PDO;
		if($this->discountCoupon != ''){
			global $PDO;
			$resultCoupon = $PDO->prepare("SELECT * FROM discountcoupons WHERE couponCode = :couponCode");
			$resultCoupon->execute(array(':couponCode' => $this->discountCoupon));
			$rowCoupon = $resultCoupon->fetch();
			if($rowCoupon['type'] == 1){
				$korting = (float) $this->total_value * ($rowCoupon['value']/100);
			} else {
				$korting = (float) $rowCoupon['value'];
			}
		
		}
		return $korting;
	}
	public function cartTextNumberOfItems(){
			return $this->numberOfItems;
	}
	
	public function showList($isCheckOut){
		global $myCart;
		global $Settings;
		$a = 0;
		$totalBTW = array(6 => 0, 21 => 0);
		$returnString = "<div class=\"w3-container w3-theme-l2\"><h3>";
		if($isCheckOut){
			$returnString = $returnString."Producten in bestelling";
		} else {
			$returnString = $returnString."Producten in winkelwagen";
		}
		$returnString = $returnString."</h3></div>
				<div class='table' style='padding: 10px;'>
		<div class='tableRow'><div class=tableCell>Status</div><div class=tableCell>Product omschrijving</div><div class=tableCell>Aantal</div><div class=tableCell>Stukprijs</div><div class=tableCell>Totaal</div></div>";
		if($this->productsArray[0] == ""){
			$returnString = $returnString."<div class='tableRow'><div class=tableCell></div><div class=tableCell>Er zitten nog geen producten in de winkelwagen</div><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div></div>";
		} else { 
			foreach ($this->productsArray as $product){
				$returnString = $returnString."<div class='tableRow'><div class=tableCell>";
				if($product != 0){
					$returnString = $returnString.inStock($this->productAvailableArray[$a], $this->productAvailableShipArray[$a], $this->productsAmountArray[$a], $this->productServiceArray[$a]);
				}
				$returnString = $returnString."</div><div class=tableCell>".$this->productsNameArray[$a]."</div>
					<div class=tableCell>";
				if(!$isCheckOut){
					$returnString = $returnString."<select name='hoeveelheid'onchange='if(options[selectedIndex].value){location = options[selectedIndex].value}'>
					<option selected value='index.php?action=showCart&changeCart=1&pId=".$product."&aantal=".$this->productsAmountArray[$a]."'>".$this->productsAmountArray[$a]."</option>";
					$b=1;
					while($b <= 100){
						$returnString = $returnString."<option value='index.php?action=showCart&changeCart=1&pId=".$product."&aantal=$b'>$b</option>";
						$b++;
					}
					$returnString = $returnString."</select> <a href='index.php?action=showCart&changeCart=1&pId=".$product."&delete=1'>verwijderen</a>";
				} else {
					$returnString = $returnString.$this->productsAmountArray[$a]." stuk(s)";
				}
				$btwTarief = ($this->productBtwArray[$a]/100)+1;
				$exlBTW = (float)$this->productPriceArray[$a]/$btwTarief;
				$totexlBTW = number_format($exlBTW, 2)*$this->productsAmountArray[$a];
				$returnString = $returnString."</div>
					<div class=tableCell><h4>&euro; ".number_format((float)$this->productPriceArray[$a], 2, ',', '.')."</h4>Excl ".$this->productBtwArray[$a]."% BTW: ".number_format($exlBTW, 2, ',', '.')."</div><div class=tableCell><h4>&euro; "
							.number_format((float)$this->productPriceArray[$a]*$this->productsAmountArray[$a], 2, ',', '.')."</h4>Excl:".number_format($totexlBTW, 2, ',', '.')."</div></div>";
				$totalBTW[$this->productBtwArray[$a]] = (float) $totalBTW[$this->productBtwArray[$a]] + ($totexlBTW );
				$a++;
			}
		}
		if($this->discountCoupon != ''){
			global $PDO;
			$resultCoupon = $PDO->prepare("SELECT * FROM discountcoupons WHERE couponCode = :couponCode");
			$resultCoupon->execute(array(':couponCode' => $this->discountCoupon));
			$rowCoupon = $resultCoupon->fetch();
			if($rowCoupon['type'] == 1){
				$korting = (float) $this->total_value * ($rowCoupon['value']/100);
				$kortingTekst = "Actiecode ".$rowCoupon['value']."% korting";
				//percentage
			} else {
				//bedrag
				$korting = (float) $rowCoupon['value'];
				$kortingTekst ="Actiecode: maximaal &euro;".number_format($korting, 2, ',', '.')." korting!";
			}
			if($this->productsArray[0] != ""){
				$percentageLaag = (float) $totalBTW[6] / ($totalBTW[6]  + $totalBTW[21]);
				$percentageHoog = (float) $totalBTW[21] / ($totalBTW[6]  + $totalBTW[21]);
			} else {
				$percentageLaag = 0;
				$percentageHoog = 0;
			}
			$kortingLaag = (float) $korting*$percentageLaag;
			$kortingHoog = (float) $korting*$percentageHoog;
			$kortingLaagExcl = (float) $kortingLaag/1.06;
			$kortingHoogExcl = (float) $kortingHoog/1.21;
			$this->total_value = $this->total_value - $korting;
			if($this->total_value < 0){
				$this->total_value = 0;
			}
			$totalBTW[6] = $totalBTW[6] -  $kortingLaagExcl;
			if($totalBTW[6] < 0){
				$totalBTW[6] = 0;
			}
			$totalBTW[21] = $totalBTW[21] - $kortingHoogExcl;
			if($totalBTW[21]  < 0){
				$totalBTW[21] = 0;
			}
			$returnString = $returnString."
				<div class='tableRow'><div class=tableCell>&nbsp;</div><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div></div>
				<div class='tableRow'><div class=tableCell></div><div class=tableCell>$kortingTekst</div><div class=tableCell></div><div class=tableCell></div><div class=tableCell>- &euro;".number_format($korting, 2, ',', '.')."</div></div>
				<div class='tableRow'><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div><div class=tableCell>Korting op 6% BTW</div><div class=tableCell>- ".number_format($kortingLaagExcl, 2, ',', '.')."</div></div>
				<div class='tableRow'><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div><div class=tableCell>Korting op 21% BTW</div><div class=tableCell>- ".number_format($kortingHoogExcl, 2, ',', '.')."</div></div>";
		}
		$returnString = $returnString."
			<div class='tableRow'><div class=tableCell>&nbsp;</div><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div></div>
			<div class='tableRow'><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div><div class=tableCell>Totaal 6% BTW: </div><div class=tableCell>  &euro; ".number_format($totalBTW[6], 2, ',', '.')."</div></div>
			<div class='tableRow'><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div><div class=tableCell>Totaal 21% BTW: </div><div class=tableCell>  &euro; ".number_format($totalBTW[21], 2, ',', '.')."</div></div>
			<div class='tableRow'><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div><div class=tableCell><h2>Totaalprijs:</h2></div><div class=tableCell><h2> &euro; ".number_format((float)$this->total_value, 2, ',', '.')."</h2></div></div>";
		if(!$isCheckOut && $this->total_value >= 0){
			$returnString = $returnString."<div class='tableRow'><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div><div class=tableCell><a class='w3-btn w3-xlarge w3-round-large w3-theme-l2' href='index.php?action=placeOrder'>Bestellen</a></div></div>";
		} elseif( $this->total_value < 0){
			$returnString = $returnString."<div class='tableRow'><div class=tableCell></div><div class=tableCell>Voeg meer producten toe om een bestelling te plaatsen</div><div class=tableCell></div><div class=tableCell></div><div class=tableCell></div></div>";	
		}
		$returnString = $returnString."</div>";
		$this->btwArray = $totalBTW;
		if($Settings->_get('allowPromotions') != 0){
			$returnString = $returnString."<div>
				<form action='index.php?action=addDiscountCoupon' method='post' class='w3-container'>
						<p>Voeg een kortingscode toe aan dit winkelmandje. Geen kortingscode? Houd onze nieuwsbrief in de gaten voor meer kortingsacties! Let op kortingscodes zijn alleen te gebruiken gedurende de actieperriode. U kunt slechts &#233;&#233;n kortingscode per winkelmandje gebruiken.</p>
						<p><label>Kortingscode:</label><input class='w3-input w3-border' type='text' name='discountCoupon' value=''></p>
						<p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Kortingscode toevoegen'></p>
						</form>
						</div>"; 
		}
		return $returnString;
	}
	
	public function _get($variabele){
		return $this->$variabele;
	}
	
	public function removeDiscountCoupon(){
		global $PDO;
		global $Page;
		$update = $PDO->prepare("UPDATE winkelwagen SET discountCoupon = :couponCode WHERE id = :id");
		$update->execute(array(':couponCode' => '', ':id' => $this->cartId));
		$this->discountCoupon = '';
	}
	
	public function addDiscountCoupon($couponCode){
		global $PDO;
		global $Page;
		$time = time();
		$result = $PDO->prepare("SELECT * FROM `discountcoupons` WHERE `couponCode` = :couponCode AND :time BETWEEN `startTime` AND `stopTime`");
		$result->execute(array(':couponCode' => $couponCode, ':time' => time()));
		$row = $result->fetch();
		if($row['id'] != ''){
			$update = $PDO->prepare("UPDATE winkelwagen SET discountCoupon = :couponCode WHERE id = :id");
			$update->execute(array(':couponCode' => $couponCode, ':id' => $this->cartId));
			$this->discountCoupon = $couponCode;
			$Page->addToBody("<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt</h3><p>De kortingscoupon is geactiveerd.</p></div><br>");
		} else {
			$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Ongeldige of verlopen actiecode.</p></div><br>");
		}
	}
	
	public function addShippingFee($amount){
		$this->productsArray[$this->numberOfItems] = '0';
		$this->productsNameArray[$this->numberOfItems] = 'Verzend en betalingskosten';
		$this->productsAmountArray[$this->numberOfItems] = '1';
		$this->productPriceArray[$this->numberOfItems] = $amount;
		$this->productBtwArray[$this->numberOfItems] = '21';
		$this->total_value = (float) $this->total_value + $amount;
		return TRUE;
	}
	
	function __construct(){
		global $PDO;
		global $User; 
		$result = $PDO->prepare("SELECT * FROM winkelwagen WHERE sessionId = :sessionId");
		$result->execute(array(':sessionId' => $User->_get('sessionHash')));
		$row = $result->fetch();
		if($row['id'] == NULL ){
			//winkelwagen bestaat niet
			$insert = $PDO->prepare("INSERT INTO winkelwagen (lastEditTime, userId, sessionId) VALUES (:lastEditTime, :userId, :sessionId)");
			$insert->execute(array(':lastEditTime' => time(), ':userId' => $User->_get('costumerId'), ':sessionId' => $User->_get('sessionHash')));
			$this->numberOfItems = 0;
		} else if($row['lastEditTime']+172800 < time()){
			//winkelwagen is te oud update de winkelwagen en haal de producten eruit
			$update = $PDO->prepare("UPDATE winkelwagen SET lastEditTime = :lastEditTime, userId = :userId WHERE sessionId = :sessionId");
			$update->execute(array(':lastEditTime' => time(), ':userId' => $User->_get('costumerId'), ':sessionId' => $User->_get('sessionHash')));
			$delete = $PDO->prepare("DELETE FROM winkelwagen_producten WHERE cartId = :cartId");
			$delete->execute(array(':cartId' => $row['id']));
			$this->numberOfItems = 0;
		} else {
			//winkelwagen is goed haal de producten op
			$update = $PDO->prepare("UPDATE winkelwagen SET lastEditTime = :lastEditTime, userId = :userId WHERE sessionId = :sessionId");
			$update->execute(array(':lastEditTime' => time(), ':userId' => $User->_get('costumerId'), ':sessionId' => $User->_get('sessionHash')));
			$resultProduct = $PDO->prepare("SELECT winkelwagen_producten.*, producten.service, producten.productNaam, producten.prijs, producten.voorraad, producten.voorraad_leverancier, producten.btwtarief FROM winkelwagen_producten LEFT JOIN producten on productId = producten.id WHERE cartId = :cartId");
			$resultProduct->execute(array(':cartId' => $row['id']));
			$a = 0;
			$this->cartId = $row['id'];
			$this->discountCoupon = $row['discountCoupon'];
			foreach ($resultProduct as $rowProduct){
				$this->productsArray[$a] = $rowProduct['productId'];
				$this->productsNameArray[$a] = $rowProduct['productNaam'];
				$this->productsAmountArray[$a] = $rowProduct['aantal'];
				$this->productPriceArray[$a] = $rowProduct['prijs'];
				$this->productBtwArray[$a] = $rowProduct['btwtarief'];
				$this->total_value = (float) $this->total_value + ($rowProduct['prijs'] * $rowProduct['aantal']);
				$this->productAvailableArray[$a] = $rowProduct['voorraad'];
				$this->productAvailableShipArray[$a] = $rowProduct['voorraad_leverancier'];
				$this->productServiceArray[$a] = $rowProduct['service'];
				$a++;
			}
			$this->numberOfItems = $a;
		}
		
	}
	
}

class page {
	private $pageBody;
	private $pageTitle;
	private $openMenu;
	
	public function catIsOpen($id){
		$this->openMenu[$id] = 1;
	}
	
	public function openCategory($id){
		if(isset($this->openMenu[$id])){
			if($this->openMenu[$id] == 1){
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	public function addToBody($content){
		$this->pageBody = $this->pageBody.$content;
	}
	
	public function clearBody(){
		$this->pageBody = '';
	}
	
	public function changePageTitle($title){
		$this->pageTitle = $title;
	}
	
	public function buildPage(){
		global $User;
		global $myCart;
		global $PDO;
		global $Settings;
		global $printThisPage;
		global $ErrorHandler;
		$return = '';
		include("header.php");
		include 'menu.php';
		$return .= "<div id='content'>".$this->pageBody."</div>";
		include 'footer.php';
		echo $return;
	}
	
	public function getPageTitle(){
		return $this->pageTitle;
	}
	
	function __construct(){
		$this->pageTitle = "eShop";
	}
}

class PDOEx extends PDO
{
	private $queryCount = 0;

	public function query($query)
	{
		// Increment the counter.
		++$this->queryCount;

		// Run the query.
		return parent::query($query);
	}

	public function exec($statement)
	{
		// Increment the counter.
		++$this->queryCount;

		// Execute the statement.
		return parent::exec($statement);
	}

	public function GetCount()
	{
		return $this->queryCount;
	}
}


class countries { 
	private $shipTo;
	private $countries;
	private $extraCharge;
	
	public function nameLandCode($landCode){
		if($landCode != NULL){
			return $this->countries[$landCode];
		} else {
			return FALSE;
		}
	}
	
	public function checkLandCode($landCode){
		$countries = $this->countries;
		if($countries[$landCode] != NULL AND $this->shipTo[$landCode] == 1){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	public function hasShippingFee($landCode){
		if($this->extraCharge[$landCode] == 0){
			return FALSE;
		} else {
			return $this->extraCharge[$landCode];
		}
	}
	
	public function landenLijst($landCode){
		$countries = $this->countries;
		$return = "<select class='w3-select w3-border' name='land'>";
		if($landCode != NULL){
			$return = $return."<option value='$landCode'>".$countries[$landCode];
				if($this->extraCharge[$landCode] != 0){
					$return = $return."(+ &euro; ".number_format((float)$this->extraCharge[$landCode], 2, ',', '.')." )";
						}
				$return = $return."</option>";
		} else {
			$return = $return."<option value='NL'>Nederland</option>";
		}
		foreach ($countries as $countryCode => $countryName){
			if($this->shipTo[$countryCode] == 1){
				$return = $return."<option value='$countryCode'>$countryName";
				if($this->extraCharge[$countryCode] != 0){
					$return = $return." (+ &euro; ".number_format((float)$this->extraCharge[$countryCode], 2, ',', '.')." )";
						}
				$return = $return."</option>";
			}
		}
		$return = $return."</select>";
		return $return;
	}
	
	function __construct(){
		global $PDO;
		$result = $PDO->query("SELECT * FROM countries");
		foreach ($result as $row){
			$this->countries[$row['countryCode']] = $row['name'];
			$this->shipTo[$row['countryCode']] = $row['ship'];
			$this->extraCharge[$row['countryCode']] = $row['charge'];
		}
	}
}

class siteSettings{
	private $siteName;
	private $siteDomain;
	private $mollieApiKey;
	private $fullPath;
	private $remboursfee;
	private $rekeningnummer;
	private $productLimit;
	private $catLimit;
	private $allowEmployees;
	private $allowReviews;
	private $themeLevel;
	private $allowServiceOrders;
	private $allowServiceCenter;
	private $supplyTracking;
	private $productHistory;
	private $allowPromotions;
	
	public function _get($variabele){
		return $this->$variabele;
	}
	
	function __construct(){
		global $PDO;
		$result = $PDO->query("SELECT * FROM shopsettings ORDER BY id DESC limit 0, 1");
		$row = $result->fetch();
		$this->siteName = $row['shopname'];
		$this->siteDomain = $row['shopdomain'];
		$this->mollieApiKey = $row['mollieapikey'];
		$this->fullPath = $row['fullpath'];
		$this->remboursfee = $row['remboursfee'];
		$this->rekeningnummer = $row['rekeningnummer'];
		$this->productLimit = $row['productLimit'];
		$this->catLimit = $row['catLimit'];
		$this->allowEmployees = $row['allowEmployees'];
		$this->allowReviews = $row['allowReviews'];
		$this->themeLevel = $row['themeLevel'];
		$this->allowServiceOrders = $row['allowServiceOrders'];
		$this->allowServiceCenter = $row['allowServiceCenter'];
		$this->supplyTracking = $row['supplyTracking'];
		$this->productHistory = $row['productHistory'];
		$this->allowPromotions = $row['allowPromotions'];
	}
}

class errorHandeling{
	private $errorMessages;
	private $debugMessages;
	private $triggerd;
	
	public function dumpError($message){
		$this->errorMessages = $this->errorMessages.$message;
		$this->triggerd = TRUE;
	}
	
	public function dumpDebug($message){
		$this->debugMessages = $this->debugMessages.$message;
	}
	
	public function getDebugMessages(){
		return $this->debugMessages;
	}
	public function getErrorMessages(){
		return $this->errorMessages;
	}
	
	function __construct(){
		$this->triggerd = FALSE;
	}
}

?>