<?php
/**
 * PersonForm
 *
 * @version    1.0
 * @date       09/05/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class PersonForm extends TCurtain
{
    protected $form; 
    private   $db;
    private   $model;
      
    /**
     * Classe construtora
     */
    function __construct($param = null)
    {
        try
        {
            parent::__construct($param);
            
            TTransaction::open('sync');
            
            //Definições de conexão
            $this->db     = 'sync';
            $this->model  = 'Person';
            $this->parent = "PersonList";
            
            if(!empty($param['return']))
            {
                if(!empty($param['return_params']))
                {
                    parent::setActionClose([$param['return'], null, $param['return_params']]);
                }
                else
                {
                    parent::setActionClose([$param['return']]);
                }
            }
            else
            {
                parent::setActionClose([$this->parent]);
            }

            //Cria a form
            $this->form  = new TFormStruct('person_form');

            $criteria    = new TCriteria();
            $criteria->add(new TFilter('id', 'IN', "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id )"));
  
            //Entradas
            $id             = new THidden('id');
            $code           = new THidden('code');
            $name           = new TEntry('name');
            $birth_date     = new TEntry('birth_date');
            $cpf            = new TEntry('cpf');
            $photo          = new TArchive('photo');
            $name_fantasy   = new TEntry('name_fantasy');
            $cnpj           = new TEntry('cnpj');
            $email          = new TEntry('email');
            $phone          = new TEntry('phone');
            $gender         = new TCombo('gender');
            $zip_code       = new TEntry('zip_code');
            $street         = new TEntry('street');
            $neighborhood   = new TEntry('neighborhood');
            $number         = new TEntry('number');
            $city_id        = new TUniqueSearch('city_id');
            $complement     = new TEntry('complement');
            $rg             = new TEntry('rg');
            $owner_id       = new TDBUniqueSearch('owner_id', $this->db, 'Person', 'id', 'name', 'name', $criteria);

            //atributos
            $owner_id->setMinLength(0);
            $owner_id->setOperator('ilike');
            $owner_id->setMask('{id} - {name} {aux}');
            $zip_code->setMask('99999-999');
            $cpf->setMask('999.999.999-99');
            $cnpj->setMask('99.999.999.9999-99');
            $birth_date->setMask('99/99/9999');
            $phone->setMask('(99)99999-9999');
            $gender->addItems(['M'=>'Masculino','F'=>'Feminino']);

            $email->setExitAction(new TAction([$this,   'onCheckEmail']));
            $city_id->addItems(TApiRestClient::get('hero', 'city', 'getArray', ['small', 'ibge']));
            $city_id->setMinLength(0);

            //Monta o formulário
            $this->form->addTab('Pessoa Física', 'mdi mdi-human-male');
            $this->form->addFieldLine($cpf,             'CPF',                      [150, null], false, null, 1);
            $this->form->addFieldLine($birth_date,      'Data de nascimento',       [150, null], false, null, 1);
            $this->form->addFieldLine($name,            'Nome',                     [400, null], true);
            $this->form->addFieldLine($rg,              'RG',                       [250, null]);
            
            //Pessoa Jurídica
            $this->form->addTab('Pessoa Jurídica', 'mdi mdi-domain');
            $this->form->addFieldLine($owner_id,        'Dono',                     [700, null]);
            $this->form->addFieldLine($name_fantasy,    'Nome fantasia',            [450, null]);
            $this->form->addFieldLine($cnpj,            'CNPJ',                     [200, null]);

            //contato
            $this->form->addTab('Contato', 'mdi mdi-card-account-mail');
            $this->form->addFieldLine($id);
            $this->form->addFieldLine($phone,    'Telefone',    [250, null]);
            $this->form->addFieldLine($email,    'E-mail',      [400, null], true);

            //endereço
            $this->form->addTab('Endereço', 'mdi mdi-map-marker');
            $this->form->addFieldLine($zip_code,        'CEP',              [110, null],    false, null, 4);
            $this->form->addFieldLine($street,          'Rua',              [400, null]);
            $this->form->addFieldLine($neighborhood,    'Bairro',           [300, null]);
            $this->form->addFieldLine($number,          'Número',           [120, null]);
            $this->form->addFieldLine($city_id,         'Cidade',           [450, null]);
            $this->form->addFieldLine($complement,      'Complemento',      [200, null]);
       
            //Botões de ações
            $button = new TButtonPress('Gravar', 'mdi mdi-content-save-settings');
            
            if(!empty($param['return']))
            {
                $button->setAction([$this, 'onSave', ['effect' => false, 'return' => $param['return']]]);
            }
            else
            {
                $button->setAction([$this, 'onSave', ['effect' => false]]);
            }

            $this->form->addButton($button);
            
            $button = new TButtonPress('Novo', 'mdi mdi-plus');
            $button->setAction([$this, 'onEdit']);
            $this->form->addButton($button);

            //Gera a form
            $this->form->generate();
            
            TTransaction::close();
            
            //Estrutura da pagina
            $page = new TPageContainer();
            $page_box = $page->createBox(false);
            $page_box->add(ScreenHelper::getHeader(__CLASS__));
            $page_box->add($this->form);
            
            parent::add($page);
        }
        catch (Exception $e) 
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        } 
    }

    public static function onCheckEmail($param)
    {
        try
        {
            TTransaction::open('sync');

            $id = null;

            if(isset($param['id']) AND !empty($param['id']))
            {
                $id = $param['id'];
            }

            //Desconsidera se for uma pessoa juridica
            if($param['cnpj'] OR $param['name_fantasy'] OR $param['owner_id'])
            {
                return true;
            }
            elseif(isset($param['email']) AND !empty($param['email']))
            {
                $email = strtolower($param['email']);

                if($id)
                {
                    $objCheck = Person::where('email',  '=',    $email)
                                         ->where('id',  '!=',   $id)
                                         ->where('id',  'IN',   "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id)")
                                         ->get();

                    if($objCheck)
                    {
                        //Verifica somente para a fisica
                        $objCheck = $objCheck[0];
                        
                        $notify = new TNotify("Este E-MAIL já está sendo usado por outra pessoa ({$objCheck->id} - {$objCheck->name}). Você não pode usá-lo para este cadastro");
                        $notify->setIcon('mdi mdi-close');
                        $notify->show();

                        TScript::create("$('[name=email]').val('')");
                    }
                }
                else
                {
                    $objCheck = Person::where('email',  '=',    $email)
                                                ->where('id',   'IN',   "NOESC: (SELECT bas_person_individual.person_id FROM bas_person_individual WHERE bas_person_individual.person_id = bas_person.id)")
                                                ->get();
                    if($objCheck)
                    {
                        $objCheck = $objCheck[0];

                        $notify = new TNotify("Este E-MAIL já está sendo usado por outra pessoa ({$objCheck->id} - {$objCheck->name}). Se você gravar este formulário o sistema atualizará a pessoa que já existe e não criará uma nova.");
                        $notify->setIcon('mdi mdi-close');
                        $notify->show();
                    }
                }
            }

            TTransaction::close();
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        }
    }
    
    public static function clearFields($type)
    {
        if($type == 'person_individual')
        {
            TField::clearField('person_form', 'name');
            TField::clearField('person_form', 'cpf');
            TField::clearField('person_form', 'birth_date');
        }
        
        if($type == 'person_company')
        {
            TField::clearField('person_form', 'name');
            TField::clearField('person_form', 'cnpj');
            TField::clearField('person_form', 'name_fantasy');
        }
    }

    /**
     * Método onSave()
     * 
     */
    function onSave()
    {
        try
        {
            TTransaction::open($this->db);
            
            $user  = UserService::getSession();

            //Validação
            $this->form->validate();
            
            //Recupera dados do Post;
            $data = $this->form->getData($this->model);

            $param                  = [];
            $param['id']            = $data->id; 
            $param['code']          = $data->code; 
            $param['name']          = $data->name; 
            $param['phone']         = $data->phone; 
            $param['email']         = $data->email;
            $param['photo']         = $data->photo;

            //Para pessoa fisica
            if($data->birth_date OR $data->cpf OR $data->genre)
            {
                $param['individual']['cpf']             = $data->cpf; 
                $param['individual']['birth_date']      = TDate::date2us($data->birth_date);
            }

            //Para empresa
            if($data->cnpj)
            {
                $param['company']['cnpj']               = $data->cnpj;
                $param['company']['name_fantasy']       = $data->name_fantasy;
                $param['company']['owner_id']           = $data->owner_id;
            }
            else
            {
                $param['individual']['gender']          = $data->gender;
                $param['individual']['rg']              = $data->rg;
            }

            $param['zip_code']      = $data->zip_code;
            $param['street']        = $data->street;
            $param['neighborhood']  = $data->neighborhood;
            $param['number']        = $data->number;
            $param['city_id']       = $data->city_id;
            $param['complement']    = $data->complement;
           
            //Apenas cria a pessoa
            $person = PersonService::create($param);

            TTransaction::close();

            //Volta os dados para o form
            $this->form->setData($data);
            
            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->show();
            
            if(!empty($param['return']))
            {
                parent::closeWindow(['person_id' => $person->id, 'return' => $param['return']]);
            }
            else
            {
                parent::closeWindow(['person_id' => $person->id]);
            }
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        }
    }
    
    /**
     * Método onEdit()
     * 
     */
    function onEdit($param)
    {
        try
        {
            if (isset($param['code']))
            {
                TTransaction::open($this->db);
                
                $person             = Person::getByCode($param['code']);
                $person_individual  = $person->getIndividual();
                $person_company     = $person->getCompany();
                
                if($person_individual)
                {
                    $person->birth_date     = TDate::date2br($person_individual->birth_date);
                    $person->cpf            = $person_individual->cpf;
                    $person->gender         = $person_individual->gender;
                    $person->rg             = $person_individual->rg;
                }

                if($person_company)
                {
                    $person->name_fantasy   = $person_company->name_fantasy;
                    $person->cnpj           = $person_company->cnpj;
                    $person->owner_id       = $person_company->owner_id;
                }

                $this->form->setData($person);
                
                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();
        }
    }
}
?>