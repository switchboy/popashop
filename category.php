<?php
function hasChildren($catId){
	global $PDO;
	$result = $PDO->prepare("SELECT COUNT(*) FROM categorie WHERE subfromcatId = :subfromcatId");
	$result->execute(array(':subfromcatId' => $catId));
	$number_of_rows = $result->fetchColumn();
	if($number_of_rows != 0){
		return TRUE;
	} else {
		return FALSE;
	}
}

function showChildList($catId){
	global $PDO;
	global $Page;
	$resultTitle = $PDO->prepare("SELECT * FROM categorie WHERE id = :subfromcatId");
	$resultTitle->execute(array(':subfromcatId' => $catId));
	$rowTitle = $resultTitle->fetch();
	$result = $PDO->prepare("SELECT * FROM categorie WHERE subfromcatId = :subfromcatId");
	$result->execute(array(':subfromcatId' => $catId));
	$returnstring = "<h3>".$rowTitle['naam']."</h3><ul>";
	$Page->changePageTitle($rowTitle['naam']);
	foreach( $result as $row){
		$returnstring = $returnstring."<li><a href='index.php?action=showCat&catId=".$row['id']."'>".$row['naam']."</a></li>";
	}
	$returnstring = $returnstring."</ul>";
	return $returnstring;
}

function showProductList($catId, $start, $limit){
	global $PDO;
	global $Page;
	$resultTitle = $PDO->prepare("SELECT * FROM categorie WHERE id = :subformcatId");
	$resultTitle->execute(array(':subformcatId' => $catId));
	$rowTitle = $resultTitle->fetch();
	if($rowTitle['id'] == NULL){
		return "<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Deze categorie bestaat niet.</p></div><br>".imageSlider().showPromotions('0', '3');
	}
	$Page->changePageTitle($rowTitle['naam']);
	$resultCount = $PDO->prepare("SELECT COUNT(*) FROM producten WHERE 	categorieId = :catId  AND actief = '1'");
	$resultCount->execute(array(':catId' => $catId));
	$number_of_rows = $resultCount->fetchColumn();
	$amount_of_pages = ceil($number_of_rows/$limit);
	$pageNumber = 1;
	$pagenumbers = '';
	while ($pageNumber <= $amount_of_pages){
		$startPlace = ($pageNumber-1)*$limit;
		if($startPlace == $start){
			$pagenumbers = $pagenumbers.$pageNumber." ";
		} else {
			$pagenumbers = $pagenumbers."<a href='index.php?action=showCat&catId=$catId&limit=$limit&start=$startPlace'>".$pageNumber."</a> ";
		}
		$pageNumber++;
	}
	
	$result = $PDO->prepare("SELECT * FROM producten WHERE 	categorieId = :catId AND actief = '1' LIMIT :start, :limit");
	$result->execute(array(':catId' => $catId, ':start' => $start, ':limit' => $limit));
	$returnstring = "<h3>".$rowTitle['naam']."</h3>Artikelen per pagina: <a href='index.php?action=showCat&catId=$catId&limit=25'>25</a> | <a href='index.php?action=showCat&catId=$catId&limit=50'>50</a> | <a href='index.php?action=showCat&catId=$catId&limit=100'>100</a> | <a href='index.php?action=showCat&catId=$catId&limit=250'>250</a> | <a href='index.php?action=showCat&catId=$catId&limit=18446744073709551615'>Alle</a> 
	<br>Pagina: $pagenumbers<br><br>
	<div class='table'>
		<div class='tableRow'><div class=tableCell>Status</div><div class=tableCell></div><div class=tableCell>Productomschrijving</div><div class=tableCell>Stukprijs</div><div class=tableCell>winkelwagen</div></div>";
	$count = 0;
	foreach ($result as $row){
		$returnstring = $returnstring
			."<div class='tableRow'><div class=tableCell>"
			.inStock($row['voorraad'], $row['voorraad_leverancier'], 1, $row['service'])
			."</div><div class=tableCell>".productImage($row['id'], 75, 75, 1)."</div><div class=tableCell><a href='index.php?action=showProduct&pId="
			.$row['id']
			."'>"
			.$row['productNaam']
			."</a> </div><div class=tableCell>&euro; "
			.number_format((float)$row['prijs'], 2, ',', '.')
			."</div><div class=tableCell><a href='index.php?changeCart=1&action=showCat&catId=$catId&limit=$limit&start=$start&pId="
			.$row['id']
			."&aantal=1'>voeg toe aan winkelwagen</a></div></div>";
		$count++;
	}
	if($count == 0){
		$returnstring = $returnstring
			."<div class='tableRow'><div class=tableCell></div><div class=tableCell></div><div class=tableCell>Geen producten in deze categorie besschikbaar</div><div class=tableCell></div><div class=tableCell></div></div>";
	}
	$returnstring = $returnstring."</div><br>Pagina: $pagenumbers<br>
	Artikelen per pagina: <a href='index.php?action=showCat&catId=$catId&limit=25'>25</a> | <a href='index.php?action=showCat&catId=$catId&limit=50'>50</a> | <a href='index.php?action=showCat&catId=$catId&limit=100'>100</a> | <a href='index.php?action=showCat&catId=$catId&limit=250'>250</a> | <a href='index.php?action=showCat&catId=$catId&limit=18446744073709551615'>Alle</a>  
	<br>Weergegeven $count van $number_of_rows producten in de categorie ".$rowTitle['naam'];
	return $returnstring;
}
if(isset($_GET['catId']) AND $_GET['catId'] != '0'){
	if(hasChildren($_GET['catId'])){
		$Page->addToBody(breadcrumb($_GET['catId'], FALSE).showPromotions($_GET['catId'], '1').showChildList($_GET['catId']));
	} else {
		if(isset($_GET['start'])){$start = $_GET['start'];} else {$start = 0;}
		if(isset($_GET['limit'])){$limit = $_GET['limit'];} else {$limit = 25;}
		$Page->addToBody(breadcrumb($_GET['catId'], FALSE).showPromotions($_GET['catId'], '1').showProductList($_GET['catId'], $start, $limit));
	}
} else {
	$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Deze categorie bestaat niet.</p></div><br>".imageSlider().showPromotions('0', '3'));
}

?>