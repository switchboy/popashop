<?php
//error_reporting(E_ERROR | E_WARNING | E_PARSE);

function connectPDO($dbserver, $dbname, $dbusername, $dbpassword){
	try{
		$db = new PDOEx("mysql:host=".$dbserver.";dbname=".$dbname.";charset=UTF8", $dbusername, $dbpassword);
		$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
	}
	catch(PDOException $e) {
		die("De verbinding met de database kon niet worden gemaakt!");
	}
	return $db;
}

function createSalt()
{
	$string = md5(uniqid(rand(), true));
	return substr($string, 0, 3);
}

function productImage($pId, $height, $width, $list){
	global $PDO;
	$result = $PDO->prepare("SELECT * FROM `productplaatjes` WHERE productId = :id");
	$result->execute(array(':id' => $pId));
	$a = 0;
	$b = 2;
	$other = '';
	foreach($result as $row){
		//do stuff
		if($a == 0){
			if($list == 0){
				$first = "<img src='".$row['url']."' height='$height' width='$width' style='cursor:zoom-in' alt='product plaatje' onclick=\"document.getElementById('modal01').style.display='block'\"><br>
				<div id='modal01' class='w3-modal' onclick=\"document.getElementById('modal01').style.display='none'\">
				<span class='w3-closebtn w3-hover-red w3-container w3-padding-16 w3-display-topright' onclick=\"document.getElementById('modal01').style.display='none'\">&times;</span>
				<div class='w3-modal-content w3-animate-zoom'>
				<img src='".$row['url']."' style='width:100%' alt='product image'>
  					</div>
				</div>";
			} else {
				$first = "<a href='index.php?action=showProduct&pId=$pId'><img src='".$row['url']."' height='$height' width='$width' alt='product plaatje'></a>";
			}
		}
		if($list == 0){
			$other = $other."<img src='".$row['url']."' height='50' width='50' style='cursor:zoom-in' alt='product plaatje' onclick=\"document.getElementById('modal0$b').style.display='block'\">
				<div id='modal0$b' class='w3-modal' onclick=\"document.getElementById('modal0$b').style.display='none'\">
  					<span class='w3-closebtn w3-hover-red w3-container w3-padding-16 w3-display-topright' onclick=\"document.getElementById('modal0$b').style.display='none'\">&times;</span>
  					<div class='w3-modal-content w3-animate-zoom'>
    					<img src='".$row['url']."' style='width:100%' alt='product image'>
  					</div>
				</div>";
			$b++;
		}
		$a++;
	}
	if($a == 0){
		//geen plaatjes voor dit product
		return "<a href='index.php?action=showProduct&pId=$pId'><img src='images/noimage.jpg' height='$height' width='$width' alt='product plaatje'></a>";
	} else {
		return $first.$other;
	}
}

function createNavBar($navArray){
	$return = "<ul class='w3-navbar w3-border-bottom w3-light-grey'>";
	$a = 1;
	foreach($navArray as $openTab => $tabName){
		$return = $return."<li><a href='#' id='link-$a' class='tablink' onclick=\"openTab(event,'".$openTab."')\">$tabName</a></li>";
		$a++;
	}
	return $return."</ul>";
}

function tabsJavaCode($openScreen, $screen){
	return "<script>
	openTab ($screen, '$openScreen');

	function openTab(evt, tabName) {
	var i, x, tablinks;
	var x = document.getElementsByClassName('tab');
	for (i = 0; i < x.length; i++) {
	x[i].style.display = 'none';
}
tablinks = document.getElementsByClassName('tablink');
for (i = 0; i < x.length; i++) {
tablinks[i].className = tablinks[i].className.replace(' w3-theme-l2', '');
}
document.getElementById(tabName).style.display = 'block';
if(evt == $screen){
document.getElementById('link-$screen').className += ' w3-theme-l2';
} else {
evt.currentTarget.className += ' w3-theme-l2';
}
}
</script>";
}

function uploadImage($target_dir, $isSiteLogo){
	/***************************
	 * Gebruik uploadImage('locatie/');
	 ***************************/
	global $ErrorHandler;
	$uploadOk = 1;
	$debug = FALSE;
	$errormessage = FALSE;
	
	if(!$isSiteLogo){
		$target_file = $target_dir . round(microtime(true)) .basename($_FILES["fileToUpload"]["name"]);
		
		// Check if file already exists (heel onwaarschijnlijk maar better safe then sorry)
		if (file_exists($target_file)) {
			// probeer het eerst nog een keer
			$target_file = $target_dir . round(microtime(true)).'1' .basename($_FILES["fileToUpload"]["name"]);
			if (file_exists($target_file)) {
				//Zeer onwaarschijnlijke uitkomst maar voor de zekerheid
				$errormessage = $errormessage. "Er bestaat al een bestand met deze naam.<br>";
				$uploadOk = 0;
			}
		}
		
	} else {
		$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
		$extensie = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
		if($extensie != 'jpg'){
			$errormessage = $errormessage. "Een logo moet een *.jpg zijn.<br>";
			$uploadOk = 0;
		}
		$target_file = $target_dir .'logo.'. $extensie;
	}
	
	$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
	// Check if image file is a actual image or fake image
	if(isset($_POST["submit"])) {
		if(!empty($_FILES["fileToUpload"]["tmp_name"])){
			$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
			if($check !== false) {
				$debug = $debug. "File is an image - " . $check["mime"] . ".";
			} else {
				$errormessage = $errormessage. "Dit bestand is geen plaajte.<br>";
				$uploadOk = 0;
			}
		} else {
				$errormessage = $errormessage. "Geen bestand gevonden, dit komt waarschijnlijk omdat het groter dan 2Mb was.<br>";
				$uploadOk = 0;
			}
	} else {
				$errormessage = $errormessage. "Geen afbeelding verzonden.<br>";
				$uploadOk = 0;
			}

	// Check file size
	if ($_FILES["fileToUpload"]["size"] > 1000000) {
		$errormessage = $errormessage. "Het plaatje mag maximaal 1Mb groot zijn.<br>";
		$uploadOk = 0;
	}
	// Allow certain file formats
	if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
		$errormessage = $errormessage. "Een plaatje mag alleen  een JPG, JPEG, PNG & GIF file zijn.<br>";
		$uploadOk = 0;
	}
	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
		$errormessage = $errormessage. "Het plaatje is niet geupload.<br>";
		$ErrorHandler->dumpDebug($debug);
		$ErrorHandler->dumpError($errormessage);
		return FALSE;
		// if everything is ok, try to upload file
	} else {
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
			$debug = $debug. "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
			$ErrorHandler->dumpDebug($debug);
			return $target_file;
		} else {
			$errormessage = $errormessage.  "Er ging iets fout bij het kopiëren mogelijk staan de schrijfrechten verkeerd.<br>";
			$ErrorHandler->dumpDebug($debug);
			$ErrorHandler->dumpError($errormessage);
			return FALSE;
		}
	}
}

