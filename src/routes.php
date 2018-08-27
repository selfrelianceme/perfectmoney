<?php

Route::post('perfectmoney/cancel', 'Selfreliance\PerfectMoney\PerfectMoney@cancel_payment')->name('perfectmoney.cancel');
Route::post('perfectmoney/confirm', 'Selfreliance\PerfectMoney\PerfectMoney@validateIPNRequest')->name('perfectmoney.confirm');
Route::any('perfectmoney/personal', function(){
	return redirect(Config::get('perfectmoney.to_account'));
})->name('perfectmoney.after_pay_to_cab');