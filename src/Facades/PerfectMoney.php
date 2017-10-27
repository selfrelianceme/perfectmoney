<?php 
namespace Selfreliance\PerfectMoney\Facades;  

use Illuminate\Support\Facades\Facade;  

class PerfectMoney extends Facade 
{
	protected static function getFacadeAccessor() { 
		return 'PerfectMoney';     
	}
}
