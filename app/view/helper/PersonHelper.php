<?php

/**
 * PersonHelper
 *
 * @version    1.0
 * @date       08-02-2015
 * @author     Rodrigo de Freitas
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class PersonHelper extends TRecord
{
    public static function getType($person)
    {
        $string = null;
        $person_individual  = $person->getIndividual();

        if($person_individual)
        {
            if($person_individual->cpf)
            {
                $string = "<i class='mdi mdi-account'></i> <b>" . TString::format($person_individual->cpf, '999.999.999-99') . "</b>";
            }
        }

        $person_company = $person->getCompany();

        if($person_company)
        {
            $owner = $person_company->getOwner();

            if($person_company->cnpj)
            {
                $string = "<i class='mdi mdi-domain'></i> <b>" . TString::format($person_company->cnpj, '99.999.999.9999-99') . "</b><br/>";
                
                if($owner)
                {
                    $string .= "Dono: {$owner->id} - {$owner->name}";
                }
            }
            else
            {
                $string = "<i class='mdi mdi-domain'></i> <b>Empresa sem CNPJ</b><br/>
                            Dono: {$owner->id} - {$owner->name}";
            }
        }

        return $string;
    }

    public static function getNameWithCpf($person)
    {
        return "{$person->id} - {$person->name} {$person->aux}";
    }

    public static function getNameWithCnpj($person)
    {
        $string = null;

        $person_company = $person->getCompany();

        if($person_company)
        {
            $string = "<b>{$person->id} - {$person->name}</b><br/><span style='color:#7a878e'>" . TString::format($person_company->cnpj, '99.999.999.9999-99') . "</span>" ;
        }

        return $string;
    }

    public static function getPhoto($person)
    {
        if($person->photo AND file_exists($person->photo))
        {
            $box = new TElement('div');
            $box->add("<div class='icon-profile' style='background: url(\"{$person->photo}\")'></div>");
            
            return $box;
        }
        else
        {
            $box = new TElement('div');
            $box->add("<div class='icon-profile'>{$person->init_word_name}</div>");
            
            return $box;
        }
    }

    public static function getPhotoSmall($person, $border_color = null, $size = 2)
    {
        $box        = new TElement('div');
        $box->class = "icon-profile{$size}";

        if($person->photo AND file_exists($person->photo))
        {
            $box->style = "background: url('{$person->photo}');";
        }
        else
        {
            $box->add("{$person->init_word_name}");
        }

        if($border_color)
        {
            $box->style .= " border: 3px solid $border_color;";
        }

        return $box;
    }

    public static function getDisplayPhoto($person, $color_border = null)
    {
        $content        = new TElement('div');
        $content->class = "user-display-photo";
        $content->title = "{$person->name}";
        $content->style = null;
        
        if($color_border)
        {
            $content->style .= "border: 2px solid {$color_border}; ";
        }

        if(file_exists($person->photo))
        {
            $content->style  .= "background: url('{$person->photo}'); ";
        }
        else
        {
            $content->add(TString::getIniWordName($person->name));
        }
        
        return $content;
    }
}
?>