function imageSlider(){
	global $Settings;
	global $PDO;
	if($Settings->_get('allowPromotions') != 0){
		$output =  '<div id="imgSlider" class="w3-display-container">';
		$result = $PDO->query("SELECT * FROM promobanner");
		$span = '';
		$a = 1;
		foreach($result as $row){
			$output = $output.'<a href="index.php?action=showProduct&pId='.$row['pid'].'"><img class="mySlides" src="'.$row['imageurl'].'" alt="promobanner"></a>';
			$span = $span. '<span class="w3-badge demo w3-border w3-transparent w3-hover-white" onclick="currentDiv('.$a.')"></span> ';
			$a++;
		}
		if($a == 1){
			return;
		} elseif($a == 2) {
			return $output.'<div class="w3-center w3-section w3-large w3-text-white w3-display-bottomleft" style="width:100%"></div></div><br><script>
var slideIndex = 1;
var isPressed = 0;
showDivs(slideIndex);
function showDivs(n) {
  var i; var x = document.getElementsByClassName("mySlides");
  var dots = document.getElementsByClassName("demo");
  if (n > x.length) {slideIndex = 1}
  if (n < 1) {slideIndex = x.length} ;
  for (i = 0; i < x.length; i++) {x[i].style.display = "none";}
  for (i = 0; i < dots.length; i++){dots[i].className = dots[i].className.replace(" w3-white", "");}
  x[slideIndex-1].style.display = "block";
  dots[slideIndex-1].className += " w3-white";
}
</script>';
		} else {
			$output = $output.'  <div class="w3-center w3-section w3-large w3-text-white w3-display-bottomleft" style="width:100%">
    <div class="w3-left w3-padding-left w3-hover-text-khaki" onclick="plusDivs(-1)">&#10094;</div>
    <div class="w3-right w3-padding-right w3-hover-text-khaki" onclick="plusDivs(1)">&#10095;</div>
		'.$span.'
  </div>
</div>
<br>
<script>
var slideIndex = 1;
var isPressed = 0;
showDivs(slideIndex);
carousel();
		
function plusDivs(n) {
  showDivs(slideIndex += n);
  isPressed = 1;
}
		
function currentDiv(n) {
  showDivs(slideIndex = n);
  isPressed = 1;
}
		
function showDivs(n) {
  var i;
  var x = document.getElementsByClassName("mySlides");
  var dots = document.getElementsByClassName("demo");
  if (n > x.length) {slideIndex = 1}
  if (n < 1) {slideIndex = x.length} ;
  for (i = 0; i < x.length; i++) {
     x[i].style.display = "none";
  }
  for (i = 0; i < dots.length; i++) {
     dots[i].className = dots[i].className.replace(" w3-white", "");
  }
  x[slideIndex-1].style.display = "block";
  dots[slideIndex-1].className += " w3-white";
}
function carousel() {
    var i;
    var x = document.getElementsByClassName("mySlides");
	var dots = document.getElementsByClassName("demo");
    for (i = 0; i < x.length; i++) {
      x[i].style.display = "none";
    }
	for (i = 0; i < dots.length; i++) {
     dots[i].className = dots[i].className.replace(" w3-white", "");
  	}
    slideIndex++;
    if (slideIndex > x.length) {slideIndex = 1}
    x[slideIndex-1].style.display = "block";
	dots[slideIndex-1].className += " w3-white";
	if(isPressed == 0){
    	setTimeout(carousel, 5000); // Change image every 5 seconds
	} else {
			setTimeout(carousel, 120000);
			isPressed = 0;
			}
}
</script>
		
';
			return $output;
		}
	} else {
		return;
	}
}

function createLandingPage(){
	global $PDO;
	$result = $PDO->query("SELECT * FROM frontpage ORDER BY id DESC limit 0, 1");
	$row = $result->fetch();
	$return = '<br><div class="w3-card-4">
					<div class="w3-container w3-theme-l2"><h3>'.$row['title'].'</h3></div>
					<div class="w3-container">'.$row['text'].'<br></div></div>';
	return imageSlider().showPromotions('0', '3').$return;
}

function createCatOptionDropDownList($admin){
	global $PDO;
	$result = $PDO->query("SELECT * FROM categorie ORDER BY naam");
	if($admin == 1){
		$return = "<option value=''>Maak een hoofdcategorie</option>";
		$disabled = "";
	} elseif($admin == 2){
		$return = "<option value='0'>Hoofdpagina</option>";
		$disabled = "";
	} else {
		$return = '';
		$disabled = "disabled";
	}
	$resultArray = array();
	$isMainCat = array();
	foreach($result as $row){
		$resultArray[$row['id']]['id'] = $row['id'];
		$resultArray[$row['id']]['naam'] = $row['naam'];
		$resultArray[$row['id']]['parentId'] = $row['subfromcatid'];
		if($row['subfromcatid'] != NULL){
			$resultArray[$row['subfromcatid']]['hasChildren'] = '1';
		} else {
			$isMainCat[$row['id']] = $row['naam'];
		}
	}
	foreach ($isMainCat as $id => $naam){
		$return = $return."<option value='$id' $disabled>$naam</option>".getCatOptionChildren($id, $resultArray, 1, $disabled);
	}
	return $return;
}

function getCatOptionChildren($ParentId, $resultArray,$a, $disabled){
	$return = '';
	$c = 0;
	$spaces = '';
	while ($c < $a){
		$spaces = $spaces."&nbsp;&nbsp;&nbsp;";
		$c++;
	}
	foreach ($resultArray as $row){
		if($row['parentId'] == $ParentId){
			if(isset($row['hasChildren']) AND $row['hasChildren'] == '1'){
				$b = $a+1;
				$return = $return."<option value='".$row['id']."' $disabled>".$spaces.$row['naam']."</option>";
				$return = $return.getCatOptionChildren($row['id'], $resultArray, $b, $disabled);
			} else {
				$return = $return."<option value='".$row['id']."'>".$spaces.$row['naam']."</option>";
			}
		}
	}
	return $return;
}

