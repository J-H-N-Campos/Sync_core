<?php
/**
 * ProductCategory
 *
 * @version    1.0
 * @date       19/05/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class ProductCategory extends TRecord
{
    const TABLENAME     = 'cad_product_category';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    const CACHECONTROL  = 'TAPCache';
    
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('dt_register');
		parent::addAttribute('name');
    }

    public function store()
    {
        if(!$this->id)
        {
            $this->dt_register = date('Y-m-d H:i:s');
        }
        
        parent::store();
    }
}
?>