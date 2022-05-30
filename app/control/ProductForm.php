<?php

use Adianti\Widget\Form\THidden;
use Adianti\Widget\Wrapper\TDBUniqueSearch;

/**
 * ProductForm
 *
 * @version    1.0
 * @date       20/05/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class ProductForm extends TCurtain
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
            
            //Definições d  e conexão
            $this->db       = 'sync';
            $this->model    = 'Product';
            $this->parent   = "ProductList";

            //Cria a form
            $this->form = new TFormStruct('product_form');

            //Ação do close
            parent::setActionClose([$this->parent]);
            
            //Entradas
            $id             = new TEntry('id');
            $name           = new TEntry('name');
            $bar_code       = new TEntry('bar_code');
            $category_id    = new TDBUniqueSearch('category_id', $this->db, 'ProductCategory', 'id', 'name');
            $price          = new TNumeric('price', 2,',','.');
            $path           = new TArchive('path');
            $user_id        = new THidden('user_id');

            //Propriedades das entradas
            $id->setEditable(false);
            $category_id->setMinLength(0);
            
            //Monta o formulário
            $this->form->addTab('Formulário', 'mdi mdi-filter-outline');
            $this->form->addFieldLine($id,          'Id',               [80,  null]);
            $this->form->addFieldLine($name,        'Nome',             [350, null], true,  false, 1);
            $this->form->addFieldLine($bar_code,    'Código de barra',  [200, null], false, false, 1);
            $this->form->addFieldLine($category_id, 'Categoria',        [200, null], false, false, 2);
            $this->form->addFieldLine($price,       'Preço',            [100, null], true,  false, 2);
            $this->form->addFieldLine($path,        'Arquivo',          [200, null], false, false, 1);
            $this->form->addFieldLine($user_id);

            //Botões de ações
            $button = new TButtonPress('Gravar', 'mdi mdi-content-save-settings');
            $button->setAction([$this, 'onSave', ['effect' => false]]);
            $this->form->addButton($button);
            
            $button = new TButtonPress('Novo', 'mdi mdi-plus');
            $button->setAction([$this, 'onEdit']);
            $this->form->addButton($button);

            //Gera a form
            $this->form->generate();
            
            //Estrutura da pagina
            $page = new TPageContainer();
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
            
            //pega o usuário da sessão
            $user = UserService::getSession();

            //Recupera dados do Post;
            $data = $this->form->getData($this->model);
            $data->user_id = $user->id;
            
            //Grava
            $data->store();

            TTransaction::close();
           
            //Volta os dados para o form
            $this->form->setData($data);
            
            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->show();
            
            parent::closeWindow();
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
     * Método onEdit()
     * 
     */
    function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                TTransaction::open($this->db);
                
                $object = new $this->model($key);

                $this->form->setData($object);
                
                TTransaction::close();
            }
            else
            {
                $this->form->clear();
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
}
?>