function retainSession($newhash){
	global $PDO;
	global $myCart;
	$update = $PDO->prepare("UPDATE winkelwagen SET sessionId = :sessionId WHERE id = :cartId" );
	$update->execute(array(':sessionId' => $newhash, ':cartId' => $myCart->_get('cartId')));
}

function displayRating($pId){
	global $PDO;
	global $Settings;
	if($Settings->_get('allowReviews') == 0){
		return '';
	} else {
		$result = $PDO->prepare("SELECT AVG(rating) as average, count(*) as total FROM productreview WHERE pid = :pid");
		$result->execute(array(':pid' => $pId));
		$row = $result->fetch();
		$total = $row['total'];
		if($row['average'] == NULL ){
			$output = "<img src='images/grayStar.jpg' alt='empty star'><img src='images/grayStar.jpg' alt='empty star'><img src='images/grayStar.jpg' alt='empty star'><img src='images/grayStar.jpg' alt='empty star'><img src='images/grayStar.jpg' alt='empty star'>";
		} else {
			$stars = round($row['average']*2) / 2;
			switch($stars){
				case 0:
					$output = "<img src='images/emptyStar.jpg' alt='empty star' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
					break;
		
				case 0.5:
					$output = "<img src='images/halfStar.jpg' alt='half star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
					break;
		
				case 1:
					$output = "<img src='images/fullStar.jpg' alt='full star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
					break;
		
				case 1.5:
					$output = "<img src='images/fullStar.jpg' alt='full star'><img src='images/halfStar.jpg' alt='half star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
					break;
		
				case 2:
					$output = "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
					break;
		
				case 2.5:
					$output = "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/halfStar.jpg' alt='half star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
					break;
		
				case 3:
					$output = "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
					break;
		
				case 3.5:
					$output = "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/halfStar.jpg' alt='half star'><img src='images/emptyStar.jpg' alt='empty star'>";
					break;
		
				case 4:
					$output = "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/emptyStar.jpg' alt='empty star'>";
					break;
		
				case 4.5:
					$output = "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/halfStar.jpg' alt='half star'>";
					break;
		
				case 5:
					$output = "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'>";
					break;
			}
		
		}
		return 'Waardering: '.$output." ($total)";
	}
}



function placeReview($pId, $review, $score){
	global $User;
	global $PDO;
	global $Settings;
	$error = FALSE;
	$errorstring;
	if($Settings->_get('allowReviews') == 0){
		$error = TRUE;
		$errorstring = $errorstring."Reviews zijn uitgeschakeld.<br>";
	}
	if($pId == '' OR $pId == NULL){
		$error = TRUE;
		$errorstring = $errorstring."Geen geldig productnummer.<br>";
	} else {
		$testresult = $PDO->prepare("SELECT * FROM producten WHERE id = :id");
		$testresult->execute(array(':id' => $pId));
		$testrow = $testresult->fetch();
		if($testrow['id'] == NULL){
			$error = TRUE;
			$errorstring = $errorstring."Geen geldig productnummer.<br>";
		}
	}
	if($score == '' OR $score > 5){
		$error = TRUE;
		$errorstring = $errorstring."Er moet een (geldige) score worden ingevoerd.<br>";
	}
	if(!$error){
		if($review == ''){
			$insert = $PDO->prepare("INSERT INTO productreview (pid, uid, rating) VALUES (:pid, :uid, :rating)");
			$insert->execute(array(':pid' => $pId, ':uid' => $User->_get('costumerId'), ':rating' => $score));
		} else {
			$insert = $PDO->prepare("INSERT INTO productreview (pid, uid, rating, review) VALUES (:pid, :uid, :rating, :review)");
			$insert->execute(array(':pid' => $pId, ':uid' => $User->_get('costumerId'), ':rating' => $score, ':review' => $review));
		}
		return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Gelukt!</h3><p>Product waardering geplaatst.</p></div>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>$errorstring</p></div>";
	}
}

function inStock($voorraad, $voorraad_levernacier, $gewensteHoeveelheid, $service){
	if($service == 0){
		$inStock;
		if($voorraad >= $gewensteHoeveelheid){
			$inStock = "op voorraad";
		} else {
			switch ($voorraad_levernacier){
		
				case '1':
					$inStock = "op voorraad leverancier";
					break;
		
				case 2:
					$inStock = "Leverbaar binnen een week";
					break;
						
				case 3:
					$inStock = "Leverbaar binnen drie weken";
					break;
		
				case 4:
					$inStock = "Leverbaar 1-2 maanden";
					break;
		
				default:
					$inStock = "Levertijd onbekend";
					break;
			}
		}
		return $inStock;
	}elseif($service == 1){
	 	return "Eenmalige betaling";
	} elseif($service == 2){
		return "Maandelijkse betaling";
	} else {
		return "Jaarlijkse betaling";
	}
} 

function showPromotions($catId, $rowsAmount){
	global $Settings;
	global $PDO;
	if($Settings->_get('allowPromotions') != 0){
		$limit = $rowsAmount*4;
		$result = $PDO->prepare("SELECT * FROM promotion LEFT JOIN producten ON productid = producten.id WHERE catid = :catId AND active = '1' ORDER BY promotion.id DESC LIMIT 0, :limit");
		$result->execute(array(':catId' => $catId, ':limit' => $limit));
		$promo = 0;
		$return = "<div class='promoTable'>";
		foreach($result as $row){
			$promo++;
			if($promo == 5){
				$promo = 1;
			}
			$middle = "<div class='promoCell'><a href='index.php?action=showProduct&pId=".$row['productid']."'>".$row['productNaam']."</a><br>".productImage($row['productid'], 200, 200, 1)."<br><b>
				&euro; ".$row['prijs']."</b><br>".inStock($row['voorraad'], $row['voorraad_leverancier'], 1, $row['service'])."<br><a href='index.php?action=showProduct&pId=".$row['productid']."&changeCart=1&&aantal=1'>Leg in winkelwagen</a><br>".displayRating($row['productid'])."</div>";
			switch($promo){
				case 1:
					$return = $return."<div class='promoRow'>".$middle;
					break;
						
				case 2:
					$return = $return.$middle;
					break;
		
				case 3:
					$return = $return.$middle;
					break;
		
				case 4:
					$return = $return.$middle."</div>";
					break;
			}
		}
		if($promo != 4){
			$return = $return."</div>";
		}
		if($promo == 0){
			return;
		} else {
			return $return."</div>";
		}
	} else {
		return;
	}
}

