<?php

Route::post('perfectmoney/cancel', 'Selfreliance\PerfectMoney\PerfectMoney@cancel_payment')->name('perfectmoney.cancel');
Route::post('perfectmoney/confirm', 'Selfreliance\PerfectMoney\PerfectMoney@confirm_payment')->name('perfectmoney.confirm');