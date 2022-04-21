<?php

use Adianti\Widget\Base\TElement;

/**
 * EventHelper
 *
 * @version    1.0
 * @date       19/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class EventHelper
{
    public static function getTop($event, $icon)
    {
        $subscription   = $event->getSubscription();

        //Criar o header
        $header = new TElement('div');
        $header->class = 'element-header';
        $header->add("<div class='contrato-header-titulo'>Evento: {$event->name}</div>");
        $header->add("<div class='contrato-header-texto'>Código: <b>{$event->code}</b></div>");

        $all = new TElement('div');
        $all->class = 'header-all';
        $all->add("<i class='{$icon} header-all-icon'></i>");
        $all->add($header);
        
        return $all;
    }
}
?>