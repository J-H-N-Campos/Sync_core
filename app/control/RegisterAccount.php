<?php
/**
 * RegisterAccount
 *
 * @version    1.0
 * @date       20/05/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class RegisterAccount extends TPage
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
            TTransaction::open('sync');
            
            //Cria a form
            $this->form = new TFormStruct();
            $this->form->setAligh('center');

            $url_imagem = "./images/logounivates.png";
            
            //Entradas
            $login      = new TEntry('login');
            $name       = new TEntry('name');
            $password   = new TShowPassword('password');
            $birth_date = new TDate('birth_date');
            $cpf        = new TEntry('cpf');

            //propriedades
            $login->setProperty('placeholder',      'Seu melhor e-mail');
            $password->setProperty('placeholder',   'Sua melhor senha');
            $name->setProperty('placeholder',       'Seu nome');
            $birth_date->setProperty('placeholder', 'Sua data de nascimento');
            $cpf->setProperty('placeholder',        'Seu CPF');
            $birth_date->setMask("dd/mm/yyyy");
            $cpf->setMask("999.999.999-99");

            //Monta o formulário
            $this->form->addFieldLine("<div class='logo-login'><img src='{$url_imagem}'></div>");
            $this->form->addFieldLine("<b>Sistema Sync</b>");
            $this->form->addFieldLine('Informe suas credenciais para criar a sua conta. Após criada você terá um prazo de 15 minutos para confirmar a sua senha no menu do seu usuário');
            $this->form->addFieldLine($name,        null,   [450, null], true);
            $this->form->addFieldLine($birth_date,  null,   [450, null], true);
            $this->form->addFieldLine($cpf,         null,   [250, null], true);
            $this->form->addFieldLine($login,       null,   [250, null], true);
            $this->form->addFieldLine($password,    null,   [250, null], true);

            $content_buttons        = new TElement('div');
            $content_buttons->class = "content-buttons";

            //Botões de ações
            $button = new TButtonPress('Criar conta', 'mdi mdi-account-arrow-right');
            $button->setAction(['RegisterAccount', 'onCreateAccount']);
            $content_buttons->add($button);

            $button = new TButtonPress('Voltar', 'mdi mdi-keyboard-return');
            $button->setAction(['LoginForm']);
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
        catch (Exception $e) 
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        } 
    }

    function onCreateAccount()
    {
        try
        {
            TTransaction::open($this->db);

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data     = $this->form->getData();
            $redirect = null;

            $params                             = [];
            $params['email']                    = $data->login;         
            $params['password']                 = $data->password;
            $params['name']                     = $data->name;
            $params['individual']['cpf']        = $data->cpf;
            $params['individual']['birth_date'] = $data->birth_date;

            //cria a pessoa e o usuário
            $person = PersonService::create($params, true, true);
                                    
            $user = new User($person->id);

            self::createSession($user);

            TServer::redirect('index.php?class=BuyList');

            TTransaction::close();
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
            throw new Exception("E-mail ou a senha estão incorretos");
        }
    }
}
?>