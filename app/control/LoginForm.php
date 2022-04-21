<?php
/**
 * LoginForm
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class LoginForm extends TPage
{
    protected $form; 
    private   $db;
    private   $model;
      
    /**
     * Classe construtora
     */
    function __construct()
    {
        try
        {
            parent::__construct();
            
            //Definições de conexão
            $this->db     = 'sync';
            $this->model  = 'Menu';
            $this->parent = "MenuList";

            //Se já existe login
            if(UserService::getSession())
            {
                TServer::redirect('index.php?class=Home');
            }
            else
            {
                TTransaction::open('sync');
                
                //Cria a form
                $this->form = new TFormStruct();
                $this->form->setAligh('center');
                
                //Entradas
                $login     = new TEntry('login');
                $password  = new TShowPassword('password');
                
                //propriedades
                $login->setProperty('placeholder',      'Seu email');
                $password->setProperty('placeholder',   'Sua senha');

                //Monta o formulário
                $this->form->addFieldLine('Informe suas credenciais para entrar');
                $this->form->addFieldLine($login,       null,   [450, null]);
                $this->form->addFieldLine($password,    null,   [250, null]);
                
                $content_buttons        = new TElement('div');
                $content_buttons->class = "content-buttons";

                //Botões de ações
                $button = new TButtonPress('Entrar', 'mdi mdi-subdirectory-arrow-right');
                $button->setAction([$this, 'onLogin']);
                $content_buttons->add($button);

                $button = new TButtonPress('Criar conta', 'mdi mdi-account');
                $button->setAction(['RegisterForm']);
                $content_buttons->add($button);

                $button = new TButtonPress('mdi mdi-lock-plus', 'Esqueci minha senha');
                $button->setAction(['RecoveryForm',   'onReload']);
                $content_buttons->add($button);

                //Gera a form
                $this->form->generate();

                TTransaction::close();

                //Estrutura da pagina
                $page     = new TPageContainer();
                $page_box = $page->createBox(false);
                $page_box->add("<br/>");
                $page_box->add($this->form);
                $page_box->add($content_buttons);
                $page_box->add("<div style='text-align: center;margin-top: 8px;'>by Sync Tecnologia</br>© 2022</div>");

                parent::add($page);
            }
        }
        catch (Exception $e) 
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        } 
    }

    function onLogin()
    {
        try
        {
            TTransaction::open($this->db);

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data     = $this->form->getData();
            $redirect = null;

            if(!$data->login OR !$data->password)
            {
                throw new Exception("Os campos login e senha são obrigatórios");
            }

            //Autentica
            $user = UserService::authenticate($data->login, $data->password, 'admin', false);
            
            self::createSession($user);

            TTransaction::close();

            self::onTerm($user, 'index.php?class=LoginForm');
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        }
    }
    
    public static function onTerm($user, $url_back)
    {
        TTransaction::open('sync');
            
        $url  = 'index.php?class=Home';
        $rest = parse_ini_file('app/config/rest_client.ini', true);
        
        if(!$user->fl_term AND !empty($rest['peopleguard']['location_code']))
        {
            //dados
            $person            = $user->getPerson();
            $person_individual = $person->getIndividual();
            $ini               = parse_ini_file('app/config/application.ini');

            //guard
            $guard_data                 = [];
            $guard_data['cpf']          = $person_individual->cpf;
            $guard_data['url_back']     = "{$ini['url']}/{$url_back}";
            $guard_data['url_continue'] = "{$ini['url']}/index.php?class=LoginForm&method=onContinue&static=1";
    
            //Chama no guard
            $url  = TRestClient::post('peopleguard', 'owner', 'create', [$rest['peopleguard']['location_code'], $guard_data]);
            $url  = $url['url'];
        }
        
        TTransaction::close();
        
        TServer::redirect($url);
    }

    public static function onContinue()
    {
        try
        {
            TTransaction::open('sync');
     
            $user = UserService::getSession();
            
            if($user)
            {
                $user->fl_term = true;
                $user->store();
            }

            TTransaction::close();
            
            if($user->isCompany())
            {
                TServer::redirect('index.php?class=PreEntryList');
            }
            else
            {
                TServer::redirect('index.php?class=Home');
            }
        }
        catch (Exception $e)
        {
            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        }
    }

    private static function createSession($user)
    {
        if($user)
        {
            //Salva as telas
            $user->session_screens = $user->getScreens();
            
            //Define na sessão
            UserService::setSession($user);
        }
        else
        {
            throw new Exception("E-mail ou a senha estão incorretos.");
        }
    }
    
    public static function onLogout()
    {
        TSession::freeSession();
        TServer::redirect('index.php?class=LoginForm');
    }

    public static function updatePermissions()
    {
        try
        {
            TTransaction::open('sync');
            
            $user     = UserService::getSession();
            $new_user = new User($user->id);
            
            self::createSession($new_user);
            
            TTransaction::close();
            
            TServer::reload();
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        }
    }
}
?>