function search($costumer){
	global $PDO;
	global $Page;
	if(isset($_GET['q'])){
		$queryOriginal = $_GET['q'];
	} elseif(isset($_POST['q'])){
		$queryOriginal = $_POST['q'];
	} else {
		$queryOriginal = NULL;
	}
	if(isset($_GET['limit'])){
		$limit = $_GET['limit'];
		if($limit == NULL){
			$limit = 25;
		}
	} else {
		$limit = 25;
	}
	if(isset($_GET['start'])){
		$start = $_GET['start'];
		if($start == NULL){
			$start = 0;
		}
	} else {
		$start = 0;
	}
	$query = '%'.$queryOriginal.'%';
	if($costumer == 1){
		$result = $PDO->prepare("SELECT * FROM producten WHERE productNaam LIKE :productNaam AND actief = '1' ORDER BY id LIMIT :start, :limit");
		$resultCount = $PDO->prepare("SELECT COUNT(*) FROM producten WHERE productNaam LIKE :productNaam AND actief = '1'");
		$baseURL = 'index.php?action=showSearch';
		$action = 'showSearch';
		$subaction = '';
		$productURL = 'index.php?action=showProduct';
		$tableHeader = "<div class='tableRow'><div class=tableCell>Status</div><div class=tableCell>Productomschrijving</div><div class=tableCell>Stukprijs</div><div class=tableCell>winkelwagen</div></div>";
	} elseif($costumer == 2){
		$result = $PDO->prepare("SELECT * FROM producten WHERE productNaam LIKE :productNaam ORDER BY id LIMIT :start, :limit");
		$resultCount = $PDO->prepare("SELECT COUNT(*) FROM producten WHERE productNaam LIKE :productNaam");
		$baseURL = 'index.php?action=employee&subaction=1';
		$action = 'employee';
		$subaction = '1';
		$productURL = 'index.php?action=employee&subaction=2';
		$tableHeader = "<div class='tableRow'><div class=tableCell>Product</div><div class=tableCell>actief</div><div class=tableCell>Stukprijs</div><div class=tableCell>voorraad</div><div class=tableCell>gereserveerd</div></div>";
	} else {
		$result = $PDO->prepare("SELECT * FROM producten WHERE productNaam LIKE :productNaam ORDER BY id LIMIT :start, :limit");
		$resultCount = $PDO->prepare("SELECT COUNT(*) FROM producten WHERE productNaam LIKE :productNaam ");
		$baseURL = 'index.php?action=showSearchAll';
		$action = 'showSearchAll';
		$subaction = '';
		$productURL = 'index.php?action=manageProduct';
		$tableHeader = "<div class='tableRow'><div class=tableCell>Product</div><div class=tableCell>actief</div><div class=tableCell>Prijs</div><div class=tableCell>Verkocht</div><div class=tableCell>voorraad</div><div class=tableCell>gereserveerd</div><div class=tableCell>in bestelling</div><div class=tableCell>te bestellen</div></div>";
	}
	$returnstring = "<form action='index.php' method='GET'><input type='hidden' name='action' value='$action'><input type='hidden' name='subaction' value='$subaction'><input class='w3-input w3-border w3-round-large' type='text' name='q' value='$queryOriginal' placeholder='Zoeken...'><br><button class='w3-btn-block w3-theme-l2 w3-input w3-border w3-round-large'>Zoeken</button></form>";
	if($queryOriginal != NULL){
		$result->execute(array(':productNaam' => $query, ':start' => $start, ':limit' => $limit));
		$resultCount->execute(array(':productNaam' => $query));
		$number_of_rows = $resultCount->fetchColumn();
		$amount_of_pages = ceil($number_of_rows/$limit);
		$pageNumber = 1;
		$pagenumbers = '';
		$articlesPerPage = "Artikelen per pagina: <a href='$baseURL&q=$queryOriginal&limit=25'>25</a> | <a href='$baseURL&q=$queryOriginal&limit=50'>50</a> | <a href='$baseURL&q=$queryOriginal&limit=100'>100</a> | <a href='$baseURL&q=$queryOriginal&limit=250'>250</a> | <a href='$baseURL&q=$queryOriginal&limit=18446744073709551615'>Alle</a>";
		while ($pageNumber <= $amount_of_pages){
			$startPlace = ($pageNumber-1)*$limit;
			if($startPlace == $start){
				$pagenumbers = $pagenumbers.$pageNumber." ";
			} else {
				$pagenumbers = $pagenumbers."<a href='$baseURL&q=$queryOriginal&limit=$limit&start=$startPlace'>".$pageNumber."</a> ";
			}
			$pageNumber++;
		}
		$returnstring = $returnstring."<h3>Zoekresultaten:</h3>$articlesPerPage<br>Pagina: $pagenumbers<br><br><div class='table'>".$tableHeader;
		$count = 0;
		foreach ($result as $row){
			if($costumer == 1){
			$returnstring = $returnstring.
				"<div class='tableRow'>
					<div class=tableCell>".inStock($row['voorraad'], $row['voorraad_leverancier'], 1)."</div>
					<div class=tableCell><a href='$productURL&pId=".$row['id']."'>".$row['productNaam']."</a> </div>
					<div class=tableCell>&euro; ".number_format((float)$row['prijs'], 2, ',', '.')."</div>
					<div class=tableCell><a href='$baseURL&q=$queryOriginal&limit=$limit&start=$startPlace&changeCart=1&pId=".$row['id']."&aantal=1'>voeg toe aan winkelwagen</a></div>
				</div>";
			} elseif($costumer == 2){
				if($row['actief'] == 1){$actief ='actief';}else{$actief ='inactief';}
				$returnstring = $returnstring.
				"<div class='tableRow'>
				<div class=tableCell><a href='$productURL&pId=".$row['id']."'>".$row['productNaam']."</a></div>
						<div class=tableCell>".$actief."</div>
						<div class=tableCell>&euro; ".number_format((float)$row['prijs'], 2, ',', '.')."</div>
						<div class=tableCell>".$row['voorraad']."</div>
						<div class=tableCell>".$row['gereserveerd']."</div>
					</div>";
			} else {
				if($row['actief'] == 1){$actief ='actief';}else{$actief ='inactief';}
				$returnstring = $returnstring.
					"<div class='tableRow'>
						<div class=tableCell><a href='$productURL&pId=".$row['id']."'>".$row['productNaam']."</a></div>
						<div class=tableCell>".$actief."</div>
						<div class=tableCell>&euro;".number_format((float)$row['prijs'], 2, ',', '.')."</div>
						<div class=tableCell>".$row['verkocht']."</div>
						<div class=tableCell>".$row['voorraad']."</div>
						<div class=tableCell>".$row['gereserveerd']."</div>
						<div class=tableCell>".$row['externBesteld']."</div>
						<div class=tableCell>".$row['uitstaandeBestelling']."</div>
					</div>";
			
			}
			$count++;
		}
		if($count == 0){
			$returnstring = $returnstring
			."<div class='tableRow'><div class=tableCell></div><div class=tableCell>Geen producten met deze zoekterm gevonden</div><div class=tableCell></div><div class=tableCell></div></div>";
		}
		$returnstring = $returnstring."</div><br>Pagina: $pagenumbers<br>$articlesPerPage<br>Weergegeven $count van $number_of_rows producten die aan de zoekcriteria voldoen.";
	}
	$Page->changePageTitle('Zoekresultaten');
	return $returnstring;
}

