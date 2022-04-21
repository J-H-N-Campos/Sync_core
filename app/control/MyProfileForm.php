<?php
/**
 * MyProfileForm
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class MyProfileForm extends TCurtain
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
            $this->model  = 'Person';
            $this->parent = "MenuList";


            TTransaction::open($this->db);

            //Recupera da sessão
            $user   = UserService::getSession();
            $person = $user->getPerson();

            //Cria a form
            $this->form = new TFormStruct();
            
            //Entradas
            $password_new         = new TShowPassword('password_new');
            $password_new_confirm = new TShowPassword('password_new_confirm');

            $this->form->addTab('Conta', 'mdi mdi-account-circle-outline');
            $this->form->addFieldLine("<b>{$person->id} - {$person->name}</b>", 'Nome');
            $this->form->addFieldLine("<b>{$person->code}</b>", 'Id externo');
            $this->form->addFieldLine("<b>{$person->email}</b>", 'E-mail');
            
            $this->form->addTab('Senha', 'mdi mdi-key');
            $this->form->addFieldLine($password_new,         'Nova Senha',        array(250, null));
            $this->form->addFieldLine($password_new_confirm, 'Confirme a senha',  array(250, null));
            $this->form->addFieldLine(" ");
            $this->form->addFieldLine("<b>Padrões da senha:</b></br>
                                  - Mínimo de <b>8</b> carácteres; </br>
                                  - Deve ter pelo menos <b>2</b> letras e <b>2</b> números;</br>
                                  - Aceitos <b>carácteres especiais</b>");

            // //Botões de ações
            $button = new TButtonPress('Gravar', 'mdi mdi-content-save-settings');
            $button->setAction(array($this, 'onSave'));
            $this->form->addButton($button);

            //Gera a form
            $this->form->generate();

            TTransaction::close();

            //Estrutura da pagina
            $page     = new TPageContainer();
            $page_box = $page->createBox(false);
            $page_box->add(ScreenHelper::getHeader(__CLASS__));
            $page_box->add($this->form);

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

    /**
     * Método onSave()
     * 
     */
    function onSave()
    {
        try
        {
            TTransaction::open($this->db);

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data  = $this->form->getData($this->model);
            $user  = UserService::getSession();

            UserService::updatePassword($user->id, $data->password_new, $data->password_new_confirm);

            TTransaction::close();
           
            //Volta os dados para o form
            $this->form->setData($data);
            
            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->show();

            TServer::redirect('index.php?class=MyProfileForm');
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