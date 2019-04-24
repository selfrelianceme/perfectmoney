<?php

namespace Selfreliance\PerfectMoney;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Config;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Selfreliance\PerfectMoney\Exceptions\PerfectMoneyException;

use Selfreliance\PerfectMoney\Events\PerfectMoneyPaymentIncome;
use Selfreliance\PerfectMoney\Events\PerfectMoneyPaymentCancel;

use Selfreliance\PerfectMoney\PerfectMoneyInterface;
use App\Models\MerchantPosts;
class PerfectMoney implements PerfectMoneyInterface
{
	use ValidatesRequests;
	protected $memo;
	public function memo($memo){
		$this->memo = $memo;
		return $this;
	}

    /**
     * @param string $unit
     * @return float
     * @throws \Exception
     */
    function balance($unit = "USD"){
		$client = new \GuzzleHttp\Client();
        try {
            $res = $client->request('GET', 'https://perfectmoney.is/acct/balance.asp', [
                'query' => [
                    "AccountID"  => config('perfectmoney.account_id'),
                    "PassPhrase" => config('perfectmoney.account_pass')
                ]
            ]);
        } catch (GuzzleException $e) {
            throw new \Exception($e->getMessage());
        }

        preg_match_all("/<input name='ERROR' type='hidden' value='(.*)'>/", $res->getBody(), $result, PREG_SET_ORDER);

		if($result){
			throw new \Exception($result[0][1]);			
		}
		preg_match_all("/<input name='".config('perfectmoney.payee_account')."' type='hidden' value='(.*)'>/", $res->getBody(), $result, PREG_SET_ORDER);
		return $result[0][1];
	}

