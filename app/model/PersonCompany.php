<?php
/**
 * PersonCompany
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class PersonCompany extends TRecord
{
    const TABLENAME     = 'bas_person_company';
    const PRIMARYKEY    = 'person_id';
    const IDPOLICY      = 'serial'; // {max, serial}

    private $menu;
    
    public function __construct($person_id = NULL)
    {
        parent::__construct($person_id);
		parent::addAttribute('name_fantasy');
        parent::addAttribute('cnpj');
        parent::addAttribute('owner_id');
    }

    public function store()
    {
        $this->prepareInformations();
        parent::store();
    }

    public function getPerson()
    {
        return new Person($this->person_id);
    }

    public function getOwner()
    {
        if($this->owner_id)
        {
            return new Person($this->owner_id);
        }
    }

    public function load($id)
    {
        $object = parent::load($id);

        return $object;
    }

    public function prepareInformations()
    {
        $this->name_fantasy = TString::toUpper($this->name_fantasy);
        $this->cnpj         = TString::prepareStrigDocument($this->cnpj);
    }

    public function delete($id = NULL)
    {  
        $id = isset($id) ? $id : $this->id;

        parent::delete($id);   
    }

    public static function getByCnpj($cnpj)
    {
        $person_company = self::where('cnpj', '=', TString::prepareStrigDocument($cnpj))->get();
        
        if($person_company)
        {
            return $person_company[0];
        }
    }
}
?>