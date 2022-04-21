<?php
/**
 * EventDetailsView
 *
 * @version    1.0
 * @date       19/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
 
class EventDetailsView extends TCurtain
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
            
            
            //Definições de conexão
    		$this->db       = 'sync';
            $this->model    = 'Event';
            $this->parent   = $this->model.'ClientList';
            
            if(!empty($param['return']) AND !empty($param['param_return_name']))
            {
                parent::setActionClose([$param['return'], null, [$param['param_return_name'] => $param['param_return_value']]]);
            }
            elseif(!empty($param['return']))
            {
                parent::setActionClose([$param['return']]);
            }
            else
            {
                parent::setActionClose(['EventClientList']);
            }
            
            TTransaction::open('sync');

            $content = new TElement('div');
            
            $event              = Event::getByCode($param['code']);
            $subscriptions      = $event->getSubscription();
            $user               = UserService::getsession();
            $user_subscriptions = Subscription::where('user_id', '=', $user->id)->get();
        
            if($event->validateDate())
            {
                //Add
                $content->add(EventHelper::getTop($event, 'mdi mdi-account-tie-voice-outline'));
                    
                //Form de acoes
                $form = new TFormStruct("form_event_detalhe");
                
                $form->addTab('Operação','mdi mdi-move-resize-variant');

                $button = new TButtonPress('mdi mdi-check-bold', 'Marcar Presença');
                $button->setClass('green');
                $button->setAction([$this, 'onBrandPresent', ['code' => $param['code'], 'effect' => false]]);
                $form->addFieldLine($button);

                $button = new TButtonPress('mdi mdi-close', 'Cancelar');
                $button->setClass('green');
                $button->setAction([$this, 'oncancel', ['code' => $param['code'], 'effect' => false]]);
                $form->addFieldLine($button);

                $button = new TButtonPress('mdi mdi-file', 'Gerar Certificado');
                $button->setClass('green');
                $button->setAction([$this, 'onGenerateCertificate', ['code' => $param['code'], 'effect' => false]]);
                $form->addFieldLine($button);

                if(!empty($user_subscriptions))
                {
                    $datagrid = new TCardDataGrid;
                    $datagrid->setMaxSize('100%');
                    
                    //Colunas
                    $datagrid->addColumn('event_id');
                    $datagrid->addColumn('user_id',         null, null, "float: left;");
                    $datagrid->addColumn('dt_subscription', null, null, "float: left;");
                    $datagrid->addColumn('description',     null, null, "float: left;");
                    $datagrid->addColumn('certificate',     null, null, "float: left;");

                    if($subscriptions)
                    { 
                        foreach($subscriptions as $subscription)
                        {
                            $user_subscription  = $subscription->getUser();
                            $event              = $subscription->getEvent();
                            $person             = $user_subscription->getPerson();

                            $subscription->dt_subscription  = "<b>Data de inscrição: ". TDateService::timeStampToBr($subscription->dt_subscription)."</b>";
                            $subscription->user_id          = "<b>Usuário: {$user_subscription->description}</b>";
                            $subscription->event_id         = "<b>Evento: {$event->name}</b>";
                            $subscription->description      = "<b>Descrição: {$event->description}</b>";

                            if(!empty($person->certificate))
                            {
                                $subscription->certificate = "<a href={$person->certificate} style='text-decoration:underline; color: var(--color-master); target='_blank''><b>Acessar certificado</b></a>";
                            }
                            
                            $datagrid->addItem($subscription);
                        }
                        
                        $datagrid->generate();
                        $form->addFieldLine($datagrid);
                    }
                }

                $form->generate();
                $content->add($form);
            }
            
            //Estrutura do conteúdo da página
            parent::add(ScreenHelper::getHeader(__CLASS__));
            parent::add($content);

            TTransaction::close();
        }
        catch (Exception $e) 
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
            
            TTransaction::rollback();

            parent::forceClose();
        } 
    }

    function onBrandPresent($param)
    {
        try
        {
            $action = new TAction([$this, 'brandPresent']);
            $action->setParameters($param);
            
            //Pergunta
            $notify = new TNotify('Comparecer ao evento', 'Confirmar presença?');
            $notify->setIcon('mdi mdi-help-circle-outline');
            $notify->addButton('Sim', $action);
            $notify->addButton('Não', null);
            $notify->show();
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
        }
    }

    function brandPresent($param)
    {
        try
        {
            TTransaction::open('sync');

            //pega o usuário da sessão
            $user = UserService::getSession();

            if(!empty($param['code']))
            {
                $event = Event::getByCode($param['code']);

                if($event)
                {
                    Subscription::brandPresent($event->id, $user->id);
                }
                else
                {
                    throw new Excpetion("Evento não encontrado");
                }
            }
            else
            {
                throw new Excpetion("Parâmetro não informado");
            }
            
            TTransaction::close();

            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->setAutoRedirect([$this, 'onReload', ['code' => $param['code'], 'effect' => false]]);
            $notify->show();
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
        }
    }

    function oncancel($param)
    {
        try
        {
            $action = new TAction([$this, 'cancel']);
            $action->setParameters($param);
            
            //Pergunta
            $notify = new TNotify('Cancelar', 'Deseja cancelar a sua presença para este evento?');
            $notify->setIcon('mdi mdi-help-circle-outline');
            $notify->addButton('Sim', $action);
            $notify->addButton('Não', null);
            $notify->show();
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
        }
    }

    function cancel($param)
    {
        try
        {
            TTransaction::open('sync');

            //pega o usuário da sessão
            $user = UserService::getSession();

            if(!empty($param['code']))
            {
                $event = Event::getByCode($param['code']);

                if($event)
                {
                    Subscription::cancel($event->id, $user->id);
                }
                else
                {
                    throw new Excpetion("Evento não encontrado");
                }
            }
            else
            {
                throw new Excpetion("Parâmetro não informado");
            }
            
            TTransaction::close();

            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->setAutoRedirect([$this, 'onReload', ['code' => $param['code'], 'effect' => false]]);
            $notify->show();
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
        }
    }

    function onGenerateCertificate($param)
    {
        try
        {
            $action = new TAction([$this, 'generateCertificate']);
            $action->setParameters($param);
            
            //Pergunta
            $notify = new TNotify('Gerar certificado', 'Deseja gerar o certificado desse evento?');
            $notify->setIcon('mdi mdi-help-circle-outline');
            $notify->addButton('Sim', $action);
            $notify->addButton('Não', null);
            $notify->show();
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
        }
    }

    function generateCertificate($param)
    {
        try
        {
            TTransaction::open('sync');

            //pega o usuário da sessão
            $user = UserService::getSession();

            if(!empty($param['code']))
            {
                $event = Event::getByCode($param['code']);

                if($event)
                {
                    Subscription::generateCertificate($event->id, $user->id);
                }
                else
                {
                    throw new Excpetion("Evento não encontrado");
                }
            }
            else
            {
                throw new Excpetion("Parâmetro não informado");
            }
            
            TTransaction::close();

            $notify = new TNotify('Sucesso', 'Operação foi realizada');
            $notify->enableNote();
            $notify->setAutoRedirect([$this, 'onReload', ['code' => $param['code'], 'effect' => false]]);
            $notify->show();
        }
        catch (Exception $e)
        {
            ErrorService::send($e);

            $notify = new TNotify('Ops! Algo deu errado!', $e->getMessage());
            $notify->setIcon('mdi mdi-close');
            $notify->show();
        }
    }
}
?>