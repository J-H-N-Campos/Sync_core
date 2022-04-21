<?php
/**
 * PersonService
 *
 * @version    1.0
 * @date       04-05-2017
 * @author     Rodrigo de Freitas
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class PersonService
{
    public static function create($param, $fl_create_user = false)
    {
        $person            = null;
        $person_individual = null;
        $person_company    = null;
        $name              = null;
        $fl_edit_form      = false;
        $email             = null;
        $phone             = null;

        //Se for passado person code
        if(!empty($param['code']))
        {
            $person = Person::getByCode($param['code']);

            if($person)
            {
                $person_individual = $person->getIndividual();
                $person_company    = $person->getCompany();
            }
            else
            {
                throw new Exception("Pessoa code {$param['code']} não existe");
            }
        } 
        elseif(!empty($param['id']))
        {
            $person = Person::where('id', '=', $param['id'])->get();

            if($person)
            {
                $person            = $person[0];
                $person_individual = $person->getIndividual();
                $person_company    = $person->getCompany();
            }
            else
            {
                throw new Exception("Pessoa id {$param['id']} não existe");
            }
        }

        //Validação do nome
        $check_name = explode(" ", $param['name']);

        //Se não tiver person, cria
        if(!$person)
        {
            $person = new Person();
        }

        //Se for passado EMAIL
        if(!empty($param['email']))
        {
            $email = TString::prepareEmail($param['email']);

            //Valida email
            $validator = new TEmailValidator();
            $validator->validate('Email', $email); 

            //Validação somente para fisica
            if(!empty($param['individual']))
            {
                //Se a pessoa ja existe
                if(!empty($person->id))
                {
                    //Verifica duplicação
                    $person_verification = Person::where('email', '=', $email)
                                                 ->where('id',    '!=', $person->id)
                                                 ->where('EXISTS', '', "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id )")
                                                 ->get();
                    if($person_verification)
                    {
                        throw new Exception("{$email} já está sendo usado por outra pessoa");
                    }
                }
                else
                {
                    $person_verification = Person::where('email', '=', $email)
                                                 ->where('EXISTS', '', "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id )")
                                                 ->get();

                    if($person_verification)
                    {
                        $person  = $person_verification[0];
                    }
                }
            }

            //Add
            $person->email = $email;
        }
        else
        {
            throw new Exception('O email é obrigatório');
        }

        //Validação para fisica
        if(!empty($param['individual']))
        {
            //Se não tem ainda
            if(empty($person_individual))
            {
                $person_individual = new PersonIndividual();
            }
            
            //Se informado cpf
            if(!empty($param['individual']['cpf']))
            {
                $cpf = TString::prepareStrigDocument($param['individual']['cpf']);

                //Valida o cpf
                $validator = new TCPFValidator();
                $validator->validate('CPF', $cpf);

                //Para edição
                if(!empty($person_individual->person_id))
                {
                    //Verifica se mudou
                    if($person_individual->cpf != $cpf)
                    {
                        //Verifica se tem outro com este cpf
                        $person_validade = PersonIndividual::where('cpf', '=', $cpf)->get();
    
                        if($person_validade)
                        {
                            throw new Exception("CPF {$cpf} já está sendo usando por outra pessoa");
                        }
                    }
                }
                //Para novo
                else
                {
                    //Verifica se tem outro com este cpf
                    $person_validade = PersonIndividual::where('cpf', '=', $cpf)->get();
                    
                    //Tem
                    if($person_validade)
                    {
                        //Verifique se a pessoa tem usuario
                        $person_individual = $person_validade[0];
                        $person            = $person_individual->getPerson();
                        $user              = $person->getUser();
                        
                        //Somente se ja tem um cpf
                        if($user)
                        {
                            throw new Exception("CPF {$cpf} já está sendo usando por outra pessoa");
                        }
                        else
                        {
                            //Deixa passar
                        }
                    }
                }
    
                //Atribui
                $person_individual->cpf = $cpf;
            }

            if(!empty($param['individual']['birth_date']))
            {
                //Valida a data
                if(!TDateService::validate($param['individual']['birth_date']))
                {
                    throw new Exception("Parâmetro 'birth_date' é invalido ou não está no formato correto");
                }
                
                $person_individual->birth_date = $param['individual']['birth_date'];
            }
            
            if(!empty($param['individual']['rg']))
            {
                $person_individual->rg = $param['individual']['rg'];
            }
        }

        //Validação para juridica
        if(!empty($param['company']))
        {
            if(empty($param['company']['cnpj']))
            {
                throw new Exception("Informe um CNPJ");
            }
            
            $cnpj = TString::prepareStrigDocument($param['company']['cnpj']);

            //Valida o cpf
            $validator = new TCNPJValidator();
            $validator->validate('cnpj', $cnpj);

            //Para edição
            if($person_company)
            {
                //Verifica se mudou
                if($person_company->cnpj != $cnpj)
                {
                    //Verifica se tem outro com este cpf
                    $person_validade = PersonCompany::where('cnpj', '=', $cnpj)->get();

                    if($person_validade)
                    {
                        throw new Exception("CNPJ {$cnpj} já está sendo usando por outra pessoa");
                    }
                }
            }
            //Para novo
            else
            {
                //Verifica se tem outro com este cpf
                $person_validade = PersonCompany::where('cnpj', '=', $cnpj)->get();

                if($person_validade)
                {
                    throw new Exception("CNPJ {$cnpj} já está sendo usando por outra pessoa");
                }

                //Cria nova
                $person_company = new PersonCompany();
            }

            //Atribui
            $person_company->cnpj = $cnpj;

            if(!empty($param['company']['name_fantasy']))
            {
                $person_company->name_fantasy  = $param['company']['name_fantasy'];
            }
        }
        
        //Se for passado endereço
        if(isset($param['zip_code']))
        {
            $person->zip_code = $param['zip_code'];
        }

        if(isset($param['street']))
        {
            $person->street = $param['street'];
        }

        if(isset($param['neighborhood']))
        {
            $person->neighborhood = $param['neighborhood'];
        }

        if(isset($param['number']))
        {
            $person->number = $param['number'];
        }

        if(isset($param['city_id']))
        {
            $person->city_id = $param['city_id'];
        }
            
        if(!empty($param['name']))
        {
            $person->name = $param['name'];
        }
        
        if(!empty($param['phone']))
        {
            $person->phone = TString::preparePhone($param['phone']);

            //Valida
            if(strlen($phone) != 11)
            {
                throw new Exception("Telefone {$phone} não está no padrão, precisa ter 9 dígitos (99)99999-9999");
            }
        }
        
        if(isset($param['individual']['gender']))
        {
            $person_individual->gender = $param['individual']['gender'];
        }
        
        if(isset($param['complement']))
        {
            $person->complement = $param['complement'];
        }
        
        if(!empty($param['cpf']))
        {
            $obj = PersonIndividual::where('cpf','=',$param['cpf'])->get();
            
            if($obj)
            {
                throw new Exception('Já existe um registro cadastrado com este CPF');
            }
            
            $person_individual->cpf = $param['cpf'];    
        }
        
        //Tipo
        $person->setIndividual($person_individual);
        $person->setCompany($person_company);
        
        //Salva
        $person->store();
                    
        //criação do usuário
        if($fl_create_user)
        { 
            $param2         = [];
            $param2['id']   = $person->id;

            if(!empty($param['password']))
            {
                $param2['password'] = $param['password']; 
            }

            //Antes de criar, verifica se já não existe
            $user = $person->getUser();
            
            //se não tem usuário ele cria
            if(!$user)
            {
                $user = UserService::create($param2);
            }

            $objUser            = TObject::toStd($user);
            $objUser->person    = $person->toStdClass();
            
            return $objUser;
        }

        return $person->toStdClass();
    }
    
    public static function getByCode($code)
    {
        $person = Person::where('code','=', $code)->get();

        if($person)
        {
            $person = $person[0];
            $user   = $person->getUser();
            
            $objPerson       = $person->toStdClass();
            $objPerson->user = TObject::toStd($user);
            
            return $objPerson;
        }
    }
    
    public static function getByCpf($cpf)
    {
        $person_individual = PersonIndividual::where('cpf','=',str_replace(['-','.'],['',''],$cpf))->get();
        $person = null;
        
        if($person_individual)
        {
            $person_individual = $person_individual[0];
            $person            = $person_individual->getPerson();
            $user              = $person->getUser();
            
            if($user)
            {
                $personStd                     = new StdClass();
                $personStd->person             = TObject::toStd($person);
                $personStd->person->individual = TObject::toStd($person_individual);
                $personStd->user               = TObject::toStd($user);
                
                return $personStd;
            }
        }
    }
    
    public static function updateByObject($person)
    {
        //transforma para array para mandar para a função create
        $params                 = (array) TObject::toStd($person);
        $individual             = $person->getIndividual();
        $params['individual']   = (array) TObject::toStd($individual);
        
        self::create($params);
    }
    
    //Preenche ou por cpf
    public static function onFillByCode($key, $type = 'cpf')
    {
        TTransaction::open('sync');

        $person = null;
        
        if($type == 'cpf')
        {
            $person_individual = PersonIndividual::getByCpf($key);
        }

        if($person_individual)
        {
            //Ja existe
            $person = $person_individual->getPerson();
            $person = TObject::merge($person, $person_individual);
            
            //Garante para edicao do formularios
            $person->person_code = $person->code;
            $person->birth_date  = TDate::date2br($person->birth_date);
        }
        
        if($type == 'cpf')
        {
            unset($person->cpf);
        }
        
        TTransaction::close();

        return $person;
    }
}
?>