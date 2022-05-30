<?php
/**
 * Product
 *
 * @version    1.0
 * @date       19/05/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class Product extends TRecord
{
    const TABLENAME     = 'cad_product';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    const CACHECONTROL  = 'TAPCache';
    
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('dt_register');
		parent::addAttribute('name');
        parent::addAttribute('bar_code');
        parent::addAttribute('category_id');
        parent::addAttribute('path');
        parent::addAttribute('price');
        parent::addAttribute('user_id');
    }

    public function getCategory()
    {
        return new ProductCategory($this->category_id);
    }

    public function getUser()
    {
        return new User($this->user_id);
    }

    public function store()
    {
        if(!$this->id)
        {
            $this->dt_register = date('Y-m-d H:i:s');
        }

        $this->path = TArchive::move($this->path, 'repository/');

        parent::store();
    }
}
?>