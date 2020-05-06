<?php
$temp = $_SESSION['sessionhash'];
session_destroy();
session_start();
$Page->addToBody("<div class=\"w3-container w3-blue\"><span onclick=\"this.parentElement.style.display='none'\" class=\"w3-closebtn\">&times;</span><h3>Informatie</h3><p>U bent uitgelogd.</p></div><br>");
$User = NULL;
$User = new user();
$update = $PDO->prepare("UPDATE winkelwagen SET sessionId = :sessionId WHERE id = :cartId" );
$update->execute(array(':sessionId' => $_SESSION['sessionhash'], ':cartId' => $temp));
?>