<?php
session_start();

include('../config/config.php');
include('../lib/app.lib.php');

userIsConnected();

$vue='addArticle';
$title = 'Editer un article';

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
    /** On se connecte ! */
    $dbh = connexion();

    if(array_key_exists('id',$_POST))
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

        /* On affecte les autre values qui peuvent être vide */
        $values['id'] = $_POST['id'];
        $values['publish'] = $_POST['publish'];

        /** SI pas d'erreurs on fini la préparation des données et on save ! */
        if($erreur =='')
        {
            //On déplace le fichier transmis pour l'image d'entêt de l'article dans le répertoire upload/articles/ 
            if (isset($_FILES["picture"]) && $_FILES["picture"]["error"] == UPLOAD_ERR_OK) 
            {
              
                //On supprime l'ancienne image si elle existe 
                if(isset($_POST['oldpicture']))
                    unlink(REP_BLOG.REP_UPLOAD.'articles/'.$_POST['oldpicture']);

                //Puis on upload la nouvelle image ;)
                $tmp_name = $_FILES["picture"]["tmp_name"];
                $name = basename(time().'_'.$_FILES["picture"]["name"]);
                if(move_uploaded_file($tmp_name, REP_BLOG.REP_UPLOAD.'articles/'.$name))
                    $values['picture'] = $name;
            }
            else{
                 addFlashBag('Aucune image ajoutée (non fournie ou trop grande) !');
            }

            
            /** Création de la requête d'update 
             * On créé une chaine quoi représente notre requête.
             * L'iumage n'est mise à jour que si elle a été transmise, sinon on ne met pas dans l'update !!
            */
            $sql = 'UPDATE article SET art_title = :titre, art_content = :content, art_datepublish=:date,art_publish=:publish';
            if(isset($values['picture']))
                $sql.= ', art_picture=:picture';
            $sql.= ' WHERE art_id = :id';

            /**2 : Prépare ma requête SQL */
            $sth = $dbh->prepare($sql);
          
            /** 3 : executer la requête */
            $sth->execute($values);
            
            /** Supprimer tous les liens catégories 
             * On supprime tous les liens catégories dans la table de liaison
             * On sauvegarde ensuite les nouveaux ;)
            */
            $sth = $dbh->prepare('DELETE FROM article_has_categorie WHERE articles_idarticles = :id');
            $sth->execute(['id'=>$values['id']]);

            /* Enregistrer les catégories */
            $categories = $_POST['categories'];
            foreach($categories as $categorie)
            {
                $sth = $dbh->prepare('INSERT INTO article_has_categorie (articles_idarticles, categories_idcategories) VALUES (:idArticle,:idCat)');
                $sth->execute(['idArticle'=>$values['id'],'idCat'=>$categorie]);
            }

            /** FLASHBAG
             * On ajoute un flashbag pour informé de l'ajout d'un utilisateur sur la page listUser
             * Le flashBag (notion connue avec le framework symfony) est une variable session qui accueille des messages 
             * à afficher lors de la prochaine requête (souvent automatique avec une redirection). Lors de l'affichage de la prochaine vue le flashbag sera analysé
             * puis son contenu affiché et enfin il sera vidé ! 
             * */
            addFlashBag('Article modifié avec succès !');

            header('Location:listArticle.php');
            exit();
        }
    }
    elseif(array_key_exists('id',$_GET))
    {
        /** Si on est en mot GET - On récupère l'article dans la base 
         * pour récupérer ses données
         */
        
        $sth = $dbh->prepare('SELECT * FROM article WHERE art_id=:id');
        $sth->execute(['id'=>$_GET['id']]);
        $article = $sth->fetch(PDO::FETCH_ASSOC);

        /** On affecte ces données au tableau values pour l'insérer dans la vue ;) */
        $values = [
            'id' => $article['art_id'],
            'titre'=>  $article['art_title'],
            'content'=>$article['art_content'],
            'date'=>$article['art_datepublish'],
            'picture'=>$article['art_picture'],
            'publish'=>$article['art_publish']
        ];
    }
    else
    {
       /** Si pas d'id en POST ou GET on retourne à la liste */
        addFlashBag('Erreur d\'accès à l\'édition !');
        header('Location:listArticle.php');
    }


    /** EN mode POST ou GET
     * On récupère toutes les catégories présentent dans la base
     */
    $sth = $dbh->prepare('SELECT * FROM categorie ORDER BY cat_title ASC');
    $sth->execute();
    $categories = $sth->fetchAll(PDO::FETCH_ASSOC);

     /** EN mode POST ou GET
     * On récupère toutes les catégories de l'article
     */
    $sth = $dbh->prepare('SELECT categories_idcategories FROM article_has_categorie WHERE articles_idarticles =:id');
    $sth->execute(['id'=>$values['id']]);
    $articleCategories = $sth->fetchAll(PDO::FETCH_ASSOC);
    $tabcategories = [];
    foreach($articleCategories as $articleCategorie)
        $tabcategories[] = $articleCategorie['categories_idcategories'];
}
catch(PDOException $e)
{
    $erreur.='Une erreur de connexion a eu lieu :'.$e->getMessage();
}

include('tpl/layout.phtml');

