<?php
session_start();
/** On modifie les variable de sssion puis on les supprime ! */
$_SESSION['connect'] = false;
$_SESSION['user'] = '';
unset($_SESSION['connect']);
unset($_SESSION['user']);


header('Location:login.php');