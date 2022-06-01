
<?php
/**
 * TesteService
 *
 * @version    1.0
 * @date       23/05/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

//php cmd.php "class=TesteService&method=createPerson&static=1"
//php cmd.php "class=TesteService&method=getByEmailType&static=1"
//php cmd.php "class=TesteService&method=createUser&static=1"
//php cmd.php "class=TesteService&method=setSessionUser&static=1"
//php cmd.php "class=TesteService&method=getArrayCitys&static=1"
//php cmd.php "class=TesteService&method=createGroup&static=1"
//php cmd.php "class=TesteService&method=deleteGroup&static=1"
//php cmd.php "class=TesteService&method=editGroup&static=1"
//php cmd.php "class=TesteService&method=searchCity&static=1"
//php cmd.php "class=TesteService&method=getErrors&static=1"

class TesteService
{
    public static function searchCity()
    {
        try
        {
            TTransaction::open('sync');

            $city = CityService::getArray(01);

            TTransaction::rollback();
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();

            echo "Error: {$e->getMessage()} \n";
        }
    }

    public static function editGroup()
    {
        try
        {
            TTransaction::open('sync');

            $group = new Group(1);
            $group->name = 'teste';
            $group->fl_admin = false;
            $group->store();

            TTransaction::rollback();
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();

            echo "Error: {$e->getMessage()} \n";
        }
    }

    //php cmd.php "class=TesteService&method=deleteGroup&static=1"
    public static function deleteGroup()
    {
        try
        {
            TTransaction::open('sync');

            $group = Group::Where('id', '=', 22)->delete();

            TTransaction::rollback();
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();

            echo "Error: {$e->getMessage()} \n";
        }
    }

    //php cmd.php "class=TesteService&method=createGroup&static=1"
    public static function createGroup()
    {
        try
        {
            TTransaction::open('sync');

            $group = new Group();
            $group->name = "João Teste";
            $group->fl_admin = true;

            TTransaction::rollback();
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();

            echo "Error: {$e->getMessage()} \n";
        }
    }

    //php cmd.php "class=TesteService&method=createPerson&static=1"
    public static function createPerson()
    {
        try
        {
            TTransaction::open('sync');

            $param = [];
            $param['name'] = 'joao';
            $param['email'] = 'joao@gmail.com';

            $person = PersonService::create($param);

            TTransaction::rollback();
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();

            echo "Error: {$e->getMessage()} \n";
        }
    }

    //php cmd.php "class=TesteService&method=getByEmailType&static=1"
    public static function getByEmailType()
    {
        try
        {
            TTransaction::open('sync');

            $login = PersonService::getByEmailType("joao@fisy.com.br","individual");
   
            TTransaction::rollback();
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();

            echo "Error: {$e->getMessage()} \n";
        }
    }

    //php cmd.php "class=TesteService&method=createUser&static=1"
    public static function createUser()
    {
        try
        {
            TTransaction::open('sync');

            $user = UserService::create(12);
            
            TTransaction::rollback();
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();

            echo "Error: {$e->getMessage()} \n";
        }
    }

    //php cmd.php "class=TesteService&method=setSessionUser&static=1"
    public static function setSessionUser()
    {
        try
        {
            TTransaction::open('sync');

            $user = UserService::setSession(12);

            TTransaction::rollback();
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();

            echo "Error: {$e->getMessage()} \n";
        }
    }

    //php cmd.php "class=TesteService&method=getErrors&static=1"
    public static function getErrors()
    {
        try
        {
            TTransaction::open('sync');

            $param  = [];
            $param[]= 'Error';

            ErrorService::send($param);

            TTransaction::rollback();
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();

            echo "Error: {$e->getMessage()} \n";
        }
    }
}
?>