function emptyCart($cartId){
	global $PDO;
	global $myCart;
	$delete = $PDO->prepare("DELETE FROM winkelwagen_producten WHERE cartId = :cartId");
	$delete->execute(array(':cartId' => $cartId));
	$myCart = NULL;
	$myCart = new cart();
	$myCart->removeDiscountCoupon();
}

function addToCart(){
	global $PDO;
	global $myCart;
	global $Page;
	
	$error = FALSE;
	
	//check of er een product is geselecteerd
	if(isset($_GET['pId'])){
		$pId = $_GET['pId'];
	} else if(isset($_POST['pId'])){
		$pId = $_POST['pId'];
	} else {
		$error = TRUE;
	}
	
	//Check of het product actief is
	$resultactive = $PDO->prepare("SELECT actief FROM producten WHERE id = :pId");
	$resultactive->execute(array(':pId' => $pId));
	$rowActive = $resultactive->fetch();
	if($rowActive['actief'] != '1'){
		$error = TRUE;
	}
	
	
	//check of dat aantal of dat delete meegegeven is en geef anders een error
	if(isset($_GET['aantal'])){
		$aantal = $_GET['aantal'];
		$delete = '';
	} else if(isset($_POST['aantal'])){
		$aantal = $_POST['aantal'];
		$delete = '';
	} else if(isset($_GET['delete'])){
		$aantal = '';
		$delete = $_GET['delete'];
	} else if(isset($_POST['delete'])){
		$aantal = '';
		$delete = $_POST['delete'];
	} else {
		$aantal = '';
		$delete = '';
		$error = TRUE;
	}
	
	if(!$error){
		if($delete == 1){
			$delete = $PDO->prepare("DELETE FROM winkelwagen_producten WHERE cartId = :cartId AND productId = :productId");
			$delete->execute(array(':cartId' => $myCart->_get('cartId'), ':productId' => $pId));
			$Page->addToBody("<div class=\"w3-container w3-blue\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Informatie</h3><p>Product uit de winkelwagen gehaald.</p></div><br>");
		} else {
			$aantal = intval($aantal);
			//kijk of er al een entry voor dit product in deze mand is
			$result = $PDO->prepare("SELECT * FROM winkelwagen_producten WHERE cartId = :cartId AND productId = :productId");
			$result->execute(array(':cartId' => $myCart->_get('cartId'), ':productId' => $pId));
			$row = $result->fetch();
			if($row['id'] == NULL){
				$insert = $PDO->prepare("INSERT INTO winkelwagen_producten (cartId, productId, aantal) VALUES (:cartId, :productId, :aantal)");
				$insert->execute(array(':cartId' => $myCart->_get('cartId'), ':productId' => $pId, ':aantal' => $aantal));
				$Page->addToBody("<div class=\"w3-container w3-blue\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Informatie</h3><p>Product aan winkelwagen toegevoegd.</p></div><br>");
				
			} else {
				$update = $PDO->prepare("UPDATE winkelwagen_producten SET aantal = :aantal WHERE cartId = :cartId AND productId = :productId" );
				$update->execute(array(':cartId' => $myCart->_get('cartId'),':aantal' => $aantal ,':productId' => $pId));
				$Page->addToBody("<div class=\"w3-container w3-blue\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Informatie</h3><p>Product aantal aangepast.</p></div><br>");
			}
		} 
		//herlaad het winkelwagentje
		$myCart = NULL;
		$myCart = new cart();
	}else {
			$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Er is iets misgegaan, probeer het opnieuw.</p></div><br>");
		}
}

function checkPostcode($postcode){
	$remove = str_replace(" ","", $postcode);
	$upper = strtoupper($remove);

	if( preg_match("/^\W*[1-9]{1}[0-9]{3}\W*[a-zA-Z]{2}\W*$/",  $upper)) {
		return $upper;
	} else {
		return false;
	}
}

function unsubscribeNewsletter(){
	global $PDO;
	$error = FALSE;
	if(isset($_GET['email']) and $_GET['email'] != NULL){
		$email = $_GET['email'];
	} else {
		$error = 'Geen email adres opgegeven';
	}
	if(!$error){
		if(isset($_GET['unsub']) AND $_GET['unsub'] == 1){
			$update = $PDO->prepare("UPDATE klanten SET newsletter = 0 WHERE email = :email");
			$update->execute(array(':email' => $email));
			return "<div class=\"w3-container w3-green\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Uitschrijven nieuwsbrief!</h3>
			<p>$email is uitgeschreven voor de nieuwsbrief.</p></div><br>".createLandingPage();
		} else {
			return "<div class=\"w3-container w3-yellow\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Uitschrijven nieuwsbrief!</h3>
					<p>U staat op het punt $email uit te schrijven voor de nieuwsbrief weet u dit zeker?<br><a href='index.php?action=newsletter&email=$email&unsub=1'>Ja</a></p></div><br>";
		}
	}else{
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>".$error."</p></div><br>";
	}
}

