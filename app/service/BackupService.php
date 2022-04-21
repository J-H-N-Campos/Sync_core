<?php
/**
 * BackupService
 *
 * @version    1.0
 * @date       04-05-2017
 * @author     Rodrigo de Freitas
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class BackupService
{
    //php cmd.php "class=BackupService&method=generateBase&static=1"
    public static function generateBase()
    {
        try
        {
            //Configurações destino
            $db                = 'sync';
            $destiny           = "fisyfiles.com.br:/var/www/html/tmp";
            $destiny_user      = "aplicacoes";
            $destiny_password  = "ulVzGsCQwq";

            //Config local
            $name_file    = "tmp/{$db}+" . date('Y-m-d') . ".sql";

            echo "Gerando {$db} ";

            //Verifica se o arquivo já existe
            if(file_exists($name_file))
            {
                //Deleta antes
                unlink($name_file);
            }

            //Dump
            exec("PGPASSWORD='p2j6v6t4' pg_dump {$db} -h localhost -U postgres > {$name_file}; ");

            //Verifica se o arquivo foi gerado
            if(file_exists($name_file))
            {
                //Envio para o servidor
                $a = exec("sshpass -p '{$destiny_password}' scp {$name_file} {$destiny_user}@{$destiny}");

                echo "{$name_file} :: Enviado ";

                //Deleta dump
                unlink($name_file);
            }
            else
            {
                throw new Exception("Falha ao gerar arquivo de dump");
            }

            echo ":: OK \n";
        }
        catch (Exception $e) 
        {
            ErrorService::send($e);
            
            echo $e->getMessage();   
        }
    }

    // php cmd.php "class=BackupService&static=1&method=generateFiles"
    public static function generateFiles()
    {
        try
        {
            //Configurações destino
            $destiny           = "fisyfiles.com.br:/var/www/html/tmp";
            $destiny_user      = "aplicacoes";
            $destiny_password  = "ulVzGsCQwq";

            //Config local
            $name_file    = "tmp/sync.zip";

            echo "BackupService::gerarArquivos({$name_file})\n";
            echo date('d/m/Y H:i:s') . "\n\n";

            //Verifica se o arquivo já existe
            if(file_exists($name_file))
            {
                //Deleta antes
                unlink($name_file);
            }

            //Dump
            exec("zip -r {$name_file} repository/ ");

            //Verifica se o arquivo foi gerado
            if(file_exists($name_file))
            {
                echo "Compactado com sucesso! \n";

                //Envio para o servidor
                $a = exec("sshpass -p '{$destiny_password}' scp {$name_file} {$destiny_user}@{$destiny}");
                echo "{$name_file} :: Enviado para {$destiny} \n";

                //Deleta dump
                unlink($name_file);
            }
            else
            {
                throw new Exception("Falha ao realizar compactação de dump");
            }

            echo "\nFim dos processamentos\n";
            echo date('d/m/Y H:i:s') . "\n";
        }
        catch (Exception $e) 
        {
            ErrorService::send($e);
            
            echo $e->getMessage();  
        }
    }
}
?>