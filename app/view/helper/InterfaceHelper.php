<?php

/**
 * InterfaceHelper
 *
 * @version    1.0
 * @date       08-02-2015
 * @author     Rodrigo de Freitas
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class InterfaceHelper
{
    public static function getNameContract($contract)
    {
        $person         = $contract->getPerson();
        $person_company = $person->getCompany();
        $name           = $person->name;

        if($person_company->name_fantasy)
        {
            $name = $person_company->name_fantasy;
        }

        return TString::getFistName($name);

    }
    public static function getMenuContracts($contracts, $current_contract)
    {
        $string = null;

        //Retira o selecionado fora
        unset($contracts[$current_contract->id]);


        if($contracts)
        {
            foreach ($contracts as $key => $contract) 
            {
                $string .= '<li>';

                $person         = $contract->getPerson();
                $person_company = $person->getCompany();
                $name           = $person->name;

                if($person_company->name_fantasy)
                {
                    $name = $person_company->name_fantasy;
                }

                $string .= '<a class="media" href="index.php?class=LoginForm&method=chanceContract&key=' . $contract->id . '&static=1">
                                <div class="media-left">
                                    <i class="mdi mdi-mdi mdi-domain"></i>
                                </div>
                                <div class="media-body">
                                    <p class="mar-no text-nowrap text-main text-semibold">' . $name . '</p>
                                    <small> ' . TString::maskCnpj($person_company->cnpj) . '</small>
                                </div>
                            </a>';
                $string .= '</li>';
            }
        }

        return $string;
    }

    public static function getTitle($title, $icon = null)
    {
        return "<div class='page-title'><i class='{$icon}'></i> {$title}</div>";
    }

    public static function getInfo($title, $text)
    {
        return "<div class='content-info'>
                    <div class='info-title'>{$title}</div>
                    {$text}
                </div>";
    }

    public static function getInfoView($code)
    {
        return "<a generator='adianti' class='link-info-view' href='index.php?class=InfoView&code={$code}'><i class='mdi mdi-help-circle'></i></a>";
    }

    public static function checkMenuBar($class)
    {
        echo "<script>

                    $('.bar-content').find('a').removeClass('bar-icon-check');
                    var element_a = $('.bar-content').find('[href=\'index.php?class={$class}\']');
                    element_a.addClass('bar-icon-check');
               </script>";
    }

    public static function getColumnTime($date)
    {
        return CalendarHelper::getDisplayDay($date);
    }

    // public static function getColumnOffer($schedule, $date)
    // {
    //     //Pega o operador
    //     $operator = $schedule->getOperator();

    //     $content        = new TElement('div');
    //     $content->class = "bar-offer";

    //     $content->add(CalendarHelper::getDisplayOffer($schedule,  $date));

    //     return $content;
    // }

    public static function getHelper($icon, $text)
    {
        $content        = new TElement('div');
        $content->class = "box-help";
        $content->add("<i class='{$icon}'></i> {$text}");

        return $content;
    }

    public static function getDisplayMessages()
    {
        $user     = UserService::getSession();
        $messages = UserService::getMessagesAlert($user);
        $string   = null;

        if($messages)
        {
            foreach ($messages as $key => $message) 
            {
                $action = null;

                if($message->action)
                {
                    $action = "<a href='{$message->action[1]}' class='link-alert'>{$message->action[0]}</a>";
                }

                $string .= "<div class='message-alert fadeIn animated' style='background: {$message->color}'>
                                <div style='width: 80%;'><i class='{$message->icon}' style='float: left; margin-right: 5px;'></i> {$message->text}</div>
                                <div style='width: 20%;text-align: right;'>{$action}</div>
                            </div>";
            }
        }

        return $string;
    }

    public static function showNotices()
    {
        //Alerts
        if(UserService::isSession())
        {
            $string_alerts = InterfaceHelper::getDisplayMessages();
            TScript::create("$('#mensages-content').html(\"{$string_alerts}\")");
        }
    }

    public static function getMessageEmpty($icon, $text)
    {
        $content        = new TElement('div');
        $content->class = 'card-message-empty';
        $content->add("<div class='card-message-icon'><i class='{$icon}'></i></div>");
        $content->add("<div class='card-message-empty-text'>{$text}</div>");
        
        return $content;
    }
    
    public static function getMenu($user)
    {
        $screens_menu   = $user->getScreensMenu();
        $person         = $user->getPerson();
        $url_imagem     = './images/logounivates.png';
        $operators       = UserService::getSession();

        $nav            = new TElement('div');
        $nav->class     = "art-main-nav";
        
        $submenu_contents        = new TElement('div');
        $submenu_contents->class = "art-main-subnav-content";
        
        //TOP
        $lane        = new TElement('div');
        $lane->class = "nav-lane-top art-nav";
        $boxer       = new TElement('div');
        $boxer->add("<div class='client_logo' style=\"background: url('{$url_imagem}')\"></div><hr>");

        //Menu de alerta
        $ball = "<div class='ball-alert pulse-red' style='display: none'></div>";

        //$boxer->add("<a  href='#' menu-target='8000'><i class='mdi mdi-bell-outline'></i>{$ball}</a>");
        $boxer->add("<a generator='adianti' href='index.php?class=MyProfileForm' menu-target='9000'><i class='mdi mdi-account-outline'></i></a><div class='nav-label'>Usuário</div>");
        $boxer->add("<a generator='adianti' href='index.php?class=LoginForm&method=updatePermissions&static=1' menu-target='9001'><i class='mdi mdi-reload'></i></a><div class='nav-label'>Atualizar</div>");
        
        $lane->add($boxer);
        $nav->add($lane);
        
        //CENTRO
        $lane        = new TElement('div');
        $lane->class = "nav-lane-center art-nav";
        $boxer       = new TElement('div');
        
        $lane->add($boxer);
        $nav->add($lane);
        
        foreach($screens_menu as $odens)
        {
            foreach($odens as $menu)
            {
                //Monta o icone do menu
                if(!in_array($menu->id, [16]))
                {
                    $boxer->add("<a href='#' menu-target='{$menu->id}'><i class='{$menu->icon}'></i></a><div class='nav-label'>{$menu->name}</div>");
                }
                
                $master_sub = new TElement('div');
                

                $submenu        = new TElement('div');
                $submenu->id    = "menu-target-{$menu->id}";
                $submenu->class = "art-main-subnav";

                $title_content = new TElement('div');
                $title_content->class = "art-main-subnav-title";
                $title_content->add("<i class='mdi mdi-chevron-down'></i> {$menu->name}");
                $submenu->add($title_content);

                foreach($menu->screens as $screen)
                {
                    $submenu->add("<a generator='adianti' href='index.php?class={$screen->controller}' submenu-target='{$screen->id}'><i class='{$screen->icon}'></i> {$screen->name}</a>");
                }
                
                $master_sub->add($submenu);
                $submenu_contents->add($master_sub);
            }
        }
    
        //BOTTOM    
        $lane        = new TElement('div');
        $lane->class = "nav-lane-bottom art-nav";
        $boxer       = new TElement('div');
        
        $lane->add($boxer);
        $boxer->add("<div generator='adianti'   href='index.php?class=MyProfileForm'                    class='art-logo' style='cursor:pointer'>" . PersonHelper::getPhotoSmall($person, null, 3)  . "</div>");
        $boxer->add("<a generator='adianti'     href='index.php?class=LoginForm&method=onLogout&static=1' menu-target='8001'><i class='mdi mdi-location-exit'></i></a><div class='nav-label'>Sair</div>");
        $nav->add($lane);
        
        $html  = $nav->getContents();
        $html .= $submenu_contents->getContents();
        
        return $html;
    }
}
?>