function searchUserForm($form){
	if($form == 'admin'){
		$return = "<div class='w3-container w3-theme-l2'><h2>Gebruikers zoeken</h2></div>
				<form action='index.php' method='get' class='w3-container'>
					<input type='hidden' name='action' value='admin'></input>
					<input type='hidden' name='screen' value='1'></input>
					<p><label>Gebruikersnummer:</label><input class='w3-input w3-border' type='text' name='userId' value=''></p>
				 	<p><input class='w3-btn w3-theme-l2' type='submit' value='Gebruiker openen'></p>
				</form>
				<form action='index.php?action=admin&subaction=1' method='post' class='w3-container'>";
	}elseif($form == 'employee') {
		//Todo andere plaatsen waarvandaan op users gezocht kan worden toevoegen
		$return = "<div class='w3-container w3-theme-l2'><h2>Logistiek: Klant zoeken</h2></div>
				<form action='index.php' method='get' class='w3-container'>
					<input type='hidden' name='action' value='employee'></input>
					<input type='hidden' name='subaction' value='10'></input>
					<p><label>Klantnummer:</label><input class='w3-input w3-border' type='text' name='uid' value=''></p>
				 	<p><input class='w3-btn w3-theme-l2' type='submit' value='Klant openen'></p>
				</form>
				<form action='index.php?action=employee&subaction=9' method='post' class='w3-container'>";
	}elseif($form == 'service') {
		//Todo andere plaatsen waarvandaan op users gezocht kan worden toevoegen
		$return = "<div class='w3-container w3-theme-l2'><h2>Service Center: Klant zoeken</h2></div>
				<form action='index.php' method='get' class='w3-container'>
					<input type='hidden' name='action' value='service'></input>
					<input type='hidden' name='subaction' value='2'></input>
					<p><label>Klantnummer:</label><input class='w3-input w3-border' type='text' name='uid' value=''></p>
				 	<p><input class='w3-btn w3-theme-l2' type='submit' value='Klant openen'></p>
				</form>
				<form action='index.php?action=service&subaction=1' method='post' class='w3-container'>";
	}else {
		//Todo andere plaatsen waarvandaan op users gezocht kan worden toevoegen
		$return = "<div class='w3-container w3-theme-l2'><h2>Gebruikers zoeken</h2></div><form action='index.php?action=???' method='post' class='w3-container'>";
	}
	$return = $return." <p><label>De gebruikersnaam bevat:</label><input class='w3-input w3-border' type='text' name='naam' value=''></p>
			 <p><label>E-mail adres:</label><input class='w3-input w3-border' type='text' name='email' value=''></p>
			 <p><label>Postcode:</label><input class='w3-input w3-border' type='text' name='postcode' value=''></p>
			 <p><label>Plaatsnaam:</label><input class='w3-input w3-border' type='text' name='plaatsnaam' value=''></p>
			 <p><label>Achternaam:</label><input class='w3-input w3-border' type='text' name='achternaam' value=''></p>
			 <p><input class='w3-btn w3-theme-l2' type='submit' name='submit' value='Zoeken'></p>
			 </form>";
	return $return;
}
function searchUser($form){
	global $PDO;
	if($form == 'admin'){
		$action = 'admin&screen=1&userId=';
		$return = "<div id='manageUser' class='tab'><div class='w3-card-4'>";
		$newsearch = 'admin&screen=1&userId=';
	}elseif($form == 'employee'){
		$action = 'employee&subaction=10&uid=';
		$return = '';
		$newsearch = 'employee&subaction=8';
	}elseif($form == 'service'){
		$action = 'service&subaction=2&uid=';
		$return = '';
		$newsearch = 'service&subaction=';
	}else {
		$action = '???';
		$return = '';
		$newsearch = '';
	}
	$isset = false;
	$queryBegin = "SELECT * FROM klanten WHERE ";
	$queryWhere = '';
	$queryEnd = "ORDER BY achternaam";
	$queryArray = array();
	if(isset($_POST['naam']) AND $_POST['naam'] != ''){
		if($isset){
			$queryWhere = $queryWhere."AND ";
		}
		$name = $_POST['naam'];
		$queryWhere = $queryWhere."username LIKE :name ";
		$queryArray[':name'] = '%'.$name.'%';
		$isset = TRUE;
	}
	if(isset($_POST['email'])  AND $_POST['email'] != ''){
		if($isset){
			$queryWhere = $queryWhere."AND ";
		}
		$email = $_POST['email'];
		$queryWhere = $queryWhere."email LIKE :email ";
		$queryArray[':email'] = '%'.$email.'%';
		$isset = TRUE;
	}
	if(isset($_POST['postcode']) AND $_POST['postcode'] != ''){
		if($isset){
			$queryWhere = $queryWhere."AND ";
		}
		$postcode = $_POST['postcode'];
		$queryWhere = $queryWhere."postcode LIKE :postcode ";
		$queryArray[':postcode'] = '%'.$postcode.'%';
		$isset = TRUE;
	}
	if(isset($_POST['plaatsnaam']) AND $_POST['plaatsnaam'] != ''){
		if($isset){
			$queryWhere = $queryWhere."AND ";
		}
		$plaatsnaam = $_POST['plaatsnaam'];
		$queryWhere = $queryWhere."stad LIKE :stad ";
		$queryArray[':stad'] = '%'.$plaatsnaam.'%';
		$isset = TRUE;
	}
	if(isset($_POST['achternaam']) AND $_POST['achternaam'] != ''){
		if($isset){
			$queryWhere = $queryWhere."AND ";
		}
		$achternaam = $_POST['achternaam'];
		$queryWhere = $queryWhere."achternaam LIKE :achternaam ";
		$queryArray[':achternaam'] = '%'.$achternaam.'%';
		$isset = TRUE;
	}
	if($isset){
		$return = $return."<div class='w3-container w3-theme-l2'><h2>Gebruiker zoeken</h2></div><div class='table' style='padding: 10px;'>
		<div class='tableRow'>
			<div class=tableCell>Gebruikersnaam</div>
			<div class=tableCell>Achternaam</div>
			<div class=tableCell>Voornaam</div>
			<div class=tableCell>Plaats</div>
			<div class=tableCell>Straatnaam</div>
			<div class=tableCell>Postcode</div>
			<div class=tableCell>Bedrijfsnaam</div>
			<div class=tableCell>E-mail</div>
		</div>";
		$result = $PDO->prepare($queryBegin.$queryWhere.$queryEnd);
		$result->execute($queryArray);
		$a = 0;
		foreach ($result as $row){
			$return = $return.
			"<div class='tableRow'>
			<div class=tableCell><a href='index.php?action=$action".$row['id']."'>".$row['username']."</a></div>
			<div class=tableCell><a href='index.php?action=$action".$row['id']."'>".$row['achternaam']."</a></div>
			<div class=tableCell><a href='index.php?action=$action".$row['id']."'>".$row['voornaam']."</a></div>
			<div class=tableCell><a href='index.php?action=$action".$row['id']."'>".$row['stad']."</a></div>
			<div class=tableCell><a href='index.php?action=$action".$row['id']."'>".$row['straatnaam']."</a></div>
			<div class=tableCell><a href='index.php?action=$action".$row['id']."'>".$row['postcode']."</a></div>
			<div class=tableCell><a href='index.php?action=$action".$row['id']."'>".$row['bedrijfsnaam']."</a></div>
			<div class=tableCell><a href='index.php?action=$action".$row['id']."'>".$row['email']."</a></div>
				</div>";
			$a++;
		}
		$return = $return."</div><p style='padding: 10px;'>Aantal gevonden resultaten: $a</p><p style='padding: 10px;'><a class='w3-btn w3-theme-l2' href='index.php?action=$newsearch'>Opnieuw zoeken</a></p>";
		if($form == 'admin'){
			$return = $return."</div></div>";
		}
		return $return;
	} else {
		if($form == 'admin'){
			return manageUserForm(NULL);
		}else {
			return;
		}
	}
}

