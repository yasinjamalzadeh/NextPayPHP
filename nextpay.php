<?php

function generateOrderID(){
    $order_id = '';
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < 10; $i++)
    {
        $order_id = $order_id.$characters[rand(0, strlen($characters)-1)];
    }
    return $order_id;
}

/*
    createNextPayPayment

    In the return, We have status which when it is true, We have trans_id that we have to save it in out Database
    and redirect user to the https://nextpay.org/nx/gateway/payment/$trans_id (replace $trans_id)
        Also when returned status is true we have 10-digit string which it called order_id that hold a random val
    If the status is not true, That means we have to check error value which can be translated on this url:
        https://nextpay.org/nx/docs#step-7
*/
function createNextPayPayment($api, $amount, $callbackUrl, $customer_phone = "none"){

    $result = array(
        'status' => false,
    );

    $customer_field = "";
    $order_id = generateOrderID();

    if($customer_phone != "none"){
        $customer_field = "&customer_phone=".$customer_phone;
    }

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://nextpay.org/nx/gateway/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'api_key='.$api.'&amount='.$amount.'&order_id='.$order_id.$customer_field.'&callback_uri='.$callbackUrl,
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $response = json_decode($response);
    if($response->code == -1 && isset($response->trans_id)){
        $result['status'] = true;
        $result['order_id'] = $order_id;
        $result['trans_id'] = $response->trans_id;
    }else{
        $result['status'] = false;
        $result['error'] = $response->code;
    }
    return $result;
}

/*
    verifyNextPayTransaction

    In the return, We have status which when it is true, means that payment is successful and its done. Else, The
    result contains error that means we have to check error value which can be translated on this url:
        https://nextpay.org/nx/docs#step-7
    
    If status was true, we have card_holder which contains the customer's censored card number, Example:
        5022-29**-****-5020
*/
function verifyNextPayTransaction($api, $amount, $trans_id){

    $result = array(
        'status' => false,
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://nextpay.org/nx/gateway/verify',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'api_key='.$api.'&amount='.$amount.'&trans_id='.$trans_id,
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $response = json_decode($response);
    if($response->code == 0){
        $result['status'] = true;
        $result['card_holder'] = $response->card_holder;
    }else{
        $result['status'] = false;
        $result['error'] = $response->code;
    }

    return $result;
}