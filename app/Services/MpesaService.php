<?php

namespace App\Services;

use App\Models\MpesaTransaction;
use Filament\Notifications\Notification;
use http\Env\Request;
use Illuminate\Support\Facades\Log;
use Kemboielvis\MpesaSdkPhp\Mpesa;

class MpesaService
{

    private Mpesa $mpesa;
    private string $passkey;
    private string $callBackUrl;
    private string $businessCode;

    public function __construct(){
        $this->mpesa = new Mpesa();
        $this->passkey = config('mpesa.passkey');
        $this->callBackUrl = config('mpesa.callback_url');
        $this->businessCode = config('mpesa.shortcode');
        $credentials =[
            'consumer_key' => config('mpesa.consumer_key'),
            'consumer_secret' => config('mpesa.consumer_secret'),
        ];
        $this->mpesa= $this->mpesa->setCredentials($credentials['consumer_key'], $credentials['consumer_secret']);
    }

//    public function registerUrls(){
//        $registerUrl=$this->mpesa->registerURL($this->businessCode)n
//        ->responseType('Completed')
//        ->validationUrl($this->businessCode)
//        ->confirmationUrl($this->businessCode)
//        ->businessCode('600984')->registerUrl;
//
//
//    }
    /**
     * @param array $data {
     * @var string $amount
     * @var string $phoneNumber
     * @var string $transactionType
     * @var string $accountReference
     * @var string $transactionDesc
     * }
     */

    public function sendSTKPush(array $data): array{
        $stkpush= $this->mpesa->stk()
            ->businessCode($this->businessCode)
            ->transactionType("CustomerOnlinePaybillOnline")
            ->phoneNumber($data['phoneNumber'])
            ->amount($data['amount'])
            ->transactionDesc($data['transaction_desc'] ?? '')
            ->callBackUrl($this->callBackUrl)
            ->accountReference($data['account_reference'] ?? '')
            ->passKey($this->passkey)
        ;

        $push = $stkpush->push();
        $response = $push->response();
        //query StkPush an=d check status
        $stkQuery = $push->query();
        Log::info("Stk Query:".json_encode($stkQuery));
        return [
            'stkQuery' => $stkQuery,
            'response' => $response,
        ];
    }
    public function handleCallBack(Request $request) :void
    {
        //get response from the callback
        $response = json_decode($request->getBody(), true);
        Log::log('info',$response);

        //find the transaction initiated
        try {
            $transaction = MpesaTransaction::query()
                ->where('MerchantRequestID',$response['body']['stkCallback']['MerchantRequestID'])
                ->where('CheckoutRequestID',$response['body']['stkCallback']['CheckoutRequestID'])
                ->first();
            Log::info('transaction:',[
                $transaction
            ]);

            //get the rest of the fields updated
            $transactionData = [
                'transaction_code'=>$response['body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'],
                'phone_number'=>$response['body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'],
                'amount'=>$response['body']['stkCallback']['CallbackMetadata']['Item'][2]['Value'],
                'transaction_date'=>$response['body']['stkCallback']['CallbackMetadata']['Item'][3]['Value'],

            ];
            //log
            Log::info('transaction:',[
                $transactionData
            ]);

            //check whether the transaction was found and the updat it in db
            if($transaction){

                $transaction->update($transactionData);
            }

        }
        catch (\Exception $exception){
            $exception->getMessage();
            Log::error('error',[$exception]);
        }
    }

    public function makeStkPayment(array $data): void
    {
        try {
            $data_ = [
                'amount' => $data['amount'],
                'phoneNumber' => $data['phone_number'],
                'transactionType' => 'CustomerPayBillOnline',
                'accountReference' => 'accountReference',

            ];
            $response = $this->sendSTKPush($data_);
            $transaction = MpesaTransaction::query()
                ->create([
                    'MerchantRequestID' => $response['response']['MerchantRequestID'],
                    'CheckoutRequestID' => $response['response']['CheckoutRequestID'],
                    'phone_number' => $data['phone_number'],
                    'transaction_amount' => $data['amount'],
                ]);

            if ($transaction) {
                Notification::make()
                    ->success()
                    ->title('MPESA PAYMENT SUCCESSFUL')
                    ->body('STK Push sent. Please check your phone to complete your transaction.')
                    ->send();
            }
            else {
                Notification::make()
                    ->warning()
                    ->title('MPESA PAYMENT FAILED')
                    ->body('Failed to initiate Transaction')
                    ->send();
            }
        }
        catch (\Exception $exception) {
            Notification::make()
                ->warning()
                ->title('Server Error')
                ->body('Error! ' . $exception->getMessage())
                ->send();
        }
    }
}
