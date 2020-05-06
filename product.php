<?php
function getreviews($pId) {
	global $PDO;
	$result = $PDO->prepare ( "SELECT *, klanten.username FROM productreview LEFT JOIN klanten ON uid = klanten.id WHERE pid = :pid AND review IS NOT NULL " );
	$result->execute ( array (
			':pid' => $pId 
	) );
	$output = "<h3>Productreviews</h3>";
	$amountOfReviews = 0;
	foreach ( $result as $row ) {
		$amountOfReviews ++;
		$output = $output . "<div class='omschrijvingRij'>
			<div class='omschrijvingCell' style='width:150px;'>Geschreven door:<br>
				" . $row ['username'] . "
			</div>
			<div class='omschrijvingCell'>Score: ";
		switch ($row ['rating']) {
			case 0 :
				$output = $output . "<img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
				break;
			
			case 1 :
				$output = $output . "<img src='images/fullStar.jpg' alt='full star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
				break;
			
			case 2 :
				$output = $output . "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
				break;
			
			case 3 :
				$output = $output . "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/emptyStar.jpg' alt='empty star'><img src='images/emptyStar.jpg' alt='empty star'>";
				break;
			
			case 4 :
				$output = $output . "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/emptyStar.jpg' alt='empty star'>";
				break;
			
			case 5 :
				$output = $output . "<img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'><img src='images/fullStar.jpg' alt='full star'>";
				break;
		}
		$output = $output . "<br>" . htmlspecialchars ( nl2br ( $row ['review'] ) ) . "
			</div>
		</div>";
	}
	if ($amountOfReviews > 0) {
		return $output;
	} else {
		return $output . "Geen reviews geplaatst";
	}
}
function showProduct($pId, $full) {
	global $PDO;
	global $User;
	global $Page;
	global $Settings;
	$result = $PDO->prepare ( "SELECT * FROM `producten` WHERE id = :id AND actief = '1'" );
	$result->execute ( array (
			':id' => $pId 
	) );
	$row = $result->fetch ();
	if($row['id'] == NULL) {
		return "<div class=\"w3-container w3-blue\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Informatie</h3><p>Dit product bestaat niet, of is niet meer actief.</p></div>";
	} else {
		$Page->changePageTitle($row['productNaam']);
		$output = "<div id='productTitle'>
		<div id='productImage'>" . productImage ( $pId, 200, 200, 0 ) . "</div>
		<h3>" . $row ['productNaam'] . "</h3><h2>&euro; ".number_format((float)$row['prijs'], 2, ',', '.')."</h2>".inStock($row['voorraad'], $row['voorraad_leverancier'], 1,$row['service'] )."<br>art-nr: " . $row ['id'] . "<br><a href='index.php?action=showProduct&pId=$pId&changeCart=1&&aantal=1'>voeg toe aan winkelwagen</a><br>" . displayRating ( $pId ) . "
	</div>
	<div class='omschrijvingContainer'>
	<h3>Productomschrijving</h3>";
		$resultDesc = $PDO->prepare ( "SELECT * FROM productdetails WHERE productId = :id" );
		$resultDesc->execute ( array (
				':id' => $pId
		) );
		foreach ( $resultDesc as $rowDesc ) {
			$output = $output . "		<div class='omschrijvingRij'>
			<div class='omschrijvingCell'>
				" . $rowDesc ['naam'] . "
			</div>
			<div class='omschrijvingCell'>
				" . nl2br ( $rowDesc ['waarde'] ) . "
			</div>
		</div>";
		}
		$output = $output . "</div>";
		if($Settings->_get('allowReviews') != 0){
			$output = $output . "<div class='omschrijvingContainer'>". getreviews ( $pId )."</div>";
			if($User->isLoggedIn()){
				$output = $output . "<div id='writeReview'><h3>Review plaatsen</h3><form action='index.php?action=placeReview&pId=$pId' method='POST'><div class='omschrijvingRij'>Score: <select name='score'>";
				$b=1;
				while($b <= 5){
					$output = $output ."<option value='$b'>$b</option>";
					$b++;
				}
				$output = $output ."</select> Review: <br><textarea name='review' style='height:125px; width: 300px;'></textarea><br>
					<input type='submit' value='Review plaatsen'></input>
			</form></div></div>";
			} else {
				$output = $output . "<div id='writeReview'><h3>Review plaatsen</h3><a href='index.php?action=login'>Log in</a> om een review te plaatsen.</div>";
			}
		}
	}
	return breadcrumb($row['categorieId'], $row['productNaam']).$output;
}

$Page->addToBody ( showProduct ( $_GET ['pId'], 1 ) );

?>





