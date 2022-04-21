<?php
/**
 * ErrorService
 *
 * @version    1.0
 * @date       21/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class ErrorService
{
    public static function send($e)
    {
        TTransaction::open('sync');

        //Verifica se existe login
        $user              = UserService::getSession();
        $content_extension = null;

        if($user)
        {
            $person             = $user->getPerson();
            $content_extension .= "User: {$person->id} - {$person->name}<br/>";
        }

        TTransaction::close();    
        
        //TError::send('sync-errors', $e, $content_extension);
    }

    public static function sendByText($title, $content)
    {
        //TError::sendByText('sync-errors', $title, $content);
    }
}
?>
