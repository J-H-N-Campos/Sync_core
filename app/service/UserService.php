<?php
/**
 * UserService
 *
 * @version    1.0
 * @date       04-05-2017
 * @author     Rodrigo de Freitas
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class UserService
{
    public static function create($param)
    {
        if(empty($param['id']))
        {
            throw new Exception("Parâmetro 'id' não informado");
        }

        $person       = Person::where('id', '=', $param['id'])->get();
        $new_password = null;

        if($person)
        {
            $person = $person[0];
            $fl_new = false;
            
            //Verifica se já tem usuario
            $user = $person->getUser();
            
            //Para novos usuários
            if(!$user)
            {
                $user              = new User();
                $user->id          = $person->id;
                $user->code        = TString::getCode();
                $user->dt_register = date('Y-m-d H:i:s');

                //Gera auto
                if(empty($param['password']))
                {
                    $new_password  = strtolower(TString::generatePassword());

                    //Define
                    $user->password              = TString::encrypt($new_password);
                    $user->fl_password_generated = true;
                }
                //Quando se passa a senha
                else
                {
                    if(empty($param['password']))
                    {
                        throw new Exception("Parâmetro 'password' não informado");
                    }

                    //Valida a senha
                    Person::validatePassword($param['password']);
                    
                    $user->password = TString::encrypt($param['password']);
                }

                $fl_new = true;
            }

            //Define os grupos
            if(!empty($param['application_token']))
            {
                $user->application_token = $param['application_token'];
            }

            if(!empty($param['fl_on']))
            {
                $user->fl_on = $param['fl_on'];
            }
            
            if(!empty($param['browser_token']))
            {
                $user->browser_token = $param['browser_token'];
            }

            //Se for vazio
            if(empty($param['application_token']))
            {
                $user->application_token = null;
            }

            //Se for vazio
            if(empty($param['browser_token']))
            {
                $user->browser_token = null;
            }

            if(!empty($param['fl_two_auth']))
            {
                $user->fl_two_auth = $param['fl_two_auth'];
            }

            //Define os grupos
            if(!empty($param['groups']))
            {
                $user->clearGroups();
                
                foreach ($param['groups'] as $key => $ref_group)
                {
                    $user->addGroup(new Group($ref_group));
                }
            }
            
            //Se não tem pip, cria
            if(!$user->pip_code)
            {
                $user->pip_code = TApiRestClient::post('pipme', 'subaccount', 'create', [$person->name]);
            }

            $user->store();
            
            if($fl_new)
            {
                if($person->email)
                {
                    UserService::sendNotification('USUARIO_CADASTRO', ['email'], $user, ['tmp_login' => $person->email, 'tmp_password' => $new_password]);
                }
            }
            
            return $user;
        }
        else
        {
            throw new Exception("Pessoa não existe");
        }
    }

    public static function setSession($user)
    {
        TSession::setValue('user', $user);
    }

    public static function getSession()
    {
        $user = TSession::getValue('user');

        return $user;
    }
    
    public static function authenticate($login, $password, $origem = null, $fl_std_class = true)
    {
        $email  = TString::prepareEmail($login);
        $person = Person::where('email', '=', $email)
                        ->where('EXISTS', '', "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id )")
                        ->get();

        if($person)
        {
            $person = $person[0];
            $user   = $person->getUser();

            if($user)
            {
                //Compara a senha
                if($user->password == TString::encrypt($password))
                {            
                    if($fl_std_class)
                    {
                        $objUser         = TObject::toStd($user);
                        $objUser->person = $person->toStdClass();
                        
                        return $objUser;
                    }
                    
                    return $user;
                }
                else
                {
                    throw new Exception("Senha incorreta");
                }
            }
            else
            {
                throw new Exception("Você não é um usuário do sistema");
            }
        }
    }

    public static function isSession()
    {
        $user = TSession::getValue('user');

        if($user)
        {
            return true;
        }

        return false;
    }

    public static function sendNotification($key_template, $methods, $user, $params = null, $push_options = null)
    {
        $person     = $user->getPerson();
        $individual = $person->getIndividual();
        $company    = $person->getCompany();
        $ini        = parse_ini_file('app/config/application.ini');
        
        //Replaces do template
        $replaces               = [];
        $replaces['main_name']  = $person->first_name;
        $replaces['url_sync']   = $ini['url'];
        
        if($individual)
        {
            $replaces['person_cpf'] = $individual->cpf;
        }
        else if(!empty($company))
        {
            $replaces['company_name'] = $person->name;
            $replaces['person_cnpj']  = $company->cnpj;
        }
        
        if($params)
        {
            $replaces = $replaces + $params;
        }
        
        //Faz o envio
        $senders = PipmeService::send($key_template, $methods, $person, $replaces, $push_options);
    }

    public static function updatePassword($id, $pass_new, $pass_new_confirm)
    {
        if($id AND $pass_new AND $pass_new_confirm)
        {
            //Verifica se a chave existe
            $user = User::where('id', '=', $id)->get();

            if($user)
            {
                $user = $user[0];

                if($pass_new == $pass_new_confirm)
                {
                    //Somente se tiver senha
                    if($user->password)
                    {
                        //Valida a senha
                        Person::validatePassword($pass_new);
            
                        $user->password = TString::encrypt($pass_new);
                        $user->store();
    
                        //Atualiza o login
                        self::setSession($user);
                    }
                    else
                    {
                        throw new Exception("Não é possível trocar a senha, sua conta esta vinculada a um login externo");
                    }
                }
                else
                {
                    throw new Exception("Senha nova não confere com a da confirmação");
                }
            }
            else
            {
                throw new Exception("User inválido");
            }
        }
    }

    public static function getByLogin($email)
    {
        //Procura pelas pessoas com o email recebido no parâmetro
        $persons = Person::where('email', '=', TString::prepareEmail($email))->get();
        
        //Se tiver
        if($persons)
        {
            $person = $persons[0];
            $user   = $person->getUser();
        }

        return $user;
    }

    public static function recover($email)
    {
        //confirma o email com o email do banco e valida ele
        $person = Person::getByEmail($email);
        
        //verifica se ele não vem vazio (null)
        if(!empty($person))
        {
            //pega o usuário
            $user = $person->getUser();
            
            //verifica se tem usuário
            if($user)
            {
                //recupera ele
                $user->recover();
            }
            else
            {
                throw new Exception("Usuário não existe");
            }
        }
        else
        {
            throw new Exception("Pessoa não encontrada");
        }
    }
}
?>