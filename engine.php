<?php

require_once 'init.php';

class TApplication extends AdiantiCoreApplication
{
    static public function run($debug = FALSE)
    {
        new TSession;
        
        if ($_REQUEST)
        {
        	//Se foi passado classe
        	if(!empty($_REQUEST['class']))
        	{
                TTransaction::open('sync');

                //Verifica o cadastro da tela
                $screen  = Screen::getByController($_REQUEST['class']);
                
                TTransaction::close();
  
                if($screen)
                {
                    $user         = UserService::getSession();
                    $user_screens = null;

                    //Verifica se a tela exige permissão
                    if(!$screen->fl_public)
                    {
                        //Verifica se tem login
                        if($user)
                        {

                        }
                        else
                        {
                            return TServer::redirect('index.php?class=LoginForm');
                        }

                        //Pega as telas
                        $user_screens = $user->session_screens;

                        // VALIDAÇÃO DE PRIMEIRO NIVEL
                        if($user_screens AND isset($user_screens[$_REQUEST['class']]))
                        {

                        }
                        elseif($user_screens AND !isset($user_screens[$_REQUEST['class']]))
                        {
                            throw new Exception("Você não tem permissão para acessar a tela {$screen->name}");
                        }
                    }
                }
                else
                {
                    throw new Exception("A tela {$_REQUEST['class']} não existe");
                }
                
                //Carrega pagina
                parent::run($debug);
        	}  
    	}
    }
}
try
{
	TApplication::run(TRUE);
}
catch (Exception $e) 
{
    ErrorService::send($e);
    
    $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
    $notify->setIcon('mdi mdi-close');
    $notify->show();
}
?>