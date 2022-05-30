<?php
/**
 * UserForm
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class UserForm extends TCurtain
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
            $this->db     = 'sync';
            $this->model  = 'User';
            $this->parent = "UserList";
            
            if(!empty($param['return']))
            {
                parent::setActionClose([$param['return']]);
            }
            else
            {
                parent::setActionClose([$this->parent]);
            }

            TTransaction::open('sync');

            //Cria a form
            $this->form  = new TFormStruct('form_user');

            $criteria    = new TCriteria();
            $criteria->add(new TFilter('id', 'IN',      "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id )"));
            $criteria->add(new TFilter('id', 'NOT IN',  "NOESC: (SELECT sys_user.id FROM sys_user WHERE sys_user.id = bas_person.id )"));

            $criteria2   = new TCriteria();
            $criteria2->add(new TFilter('fl_on', '=', 't'));

            //Entradas
            $id         = new TDBUniqueSearch('id', $this->db, 'Person', 'id', 'name', 'name', $criteria);
            $groups_id  = new TOption('groups_id');
            $fl_on      = new TSwitch('fl_on');
            $pip_code   = new TEntry('pip_code');
            
            //Funções das entradas
            $id->setMinLength(0);
            $pip_code->setEditable(false);
            $id->setEditable(false);
            $id->disableIdSearch();
            $id->setMask("{id} - {description}");
            $id->setOperator('ilike');
            $groups_id->addItems(Group::getArray());
            $groups_id->enableMultipleCheck(true);
            $groups_id->setBoxSize(110);
            
            //Monta o formulário
            $this->form->addTab('Formulário', 'mdi mdi-filter-outline');
            $this->form->addFieldLine($id,              'Pessoa',           [450, null]);
            $this->form->addFieldLine($pip_code,        'Pip Code',         [450, null]);
            $this->form->addFieldLine($groups_id,       'Grupos de acesso', [700, null]);
            $this->form->addFieldLine($fl_on,           'Ativo');     
            
            //Botões de ações
            $button = new TButtonPress('Gravar', 'mdi mdi-content-save-settings');

            if(!empty($param['return']))
            {
                $button->setAction([$this, 'onSave', ['effect' => false, 'return' => $param['return']]]);
            }
            else
            {
                $button->setAction([$this, 'onSave', ['effect' => false]]);
            }
            
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
            
            //Recupera dados do Post;
            $data = $this->form->getData($this->model);
           
            //Volta os dados para o form
            $this->form->setData($data);
            
            //Validação
            $this->form->validate();

            $param                = [];
            $param['id']          = $data->id;
            $param['groups']      = [];
            $param['pip_code']    = $data->pip_code;

            //Grupos
            if($data->groups_id)
            {
                foreach ($data->groups_id as $key => $value) 
                {
                    $param['groups'][] = $value;
                }
            }

            $user = UserService::create($param);

            TTransaction::close();
            
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
                TTransaction::open($this->db);

                $object             = new $this->model($param['key']);
                $object->groups_id  = $object->getGroups();

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