function sendEmail($to, $from, $message, $subject){
	$message = wordwrap($message, 70, "\r\n");
	$headers = 'From: ".$clientEmail."' . "\r\n" .
			'Reply-To: ".$from."' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
	mail($to, $subject, $message, $headers);
}


function showMessage(){
	global $PDO;
	global $Page;
	if(!isset($_GET['id']) OR $_GET['id'] == ''){
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Geen artikel geslecteerd.</p></div><br>";
	} else {
		$result = $PDO->prepare("SELECT * FROM shopmessages WHERE id = :id");
		$result->execute(array(':id' => $_GET['id']));
		$row = $result->fetch();
		if($row['id'] == NULL){
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Dit bericht bestaat niet.</p></div><br>";
		} else {
			$Page->changePageTitle($row['title']);
			return  "<div class='w3-container w3-theme-l2'><h2>". $row['title']."</h2></div><div class='w3-container'>".$row['text']."</div><br>";
		}
	}
}

function showNewsletter(){
	global $PDO;
	global $Page;
	if(!isset($_GET['id']) OR $_GET['id'] == ''){
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Geen artikel geslecteerd.</p></div><br>";
	} else {
		$result = $PDO->prepare("SELECT * FROM newsletters WHERE id = :id");
		$result->execute(array(':id' => $_GET['id']));
		$row = $result->fetch();
		if($row['id'] == NULL){
			return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Dit bericht bestaat niet.</p></div><br>";
		} else {
			$Page->changePageTitle($row['title']);
			return  "<div class='w3-container w3-theme-l2'><h2>". $row['title']."</h2></div><div class='w3-container'>".$row['text']."</div><br>";
		}
	}
}

function showOrderDetails($consumer){
	global $PDO;
	global $Landen;
	global $User;
	global $printThisPage;

	if(isset($_GET['orderId'])){
		$orderId = $_GET['orderId'];
	} else {
		$orderId = NULL;
	}
	$result = $PDO->prepare("SELECT orders.*, betalingsopties.betaalmethode as naamBetaalMethode FROM orders LEFT JOIN betalingsopties ON orders.betaalmethode = betalingsopties.id WHERE orders.id = :id");
	$result->execute(array(':id' =>$orderId));
	$row = $result->fetch();
	if($row['id'] == NULL){
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Deze order bestaat niet.</p></div><br>";
	} elseif($row['userId'] != $User->_get('costumerId') AND !( $User->isAdmin() OR $User->hasServiceRights() OR $User->isEmployee() OR $User->canEditProducts() ) ){
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>U mag alleen uw eigen orders bekijken. Hiervoor moet u met het juiste account inloggen.</p></div><br>";
	} else {
		if($row['tussenvoegsel'] != ''){
			$tussenvoegsel = ", ".$row['tussenvoegsel'];
		} else {
			$tussenvoegsel = '';
		}
		$titel = "<div class='w3-container w3-theme-l2'><h2>Ordernummer: ".$row['id']." besteld op ".date('d-m-Y', $row['besteldatum'])."</h2></div>";
		$afleveradres = "<h3>Leveringsadres:</h3><p>".$row['voornaam']." ".$row['achternaam'].$tussenvoegsel."<br>".$row['straat']." ".$row['huisnummer']." ".$row['toevoeging']."<br>".$row['postcode']." ".$row['stad']."<br>".$Landen->nameLandCode($row['landCode'])."</p>";
		if($printThisPage != '1'){
			if($row['service'] == 1){
				$betaling = 'Service';
			} elseif($row['shop'] == 0){
				$betaling = janee($row['voldaan']);
			} else {
				$betaling = 'Shop';
			}
			$orderStatus = "<h3>Status van de bestelling:</h3><p>Order proces afgerond: ".janee($row['costumercomfirmed'])."<br>
						Betaling voldaan: $betaling<br>
						Bestelling compleet in magazijn: ".janee($row['compleet'])."<br>
						Bestelling geraapt: ".janee($row['geraapt'])."<br>
						Bestelling verzonden: ".janee($row['verzonden'])."</p>";
		} else {
			$orderStatus = '';
		}
		if($consumer){
			$productlijst = "<h3>Producten:</h3><ul>".orderConsumerProductList($orderId)."</ul>";
			$totaal = "<p><h1 style='text-align: right'>Totaalprijs &euro;".number_format($row['prijs'], 2, ',', '.')."</h1></p>";
			return "<div class='w3-card-4'>".$titel."<div class='w3-container'>".$afleveradres.$orderStatus.$productlijst.$totaal."</div></div>";
		} else {
			$productlijst = '<h3>Producten:</h3><ul>'.orderEmployeeProductList($orderId).'</ul>';
			return "<div class='w3-card-4'>".$titel."<div class='w3-container'>".$afleveradres.$orderStatus.$productlijst."</div></div>";
		}
	}
}

function janee($int){
	if($int == 1){
		return "Ja";
	} else {
		return "Nee";
	}
}

function orderConsumerProductList($orderId){
	global $PDO;
	$return = '';
	$a = 0;
	$result = $PDO->prepare("SELECT orderproducten.prijs , orderproducten.productid, orderproducten.aantal, producten.ProductNaam, producten.btwtarief FROM orderproducten LEFT JOIN producten ON productid = producten.id WHERE orderid = :orderId");
	$result->execute(array(':orderId' => $orderId ));
	foreach($result as $row){
		if($row['productid'] == 0){
			$return = $return . "<li>".$row['aantal']."x Verzend en rembourskosten stukprijs: &euro;".number_format($row['prijs'], 2, ',', '.')." <b>Totaal: ".number_format($row['prijs']*$row['aantal'], 2, ',', '.')." </b></li>";
		} else {
			$return = $return . "<li>".$row['aantal']."x ".$row['ProductNaam']." stukprijs: &euro;".number_format($row['prijs'], 2, ',', '.')." <b>Totaal: ".number_format($row['prijs']*$row['aantal'], 2, ',', '.')." </b></li>";
		}
		$a++;
	}
	return $return;
}

