<?php
/**
 * Menu
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class Menu extends TRecord
{
    const TABLENAME     = 'sys_menu';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    const CACHECONTROL  = 'TAPCache';
    
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
		parent::addAttribute('icon');
        parent::addAttribute('sequence');
    }

    public static function getName($ref)
    {
        $obj = new self($ref);
        
        return $obj->name;
    }

    public static function getIcon($icon)
    {
    	return "<i class='{$icon}' style='font-size: 15px;'></i>";
    }
}
?>