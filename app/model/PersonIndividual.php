<?php
/**
 * PersonIndividual
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class PersonIndividual extends TRecord
{
    const TABLENAME     = 'bas_person_individual';
    const PRIMARYKEY    = 'person_id';
    const IDPOLICY      = 'serial'; // {max, serial}
    
    public function __construct($person_id = NULL)
    {
        parent::__construct($person_id);
        parent::addAttribute('birth_date');
		parent::addAttribute('cpf');
		parent::addAttribute('gender');
		parent::addAttribute('rg');
    }
    
    public function getPerson()
    {
        return new Person($this->person_id);
    }

    public static function getByCpf($cpf)
    {
        $person_individual = self::where('cpf', '=', TString::prepareStrigDocument($cpf))->get();
        
        if($person_individual)
        {
            return $person_individual[0];
        }
    }
    
    public function prepareInformations()
    {
        $this->cpf = TString::prepareStrigDocument($this->cpf);
    }

    public function delete($id = NULL)
    {  
        $id = isset($id) ? $id : $this->id;

        parent::delete($id);   
    }

    public function store()
    {
        $this->prepareInformations();

        parent::store();
    }
}
?>