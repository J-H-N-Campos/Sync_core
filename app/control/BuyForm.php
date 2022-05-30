<?php

use Adianti\Database\TTransaction;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Wrapper\TDBUniqueSearch;

/**
 * BuyForm
 *
 * @version    1.0
 * @date       23/05/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class BuyForm extends TCurtain
{
    protected $form; 
    private   $db;
    private   $model;
      
    /**
     * Classe construtora
     */
    function __construct($param = null)
    {
        try
        {
            parent::__construct($param);
            
            //Definições d  e conexão
            $this->db       = 'sync';
            $this->model    = 'Product';
            $this->parent   = "BuyList";

            //Cria a form
            $this->form = new TFormStruct('product_form');

            //Ação do close
            parent::setActionClose([$this->parent]);
            
            if(!empty($param['id']))
            {
                TTransaction::open('sync');

                $product = Product::where('id', '=', $param['id'])->get();
                
                TTransaction::close();

                $product = $product[0];
            }

            //Entradas
            $name           = new TEntry('name');
            $bar_code       = new TEntry('bar_code');
            $category_id    = new TDBUniqueSearch('category_id', $this->db, 'ProductCategory', 'id', 'name');
            $price          = new TNumeric('price', 2,',','.');

            //Propriedades das entradas
            $name->setValue($product->name);
            $bar_code->setValue($product->barcode);
            $category_id->setValue($product->category_id);
            $price->setValue($product->price);

            $name->setEditable(false);
            $bar_code->setEditable(false);
            $category_id->setEditable(false);
            $price->setEditable(false);
            $category_id->setMinLength(0);
            
            //Monta o formulário
            $this->form->addTab('Formulário', 'mdi mdi-filter-outline');
            $this->form->addFieldLine($name,        'Nome',             [350, null], true,  false, 1);
            $this->form->addFieldLine($bar_code,    'Código de barra',  [200, null], false, false, 1);
            $this->form->addFieldLine($category_id, 'Categoria',        [200, null], false, false, 2);
            $this->form->addFieldLine($price,       'Preço',            [100, null], true,  false, 2);

            //Botões de ações
            $button = new TButtonPress('Comprar', 'mdi mdi-content-save-settings');
            $button->setAction([$this, 'onBuy', ['effect' => false]]);
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
    function onBuy($param = null)
    {
        try
        {
            TTransaction::open($this->db);

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data = $this->form->getData($this->model);
            
            //Grava
            //$data->store();

            TTransaction::close();
           
            //Volta os dados para o form
            $this->form->setData($data);
            
            $notify = new TNotify('Sucesso', 'A sua compra foi realizada. Enviamos um e-mail com os seus dados');
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