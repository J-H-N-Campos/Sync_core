<?php

use Adianti\Widget\Wrapper\TDBUniqueSearch;

/**
 * TypeDocumentForm
 *
 * @version    1.0
 * @date       20/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class TypeDocumentForm extends TCurtain
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
            $this->model    = 'TypeDocument';
            $this->parent   = $this->model.'List';
            
            parent::setActionClose([$this->parent]);
            parent::setExpanded("70%");

    		//Cria a form
            $this->form  = new TFormStruct("form_{$this->parent}");
            
            //Entradas
            $id         = new TEntry('id');
            $name       = new TEntry('name');
            $event_id   = new TDBUniqueSearch('event_id', $this->db, 'Event', 'id', 'name');
            $template   = new THtmlEditor('template');

            //Propriedades das entradas
            $id->setEditable(false);
            $event_id->setMinLength(0);

    		//Monta o formulário
    		$this->form->addTab('Formulário',   'mdi mdi-format-align-center');
            $this->form->addFieldLine($id,          'Id',       [80,  null]);
            $this->form->addFieldLine($name,        'Nome',     [250, null], true);
            $this->form->addFieldLine($event_id,    'Evento',   [400, null], true);
 
            $this->form->addTab('Template', 'mdi mdi-minus-box');
            $this->form->addFieldLine($template, 'Nome', [900, 650], true);
            
            //Botões de ações
            $button = new TButtonPress('mdi mdi-content-save','Gravar');
            $button->setAction([$this, 'onSave', ['effect' => false]]);
            $this->form->addButton($button);

            $button = new TButtonPress('mdi mdi-shape-circle-plus ','Novo');
            $button->setAction([$this, 'onEdit']);
            $this->form->addButton($button);
            
            //Gera a form
            $this->form->generate();
    
            //Estrutura do conteúdo da página
            parent::add(ScreenHelper::getHeader(__CLASS__));
            parent::add($this->form);
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

            //Grava
            $data->store();
            
            TTransaction::close();
           
			//Volta os dados para o form
            $this->form->setData($data);
			
            $notify = new TNotify('success', 'Operação foi realizada');
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
                
                $objeto = new $this->model($key);

                $this->form->setData($objeto);
				
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