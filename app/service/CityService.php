<?php
/**
 * CityService
 *
 * @version    1.0
 * @date       16/12/2021
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class CityService
{
    public static function getArray()
    {
        //vai pro Hero pegar pegar os dados passados abaixo
        $query = TApiRestClient::get('hero', 'city', 'getArray', ['small', 'ibge']);

        return $query;
    }
}
?>