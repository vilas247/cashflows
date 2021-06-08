<?php
/**
 * This file is part of the 247Commerce BigCommerce CASHFLOW App.
 *
 * ©247 Commerce Limited <info@247commerce.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
if (! function_exists('set_status'))
{
	function set_status(string $value, array $row): string
	{
		return $value === '1' ? 'Active' : 'Inactive';
	}
}

if (! function_exists('action_links'))
{
	function action_links(string $value, array $row): string
	{
		return '<a href="'.base_url('apilogs/'.$value).'">View</a>';
	}
}