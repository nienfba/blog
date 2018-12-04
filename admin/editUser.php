<?php
session_start();

include('../config/config.php');
include('../lib/app.lib.php');

userIsConnected('ROLE_ADMIN');

$vue='addUser';
$title = 'Modifier un utilisateur';

//Initialisation des erreurs à false
$erreur = '';

//Tableau correspondant aux valeurs à récupérer dans le formulaire (hors fichiers)
$values = [
'id'=>'',
'nom'=>'',
'prenom'=>'',
'email'=>'',
'bio'=>'',
'role'=>''];

//'password'=>'', pas de mot de passe car pas obligé de le modifier quand on édit un utilisateur

$tab_erreur =
[
'nom'=>'Le nom doit être rempli !',
'prenom'=>'Le prénom doit être rempli !',
'email'=>'L\'email doit être rempli !',
];
//'password'=>'Le mot de passe ne peut être vide'

try
{

    /** Edition d'un utilisateur on reçoit l'id à éditer
     */
    if(array_key_exists('id',$_GET))
    {
        //On charge les données user de la base
        $dbh = connexion();
        $sth = $dbh->prepare('SELECT * FROM user WHERE user_id = :id');
        $sth->execute(array('id'=>$_GET['id']));
        $user = $sth->fetch(PDO::FETCH_ASSOC);
        if($user)
        {
            $values['id'] = $user['user_id'];
            $values['nom'] = $user['user_firstname'];
            $values['prenom'] = $user['user_lastname'];
            $values['email'] = $user['user_email'];
            $values['bio'] = $user['user_bio'];
            $values['role'] = $user['user_role'];
            $values['avatar'] = $user['user_avatar'];
        }
    }
    elseif(array_key_exists('id',$_POST))
    {
        //Le formulaire est posté !
        //On valide que tous les champs ne sont pas vides et fournis !
        foreach($values as $champ => $value)
        {
            if(isset($_POST[$champ]) && trim($_POST[$champ])!='')
                $values[$champ] = $_POST[$champ];
            elseif(isset($tab_erreur[$champ]))   
                $erreur.= '<br>'.$tab_erreur[$champ];
            else
                $values[$champ] = NULL;
        }
        var_dump($values);

        
        //On valide l'égalité des 2 mots de passe !
        if($_POST['password'] != $_POST['passwordConf'])
            $erreur.= '<br> Erreur confirmation mot de passe';

        //On valide le champ email spécifique
        if(!filter_var($values['email'],FILTER_VALIDATE_EMAIL))
            $erreur.= '<br> Email erroné !';

        /** SI pas d'erreurs on fini la préparation des données et on save ! */
        if($erreur =='')
        {
            if($_POST['password'] != '')
                $values['password']     = password_hash($_POST['password'],PASSWORD_DEFAULT);
            
            
            //On déplace le fichier transmis pour l'avatar dans le répertoire upload/users/ 
            if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] == UPLOAD_ERR_OK && $_FILES["avatar"]["size"]<800000) {
                $tmp_name = $_FILES["avatar"]["tmp_name"];
                $name = basename(time().'_'.$_FILES["avatar"]["name"]);
                if(move_uploaded_file($tmp_name, REP_BLOG.REP_UPLOAD.'users/'.$name))
                    $values['avatar'] = $name;
            }

            /**1 : connexion au serveur de bdd */
            $dbh = connexion();
            /**2 : Prépare ma requête SQL */
            $sql = 'UPDATE user SET user_email=:email, user_firstname=:prenom, user_lastname=:nom, user_bio=:bio, user_role= :role';

            //Si on a pas d'avatar on n ele met pas à jour
            if(isset($values['avatar']))
                $sql.= ',user_avatar=:avatar';
            //Si on a pas de mot de passe on ne le met pas à jour
            if(isset($values['password']))
                $sql.= ',user_password=:password';
            
            $sql.=' WHERE user_id=:id';

            var_dump($values);
            var_dump($sql);

            $sth = $dbh->prepare($sql);
            /** 3 : executer la requête */
            if($sth->execute($values))
                addFlashBag('Utilisateur modifié avec succès !');

            header('Location:listUser.php');
            exit();
        }

    }
}
catch(PDOException $e)
{
    $erreur.='Une erreur de connexion a eu lieu :'.$e->getMessage();
}


include('tpl/layout.phtml');

