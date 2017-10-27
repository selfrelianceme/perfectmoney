<?php

namespace Selfreliance\PerfectMoney;

use Illuminate\Http\Request;
use Config;
use Route;

use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Libraries\Deposit;
class PerfectMoney
{
	use ValidatesRequests;
	public $errorMessage;
	function balance(){
		$client = new \GuzzleHttp\Client();
		$res = $client->request('GET', 'https://perfectmoney.is/acct/balance.asp', [
			'query' => [
				"AccountID"  => Config::get('perfectmoney.account_id'),
				"PassPhrase" => Config::get('perfectmoney.account_pass')
			]
		]);
		
		preg_match_all("/<input name='ERROR' type='hidden' value='(.*)'>/", $res->getBody(), $result, PREG_SET_ORDER);
		if($result){
			$this->errorMessage = $result[0][1];
		}

		dump($this->errorMessage);
	}

	function form($payment_id, $sum, $units='USD'){
		$sum = number_format($sum, 2, ".", "");
		$form_data = array(
			"PAYEE_ACCOUNT"        => Config::get('perfectmoney.payee_account'),
			"PAYEE_NAME"           => Config::get('perfectmoney.account_name'),
			"PAYMENT_ID"           => $payment_id,
			"PAYMENT_AMOUNT"       => $sum,
			"PAYMENT_UNITS"        => $units,
			"STATUS_URL"           => route('perfectmoney.confirm'),
			"PAYMENT_URL"          => route('personal.index'),
			"PAYMENT_URL_METHOD"   => "POST",
			"NOPAYMENT_URL"        => route('perfectmoney.cancel'),
			"PAYER_ACCOUNT"        => "",
			"NOPAYMENT_URL_METHOD" => "POST",
			"SUGGESTED_MEMO"       => ""
		);
		ob_start();
			echo '<form class="form_payment" id="FORM_pay_ok" action="https://perfectmoney.is/api/step1.asp" method="POST">';
			foreach ($form_data as $key => $value) {
				echo '<input type="hidden" name="'.$key.'" value="'.$value.'">';
			}
			echo '<input type="submit" style="width:0;height:0;border:0px; background:none;" class="content__login-submit submit_pay_ok" name="PAYMENT_METHOD" value="">';
			echo '</form>';
		$content = ob_get_contents();
		ob_end_clean();
		return $content;		
	}

	function cancel_payment(Request $request){
		$this->validate($request, [
            'PAYMENT_ID' => 'required',
        ]);		
		
		(new Deposit)->cancel_purchase($request->input('PAYMENT_ID'));	

		return redirect()->route('personal.index');
	}
}