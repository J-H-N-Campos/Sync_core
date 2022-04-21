<?php
/**
 * Screen
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class Screen extends TRecord
{
    const TABLENAME     = 'sys_screen';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    const CACHECONTROL  = 'TAPCache';

    private $menu;
    
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
		parent::addAttribute('controller');
        parent::addAttribute('icon');
        parent::addAttribute('fl_view_menu');
        parent::addAttribute('fl_public');
        parent::addAttribute('menu_id');
        parent::addAttribute('fl_admin');
        parent::addAttribute('helper');
    }

    public static function getName($ref)
    {
        $obj = new self($ref);
        
        return $obj->name;
    }

    public function setMenu($menu)
    {
        $this->menu     = $menu;
        $this->ref_menu = $menu->id;
    }

    public function clone()
    {
        unset($this->id);

        $this->name       = "{$this->name} (CLONE)";
        $this->controller = "{$this->controller} (CLONE)";
        $this->store();
    }

    public function getMenu()
    {
        return $this->menu;
    }

    public function store()
    {
        parent::store();
    }

    public function load($id)
    {
        $object     = parent::load($id);
        $this->menu = new Menu($this->menu_id);

        return $object;
    }

    public static function getByController($controller)
    {
        $criteria = new TCriteria();
        $criteria->add(new TFilter('controller', '=', $controller));
        
        $screen = self::getObjects($criteria, false);

        if($screen)
        {
            return $screen[0];
        }
    }

    public function getParent()
    {
        if (strstr($this->controller, 'Form') !== FALSE)
        {
            $controller_parent = str_replace('Form', 'List', $this->controller);

            $screen = self::where('controller', '=', $controller_parent)->get();

            if($screen)
            {
                return $screen[0];
            }
        }

        if (strstr($this->controller, 'List') !== FALSE)
        {
            return $this;
        }
    }

    public function delete($id = NULL)
    {  
        $id = isset($id) ? $id : $this->id;

        GroupScreen::where('screen_id', '=', $this->id)->delete();

        parent::delete($id);   
    }
}
?>