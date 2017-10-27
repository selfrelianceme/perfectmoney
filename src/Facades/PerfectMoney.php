<?php 
namespace Selfreliance\PerfectMoney\Facades;  

use Illuminate\Support\Facades\Facade;  

use Selfreliance\PerfectMoney\PerfectMoney as PerfectMoneyClass;

class PerfectMoney extends Facade 
{
	protected static function getFacadeAccessor() { 
		return PerfectMoneyClass::class;   
	}
}
