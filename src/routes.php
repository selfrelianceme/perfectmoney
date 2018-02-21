<?php

Route::post('perfectmoney/cancel', 'Selfreliance\PerfectMoney\PerfectMoney@cancel_payment')->name('perfectmoney.cancel');
Route::post('perfectmoney/confirm', 'Selfreliance\PerfectMoney\PerfectMoney@validateIPNRequest')->name('perfectmoney.confirm');
Route::any('perfectmoney/personal', function(){
	return redirect(env('PERSONAL_LINK_CAB'));
})->name('perfectmoney.after_pay_to_cab');