<?php
if (!defined("WHMCS")) die("Access Denied");

use Illuminate\Database\Capsule\Manager as Capsule;

function qnbefatura_config() {
    return [
        "name"        => "QNB e-Invoice & e-Archive",
        "description" => "QNB Finansbank Integration",
        "author"      => "Ens",
        "version"     => "1",
        "fields"      => [
            "username" => ["FriendlyName" => "API Username", "Type" => "text", "Size" => "30"],
            "password" => ["FriendlyName" => "API Password", "Type" => "password", "Size" => "30"],
            "vkn"      => ["FriendlyName" => "Company Tax ID (VKN)", "Type" => "text", "Size" => "10"],
            "testmode" => ["FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick to use Sandbox (Test) environment"],
        ]
    ];
}

function qnbefatura_activate() {
    return ['status' => 'success', 'description' => 'QNB Addon activated.'];
}

function qnbefatura_output($vars) {
    $modulelink = $vars['modulelink'];

    if (isset($_POST['test_invoice_id']) && !empty($_POST['test_invoice_id'])) {
        $invoiceId = (int)$_POST['test_invoice_id'];
        
        echo '<div class="alert alert-info">Sending Invoice ID: ' . $invoiceId . ' to QNB... Check Module Logs for results.</div>';
        
        run_hook('InvoicePaid', ['invoiceid' => $invoiceId]);
    }

    echo "<h2>QNB Test Management</h2>";
    echo '<div class="well">
            <form method="post" action="' . $modulelink . '">
                <p>Enter an <b>Unpaid</b> or <b>Paid</b> Invoice ID to manually trigger the QNB process:</p>
                <input type="number" name="test_invoice_id" class="form-control" style="width:200px; display:inline-block;" placeholder="Invoice ID (e.g. 105)">
                <button type="submit" class="btn btn-primary">Send Test Invoice</button>
            </form>
          </div>';
    
    echo "<p><i class='fa fa-info-circle'></i> This button manually triggers the <b>InvoicePaid</b> hook for the specified ID.</p>";
}