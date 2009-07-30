<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette\Forms
 * @version    $Id: Checkbox.php 315 2009-05-24 21:20:42Z david@grudl.com $
 */

/*namespace Nette\Forms;*/



require_once dirname(__FILE__) . '/../../Forms/Controls/FormControl.php';



/**
 * Check box control. Allows the user to select a true or false condition.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
class Checkbox extends FormControl
{

	/**
	 * @param  string  label
	 */
	public function __construct($label = NULL)
	{
		parent::__construct($label);
		$this->control->type = 'checkbox';
		$this->value = FALSE;
	}



	/**
	 * Sets control's value.
	 * @param  bool
	 * @return void
	 */
	public function setValue($value)
	{
		$this->value = is_scalar($value) ? (bool) $value : FALSE;
	}



	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		return parent::getControl()->checked($this->value);
	}

}
