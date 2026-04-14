<?php
if (!defined("WHMCS")) die("Access Denied");

require_once __DIR__ . '/../../vendor/autoload.php';

use EFINANS\Component\data;
use EFINANS\Config\config;
use EFINANS\Libraries\efatura;
use EFINANS\Libraries\earsiv;
use Illuminate\Database\Capsule\Manager as Capsule;

add_hook('InvoicePaid', 1, function($vars) {
    $invoiceId = $vars['invoiceid'];

    // --- Dynamic Configuration from Database ---
    $settings = Capsule::table('tbladdonmodules')->where('module', 'qnbefatura')->pluck('value', 'setting');
    
    $qnb_username = $settings['username'];
    $qnb_password = $settings['password'];
    $qnb_vkn      = $settings['vkn'];
    $test_mode    = ($settings['testmode'] == 'on'); // Check if Test Mode is checked

    // Live vs Test URLs
    $qnb_url = $test_mode 
        ? "https://earsivtest.efinans.com.tr/earsiv/ws/EarsivWebService?wsdl" 
        : "https://earsiv.efinans.com.tr/earsiv/ws/EarsivWebService?wsdl";

    $custom_field_tax_id_name = 'TKCN/VKN';
    $custom_field_tax_office_name = 'Vergi Dairesi';
    
    // Check if already sent (Avoid duplicate invoices)
    $invoiceData = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
    if ($invoiceData['result'] != 'success' || strpos($invoiceData['notes'], 'QNB UUID:') !== false) {
        return;
    }

    $clientData = localAPI('GetClientsDetails', ['clientid' => $invoiceData['userid'], 'stats' => true]);
    $tax_id_value = '11111111111';
    $tax_office_value = '';

    if (isset($clientData['customfields'])) {
        foreach ($clientData['customfields'] as $field) {
            if ($field['name'] == $custom_field_tax_id_name && !empty($field['value'])) {
                $tax_id_value = preg_replace('/[^0-9]/', '', $field['value']); 
            }
            if ($field['name'] == $custom_field_tax_office_name && !empty($field['value'])) {
                $tax_office_value = $field['value'];
            }
        }
    }

    $qnbConfigObj = new config();
    $options = $qnbConfigObj->setUsername($qnb_username)
                      ->setpassword($qnb_password)
                      ->setvergiTcKimlikNo($qnb_vkn)
                      ->setUrl($qnb_url)
                      ->getConfig();

    $efat = new efatura();
    $isEInvoiceUser = false;
    
    if (strlen($tax_id_value) >= 10 && $tax_id_value != '11111111111') {
        try {
            $isEInvoiceUser = $efat->setConfig($options)->setStart()->getEfaturaKullanicisi($tax_id_value);
        } catch (Exception $e) {
            logActivity("QNB Lookup Error: " . $e->getMessage());
        }
    }

    $qnbDataObj = new data();
    $uuid = $qnbDataObj->getUuid();
    
    $qnbDataObj->setStartData([
        "ID" => "",
        "ProfileID" => $isEInvoiceUser ? "TEMELFATURA" : "EARSIVFATURA",
        "UUID" => $uuid,
        "IssueDate" => date('Y-m-d'),
        "IssueTime" => date('H:i:s'),
    ]);

    if (!$isEInvoiceUser) {
        $qnbDataObj->setAddNote(["ID" => 1, "Value" => "Delivery Method: ELECTRONIC"]);
    }

    $companyName = !empty($clientData['companyname']) ? $clientData['companyname'] : ($clientData['firstname'] . ' ' . $clientData['lastname']);
    
    $qnbDataObj->setSupplierCustomerParty('Customer', [
        "Party" => [
            "PartyIdentificationID" => $tax_id_value,
            "PartyName" => $companyName,
            "Telephone" => $clientData['phonenumber'],
            "ElectronicMail" => $clientData['email'],
            "PartyTaxSchemeName" => $tax_office_value, 
        ],
        "PostalAddress" => [
            "StreetName" => trim($clientData['address1'] . ' ' . $clientData['address2']),
            "CityName" => $clientData['city'],
            "CountryName" => "Türkiye",
        ],
    ]);

    if (strlen($tax_id_value) == 11) {
        $qnbDataObj->setPerson('Customer', [
            "FirstName" => $clientData['firstname'],
            "FamilyName" => $clientData['lastname'],
        ]);
    }

    if (isset($invoiceData['items']['item'])) {
        foreach ($invoiceData['items']['item'] as $index => $item) {
            $lineAmount = (float)$item['amount'];
            $taxRate = ($item['taxed'] == 1) ? (float)$invoiceData['taxrate'] : 0;
            $taxAmount = $lineAmount * ($taxRate / 100);

            $qnbDataObj->setInvoiceLine([
                "ID" => (string)($index + 1),
                "InvoicedQuantity" => "1",
                "LineExtensionAmount" => number_format($lineAmount, 2, '.', ''),
                "ItemName" => $item['description'],
                "PriceAmount" => number_format($lineAmount, 2, '.', ''),
                "TaxSubtotal" => [
                    [
                        "TaxableAmount" => number_format($lineAmount, 2, '.', ''),
                        "TaxAmount" => number_format($taxAmount, 2, '.', ''),
                        "Percent" => number_format($taxRate, 0, '.', ''),
                        "TaxSchemeName" => "KDV",
                        "TaxSchemeTaxTypeCode" => "0015",
                    ]
                ]
            ]);
        }
    }

    $finalData = $qnbDataObj->setTotals()->getData();

    try {
        if ($isEInvoiceUser) {
            $response = $efat->setData($finalData)->setEFatura();
            logActivity("QNB E-Invoice Sent (OID: " . ($response->belgeOid ?? 'N/A') . ")");
        } else {
            $earsiv = new earsiv();
            $response = $earsiv->setConfig($options)->setStart()->setSube("DFLT")->setKasa("DFLT")->setData($finalData)->setEArsiv();
            logActivity("QNB E-Archive Sent.");
        }
        
        localAPI('UpdateInvoice', [
            'invoiceid' => $invoiceId,
            'notes' => $invoiceData['notes'] . "\nQNB UUID: " . $uuid
        ]);

    } catch (Exception $e) {
        logActivity("QNB Critical Error (Invoice {$invoiceId}): " . $e->getMessage());
    }
});