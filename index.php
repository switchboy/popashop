<?php
$time_start = microtime(true);
session_start();
if(file_exists("variables.php")){
	include("variables.php");
} else {
	include("setup.php");
	die();
}
if(file_exists("setup.php")){
	die("<b>Fout!</b> Delete eerst 'setup.php' om verder te gaan. 'setup.php' laten staan is een veligheids risico!");
}
include("functions.php");
include("classes.php");
$PDO = connectPDO($dbserver, $dbname, $dbusername, $dbpassword);
$User = new user();
$myCart = new cart();
$Page = new page();
$Landen = new countries();
$Settings = new siteSettings();
$ErrorHandler = new errorHandeling();
if(isset($_GET['print'])){
	$printThisPage = $_GET['print'];
} else {
	$printThisPage = '';
}
if(isset($_GET['changeCart'])){
		addToCart();
}

if(isset($_GET['action'])){
	$doAction = $_GET['action'];
} else {
	$doAction = '';
}
switch($doAction){
	case 'register':
		include("register.php");
		break;
		
	case 'login':
		include("login.php");
		break;

	case 'logout':
		include("logout.php");
		$Page->addToBody(imageSlider().showPromotions('0', '3'));
		break;
		
	case 'showCat':
		include("category.php");
		break;
		
	case 'showCart':
		$Page->changePageTitle("Winkelwagen");
		$Page->addToBody($myCart->showList(FALSE));
		break;
	
	case 'showProduct':
		include("product.php");
		break;
		
	case 'placeReview':
		include($Page->addToBody(placeReview($_GET['pId'], $_POST['review'], $_POST['score']))."product.php");
		break;
		
	case 'placeOrder':
		include ("placeOrder.php");
		break;
		
	case 'showSearch':
		$Page->addToBody(search(1));
		break;
	
	case 'showSearchAll':
		if($User->isAdmin() OR $User->canEditProducts()){
			$Page->addToBody(search(0));
		} else {
			$Page->addToBody("<div class=\"w3-container w3-red\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Fout!</h3><p>Uw account besschikt over onvoldoende rechten om inactieve producten te zoeken.</p></div><br>".search(1));
		}
		break;
		
	case 'manageProduct':
		include 'manageProduct.php';
		break;
	
	case 'admin':
		include 'admin.php';
		break;
	
	case 'addDiscountCoupon':
		$myCart->addDiscountCoupon($_POST['discountCoupon']);
		$Page->changePageTitle("Winkelwagen");
		$Page->addToBody($myCart->showList(FALSE));
		break;
	
	case 'doPromotions':
		include 'promotions.php';
		break;
	
	case 'showOrder':
		$Page->addToBody(showOrderDetails(TRUE));
		break;
		
	case 'showOrderList':
		$Page->addToBody(showConsumerOrderList());
		break;
		
	case 'employee':
		include 'employee.php';
		break;
	
	case 'service':
		include 'serviceCenter.php';
		break;
		
	case 'serviceOrders':
		include 'serviceOrders.php';
		break;
	
	case 'showMessage':
		$Page->addToBody(showMessage());
		break;
	
	case 'showNewsletter':
		$Page->addToBody(showNewsletter());
		break;
		
	case 'newsletter':
		$Page->addToBody(unsubscribeNewsletter());
		break;
			
	default:
		$Page->addToBody(createLandingPage());
		break;
}

$Page->buildPage();

?>