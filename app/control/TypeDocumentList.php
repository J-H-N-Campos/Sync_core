<?php
/**
 * TypeDocumentList 
 *
 * @version    1.0
 * @date       20/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class TypeDocumentList  extends TPage
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
            $this->db    = 'sync';
            $this->model = 'TypeDocument';
            
            //Busca - Cria a form
            $this->form = new TFormStruct("form_{$this->model}");
            $this->form->enablePostSession($this->model);
            
            //Busca - Entradas
            $name = new TEntry('name');

            //Busca - Formulário
            $this->form->addTab('Filtros',      'mdi mdi-filter-outline');
            $this->form->addFieldLine($name,    'Nome', [200, null]);

            //Busca - Ações
            $button = new TButtonPress('mdi mdi-magnify','Procurar');
            $button->setAction([$this, 'onSearch']);
            $this->form->addButton($button);

            $button = new TButtonPress('mdi mdi-shape-circle-plus','Novo');
            $button->setAction(['TypeDocumentForm', 'onEdit']);
            $this->form->addButton($button);
            
            //Busca - Gera a forma
            $this->form->generate();

            //Cria datagrid
            $this->datagrid = new TDataGridResponsive;
            $this->datagrid->setConfig(false);
            $this->datagrid->setDb($this->db);
    
            $this->datagrid->addColumn('id',    'Id');
            $this->datagrid->addColumn('name',  'Nome');
    
            //Ações
            $this->datagrid->addGroupAction('mdi mdi-dots-vertical');
            $this->datagrid->addGroupActionButton('Editar',     'mdi mdi-table-edit',   ['TypeDocumentForm',   'onEdit']);
            $this->datagrid->addGroupActionButton('Deletar',    'mdi mdi-delete',       [$this, 'onDelete']);

            //Nevegação
            $this->page_navigation = new TPageNavigation;
            $this->page_navigation->setAction(new TAction([$this, 'onReload']));
            $this->page_navigation->setWidth($this->datagrid->getWidth());
            
            //Estrutura do conteúdo da página
            parent::add(ScreenHelper::getHeader(__CLASS__));  
            parent::add($this->form);
            parent::add($this->datagrid);
            parent::add($this->page_navigation);
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

        if($data->name)
        {
            $filters[]  = new TFilter('name', ' ILIKE ', "NOESC: '%$data->name%'");
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
                    $this->datagrid->addItem($object);
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
    
    /**
     * Method onDelete()
     * Executa uma confirmação se tem ou não certeza antes de deletar
     * 
     */
    function onDelete($param)
    {
        //Ação de delete
        $action = new TAction([$this, 'delete']);
        $action->setParameters($param);
        
        $notify = new TNotify('Deletar registro', 'Você tem certeza que quer apagar este(s) registro(s)?');
        $notify->setIcon('mdi mdi-help-circle-outline');
        $notify->addButton('Sim', $action);
        $notify->addButton('Não', null);
        $notify->show();
    }
    
    /**
     * Method Delete()
     * Deleta o cadastro
     * 
     */
    function delete($param)
    {
        try
        {
            //Abre transação
            TTransaction::open($this->db);
            
            $object = new $this->model($param['key']);
            $object->delete();  
            
            TTransaction::close();

            //Avisa que foi excluido
            $notify = new TNotify('success', 'Operação foi realizada');
            $notify->enableNote();
            $notify->setAutoRedirect([$this, 'onReload']);
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