<?php
session_start();

include('../config/config.php');
include('../lib/app.lib.php');

userIsConnected('ROLE_ADMIN');

$vue='addArticle';
$title = 'Ajouter un article';

//Initialisation des erreurs à false
$erreur = '';



//Tableau correspondant aux valeurs à récupérer dans le formulaire (hors fichiers)
$values = [
'titre'=>'',
'content'=>'',
'date'=>''
];

$tab_erreur =
[
'titre'=>'Le titre doit être rempli !',
'content'=>'Le contenu est vide !',
'date'=>'La date ne peut-être vide !'
];

try
{
    $dbh = connexion();


    if(array_key_exists('titre',$_POST))
    {
    
        //On valide que tous les champs ne sont pas vides sinon on référence un erreur !
        foreach($values as $champ => $value)
        {
            if(isset($_POST[$champ]) && trim($_POST[$champ])!='')
                $values[$champ] = $_POST[$champ];
            elseif(isset($tab_erreur[$champ]))   
                $erreur.= '<br>'.$tab_erreur[$champ];
            else
                $values[$champ] = NULL;
        }


        /** SI pas d'erreurs on fini la préparation des données et on save ! */
        if($erreur =='')
        {
            //Affectation de la date d'enregistrement
            $values['dateCreated']  = date('Y-m-d h:i:s');
            
            //On déplace le fichier transmis pour l'image d'entêt de l'article dans le répertoire upload/articles/ 
            if (isset($_FILES["picture"]) && $_FILES["picture"]["error"] == UPLOAD_ERR_OK) 
            {

                //test.jpg 124324325345436_test.jpg
                $tmp_name = $_FILES["picture"]["tmp_name"];
                $name = basename(time().'_'.$_FILES["picture"]["name"]);
                if(move_uploaded_file($tmp_name, REP_BLOG.REP_UPLOAD.'articles/'.$name))
                    $values['picture'] = $name;
                else
                    $values['picture'] = NULL;
            }
            else
                $values['picture'] = NULL;

            $values['userId'] = $_SESSION['user']['id'];

            $values['publish'] = $_POST['publish'];
                /*art_id 	
art_title 	art_content 	art_datepublish 	art_user_id 	art_datecreated 	
art_picture 	art_publish 
*/
            /**2 : Prépare ma requête SQL */
            $sth = $dbh->prepare('INSERT INTO article VALUES (NULL,:titre,:content, :date, :userId,:dateCreated,:picture,:publish)');
            var_dump($values);
            /** 3 : executer la requête */
            $sth->execute($values);
            
            $articleId = $dbh->lastInsertId(); 


            /* Enregistrer les catégories */
            $categories = $_POST['categories'];

            foreach($categories as $categorie)
            {
                $sth = $dbh->prepare('INSERT INTO article_has_categorie (articles_idarticles, categories_idcategories) VALUES (:idArticle,:idCat)');
                $sth->execute(['idArticle'=>$articleId,'idCat'=>$categorie]);
            }

            /** FLASHBAG
             * On ajoute un flashbag pour informé de l'ajout d'un utilisateur sur la page listUser
             * Le flashBag (notion connue avec le framework symfony) est une variable session qui accueille des messages 
             * à afficher lors de la prochaine requête (souvent automatique avec une redirection). Lors de l'affichage de la prochaine vue le flashbag sera analysé
             * puis son contenu affiché et enfin il sera vidé ! 
             * */
            addFlashBag('Article ajouté avec succès !');

            header('Location:listArticle.php');
            exit();
        }
    }
    else
    {
        $sth = $dbh->prepare('SELECT * FROM categorie ORDER BY cat_title ASC');
        $sth->execute();
        $categories = $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}
catch(PDOException $e)
{
    $erreur.='Une erreur de connexion a eu lieu :'.$e->getMessage();
}


include('tpl/layout.phtml');

