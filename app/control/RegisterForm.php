<?php

use Adianti\Database\TTransaction;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Form\TUniqueSearch;

/**
 * RegisterForm
 *
 * @version    1.0
 * @date       20/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class RegisterForm extends TPage
{
    private $html;
    private $form;
    
    /**
     * Class constructor
     * Creates the page
     */
    function __construct($param = null)
    {
        try
        {
            parent::__construct($param);

            $page = new TPageContainer();

            //Cria a form
            $this->form     = new TFormStruct();
            $this->form->setAligh('center');
            
            //Entradas
            $id         = new THidden('id');
            $name       = new TEntry('name');
            $email      = new TEntry('email');
            $password   = new TPassword('password');

            //propriedades
            $name->setProperty('placeholder',       'Seu nome completo');
            $email->setProperty('placeholder',      'Seu principal e-mail');
            $password->setProperty('placeholder',   'Sua senha mais forte');

            $this->form->addFieldLine('<b>Vamos começar!!</b>');
            $this->form->addFieldLine($name,        null, [350, null], true);
            $this->form->addFieldLine($email,       null, [350, null], true);
            $this->form->addFieldLine($password,    null, [350, null], true);
            $this->form->addFieldLine($id);

            $content_buttons = new TElement('div');
            $content_buttons->class = "content-buttons";
            
            //Botões de ações
            $button = new TButtonPress(null, 'Enviar');
            $button->setClass("full-green");
            $button->setAction([$this, 'onSave']);
            $content_buttons->add($button);

            $button = new TButtonPress(null, 'Voltar');
            $button->setClass("full-green");
            $button->setAction(['LoginForm']);
            $content_buttons->add($button);

            //Gera a form
            $this->form->generate();
            
            //Exibe
            parent::add($this->form);
            parent::add($content_buttons);
            parent::add($page);
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
        }
    }

    function onSave()
    {
        try
        {
            //Recupera dados do Post;
            $data  = $this->form->getData();

            //Volta os dados para o form
            $this->form->sendData('form', $data, false, false);
            $this->form->validate();
            
            $params             = [];
            $params['id']       = $data->id;
            $params['name']     = $data->name;
            $params['email']    = $data->email;
            $params['password'] = $data->password;

            TTransaction::open('sync');

            $person = PersonService::create($params, true);

            $user = User::where('id', '=', $person->id)->get();
            $user = $user[0];

            Self::createSession($user);

            TTransaction::close();
            
            $notify = new TNotify('Sucesso', 'Dados registrados!');
            $notify->setIcon('mdi mdi-check');
            $notify->addButton('Seguir', ['EventClientList']);
            $notify->show();
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
        }
    }

    private static function createSession($user)
    {
        if($user)
        {
            //Salva as telas
            $user->session_screens = $user->getScreens();

            $group              = new UserGroup();
            $group->user_id     = $user->id;
            $group->group_id    = 2;
            $group->store();
            
            //Define na sessão
            UserService::setSession($user);
        }
        else
        {
            throw new Exception("E-mail ou a senha estão incorretos.");
        }
    }
}
?>
