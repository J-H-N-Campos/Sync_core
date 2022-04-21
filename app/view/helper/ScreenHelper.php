<?php

/**
 * ScreenHelper
 *
 * @version    1.0
 * @date       08-02-2015
 * @author     Rodrigo de Freitas
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */

class ScreenHelper extends TRecord
{
	public static function getHeader($controller, $fl_center = false)
	{
		$string = null;

    	TTransaction::open('sync');
		
		//Menu
		$screen = Screen::where('controller', '=', $controller)->get();

		if($screen)
		{
			$screen     = $screen[0];
			$menu       = $screen->getMenu();
			
			$content        	= new TElement('div');
			$content->class 	= "page-header-tag";
			
			if($screen->helper)
			{
				$content->add("<div class='page-header-tag-helper-content' onclick=' __adianti_post_data(\"form236\", \"class=HelpView&key={$screen->id}\"); return false;'><div class='icon-helper pulse-blue'><i class='mdi mdi-help'></i></div></div>");
			}

			$box = new TElement('div');
			$box->add("<div class='page-header-tag-menu'>{$menu->name} <i class='mdi mdi-chevron-right'></i></div>
					   <div class='page-header-tag-title'><i class='{$screen->icon}'></i> {$screen->name}</div>");

			if($fl_center)
			{
				$content->style = 'justify-content: center;text-align: center;';
			}

			$content->add($box);

			//Controle do fechamento do menu
			$class = new ReflectionClass($screen->controller);

			//Precisa ser diferente de window
			if($class->getParentClass()->name != 'TCurtain')
			{
				//Se tiver pai
				$parent = $screen->getParent();
	
				if($parent)
				{
					$screen = $parent;
				}
				
				if(TPage::isMobile())
				{
					//Check do menu
					$content->add("<script>art_expand_menu('close');</script>");
				}
				
				//Check do menu
				$content->add("<script>art_check_submenu({$screen->id});</script>");
			}
		}
		
		TTransaction::close();

		return $content;
	}
}
?>