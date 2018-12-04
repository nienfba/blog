<?php

/** function connexion
 * Créée une instance de connexion vers la base de données
 * @param void
 * @return PDO $dbh
 */
function connexion()
{
    $dbh = new PDO(DB_DSN,DB_USER,DB_PASS);
    //On dit à PDO de nous envoyer une exception s'il n'arrive pas à se connecter ou s'il rencontre une erreur
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $dbh;
}

/** function userIsConnected
 * Vérifie si l'utilisateur est connecté avec le bon rôle ! 
 * Redirige vers la page de login sinon 
 * @param string $role
 * @return void
 */
function userIsConnected($role= 'ROLE_AUTHOR')
{
    $autho = false;

    //GEstion des rôles 2 rôle pour le moment c'est assez simple, si plus de rôle on pourra gérer une hiérarchie de rôles !!
    switch ($_SESSION['user']['role'])
    {
        case 'ROLE_ADMIN':
            $autho = true; //access granted
            break;
        case 'ROLE_AUTHOR':
            if($role == 'ROLE_AUTHOR') 
                $autho = true; //access granted si le role de la page ne nécessite pas ROLE_ADMIN
            break;
    }


    //Redirection vers le login si user n'a pas le droit ou n'est pas connect !
    if(!isset($_SESSION['connect']) || $_SESSION['connect'] != true || $autho == false)
    {
        header('Location:login.php');
        exit();
    }
    
}

/** function addFlashBag
 * Ajoute une valeur au flashbag
 * @param string $texte
 * @return void
 */

function addFlashBag($texte)
{
    if(!isset($_SESSION['flashbag']) || !is_array($_SESSION['flashbag']))
        $_SESSION['flashbag'] = array();

    $_SESSION['flashbag'][] = $texte;
}

/** function getFlashBag
 * Ajoute une valeur au flashbag
 * @param void
 * @return array flashbag
 */
function getFlashBag()
{
    if(isset($_SESSION['flashbag']) && is_array($_SESSION['flashbag']))
    {
        $flashbag = $_SESSION['flashbag'];
        unset($_SESSION['flashbag']);
        return $flashbag;
    }
    return false;
}