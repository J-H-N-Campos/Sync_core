<?php
/**
 * Subscription
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class Subscription extends TRecord
{
    const TABLENAME     = 'cad_subscription';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    const CACHECONTROL  = 'TAPCache';
    
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('user_id');
		parent::addAttribute('event_id');
        parent::addAttribute('dt_subscription');
        parent::addAttribute('fl_present');
    }

    public function getEvent()
	{
		return new Event($this->event_id);
    }

    public function getUser()
	{
		return new User($this->user_id);
    }

    public static function register($event_id, $user_id)
	{
		if(!empty($event_id) AND !empty($user_id))
        {
            $self = Self::where('event_id', '=', $event_id)
                        ->where('user_id',  '=', $user_id)
                        ->get();
            
            if(empty($self))
            {
                $self                   = new Self();
                $self->user_id          = $user_id;
                $self->event_id         = $event_id;
                $self->dt_subscription  = date('Y-m-d H:i:s');
                $self->store();
            }
            else
            {
                throw new Exception("Você já é inscrito para este evento");
            }
        }
        else
        {
            throw new Exception("Parâmetro não informado");
        }
    }

    public static function brandPresent($event_id, $user_id)
	{
		if(!empty($event_id) AND !empty($user_id))
        {
            $self = Self::where('event_id',     '=', $event_id)
                        ->where('user_id',      '=', $user_id)
                        ->get();
                        
            if(!empty($self))
            {
                $self = $self[0];

                if($self->fl_present != true)
                {
                    $self->fl_present = true;
                    $self->store();
                }
                else
                {
                    throw new Exception("Você já marcou presença para este evento");
                }
            }
            else
            {
                throw new Exception("Você não é inscrito para marcar presença");
            }
        }
        else
        {
            throw new Exception("Parâmetro não informado");
        }
    }

    public static function cancel($event_id, $user_id)
	{
		if(!empty($event_id) AND !empty($user_id))
        {
            $self = Self::where('event_id', '=', $event_id)
                        ->where('user_id',  '=', $user_id)
                        ->get();
                        
            if(!empty($self))
            {
                $self = $self[0];
                $self->delete();
            }
            else
            {
                throw new Exception("Você não é inscrito para cancelar este evento");
            }
        }
        else
        {
            throw new Exception("Parâmetro não informado");
        }
    }

    public static function generateCertificate($event_id, $user_id)
	{
		if(!empty($event_id) AND !empty($user_id))
        {
            $self = Self::where('event_id',     '=', $event_id)
                        ->where('user_id',      '=', $user_id)
                        ->where('fl_present',   '=', 't')
                        ->get();
                        
            if(!empty($self))
            {
                $self           = $self[0];
                $user           = $self->getUser();
                $person         = $user->getPerson();
                $event          = $self->getEvent();
                $type_document  = $event->getTypeDocument();
                $type_document  = $type_document[0];

                //Pega o template e faz o replace do contratante
                $html = $type_document->template;
                $html = str_replace('{person_name}',    $person->name,                              $html);
                $html = str_replace('{person_email}',   $person->email,                             $html);
                $html = str_replace('{event_name}',     $event->name,                               $html);
                $html = str_replace('{event_dt_event}', TDateService::dateToTasy($event->dt_event), $html);

                //Gera o pdf
                $options = new \Dompdf\Options();
                $options->setIsRemoteEnabled(true);
                $options->setTempDir('tmp/');
                $options->setChroot(getcwd());
                $options->setLogOutputFile('tmp/dompdf');

                //Converts the HTML template into PDF
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->setOptions($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                //Write and open file
                $path = "tmp/certificate_{$self->id}.pdf";
                $exec = file_put_contents($path, $dompdf->output());

                if($path)
                {
                    $person = new Person($person->id);
                    $person->certificate = $path;
                    $person->store();

                    //Retornar o path
                    return $path;
                }
            }
            else
            {
                throw new Exception("Você não é inscrito para gerar certificado deste evento ou não marcou presença");
            }
        }
        else
        {
            throw new Exception("Parâmetro não informado");
        }
    }
}
?>