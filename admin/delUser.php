<?php
session_start();

include('../config/config.php');
include('../lib/app.lib.php');

userIsConnected('ROLE_ADMIN');

$vue = 'delUser';
$erreur = false;
$users = array();

try
{
    /* Si on reçoit bien un id utilisateur à supprimer */
    if(array_key_exists('id',$_GET))
    {
        /* Attention on ne peut pas supprimer l'utilisateur en cours ;) */
        if($_GET['id'] != $_SESSION['user']['id'])
        {
            $dbh = connexion();

            /** On vérifie si l'utilisateur n'a pas d'article attaché !
             * S'il en a on ne peut pas le supprimer bien sûr
             */
            $sql  ='SELECT art_id FROM article WHERE art_user_id = :id';
            $sth = $dbh->prepare($sql);
            $sth->bindValue(':id',$_GET['id'],PDO::PARAM_INT);
            $sth->execute();
            if($sth->fetchColumn() > 0)
                addFlashBag('L\'utilisateur est actuellement auteur de plusieurs articles sur le blog. Il est impossible de le supprimer (bon le développeur est fénéant il aurait pu vous proposer de basculer ces articles sur un autre auteur)!');
            else {
                /** On supprime d'abord le fichier Avatar lié à l'user
                 * En effet ne pas oublier de supprimer la photo sur le disque ;)
                 */
                $sql  ='SELECT user_avatar FROM user WHERE user_id = :id';
                $sth = $dbh->prepare($sql);
                $sth->bindValue(':id',$_GET['id'],PDO::PARAM_INT);
                $sth->execute();
                $user = $sth->fetch(PDO::FETCH_ASSOC);
                //Si le fichier existe sur le disque on le supprime !
                if($user && file_exists(REP_BLOG.REP_UPLOAD.'users/'.$user['user_avatar']))
                    unlink(REP_BLOG.REP_UPLOAD.'users/'.$user['user_avatar']);

                /** Puis on supprime l'utilisateur dans la bdd */
                $sql  ='DELETE FROM user WHERE user_id = :id';
                $sth = $dbh->prepare($sql);
                $sth->bindValue(':id',$_GET['id'],PDO::PARAM_INT);
                if($sth->execute())
                     addFlashBag('Utilisateur supprimé !');
                else
                     addFlashBag('Une erreur a empêché de supprimer l\'utilisateur !');
            }
        }
        else
        {
            addFlashBag('Il est interdit de se supprimer !');
        }
    }
    else
    {
        addFlashBag('Vous vous êtes perdu !');
    }
}
catch(PDOException $e)
{
    addFlashBag('Une erreur de connexion a eu lieu :'.$e->getMessage());
}

header('Location:listUser.php');