<?php
/**
 * Group
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class Group extends TRecord
{
    const TABLENAME     = 'sys_group';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    const CACHECONTROL  = 'TAPCache';

    private $screens;

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
        parent::addAttribute('fl_admin');
    }

    public static function getName($ref)
    {
        $obj = new self($ref);
        
        return $obj->name;
    }

    public function getScreens()
    {
        return $this->screens;
    }

    public function clone()
    {
        unset($this->id);
        
        $this->name = "{$this->name} (CLONE)";
        $this->update();

        //Pega as configs
        $screens = $this->getScreens();

        if($screens)
        {
            foreach ($screens as $key => $screen) 
            {
                $group_app_screen            = new GroupScreen();
                $group_app_screen->screen_id = $screen->id;
                $group_app_screen->group_id  = $this->id;
                $group_app_screen->store();
            }
        }
    }

    public function update()
    {
        parent::store();
    }
    
    public function addScreen($screen)
    {
        $this->screens[$screen->id] = $screen;
    }

    public function clearScreens()
    {
        $this->screens = [];
    }

    public function store()
    {
        parent::store();

        //Screens
        GroupScreen::where('group_id', '=', $this->id)->delete();

        if($this->screens)
        {
            foreach ($this->screens as $key => $screen) 
            {
                $group_app_screen            = new GroupScreen();
                $group_app_screen->screen_id = $screen->id;
                $group_app_screen->group_id  = $this->id;
                $group_app_screen->store();
            }
        }
    }

    public static function getGroupsByScreen($screen_id)
    {
        //Screens
        $screens = GroupScreen::where('screen_id', '=', $screen_id)->get();
        $array   = [];

        if($screens)
        {
            foreach ($screens as $key => $screen) 
            {
                $array[$screen->group_id] = new Group($screen->group_id);
            }
        }

        return $array;
    }

    public function load($id)
    {
        $object = parent::load($id);

        //Screens
        $screens = GroupScreen::where('group_id', '=', $object->id)->get();

        if($screens)
        {
            foreach ($screens as $key => $screen) 
            {
                $this->addScreen(new Screen($screen->screen_id));
            }
        }

        return $object;
    }

    public function delete($id = NULL)
    {  
        $id = isset($id) ? $id : $this->id;
        
        UserGroup::where('group_id', '=', $this->id)->delete();
        GroupScreen::where('group_id', '=', $this->id)->delete();

        parent::delete($id);   
    }

    public static function getArray()
    {
        $profiles   = Group::orderBy('name', 'asc')->get();
        $array      = [];
        
        if($profiles)
        {
            foreach($profiles as $profile)
            {
                $array[$profile->id] = $profile->name; 
            }
        }
        
        return $array;
    }
}
?>