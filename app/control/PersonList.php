<?php

/**
 * PersonList
 *
 * @version    1.0
 * @date       09/05/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class PersonList extends TPage
{
    private $loaded;
    private $datagrid;
    private $db;
    private $model;
    private $page_navigation;
    private $form;
    
    /**
     * Classe contrutora
     * 
     */
    public function __construct()
    {
        try
        {
            parent::__construct();
            
            //Definições de conexão
            $this->db     = 'sync';
            $this->model  = 'Person';
            $this->parent = 'PersonForm';
            
            //Busca - Cria a form
            $this->form = new TFormStruct();
            $this->form->enablePostSession($this->model);
            
            //Busca - Entradas
            $phone          = new TEntry('phone');
            $id             = new TDBUniqueSearch('id',         $this->db,  'Person',   'id', 'name',   'name');
            $group_id       = new TDBUniqueSearch('group_id',   $this->db,  'Group',    'id', 'name',   'name');

            $id->setMinLength(0);
            $id->disableIdSearch();
            $id->setMask("{id} - {description}");
            $id->setOperator('ilike');
            $id->setService('AdiantiMultiSearchServiceAccent');
            $group_id->setMinLength(0);
            $phone->setMask('99999999999');

            //Busca - Formulário
            $this->form->addTab('Dados', 'mdi mdi-chart-donut');
            $this->form->addFieldLine($id,      'Pessoa',    [600, null]);
            $this->form->addFieldLine($phone,   'Telefone',  [120, null]);
            
            //Aba Usuário
            $this->form->addTab('Usuário',  'mdi mdi-account');
            $this->form->addFieldLine($group_id, 'Grupo de usuário', [300, null], false, null, 1);

            //Busca - Ações
            $button = new TButtonPress('Filtrar', 'mdi mdi-filter');
            $button->setAction([$this, 'onSearch']);
            $this->form->addButton($button);

            $button = new TButtonPress('Novo', 'mdi mdi-plus');
            $button->setAction([$this->parent, 'onEdit']);
            $this->form->addButton($button);

            //Busca - Gera a forma
            $this->form->generate();
            
            //Cria datagrid
            $this->datagrid = new TDataGridResponsive;
            $this->datagrid->setConfig(false);
            $this->datagrid->setDb($this->db);

            $this->datagrid->addColumnReduced('dt_register',    'mdi mdi-calendar-text',            ['TDateService',    'timeStampToBr'],   'Data do cadastro');
            $this->datagrid->addColumnReduced('dt_update',      'mdi mdi-calendar-sync-outline',    ['TDateService',    'timeStampToBr'],   'Última atualização');

            $this->datagrid->addColumn('id',         'Id',      [$this, 'getId']);
            $this->datagrid->addColumn('name',       'Nome');
            $this->datagrid->addColumn('email',      'Email');
            $this->datagrid->addColumn('phone',      'Telefone');
            $this->datagrid->addColumn('name',       'Tipo',    [$this, 'getType']);

            $this->datagrid->addGroupAction('mdi mdi-dots-vertical');
            $this->datagrid->addGroupActionButton('Editar',             'mdi mdi-pencil',           [$this->parent,    'onEdit'],   false,  'code');
            $this->datagrid->addGroupActionButton('Criar usuário',      'mdi mdi-account-plus',     [$this, 'onCreateUser']);
            $this->datagrid->addGroupActionButton('Deletar',            'mdi mdi-delete',           [$this, 'onDelete']);

            //grupos de botões secundários
            $this->datagrid->addGroupAction('mdi mdi-account-circle');
            $this->datagrid->addGroupActionButton('Editar acesso',      'mdi mdi-account-edit',     ['UserForm',    'onEdit',   ['return' => 'PersonList']]);
            $this->datagrid->addGroupActionButton('Remover',            'mdi mdi-account-minus',    [$this,         'onRemoveUser']);
            
            //Nevegação
            $this->page_navigation = new TPageNavigation;
            $this->page_navigation->setAction(new TAction([$this, 'onReload']));
            $this->page_navigation->setWidth($this->datagrid->getWidth());
            $this->datagrid->setPageNavigation($this->page_navigation);
            
            //Estrutura da pagina
            $page = new TPageContainer();
            $page_box = $page->createBox(false);
            $page_box->add(ScreenHelper::getHeader(__CLASS__));
            $page_box->add($this->form);
            $page_box->add($this->datagrid);
            $page_box->add($this->page_navigation);
            
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
     * Method onSearch()
     * Registra uma busca na sessão
     */
    function onSearch()
    {
        $data           = $this->form->getData();
        $session_name   = $this->form->getPostSessionName();
        $filters        = [];

        if($data->id)
        {
            $filters[]  = new TFilter('id', '=', $data->id);
        }
        
        if($data->phone)
        {
            $filters[]  = new TFilter('phone', '=', $data->phone);
        }
        
        if($data->group_id)
        {
            $filters[]  = new TFilter('EXISTS',     '',     "NOESC: (SELECT * FROM sys_user_group where sys_user_group.user_id = bas_person.id and sys_user_group.group_id = {$data->group_id})");
        }

        //Registra o filtro na sessão
        TSession::setValue("filters_{$session_name}", $filters);

        //Recarrega a página
        $this->form->setData($data);
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }
    
    /**
     * Method onReload()
     * Carrega dados para a tela
     */
    function onReload($param = NULL)
    {
        try
        {
            TTransaction::open($this->db);

            //Cria filtros
            $criteria = new TCriteria;
            $limit    = 15;

            // default order
            if (empty($param['order']))
            {
                $param['order']     = 'id';
                $param['direction'] = 'desc';
            }
    
            //Define ordenação e limite da pagina
            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);
                
            //Sessão de filtros da form
            $session_name = $this->form->getPostSessionName();
    
            //Se tiver filtros, aplica
            if ($filters = TSession::getValue("filters_{$session_name}"))
            {
                foreach ($filters as $filter)
                {
                    $criteria->add($filter);
                }
            }

            //Carrega os objetos
            $repository = new TRepository($this->model);
            $objects    = $repository->load($criteria, true);
            $this->datagrid->clear();

            if ($objects)
            {
                //Percorre os resultados
                foreach ($objects as $object)
                {
                    //Se tiver usuário
                    $user = $object->getUser();
                    
                    if($user)
                    {
                        $this->datagrid->addItem($object, null, false, null, false, ['Criar usuário']);
                    }
                    else
                    {
                        $this->datagrid->addItem($object, null, false, null, false, ['Editar acesso', 'Remover', 'Senha provisória']);
                    }
                }
            }

            $criteria->resetProperties();
            $this->page_navigation->setCount($repository->count($criteria));
            $this->page_navigation->setProperties($param);
            $this->page_navigation->setLimit($limit);
            $this->loaded = true;
            
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
    
    function onDelete($param)
    {
        try
        {
            $data         = $this->datagrid->getData();
            $param['ids'] = $data;

            //Ação de delete
            $action = new TAction([$this, 'delete']);
            $action->setParameters($param);
            
            //Pergunta
            $notify = new TNotify('Cancelar registro', 'Você tem certeza que quer cancelar este agendamento?');
            $notify->setIcon('mdi mdi-help-circle-outline');
            $notify->addButton('Sim', $action);
            $notify->addButton('Não', null);
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

    function delete($param)
    {
        try
        {
            //Abre transação
            TTransaction::open($this->db);

            //Para lote
            if(!empty($param['ids']))
            {
                foreach ($param['ids'] as $key => $value) 
                {
                    $object = new $this->model($value);
                    $object->delete();
                }
            }
            elseif(!empty($param['key']))
            {
                $object = new $this->model($param['key']);
                $object->delete();
            }
            else
            {
                throw new Exception("Selecione algo para deletar!");
            }

            TTransaction::close();

            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->setAutoRedirect([$this,'onReload']);
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

    function onRemoveUser($param)
    {
        try
        {
            $data         = $this->datagrid->getData();
            $param['ids'] = $data;

            //Ação de delete
            $action = new TAction([$this, 'removeUser']);
            $action->setParameters($param);
            
            //Pergunta
            $notify = new TNotify('Remover usuário', 'Você tem certeza que quer remover este usuário');
            $notify->setIcon('mdi mdi-help-circle-outline');
            $notify->addButton('Sim', $action);
            $notify->addButton('Não', null);
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
    
    function onSendPassword($param)
    {
        try
        {
            $data         = $this->datagrid->getData();
            $param['ids'] = $data;

            //Ação de delete
            $action = new TAction([$this, 'sendPassword']);
            $action->setParameters($param);
            
            //Pergunta
            $notify = new TNotify('Enviar para o usuário', 'Esta é uma senha aleatória e provisória para acesso rápido ao portal e expira em 10 dias. O usuário receberá um E-mail e SMS com os dados de acesso. Tem certeza que deseja enviar?');
            $notify->setIcon('mdi mdi-help-circle-outline');
            $notify->addButton('Sim', $action);
            $notify->addButton('Não', null);
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
    
    function sendPassword($param)
    {
        try
        {
            //Abre transação
            TTransaction::open($this->db);

            if(!empty($param['key']))
            {
                $person = new $this->model($param['key']);
                $user   = $person->getUser();
                
                if($user)
                {
                    $user->createPassword();
                }
                else
                {
                    throw new Exception("Pessoa não possui usuário");
                }
            }
            else
            {
                throw new Exception("Selecione algo para remover!");
            }

            TTransaction::close();

            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->setAutoRedirect([$this,'onReload']);
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

    function removeUser($param)
    {
        try
        {
            //Abre transação
            TTransaction::open($this->db);


            if(!empty($param['key']))
            {
                $object = new $this->model($param['key']);
                $object->removeUser();
            }
            else
            {
                throw new Exception("Selecione algo para remover!");
            }

            TTransaction::close();

            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->setAutoRedirect([$this,'onReload']);
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

    function onCreateUser($param)
    {
        try
        {
            $data         = $this->datagrid->getData();
            $param['ids'] = $data;

            //Ação de delete
            $action = new TAction([$this, 'createUser']);
            $action->setParameters($param);
            
            //Pergunta
            $notify = new TNotify('Criar usuário', 'Você tem certeza que quer criar usuário para esta pessoa?');
            $notify->setIcon('mdi mdi-help-circle-outline');
            $notify->addButton('Sim', $action);
            $notify->addButton('Não', null);
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
    
    function createUser($param)
    {
        try
        {
            //Abre transação
            TTransaction::open($this->db);

            if(!empty($param['key']))
            {
                $object = new $this->model($param['key']);
                $object->createUser();
            }
            else
            {
                throw new Exception("Selecione algo para criar!");
            }

            TTransaction::close();

            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->setAutoRedirect([$this,'onReload']);
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
    
    public function getType($param, $person)
    {
        return PersonHelper::getType($person);
    }

    public function getId($param, $person)
    {
        return "{$person->id}";
    }
    
    public function getPhoto($param, $person)
    {
        return PersonHelper::getPhotoSmall($person);
    }

    function isUser($param)
    {
        try
        {
            //Abre transação
            TTransaction::open($this->db);

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
    
    /**
     * Method show()
     * Exibe conteúdos pertencentes a tela criada
     * 
     */
    function show()
    {
        if (!$this->loaded)
        {
            $this->onReload( func_get_arg(0) );
        }
        
        parent::show();
    }
}
?>