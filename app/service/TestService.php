
<?php
/**
 * TestService
 *
 * @version    1.0
 * @date       29/10/2021
 * @author     João De Campos
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class TestService
{
    public static function get($code = null)
    {
        //retorna todos os testes
        if($code)
        {
            $test = Test::where('code', '=', $code)->orderBy('id',   'desc')->get();

            if($test)
            {
                $test = $test[0];

                return self::getData($test);
            }
            else
            {
                throw new Exception("Teste não existe");
            }
        }
        else
        {
            $tests = Test::orderBy('id',   'desc')->get();
        }

        $array = [];
        
        //percorre os testes se tem
        if(!empty($tests))
        {
            foreach($tests  as $test)
            {
                $array[] = self::getData($test);
            }
        }

        return $array;
    }

    public static function getByUrl($url)
    {
        $test = Test::where('url', '=', $url)->get();
        
        if($test)
        {
            return self::getData($test[0]);
        }
        else
        {
            throw new Exception("Teste não existe");
        }
    }

    public static function getData($test)
    {
        $objTest            = TObject::toStd($test);
        $objTest->url_image = $test->getUrlLogo();

        //Pega as questoes
        $questions       = $test->getQuestions();
        $array_questions = [];
    
        //percorre as questões
        foreach($questions as $question)
        {
            $answers           = $question->getAnswers();
            $index             = $question->getIndex();
            $question_validate = $question->getQuestionValidate();

            $objQuestion                    = TObject::toStd($question);
            $objQuestion->answers           = TObject::castToStdClassArray($answers);
            $objQuestion->index             = TObject::toStd($index);
            $objQuestion->question_validate = null;

            if($question_validate)
            {
                $objQuestion->question_validate = TObject::toStd($question_validate);
            }

            $array_questions[$question->id]  = $objQuestion;

        }

        $objTest->questions = $array_questions;

        return $objTest;
    }
}
?>