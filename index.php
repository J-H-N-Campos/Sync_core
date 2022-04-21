<?php

require_once 'init.php';
new TSession;

try
{
    //Carrega template
    $layout = file_get_contents("app/templates/layout.html");

    TTransaction::open('sync');

    //Para logados
    if(UserService::isSession())
    {
        $user    = UserService::getSession();
        $person  = $user->getPerson();
        $menu    = InterfaceHelper::getMenu($user);
        
        //Menu de alerta
        $menu    = str_replace('{MENU_ALERT}',   "<a generator='adianti' href='index.php?class=PushList' menu-target='8000'><i class='mdi mdi-bell-outline'></i> Alertas</a>", $menu);
        $layout  = str_replace('{MENU}', $menu,  $layout);
    }
    else
    {
        //Carrega login
        $layout  = file_get_contents("app/templates/login.html");
    }

    TTransaction::close();

    //Start fire
    $fire = new Fire('sync-1.0');
    $fire->setLayout($layout);
    $fire->addLibTemplate('link',   'art',  'https://fonts.googleapis.com/css?family=Open+Sans:300,400,700,800&display=swap');
    $fire->addLibTemplate('link',   'art',  'app/templates/art.css');
    $fire->addLibTemplate('script', 'art',  'app/templates/art.js');
    $fire->addLibCustom('script',   'app',  'app/templates/application.js');
    $fire->addLibCustom('link',     'app',  'app/templates/application.css');
    $fire->removeLib('fontawesome');

    echo $fire->get();

    //Controle do carreamento da pagina
    if(isset($_REQUEST['class']))
    {
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;
        
        AdiantiCoreApplication::loadPage($_REQUEST['class'], $method, $_REQUEST);
    }
    else
    {
        AdiantiCoreApplication::loadPage("Home");
    }
}
catch (Exception $e)
{
    echo $e->getMessage();
}
?>