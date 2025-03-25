<?php
    include 'header.php';
    include 'data.php';
    include 'logic.php';

    // processPayments
    $response = processData($arData);

    // echo "<pre>";
    // print_r($response);
    // echo "</pre>";

    // Check for Errors
    if ($response['errorMessage']) {
        if (is_array($response['errorMessage'])) { 
        foreach ($response['errorMessage'] as $msg) {
                echo "<div class='alert alert-danger'>$msg</div>";
            }
        }
        else {
            echo "<div class='alert alert-danger'>{$response['errorMessage']}</div>";
        }
    } 
    else 
    {
        renderTable("Общо платени суми по дата","Дата",$response['totalPaymentsByDate']);
        renderTable("Общо дължими суми по дата","Дата",$response['totalDuesByDate'], true);
        renderTable("Изплатени суми за всеки гост","Гост",$response['guestPayments']);
        renderTable("Дължими суми за всеки гост","Гост",$response['guestDues']);
        
    }

    function renderTable($title,$col1, $data, $highlightPast = false) {
        echo "<h2 class='mt-4'>" . htmlspecialchars($title) . "</h2>";
        echo "<table class='table table-bordered'>";
        echo "<thead class='table-light'><tr><th>$col1</th><th>Сума (EUR)</th></tr></thead><tbody>";
        foreach ($data as $date => $amount) {
            $class = ($highlightPast && $date < date('Y-m-d')) ? "class='text-danger'" : "";
            echo "<tr><td>$date</td><td $class>" . number_format($amount, 2) . "</td></tr>";
        }
        echo "</tbody></table>";
    }

    include 'footer.php';
?>