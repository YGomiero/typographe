<?php
/**
 * Typographe-Joomla plugin
 * @author		Typographe
 * @copyright	Copyright (C) 2018 Typographe All Rights Reserved.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// no direct access

defined( '_JEXEC' ) or die( 'Restricted access' );

use JoliTypo\Fixer;

require_once JPATH_PLUGINS . '/system/typographe/vendor/autoload.php';

class plgSystemTypographe  extends JPlugin
{
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		$app		= JFactory::getApplication();
		$document	= JFactory::getDocument();
		$input		= $app->input;

		if ($document->getType() !== 'html'
			|| $input->get('tmpl', '', 'cmd') === 'component'
			|| $app->isClient('administrator')
			|| $context == 'com_finder.indexer')
		{
			return true;
		}
			
		JLoader::registerPrefix('Fixer', JPATH_PLUGINS.'/system/typographe/vendor/', true);		

				$parametres = array();
				if ($this->params->get('ellipsis', 1) == 1) { array_push($parametres, 'Ellipsis'); }
				if ($this->params->get('dimension', 1) == 1) { array_push($parametres, 'Dimension'); }
				if ($this->params->get('unit', 1) == 1) { array_push($parametres, 'Unit'); }
				if ($this->params->get('dash', 1) == 1) { array_push($parametres, 'Dash'); }
				if ($this->params->get('smartquotes', 1) == 1) { array_push($parametres, 'SmartQuotes'); }
				if ($this->params->get('frenchnobreakspace', 1) == 1) { array_push($parametres, 'FrenchNoBreakSpace'); }
				if ($this->params->get('nospacebeforecomma', 1) == 1) { array_push($parametres, 'NoSpaceBeforeComma'); }
				if ($this->params->get('curlyquote', 1) == 1) { array_push($parametres, 'CurlyQuote'); }
				if ($this->params->get('hyphen', 1) == 1) { array_push($parametres, 'Hyphen'); }
				if ($this->params->get('trademark', 1) == 1) { array_push($parametres, 'Trademark'); }
		
		$fixer = new Fixer($parametres);	
		$fixer->setLocale($this->params->get('setlocale','fr_FR'));
		$article->text = $fixer->fix($article->text); 
		
	}

}
