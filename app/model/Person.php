<?php
/**
 * Person
 *
 * @version    1.0
 * @date       09/05/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class Person extends TRecord
{
    const TABLENAME     = 'bas_person';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial';

    private $person_individual;
    private $person_company;
    
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('dt_register');
		parent::addAttribute('name');
        parent::addAttribute('code');
        parent::addAttribute('phone');
        parent::addAttribute('email');
        parent::addAttribute('photo');
        parent::addAttribute('zip_code');
        parent::addAttribute('street');
        parent::addAttribute('neighborhood');
        parent::addAttribute('number');
        parent::addAttribute('city_id');
        parent::addAttribute('description');
        parent::addAttribute('complement');
    }
    
    public function get_init_word_name()
    {
        return TString::getIniWordName($this->name);
    }
    
    public function setIndividual($obj)
    {
        $this->person_individual = $obj;
    }

    public function getUsers()
    {
        $users  = User::where('id', '=', $this->id)->get();
        $array  = [];

        if($users)
        {
            foreach ($users as $key => $user) 
            {
                $array[] = $users->getPerson();
            }
        }
        
        return $array;
    }

    public function getContracts()
    {
        return Contract::where('person_id', '=', $this->id)->get();
    }

    public function getContractsUsers()
    {
        $array  = [];
        $users  = $this->getUsers();
        
        if($users)
        {
            foreach ($users as $key => $user) 
            {
                $contracts = $user->getContracts();

                if($contracts)
                {
                    $contract               = $contracts[0];
                    $array[$contract->id]   = $contract;
                }
            }
        }
    }

    public function getArrayContractsUsers($fl_unic_on = false)
    {
        $contracts = $this->getContractsUsers();
        $array     = [];

        if($contracts)
        {
            foreach ($contracts as $key => $contract) 
            {
                if($fl_unic_on)
                {
                    if($contract->status == 'operating' OR $contract->status == 'wait_config' OR $contract->status == 'cancel')
                    {
                        $array[$contract->id] = $contract->description;
                    }
                }
                else
                {
                    $array[$contract->id] = $contract->description;
                }
            }
        }

        return $array;
    }

    public function getDocument()
    {
        if($this->person_company)
        {
            return TString::maskCnpj($this->person_company->cnpj);
        }
        elseif($this->person_individual)
        {
            return TString::maskCpf($this->person_individual->cpf);
        }
    }

    public static function getByCode($code)
    {
        $person = self::where('code', '=', $code)->get();
        
        if($person)
        {
            return $person[0];
        }
    }

    public static function getByCpf($cpf)
    {   
        $cpf    = TString::prepareAll($cpf);
        $person = self::where('EXISTS', '', "NOESC: (SELECT * FROM bas_person_individual WHERE bas_person_individual.cpf = '{$cpf}' AND bas_person_individual.person_id = bas_person.id)")->get();
        
        if($person)
        {
            return $person[0];
        }
    }

    public static function getByCnpj($cnpj)
    {
        $person = self::where('EXISTS', '', "NOESC: (SELECT * FROM bas_person_company WHERE bas_person_company.cnpj = '{$cnpj}' AND bas_person_company.person_id = bas_person.id)")->get();
        
        if($person)
        {
            return $person[0];
        }
    }

    public static function getByEmail($email)
    {
        $person = self::where('email', '=', TString::prepareEmail($email))->get();
        
        if($person)
        {
            return $person[0];
        }
    }

    public static function getByPhone($phone)
    {
        $person = self::where('phone', '=', TString::preparePhone($phone))->get();
        
        if($person)
        {
            return $person[0];
        }
    }

    public function setCompany($obj)
    {
        $this->person_company = $obj;
    }

    public function getIndividual()
    {
        $obj = PersonIndividual::where('person_id', '=', $this->id)->get();

        if($obj)
        {
            return $obj[0];
        }
    }

    public static function getName($ref)
    {
        $obj = new self($ref);
        
        return $obj->name;
    }
    
    public function getUser()
    {
        $account = User::where('id', '=', $this->id)->get();

        if($account)
        {
            return $account[0];
        }
    }

    public function getCompany()
    {
        $obj = PersonCompany::where('person_id', '=', $this->id)->get();

        if($obj)
        {
            return $obj[0];
        }
    }

    public function get_first_name()
    {
        return TString::getFistName($this->name);
    }

    public function get_aux()
    {
        $person_individual = $this->getIndividual();

        if($person_individual)
        {
            $cpf        = null;
            $birth_date = null;
            
            if($person_individual->cpf)
            {
                $cpf = "({$person_individual->cpf})";
            }
            
            if($person_individual->birth_date)
            {
                $birth_date = TDate::date2br($person_individual->birth_date);
            }

            return "{$cpf} {$birth_date}";
        }

        $person_company = $this->getCompany();

        if($person_company AND $person_company->cnpj)
        {
            return "({$person_company->cnpj})";
        }
    }

    public function prepareInformations()
    {
        if(!$this->id)
        {
            $this->dt_register = date('Y-m-d H:i:s');
            $this->code        = TString::getCode();
        }

        $this->name         = TString::toUpper($this->name);
        $this->phone        = TString::preparePhone($this->phone);
        $this->email        = TString::prepareEmail($this->email);
        $this->description  = "{$this->name} {$this->aux}";
    }

    public function store()
    {
        $this->prepareInformations();
        
        parent::store();

        if($this->person_individual)
        {
            $this->person_individual->person_id = $this->id;
            $this->person_individual->store();

            //Para trocas
            PersonCompany::where('person_id', '=', $this->id)->delete();
        }

        if($this->person_company)
        {
            $this->person_company->person_id = $this->id;
            $this->person_company->store();

            //Para trocas
            PersonIndividual::where('person_id', '=', $this->id)->delete();
        }
    }

    public function load($id)
    {
        $object = parent::load($id);

        return $object;
    }

    public function delete($id = NULL)
    {  
        $id = isset($id) ? $id : $this->id;

        if($this->getUser())
        {
            throw new Exception("Essa pessoa é um usuário. Por favor remova ela do grupo de usuários para depois deleta-lá");
        }

        PersonIndividual::where('person_id', '=', $id)->delete();
        PersonCompany::where('person_id', '=', $id)->delete();

        parent::delete($id);   
    }
    
    public function removeUser()
    {
        $user = $this->getUser();
        
        if($user)
        {
            $user->delete();
        }
        else
        {
            throw new Exception("Pessoa não possui usuário");    
        }
    }

    public function createUser()
    {
        $user = $this->getUser();
        
        if($user)
        {
            throw new Exception("Pessoa já possui usuário");  
        }
        else
        {
            //Se for uma empresa
            if($this->getCompany())
            {
                throw new Exception("Não foi possível criar usuário para uma pessoa jurídica"); 
            }
            
            $user = UserService::create(['id' => $this->id]);

            if(!$user)
            {
                throw new Exception("Não foi possível criar usuário, verifica se o mesmo possui CPF"); 
            }
        }
    }

    public static function validatePassword($nova_senha)
    {
        //Valida o padrão da senha
        $caracteres_minimos = 8;
        $minimo_numeros     = 0;
        $minimo_letras      = 0;
        $senha_array        = str_split($nova_senha);
        $array_letras       = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q',
                              'R', 'S', 'T', 'U', 'V', 'X', 'Z', 'W',
                              'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q',
                              'r', 's', 't', 'u', 'v', 'x', 'z', 'x'];
        $aviso              = null;

        // 1 - Valida tamanho caracteres
        if(strlen($nova_senha) < $caracteres_minimos)
        {
            $aviso .= " - Não contem o mínimo de <b>$caracteres_minimos caracteres!</b> </br>";
        }
        
        // 2 - Valida minimo números
        $count_numero = 0;
        
        foreach($senha_array as $digito)
        {
            $num = is_numeric($digito);
            
            if($num)
            {
                $count_numero++;
            }
        }
        if($count_numero < $minimo_numeros)
        {
            $aviso .= " - Não contém o mínimo de <b>$minimo_numeros números!</b> </br>";
        }
        
        // 3 - Valida minimo letras e caracteres permitidos
        $count_letras   = 0;
        $trava          = false;

        foreach($senha_array as $digito)
        {
            //Conta as letras
            foreach($array_letras as $letra)
            {
                if($letra == $digito)
                {
                    $count_letras++;
                }
            }
        }

        if($count_letras < $minimo_letras)
        {
            $aviso .= " - Não contém o mínimo de <b>$minimo_letras letras!</b> </br>";
        }

        if($trava)
        {
            $aviso .= " - Contém caracteres não aceitos. Somente são aceitos letras e números. </br>";
        }
        
        //Se não teve nenhum problema, retorna true
        if(!$aviso)
        {
            return true;
        }
        else
        {
            throw new Exception("<b>Senha fora dos padrões de segurança!</b> </br>
                                O seguintes problemas foram encontrados nesta senha:</br>
                                $aviso");
        }
    }
}
?>