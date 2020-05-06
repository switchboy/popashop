<?php

require_once "src/Mollie/API/Autoloader.php";


/*
 * Initialize the Mollie API library with your API key.
 *
 * See: https://www.mollie.com/beheer/account/profielen/
 */
$mollie = new Mollie_API_Client;
$mollie->setApiKey($Settings->_get('mollieApiKey'));

?>