<?php
if($printThisPage != '1'){
	//Maak menu
	$return .=  "<div id='menu'>";
	
	if($User->isLoggedIn()){
		$return .=  "<div class='menuCat w3-theme'><a class='menuHeader' href=''>Welkom, ".$User->_get('username')."</a></div><ul class='w3-ul w3-border'>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=logout'>Uiloggen</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=showCart'>Winkelwagen</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=showOrderList'>Bestelgeschiedenis</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=register&subaction=7'>Klantgegevens aanpassen</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=register&subaction=9'>Wachtwoord aanpassen</a></li>";
		$return .=  "</ul>";
	}
	
	$resultMenu = $PDO->query("SELECT * FROM categorie WHERE subfromcatid is NULL");
	foreach( $resultMenu as $rowMenu){
		$return .=  "<div class='menuCat w3-theme'><a class='menuHeader' href='index.php?action=showCat&catId=".$rowMenu['id']."'>".$rowMenu['naam']."</a></div><ul class='w3-ul w3-border'>";
		$resultArtikelen = $PDO->prepare("SELECT * FROM categorie WHERE subfromcatid = :subfromcatid ");
		$resultArtikelen->execute(array(':subfromcatid' => $rowMenu['id']));
		foreach( $resultArtikelen as $rowArtikelen){
			$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=showCat&catId=".$rowArtikelen['id']."'>".$rowArtikelen['naam']."</a></li>";
			if($this->openCategory($rowArtikelen['id'])){
				$return .=  getChildMenuList($rowArtikelen['id']);
			}
		}
		$return .=  "</ul>";
	}
	
	global $Settings;
	$resultLinks = $PDO->query("SELECT * FROM shopmessages WHERE linkmenu = 1 OR linkfooter = 1");
	$linksBuffer = FALSE;
	$footerLinkBuffer = '';
	foreach($resultLinks as $rowLink){
		if($rowLink['linkmenu'] == '1'){
			$linksBuffer .= "<li class='w3-hover-light-blue'><a href='index.php?action=showMessage&id=".$rowLink['id']."'>".$rowLink['title']."</a></li>";
		}  
		if ($rowLink['linkfooter'] == '1'){
			$footerLinkBuffer .= "<a href='index.php?action=showMessage&id=".$rowLink['id']."'>".$rowLink['title']."</a> | ";
		}
	}
	$footerLinkBuffer = substr($footerLinkBuffer, 0, -3);
	if($linksBuffer){
		$return .=  "<div class='menuCat w3-theme'><a class='menuHeader' href=''>".$Settings->_get('siteName')."</a></div><ul  class='w3-ul w3-border'>$linksBuffer</ul>";
	}
		
	if($User->isEmployee() or $User->hasServiceRights() or $User->isAdmin()){
		if($Settings->_get('allowEmployees') != 0){
			$return .=  "<div class='menuCat w3-theme'><a class='menuHeader' href=''>Mederwerker</a></div><ul  class='w3-ul w3-border'>";
		} else {
			$return .=  "<div class='menuCat w3-theme'><a class='menuHeader' href=''>Logistiek</a></div><ul  class='w3-ul w3-border'>";
		}
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=employee&subaction=8'>Shop order</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=employee'>Logistiek</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=employee&subaction=1'>Inboeken</a></li>";
		if($User->hasServiceRights() or $User->isAdmin()){
			if($Settings->_get('allowServiceCenter') != 0){
				$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=service'>Service Center</a></li>";
				$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=service&subaction=11'>Mijn innames</a></li>";
				$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=service&subaction=10'>Mijn episodes</a></li>";
			} elseif($Settings->_get('allowServiceOrders') != 0) {
				$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=service'>Service Order</a></li>";
			}
		}
		if($Settings->_get('allowServiceOrders') != 0) {
			$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=employee&subaction=12'>Product terug op schap</a></li>";
		}
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=employee&subaction=14'>Product status</a></li>";
		$return .=  "</ul>";
	}
	
	if($User->canEditProducts() or $User->isAdmin()){
		$return .=  "<div class='menuCat w3-theme'><a class='menuHeader' href=''>Product opties</a></div><ul class='w3-ul w3-border'>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=manageProduct'>Product toevoegen</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=showSearchAll'>Product zoeken</a></li>";
		if($Settings->_get('allowPromotions') != 0) {
			$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=doPromotions'>Promoties</a></li>";
			$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=doPromotions&subaction=7'>Nieuwsbrieven</a></li>";
		}
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=doPromotions&subaction=12'>Welkomstbericht aanpassen</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=doPromotions&subaction=11'>Plaatje uploaden</a></li>";
		$return .=  "</ul>";
	}
		
	if($User->isAdmin()){
		$return .=  "<div class='menuCat w3-theme'><a class='menuHeader' href=''>Admin opties</a></div><ul class='w3-ul w3-border'>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=admin&subaction=13'>Pagina's beheren</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=admin&screen=1'>Gebruikers beheren</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=admin&screen=2'>Gebruikers maken</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=admin&screen=5'>Categorie&euml;n beheren</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=admin&screen=3'>Webshop beheren</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=admin&screen=4'>Logo aanpassen</a></li>";
		$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=admin&subaction=10'>Bekijk jaaromzet</a></li>";
		if($Settings->_get('allowServiceOrders') != 0) {
			$return .=  "<li class='w3-hover-light-blue'><a href='index.php?action=admin&subaction=14'>Openstaande betalingen</a></li>";
		}
		$return .=  "</ul>";
	}
	
	
	
	$return .=  "</div>";
}

function getChildMenuList($id){
	global $PDO;
	global $Page;
	$result = $PDO->prepare("SELECT * FROM categorie WHERE subfromcatid = :id ");
	$result->execute(array(':id' => $id));
	$return = '<ul>';
	foreach ($result as $row){
		$return = $return."<li class='w3-hover-light-blue'><a href='index.php?action=showCat&catId=".$row['id']."'>".$row['naam']."</a></li>";
		if($Page->openCategory($row['id'])){
			$return = $return.getChildMenuList($row['id']);
		}
	}
	$return = $return."</ul>";
	return $return;
}
?>