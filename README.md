# PerfectMoney


Require this package with composer:
```
composer require selfreliance/perfectmoney
```
## Publish Config

```
php artisan vendor:publish --provider="Selfreliance\PerfectMoney\PerfectMoneyServiceProvider"
```

## Use name module

```
use Selfreliance\PerfectMoney\PerfectMoney;
```
or
```
$pm = resolve('payment.perfectmoney');
```

## Configuration

Add to **.env** file:

```
#PerfectMoney_Settings
PM_ACCOUNT_ID=
PM_ACCOUNT_PASS=
PM_PAYEE_ACCOUNT=
PM_ALT=
PM_ACCOUNT_NAME=
PERSONAL_LINK_CAB=/personal
```