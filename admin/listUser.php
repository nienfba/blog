<?php
session_start();

include('../config/config.php');
include('../lib/app.lib.php');

userIsConnected();

$vue='listUser';
$title = 'Liste des utilisateurs';
$erreur = false;
$users = array(); //On initialise le tableau des users.. si pas de users pas de users ;)

try
{
    //on récupère le flashbag
    $flashbag = getFlashBag();

    //On récupère tous les utilisateurs dans la base données
	//Fonction connectBdd() dans le fichier core/utilities.php
	$dbh = connexion();

	$sql  ='SELECT * FROM user';
	$sth = $dbh->prepare($sql);
	$sth->execute();
	$users = $sth->fetchAll(PDO::FETCH_ASSOC);
}
catch (PDOException $e)
{
	$erreur = 'Erreur base de données : '.$e->getMessage();
}

include('tpl/layout.phtml');
