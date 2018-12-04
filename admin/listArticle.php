<?php
session_start();

include('../config/config.php');
include('../lib/app.lib.php');

userIsConnected();

$vue='listArticle';
$title = 'Liste des articles';
$erreur = false;
$users = array(); //On initialise le tableau des users.. si pas de users pas de users ;)

try
{
    //on récupère le flashbag
    $flashbag = getFlashBag();

    //On récupère tous les utilisateurs dans la base données
	//Fonction connectBdd() dans le fichier core/utilities.php
	$dbh = connexion();

	$sql  ='SELECT * FROM article';
	$sth = $dbh->prepare($sql);
	$sth->execute();
	$articles = $sth->fetchAll(PDO::FETCH_ASSOC);
}
catch (PDOException $e)
{
	$erreur = 'Erreur base de données : '.$e->getMessage();
}

include('tpl/layout.phtml');
