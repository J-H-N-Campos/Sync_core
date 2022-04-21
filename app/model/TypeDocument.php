<?php
/**
 * TypeDocument
 *
 * @version    1.0
 * @date       20/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class TypeDocument extends TRecord
{
    const TABLENAME     = 'cad_type_document';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    const CACHECONTROL  = 'TAPCache';
    
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
        parent::addAttribute('template');
        parent::addAttribute('event_id');
    }

    public function getEvent()
	{
		return new Event($this->event_id);
    }
}
?>