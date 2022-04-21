<?php
/**
 * RecoveryForm 
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class RecoveryForm  extends TPage
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

            //Cria a form
            $this->form = new TFormStruct();
            $this->form->setAligh('center');
            
            //Entradas
            $email = new TEntry('email');
            
            //Monta o formulário
            $this->form->addFieldLine($email, 'Seu E-mail',  [450, null]);

            $content_buttons = new TElement('div');
            $content_buttons->class = "content-buttons";

            //Botões de ações
            $button = new TButtonPress('Recuperar', 'mdi mdi-content-save-settings');
            $button->setAction([$this, 'onRecover']);
            $content_buttons->add($button);

            $button = new TButtonPress('Voltar', 'mdi mdi-keyboard-return');
            $button->setAction(['LoginForm']);
            $content_buttons->add($button);

            //Gera a form
            $this->form->generate();
            
            //Estrutura da pagina
            $page     = new TPageContainer();
            $page_box = $page->createBox(false);
            $page_box->add("<br/>");
            $page_box->add($this->form);
            $page_box->add($content_buttons);

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

    function onRecover()
    {
        try
        {
            TTransaction::open($this->db);

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data = $this->form->getData();

            if(!$data->email)
            {
                throw new Exception("O campo E-mail é obrigatório");
            }
            
            UserService::recover($data->email);

            TTransaction::close();
            
            $notify = new TNotify('Certo', 'Verifique seu e-mail, enviamos as instruções para recuperação da sua senha');
            $notify->setIcon('mdi mdi-help-circle-outline');
            $notify->addButton('Ok', ['LoginForm']);
            $notify->show();
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
   
    public static function onLogout()
    {
        TSession::freeSession();
        TServer::redirect('index.php?class=LoginForm');
    }
}
?>