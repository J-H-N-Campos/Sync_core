<?php

require_once 'init.php';

try
{
	$config = ApiService::getConfig();
    $db     = $config['db_name'];

    TTransaction::open($db);

	//Inicia a api rest
	$api_rest	= new ApiRest($db);
	$return 	= $api_rest->run();
	
	TTransaction::close();
	
	echo $return;
}
catch (Exception $e)
{
	TTransaction::rollback();

	echo $api_rest->getError($e->getMessage());
}
?>