<?php
/**
 * Event
 *
 * @version    1.0
 * @date       18/04/2022
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class Event extends TRecord
{
    const TABLENAME     = 'cad_event';
    const PRIMARYKEY    = 'id';
    const IDPOLICY      = 'serial'; // {max, serial}
    const CACHECONTROL  = 'TAPCache';
    
    public function __construct($id = NULL)
    {
        parent::__construct($id);
        parent::addAttribute('name');
        parent::addAttribute('code');
		parent::addAttribute('dt_event');
        parent::addAttribute('description');
    }

    public function store()
    {
        if(!$this->id)
        {
            $this->code = TString::getCode();
        }
        
        Parent::store();
    }

    public function validateDate()
    {
        $date_current = date('Y-m-d');
        $days = TDateService::diff($date_current, $this->dt_event);

        if($days >= 0)
        {
            return $days;
        }
        else
        {
            throw new Exception("Esse evento já passou");
        }
    }

    public function getTypeDocument()
    {
        return TypeDocument::where('event_id', '=', $this->id)->get();
    }

    public function getSubscription()
    {
        return Subscription::where('event_id', '=', $this->id)->get();
    }

    public static function getByCode($code)
    {
        $self = self::where('code', '=', $code)->get();
        
        if($self)
        {
            return $self[0];
        }
    }
}
?>