    /**
     * @param int $payment_id
     * @param float $sum
     * @param string $units
     * @return string
     */
    function form($payment_id, $sum, $units='USD'){
		$sum = number_format($sum, 2, ".", "");
		$form_data = array(
			"PAYEE_ACCOUNT"        => config('perfectmoney.payee_account'),
			"PAYEE_NAME"           => config('perfectmoney.account_name'),
			"PAYMENT_ID"           => $payment_id,
			"PAYMENT_AMOUNT"       => $sum,
			"PAYMENT_UNITS"        => $units,
			"STATUS_URL"           => route('perfectmoney.confirm'),
			"PAYMENT_URL"          => route('perfectmoney.after_pay_to_cab'),
			"PAYMENT_URL_METHOD"   => "POST",
			"NOPAYMENT_URL"        => route('perfectmoney.cancel'),
			"PAYER_ACCOUNT"        => "",
			"NOPAYMENT_URL_METHOD" => "POST",
			"SUGGESTED_MEMO"       => ($this->memo)?$this->memo:null,
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

    /**
     * @param Request $request
     * @return bool
     */
    public function validateIPNRequest(Request $request) {
        return $this->check_transaction($request->all(), $request->server(), $request->headers);
    }

    /**
     * @param array $post_data
     * @param array $server_data
     * @return bool
     * @throws PerfectMoneyException
     */
    public function validateIPN(array $post_data, array $server_data){
		if(!isset($post_data['PAYMENT_ID'])){
			throw new PerfectMoneyException("For validate IPN need order id");
		}

		if($post_data['PAYMENT_AMOUNT'] <= 0){
			throw new PerfectMoneyException("Need amount for transaction");	
		}

		if($post_data['PAYEE_ACCOUNT'] != config('perfectmoney.payee_account')){
			throw new PerfectMoneyException("Payeer dont admin account");
		}

		$PAYMENT_ID        = $post_data['PAYMENT_ID'];
		$PAYMENT_AMOUNT    = $post_data['PAYMENT_AMOUNT'];
		$PAYMENT_BATCH_NUM = $post_data['PAYMENT_BATCH_NUM'];
		$PAYER_ACCOUNT     = $post_data['PAYER_ACCOUNT'];
		$TIMESTAMPGMT      = $post_data['TIMESTAMPGMT'];
		$V2_HASH           = $post_data['V2_HASH'];
		$PAYEE_ACCOUNT     = $post_data['PAYEE_ACCOUNT'];

		$sign = @$PAYMENT_ID .":". config('perfectmoney.payee_account') .":". @$PAYMENT_AMOUNT .":USD:". @$PAYMENT_BATCH_NUM .":". @$PAYER_ACCOUNT .":". strtoupper(md5(config('perfectmoney.alt'))) .":". @$TIMESTAMPGMT;
		$sign = strtoupper(md5($sign));

		if($sign !== $V2_HASH){
			throw new PerfectMoneyException("Missing sign !== V2 Hash");
		}

		return true;
	}

    /**
     * @param array $request
     * @param array $server
     * @param array $headers
     * @return bool
     */
    function check_transaction(array $request, array $server, $headers = []){
		MerchantPosts::create([
			'type'      => 'PerfectMoney',
			'ip'        => real_ip(),
			'post_data' => $request
		]);
		$textReponce = [
			'status' => 'success'
		];
		try{
			$is_complete = $this->validateIPN($request, $server);
			if($is_complete){
				$PassData                     = new \stdClass();
				$PassData->amount             = $request['PAYMENT_AMOUNT'];
				$PassData->payment_id         = $request['PAYMENT_ID'];
				$PassData->transaction        = $request['PAYMENT_BATCH_NUM'];
				$PassData->add_info           = [
					"full_data_ipn" => $request
				];
				event(new PerfectMoneyPaymentIncome($PassData));
				return \Response::json($textReponce, "200");
			}
		}catch(PerfectMoneyException $e){
			MerchantPosts::create([
				'type'      => 'PerfectMoney_Error',
				'ip'        => real_ip(),
				'post_data' => ['request' => $request, 'message' => $e->getMessage()],
			]);
		}
		return \Response::json($textReponce, "200");
	}

    /**
     * @param int $payment_id
     * @param float $amount
     * @param $address
     * @param string $currency
     * @return bool|\stdClass
     * @throws GuzzleException
     * @throws \Exception
     */
    function send_money($payment_id, $amount, $address, $currency){
		$amount = number_format($amount, 2, ".", "");
		$client = new \GuzzleHttp\Client();
		$res = $client->request('GET', 'https://perfectmoney.is/acct/confirm.asp', [
			'query' => [
				'AccountID'		=>	config('perfectmoney.account_id'),
				'PassPhrase'	=>	config('perfectmoney.account_pass'),
				'Payer_Account'	=>	config('perfectmoney.payee_account'),
				'Payee_Account'	=>	strtoupper(trim($address)),
				'Amount'		=>	$amount,
				'PAY_IN'		=>	$amount,
				'Memo'			=>	config('perfectmoney.account_name')." ".$payment_id,
				'PAYMENT_ID'	=>	$payment_id
		    ]
		]);

		preg_match_all("/<input name='ERROR' type='hidden' value='(.*)'>/", $res->getBody(), $result, PREG_SET_ORDER);
		if($result){
			throw new \Exception($result[0][1]);			
		}

		preg_match_all("/<input name='(.*)' type='hidden' value='(.*)'>/", $res->getBody(), $result, PREG_SET_ORDER);
		

		$rezult = [];
		foreach($result as $item){
			$rezult[$item[1]] = $item[2];
		}

		$PassData              = new \stdClass();
		$PassData->transaction = $rezult['PAYMENT_BATCH_NUM'];
		$PassData->sending     = true;
		$PassData->add_info    = [
			"fee"       => $amount*0.5/100,
			"full_data" => $rezult
		];
		return $PassData;
	}

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    function cancel_payment(Request $request){
		$PassData     = new \stdClass();
		$PassData->id = $request->input('PAYMENT_ID');
		
		event(new PerfectMoneyPaymentCancel($PassData));

		return redirect(config('perfectmoney.to_account'));
	}
}