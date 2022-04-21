<?php
/**
 * PipService
 *
 * @version    1.0
 * @date       04-05-2017
 * @author     Rodrigo de Freitas
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class PipmeService
{
    public static function send($template_key, $methods, $person, $replaces = null, $push_options = null, $invite = null)
    {
        $user = $person->getUser();
        
        //Informações para o pip
        $params               = [];
        $params['identifier'] = $template_key;
        
        //Parametros extras
        if($replaces)
        {
            foreach ($replaces as $key => $replace) 
            {
                $params['replaces'][$key] = $replace;
            }
        }

        //Verifica se tem push antes
        if(in_array('push', $methods) AND in_array('sms', $methods))
        {
            //Verifica se o usuário tem push
            if($user->application_token)
            {
                foreach($methods as $key => $method)
                {
                    if($method == 'sms')
                    {
                        //Remove o sms
                        unset($methods[$key]);    
                    }
                }
            }
        }
        
        //Define os metodos
        foreach($methods as $method)
        {
            if($method == 'email')
            {
                if($person->email)
                {
                    $params['methods']['email']['to']     = $person->email;
                    $params['methods']['email']['name']   = $person->name;  
                    $params['methods']['email']['invite'] = $invite; 
                }
            }
            elseif($method == 'sms')
            {
                if($person->phone)
                {
                    $params['methods']['sms']['to'] = $person->phone;
                }
            }
            elseif($method == 'push')
            {
                if(!empty($user->application_token))
                {
                    $params['methods']['push']['to'] = $user->application_token;
                }
                    
                //O mesmo para o pip
                if(!empty($user->pip_code))
                {
                    $params['methods']['pip']['to']     = $user->pip_code;
                    
                    if(!empty($push_options['action']))
                    {
                        $params['methods']['pip']['action'] = $push_options['action'];
                    }

                    if(!empty($push_options['priority']))
                    {
                        $params['methods']['pip']['priority'] = $push_options['priority'];
                    }

                    if(!empty($push_options['icon']))
                    {
                        $params['methods']['pip']['icon'] = $push_options['icon'];
                    }
                }
            }
        }

        if(!empty($params['methods']))
        {
            //Faz o envio
            TApiRestClient::post('pipme', 'send', 'transmit', [$params]);    
        }
    }
}
?>