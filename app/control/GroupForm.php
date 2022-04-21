<?php
/**
 * GroupForm
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class GroupForm extends TCurtain
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
            $this->model  = 'Group';
            $this->parent = "GroupList";

            //Cria a form
            $this->form  = new TFormStruct('form_group');

            $criteria    = new TCriteria;
            $criteria->add(new TFilter('fl_public', '=', 'f'));
            
            //Entradas
            $id          = new TEntry('id');
            $name        = new TEntry('name');
            $icon        = new TEntry('icon');
            $screens_id  = new TDBOption('screens_id', $this->db, 'Screen', 'id', 'name', 'name', $criteria);
            $fl_admin    = new TSwitch('fl_admin');

            //Propriedades das entradas
            $id->setEditable(false);
            $screens_id->enableMultipleCheck();
            $screens_id->enableVerticalMode();
            $screens_id->setBoxSize(200);
            
            //Monta o formulário
            $this->form->addFieldLine($id,          'Código',           [80,  null]);
            $this->form->addFieldLine($name,        'Nome',             [350, null], true);
            $this->form->addFieldLine($fl_admin,    'Administrador');
            $this->form->addFieldLine($screens_id,  'Telas');

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
            $data = $this->form->getData($this->model);

            if($data->screens_id)
            {
                foreach ($data->screens_id as $key => $screen_id) 
                {
                    $data->addScreen(new Screen($screen_id));
                }
            }
            
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

                $object->screens_id = $object->getScreens();
                
                if($object->screens_id)
                {
                    $array = [];

                    foreach ($object->screens_id as $key => $screen) 
                    {
                        $array[$screen->id] = $screen->id;
                    }

                    $object->screens_id = $array;
                }

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