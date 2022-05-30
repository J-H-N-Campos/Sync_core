<?php

use Adianti\Database\TTransaction;
use Adianti\Widget\Wrapper\TDBUniqueSearch;

/**
 * BuyList
 *
 * @version    1.0
 * @date       23/05/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class BuyList extends TPage
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
    public function __construct($param = null)
    {
        try
        {
            parent::__construct($param);
            
            //Definições de conexão
            $this->db     = 'sync';
            $this->model  = 'Product';
            
            //Busca - Cria a form
            $this->form = new TFormStruct();
            $this->form->enablePostSession($this->model);
            
            //Busca - Entradas
            $category_id    = new TDBUniqueSearch('category_id', $this->db, 'ProductCategory', 'id', 'name');
            $name           = new TEntry('name');

            //propriedades
            $category_id->setMinLength(0);

            //Busca - Formulário
            $this->form->addTab('Filtros','mdi mdi-filter-outline');
            $this->form->addFieldLine($name,        'Nome',         [350, null]);
            $this->form->addFieldLine($category_id, 'Categoria',    [350, null]);

            //Busca - Ações
            $button = new TButtonPress('Filtrar', 'mdi mdi-filter');
            $button->setAction([$this, 'onSearch']);
            $this->form->addButton($button);           
            
            //Cria datagrid
            $this->datagrid = new TCardDataGrid;
            $this->datagrid->setMaxSize(200); 

            $user = UserService::getSession();

            $this->form->addFieldLine("<b>Registre-se ou faça login para poder adquirir os produtos</b>");
            $this->datagrid->addColumn('category_id',   'Categoria');
            $this->datagrid->addColumn('name',          'Nome');
            $this->datagrid->addColumn('price',         'Preço');
            $this->datagrid->addColumn('path',          'Foto');

            if(!empty($user->id))
            {
                $this->datagrid->createActionGroup();
                $this->datagrid->addActionButton('Adquirir produto', 'mdi mdi-chart-ppf', ['BuyForm', 'onEdit'], ['id']);
            }
            else
            {
                $button = new TButtonPress('Criar conta', 'mdi mdi-account-plus');
                $button->setAction(['RegisterAccount']);
                $this->form->addButton($button);

                $button = new TButtonPress('Login', 'mdi mdi-account');
                $button->setAction(['LoginForm2']);
                $this->form->addButton($button);
            }
            
            //Busca - Gera a forma
            $this->form->generate();

            //Nevegação
            $this->page_navigation = new TPageNavigation;
            $this->page_navigation->setAction(new TAction([$this, 'onReload']));
            $this->page_navigation->setWidth($this->datagrid->getWidth());
            $this->datagrid->setPageNavigation($this->page_navigation);
            
            //Estrutura da pagina
            $page     = new TPageContainer();
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

        if($data->category_id)
        {
            $filters[]  = new TFilter('category_id', ' = ', $data->category_id);
        }
        
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
            
            $user = UserService::getSession();

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
                    $object->price  = TCoin::toBr($object->price);
                    $category       = $object->getCategory();

                    if(!empty($object->category_id))
                    {
                        $object->category_id = $category->name;
                    }

                    if(!empty($object->path))
                    {
                        $object->path = TArchive::getDisplay($object->path);
                    }
                    
                    $this->datagrid->addItem($object);
                }
            }

            $criteria->resetProperties();
            $this->page_navigation->setCount($repository->count($criteria));
            $this->page_navigation->setProperties($param);
            $this->page_navigation->setLimit($limit);
            $this->loaded = true;

            //Gera a datagrid
            $this->datagrid->generate();
            
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
