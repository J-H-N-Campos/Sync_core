<?php
/**
 * UserGroup
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class UserGroup extends TRecord
{
    const TABLENAME     = 'sys_user_group';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    //const CACHECONTROL = 'TAPCache';

    private $screens;

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('user_id');
        parent::addAttribute('group_id');
    }
}
?>