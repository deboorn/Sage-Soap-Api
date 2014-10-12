<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once 'sage/api.php';

$merchantId = '287183824687';
$merchantKey = 'R6C5S2S1Q7O8';

$customer = array(
    'C_NAME'            => 'John Doe',
    'C_ADDRESS'         => '123 Someplace Dr',
    'C_CITY'            => 'Myrtle Beach',
    'C_STATE'           => 'SC',
    'C_ZIP'             => '29579',
    'C_COUNTRY'         => 'USA',
    'C_EMAIL'           => 'test@example.com',
    'T_CUSTOMER_NUMBER' => rand(1001, 9999),
);

$sage = new \Sage\API($merchantId, $merchantKey);

$testCreditCard = function () use ($sage, $customer) {
    //example of vaulting credit card
    $vaultId = $sage->addCustomer(array(
        'CARDNUMBER'      => '4111111111111111',
        'EXPIRATION_DATE' => '1016',
    ), $sage::CREDIT);
    var_dump($vaultId);

    //charge credit card
    $response = $sage->chargeCustomer($vaultId, array_merge($customer, array(
        'T_AMT'      => rand(20, 90),
        'T_ORDERNUM' => rand(500, 600),
    )), $sage::CREDIT);
    var_dump($response);
};


$testAch = function () use ($sage, $customer) {
    //example of vaulting ach (check)
    $vaultId = $sage->addCustomer(array(
        'ROUTING_NUMBER' => '056008849',
        'ACCOUNT_NUMBER' => '12345678901234',
        'C_ACCT_TYPE'    => $sage::ACH_CHECKING,
    ), $sage::ACH);
    var_dump($vaultId);

//charge ach
    $achCustomer = array_merge($customer, array(
        'C_FIRST_NAME' => 'John',
        'C_LAST_NAME'  => 'Doe',
    ));
    unset($achCustomer['C_NAME']);

    $response = $sage->chargeCustomer($vaultId, array_merge($achCustomer, array(
        'T_AMT'      => rand(20, 90),
        'T_ORDERNUM' => rand(500, 600),
    )), $sage::ACH);
    var_dump($response);
};


// Uncomment below to test
$testCreditCard();
$testAch();



