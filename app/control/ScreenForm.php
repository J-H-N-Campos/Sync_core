<?php
/**
 * ScreenForm
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class ScreenForm extends TCurtain
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
            
            //Definições de conexão
            $this->db       = 'sync';
            $this->model    = 'Screen';
            $this->parent   = "ScreenList";
            
            parent::setActionClose([$this->parent]);

            //Cria a form
            $this->form     = new TFormStruct('screen_form');
            
            //Entradas
            $id             = new TEntry('id');
            $name           = new TEntry('name');
            $controller     = new TEntry('controller');
            $icon           = new TEntry('icon');
            $menu_id        = new TDBCombo('menu_id', $this->db, 'Menu',  'id', 'name', 'name');
            $fl_view_menu   = new TSwitch('fl_view_menu');
            $fl_public      = new TSwitch('fl_public');
            $groups_id      = new TDBOption('groups_id', $this->db, 'Group', 'id', 'name', 'name');

            //atributos
            $groups_id->enableMultipleCheck();
            $groups_id->setBoxSize(130);

            //Propriedades das entradas
            $id->setEditable(false);

            //Monta o formulário
            $this->form->addTab('Dados',                'mdi mdi-format-page-break');
            $this->form->addFieldLine($id,              'Código',           [80,  null]);
            $this->form->addFieldLine($name,            'Nome',             [400, null], true);
            $this->form->addFieldLine($controller,      'Controladora',     [400, null], true);
            $this->form->addFieldLine($icon,            'Ícone',            [300, null], true);
            $this->form->addFieldLine($menu_id,         'Menu',             [250, null], true);
            $this->form->addFieldLine($fl_view_menu,    'No menu');
            $this->form->addFieldLine($fl_public,       'Público');
            
            //separador
            $this->form->addSeparator('Adicionar ao grupo', 'mdi mdi-account-group');
            $this->form->addFieldLine($groups_id,   'Grupos');

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
            
            //Grava
            $data->store();

            //Groups
            if($data->groups_id)
            {
                $group_screens = Group::getGroupsByScreen($data->id);
                $adds          = [];

                foreach ($data->groups_id as $key => $group_id) 
                {
                    $group = new Group($group_id);
                    $group->addScreen(new Screen($data->id));
                    $group->store();

                    $adds[$group->id] = true;
                }

                //Descobre qual foi removida
                if($group_screens)
                {
                    foreach ($group_screens as $key => $group) 
                    {
                        if(!isset($adds[$group->id]))
                        {
                            GroupScreen::where('screen_id', '=', $data->id)->where('group_id', '=', $group->id)->delete();
                        }
                    }
                }
            }

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

                $object->groups_id = Group::getGroupsByScreen($object->id);

                if($object->groups_id)
                {
                    $array = [];

                    foreach ($object->groups_id as $key => $group) 
                    {
                        $array[$group->id] = $group->id;
                    }

                    $object->groups_id = $array;
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