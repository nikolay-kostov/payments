<?php
    
    function validateData($arData) {
        $errors = [];
        
        if (!isset($arData['Payments']) || !is_array($arData['Payments'])) {
            $errors[] = "Invalid or missing 'Payments' data.";
        }
        else{
            foreach ($arData['Payments'] as $payment) {
                if (!isset($payment['PostDate'], $payment['Amount'], $payment['Currency'])) {
                    $errors[] = "Each payment must include 'PostDate', 'Amount', and 'Currency'.";
                }
                if (!is_numeric($payment['Amount']) || $payment['Amount'] <= 0) {
                    $errors[] = "Payment amount must be a positive number.";
                }
            }
        }
        if (!isset($arData['GuestsDue']) || !is_array($arData['GuestsDue'])) {
            $errors[] = "Invalid or missing 'GuestsDue' data.";
        }
        else{
            foreach ($arData['GuestsDue'] as $guestIndex => $dues) {
                if (!is_array($dues)) {
                    $errors[] = "Dues for guest $guestIndex must be an array.";
                }
                foreach ($dues as $date => $amount) {
                    if (!is_numeric($amount) || $amount < 0) {
                        $errors[] = "Due amount for date $date must be a non-negative number.";
                    }
                }
            }
        }
        if (!isset($arData['Currency']) || !is_string($arData['Currency'])) {
            $errors[] = "Invalid or missing 'Currency' value.";
        }
        if (!isset($arData['Exchange']) || !is_numeric($arData['Exchange']) || $arData['Exchange'] <= 0) {
            $errors[] = "Invalid or missing 'Exchange' rate.";
        }

        // Return errors if have
        if (!empty($errors)) {
            return $errors;
        }
        
        return null;  // Return null if we don't have errors
    }

    // BGN to EUR
    function convertToEUR($amount, $currency, $exchangeRate) {
        return $currency === 'BGN' ? floatval($amount) / $exchangeRate : floatval($amount);
    }

    //Process Data
    function processData($arData){
        $totalPaymentsByDate = [];
        $guestPayments = [];
        $totalDuesByDate = [];
        $guestDues =[];
        $errorMessagе=null;

        //Data validation
        $validationError = validateData($arData);

        
        if ($validationError) {
            return [
                $validationError, 
                'totalPaymentsByDate' => [], 
                'guestPayments' => [], 
                'totalDuesByDate' => [], 
                'guestDues' => []
            ];
        }
        
        $processPaymentsResult = processPayments($arData);
        $processDuesResult = processDues($arData);

        return [
            'errorMessage' => $processPaymentsResult['errorMessage'], 
            'totalPaymentsByDate' => $processPaymentsResult['totalPaymentsByDate'],  
            'guestPayments' => $processPaymentsResult['guestPayments'],
            'totalDuesByDate' => $processDuesResult['totalDuesByDate'],
            'guestDues' => $processDuesResult['guestDues']
        ];

    }

    // Payments process
    function processPayments($arData) {
        $totalPaymentsByDate = [];
        $guestPayments = [];

        foreach ($arData['Payments'] as $payment) {
            $date = substr($payment['PostDate'], 0, 10);
            $amountEUR = convertToEUR($payment['Amount'], $payment['Currency'], $arData['Exchange']);
            
            // update totals by date
            if (!isset($totalPaymentsByDate[$date])) {
                $totalPaymentsByDate[$date] = 0;
            }
            $totalPaymentsByDate[$date] += $amountEUR;
    
            // If GuestIndex is NULL, we distribute the payment among all guests
            if ($payment['GuestIndex'] === NULL) {
                $numGuests = count($arData['GuestsDue']);
                if ($numGuests > 0) {
                    $share = $amountEUR / $numGuests;
                    foreach ($arData['GuestsDue'] as $guestIndex => $dues) {
                        if (!isset($guestPayments[$guestIndex])) {
                            $guestPayments[$guestIndex] = 0;
                        }
                        $guestPayments[$guestIndex] += $share;
                    }
                }
            } else {
                // Check if the GuestIndex is valid
                if (!isset($arData['GuestsDue'][$payment['GuestIndex']])) {
                    return ['errorMessage' => "Invalid GuestIndex {$payment['GuestIndex']} for payment.",'totalPaymentsByDate'=>[], 'guestPayments' =>[]];
                }
                if (!isset($guestPayments[$payment['GuestIndex']])) {
                    $guestPayments[$payment['GuestIndex']] = 0;
                }
                $guestPayments[$payment['GuestIndex']] += $amountEUR;
            }
        }
        return ['errorMessage'=>null, 'totalPaymentsByDate'=>$totalPaymentsByDate, 'guestPayments'=>$guestPayments];
    }

    // Dues process
    function processDues($arData) {
        $totalDuesByDate = [];
        $guestDues = [];
        
        foreach ($arData['GuestsDue'] as $guestIndex => $dues) {
            foreach ($dues as $date => $amount) {
                if (!isset($totalDuesByDate[$date])) {
                    $totalDuesByDate[$date] = 0;
                }
                $totalDuesByDate[$date] += $amount;
                
                if (!isset($guestDues[$guestIndex])) {
                    $guestDues[$guestIndex] = 0;
                }
                $guestDues[$guestIndex] += $amount;
            }
        }
        
        return ['totalDuesByDate'=>$totalDuesByDate, 'guestDues'=>$guestDues];
    }