function breadcrumb($id, $isProduct){
	global $PDO;
	if($isProduct){
		$return = getParent($id)." &raquo; ".$isProduct;
	} else {
		$return = getParent($id);
	}
	return $return;
}

function getParent($catId){
	global $PDO;
	global $Page;
	$Page->catIsOpen($catId);
	$result = $PDO->prepare("SELECT* FROM categorie WHERE id = :catId");
	$result->execute(array(':catId' => $catId));
	$row = $result->fetch();
	if($row['subfromcatid'] != NULL){
		$return = getParent($row['subfromcatid']) ." &raquo; <a href='index.php?action=showCat&catId=".$row['id']."'>".$row['naam']."</a>";
	} else {
		$return = "<a href='index.php?action=showCat&catId=".$row['id']."'>".$row['naam']."</a>";
	}
	return $return;
}

function showConsumerOrderList(){
	global $PDO;
	global $User;
	if($User->isLoggedIn()){
		$result = $PDO->prepare("SELECT * FROM orders WHERE userId = :userId AND costumercomfirmed = 1");
		$result->execute(array(':userId' => $User->_get('costumerId')));
		$return = "<div class='w3-card-4'><div class='w3-container w3-theme-l2'><h2>Uw orders</h2></div><div class='w3-container'><p>";
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
			$return = $return . "U heeft nog geen bestellingen geplaatst.";
		}
		$return = $return."</p></div></div>";
	} else {
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>U bent niet ingelogd. Log in om uw orders te kunnen zien.</p></div><br>";
	}
	return $return;
}
function checkOrdersForCompleteness(){
	global $PDO;
	$result = $PDO->query("SELECT * FROM orders WHERE voldaan = 1 AND compleet = 0");
	$return = FALSE;
	foreach ($result as $row){
		$resultProducten = $PDO->prepare("SELECT *, orderproducten.id AS opid FROM orderproducten LEFT JOIN producten ON orderproducten.productid = producten.id WHERE orderid = :id");
		$resultProducten->execute(array(':id' => $row['id']));
		$opVoorraad = TRUE;
		$return .= "Order ".$row['id']." passing: <br>";
		$PDO->beginTransaction();
		foreach ($resultProducten as $rowProduct){
			if($rowProduct['service'] == 0){
				if($rowProduct['aantal'] >= 0){
					if($rowProduct['ordergereserveerd'] == '0'){
						if($rowProduct['productid'] != NULL && $rowProduct['voorraad'] < $rowProduct['aantal']){
							$return .= "ohoh!: ".$rowProduct['productNaam']." is niet op voorraad...  ".$rowProduct['voorraad']." - ".$rowProduct['aantal']."<br>";
							$opVoorraad = FALSE;
							if($rowProduct['orderbesteld'] == '0'){
								$uitstaandeBestelling = $rowProduct['uitstaandeBestelling'] + $rowProduct['aantal'];
								$update = $PDO->prepare("UPDATE producten SET uitstaandeBestelling = :uitstaandeBestelling WHERE id = :id");
								$update->execute(array(':uitstaandeBestelling' => $uitstaandeBestelling, ':id' => $rowProduct['productid']));
								$update->closeCursor();
								$update = $PDO->prepare("UPDATE orderproducten SET orderbesteld = 1 WHERE id = :opid");
								$update->execute(array(':opid' => $rowProduct['opid']));
								$update->closeCursor();
							}
						} else {
							$voorraad = $rowProduct['voorraad'] - $rowProduct['aantal'];
							$gereserveerd = $rowProduct['gereserveerd'] + $rowProduct['aantal'];
							if($rowProduct['orderbesteld'] == '1'){
								$uitstaandeBestelling = $rowProduct['uitstaandeBestelling'] - $rowProduct['aantal'];
								$update = $PDO->prepare("UPDATE producten SET voorraad = :voorraad, gereserveerd = :gereserveerd,  uitstaandeBestelling = :uitstaandeBestelling WHERE id = :id ");
								$update->execute(array(':uitstaandeBestelling' => $uitstaandeBestelling, ':id' => $rowProduct['productid'], ':voorraad' => $voorraad, ':gereserveerd' => $gereserveerd));
								$update->closeCursor();
								$update = $PDO->prepare("UPDATE orderproducten SET ordergereserveerd = 1, orderbesteld = 0 WHERE id = :opid");
								$update->execute(array(':opid' => $rowProduct['opid']));
								$update->closeCursor();
							} else {
								$update = $PDO->prepare("UPDATE producten SET voorraad = :voorraad, gereserveerd = :gereserveerd WHERE id = :id");
								$update->execute(array(':id' => $rowProduct['productid'], ':voorraad' => $voorraad, ':gereserveerd' => $gereserveerd));
								$update->closeCursor();
								$update = $PDO->prepare("UPDATE orderproducten SET ordergereserveerd = 1 WHERE id = :opid");
								$update->execute(array(':opid' => $rowProduct['opid']));
								$update->closeCursor();
							}
						}
					} else {
						$return .= $rowProduct['productNaam']." is al gereserveerd <br>";
					}
				} else {
					if($rowProduct['productid'] != NULL){
						$return .= $rowProduct['productNaam'].' was '.$rowProduct['aantal'].'x besteld, genegeerd. Servicemedewerker dient product handmatig in te boeken.<br>';
					}
				}
			} else {
				$return .= $rowProduct['productNaam'].'dit is een service en geen product en is dus altijd op voorraad.';
			}
		}
		if($opVoorraad){
			$updateOrder = $PDO->prepare("UPDATE orders SET compleet = 1 WHERE id = :id");
			$updateOrder->execute(array(':id' => $row['id']));
			$updateOrder->closeCursor();
			$PDO->commit();
		} else {
			if($row['firstCheckComplete'] == '0'){
				$updateOrder = $PDO->prepare("UPDATE orders SET firstCheckComplete = 1 WHERE id = :id");
				$updateOrder->execute(array(':id' => $row['id']));
				$updateOrder->closeCursor();
				$PDO->commit();
			} else {
				$PDO->commit();
			}
		}
	}
	return $return;
}
?>
