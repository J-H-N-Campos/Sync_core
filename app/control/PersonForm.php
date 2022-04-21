<?php
/**
 * PersonForm
 *
 * @version    1.0
 * @date       18/04/2022
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
            $id                 = new THidden('id');
            $code               = new THidden('code');
            $name               = new TEntry('name');
            $birth_date         = new TEntry('birth_date');
            $cpf                = new TEntry('cpf');
            $photo              = new TArchive('photo');
            $name_fantasy       = new TEntry('name_fantasy');
            $cnpj               = new TEntry('cnpj');
            $email              = new TEntry('email');
            $phone              = new TEntry('phone');
            $gender             = new TCombo('gender');
            $zip_code           = new TEntry('zip_code');
            $street             = new TEntry('street');
            $neighborhood       = new TEntry('neighborhood');
            $number             = new TEntry('number');
            $city_id            = new TUniqueSearch('city_id');
            $complement         = new TEntry('complement');
            $rg                 = new TEntry('rg');
            $gender             = new TCombo('gender');

            //atributos
            $zip_code->setMask('99999-999');
            $cpf->setMask('999.999.999-99');
            $birth_date->setMask('99/99/9999');
            $phone->setMask('(99)99999-9999');
            $gender->addItems(['M'=>'Masculino','F'=>'Feminino']);
            $cpf->setExitAction(new TAction([$this,     'onCpf']));
            $city_id->addItems(TApiRestClient::get('hero', 'city', 'getArray', ['small', 'ibge']));
            $city_id->setMinLength(0);
            $gender->addItems(['M'=>'Masculino','F'=>'Feminino', 'O'=>'Outro']);

            //Monta o formulário
            $this->form->addTab('Pessoa Física');
            $this->form->addFieldLine(TInterface::getHelp('Evite duplicação de cadastro quando não informar o CPF, consulte antes para ver se a pessoa ja existe', 'mdi mdi-information-outline'));
            $this->form->addFieldLine($cpf,             'CPF',                      [150, null], false, null, 1);
            $this->form->addFieldLine($birth_date,      'Data de nascimento',       [150, null], false, null, 1);
            $this->form->addFieldLine($name,            'Nome',                     [400, null]);
            $this->form->addFieldLine($gender,          'Gênero',                   [250, null]);

            //contato
            $this->form->addTab('Contato');
            $this->form->addFieldLine($id);
            $this->form->addFieldLine($email,    'E-mail',      [400, null], true);
            $this->form->addFieldLine($phone,    'Telefone',    [250, null]);
            

            //endereço
            $this->form->addTab('Endereço');
            $this->form->addFieldLine($zip_code,        'CEP',              [110, null],    false, null, 4);
            $this->form->addFieldLine($street,          'Rua',              [400, null],    false, 'Auto preenchido ao digitar o cep');
            $this->form->addFieldLine($neighborhood,    'Bairro',           [300, null],    false, 'Auto preenchido ao digitar o cep');
            $this->form->addFieldLine($number,          'Número',           [120, null]);
            $this->form->addFieldLine($city_id,         'Cidade',           [450, null],    false, 'Auto preenchido ao digitar o cep');
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
            $page     = new TPageContainer();
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

    public static function onCpf($params)
    {
        try
        {   
            if(!empty($params['cpf']) AND empty($params['code_sus']))
            {
                $objPerson = PersonService::onFillByCode($params['cpf'], 'cpf');

                return TForm::sendData('person_form', $objPerson, false, false);
            }
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            self::clearFields('person_individual');
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

    public static function onCep($params)
    {
        try
        {
            if(!empty($params['zip_code']))
            {
                $query = TApiRestClient::get('hero', 'zip-code', 'get', [$params['zip_code']]);

                if($query)
                {
                    $objReturn               = new StdClass;
                    $objReturn->street       = $query['street'];
                    $objReturn->neighborhood = $query['neighborhood'];
                    $objReturn->city_id      = $query['city']['ibge'];

                    TForm::sendData('person_form', $objReturn);
                }
            }
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
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
            if($data->name_fantasy OR $data->cnpj)
            {
                $param['company']['cnpj']               = $data->cnpj;
                $param['company']['name_fantasy']       = $data->name_fantasy;
                $param['name']                          = $data->name_fantasy;
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
                    
                    $this->form->removeTab('Pessoa Juridica');
                }

                if($person_company)
                {
                    $person->name_fantasy   = $person_company->name_fantasy;
                    $person->cnpj           = $person_company->cnpj;
                    $person->name           = null;
                    
                    $this->form->removeTab('Pessoa Física');
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