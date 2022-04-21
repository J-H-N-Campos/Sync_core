<?php

/**
 * Home
 *
 * @version    1.0
 * @date       22-01-2016
 * @author     Rodrigo de Freitas
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class Home extends TPage
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

            TTransaction::open('sync');
 
            //Estrutura da pagina
            $page = new TPageContainer();
            $page_box = $page->createBox(false);

            $page_box->add(ScreenHelper::getHeader(__CLASS__));
            $page_box->add("Olá, comece agora...");

            TTransaction::close();

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
}
?>