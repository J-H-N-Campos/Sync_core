<?php
/**
 * User
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class User extends TRecord
{
    const TABLENAME     = 'sys_user';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    const CACHECONTROL  = 'TAPCache';

    private $groups;

    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('code');
        parent::addAttribute('dt_register');
        parent::addAttribute('fl_on');
        parent::addAttribute('pip_code');
        parent::addAttribute('password');
        parent::addAttribute('fl_term');
        parent::addAttribute('description');
    }
    
    public static function getName($user_id)
	{
		$person = new Person($user_id);
		
		return $person->description;
	}
    
    public static function getFirstName($user_id)
    {
        $person = new Person($user_id);

        return $person->first_name;
    }
    
    public function addGroup($obj)
    {
        $this->groups[$obj->id] = $obj;
    }
    
    public function getGroups()
    {
        return $this->groups;
    }
    
    public function getPerson()
    {
        return new Person($this->id);
    }

    public function clearGroups()
    {
        $this->groups = [];
    }

    public function store()
    {
        $person = $this->getPerson();
        
        //Gera a descricao do nome
        $this->description = $person->description;
        
        parent::store();

        UserGroup::where('user_id', '=', $this->id)->delete();

        if($this->groups)
        {
            foreach($this->groups as $key => $group) 
            {
                $user_group            = new UserGroup();
                $user_group->user_id   = $this->id;

                if(empty($group->id))
                {
                    $user_group->group_id  = 2;
                }
                else
                {
                    $user_group->group_id  = $group->id;
                }

                $user_group->store();
            }
        }
    }

    public function load($id)
    {
        $id = isset($id) ? $id : $this->id;
        
        $object = parent::load($id);
        
        $groups = UserGroup::where('user_id', '=', $object->id)->get();

        if($groups)
        {
            foreach ($groups as $key => $group) 
            {
                $this->addGroup(new Group($group->group_id));
            }
        }
        
        return $object;
    }

    public function delete($id = NULL)
    {  
        $id = isset($id) ? $id : $this->id;

        UserGroup::where('user_id',     '=', $this->id)->delete();

        parent::delete($id);   
    }

    public function getScreens($controller = null)
    {
        $screens    = UserGroup::where('user_id', '=', $this->id)->get();
        $array      = [];
        
        if($screens)
        {
            foreach ($screens as $key => $screen) 
            {
                $group       = new Group($screen->group_id);
                $screens_app = $group->getScreens();

                if($screens_app)
                {
                    foreach ($screens_app as $key => $screen_app) 
                    {
                        $array[$screen_app->controller] = $screen_app;
                    }
                }
            }
        }

        return $array;
    }

    public function getScreensMenu()
    {
        $screens      = $this->getScreens();
        $user         = UserService::getSession();
        $menus        = [];
        $menus_return = [];

        if($screens)
        {
            //Percorre os menus
            foreach ($screens as $key => $screen) 
            {
                //Somente as visiveis
                if($screen->fl_view_menu)
                {
                    $fl_add = true;

                    $menus[$screen->menu_id][$screen->name] =  $screen;
                }
            }

            if($menus)
            {
                foreach ($menus as $menu_id => $screens) 
                {
                    //Ordena as telas
                    ksort($screens);
                    $objMenu            = new Menu($menu_id);
                    $objMenu->screens   = $screens;

                    $menus_return[$objMenu->sequence][] = $objMenu;
                }
            }

            //Ordena o menu
            ksort($menus_return);
        }

        return $menus_return;
    }

    public function recover()
    {
        $person          = $this->getPerson();
        $new_password    = TString::generatePassword();
        $this->password  = TString::encrypt($new_password);
        
        UserService::sendNotification('USUARIO_RECUPERACAO_SENHA', ['email'], $this, ['tmp_password' => $new_password, 'tmp_login' => $person->email]);
        $this->store();
    }
    
    public function isAdministrator()
    {
        $groups = $this->getGroups();

        if($groups)
        {
            foreach ($groups as $key => $group) 
            {
                if($group->fl_admin)
                {
                    return true;
                }
            }
        }

        return false;
    }

    public static function getByCode($code)
    {
        $data = User::where('code', '=', $code)->get();
        
        if($data)
        {
            return $data[0];
        }
    }
}
?>