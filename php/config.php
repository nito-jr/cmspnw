<?php
require_once __DIR__ . '/db_config.php';

// Upload directory: php/ is one level inside cmsp/, uploads/ is at cmsp/uploads/
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PLATFORM_CHARGE', 4000);

// --- MeSomb Mobile Money credentials -----------------------------------
// Get these from your MeSomb dashboard: https://mesomb.hachther.com
// Requires: composer require hachther/mesomb
define('MESOMB_APPLICATION_KEY', getenv('MESOMB_APPLICATION_KEY') ?: '');
define('MESOMB_ACCESS_KEY',      getenv('MESOMB_ACCESS_KEY') ?: '');
define('MESOMB_SECRET_KEY',      getenv('MESOMB_SECRET_KEY') ?: '');

// Number members can send money to manually (MoMo/OM) if they prefer to pay
// first and upload proof afterwards. Shown on the member dashboard.
define('COUNCIL_MOMO_NUMBER',   getenv('COUNCIL_MOMO_NUMBER') ?: '');
define('COUNCIL_OM_NUMBER',     getenv('COUNCIL_OM_NUMBER') ?: '');
define('COUNCIL_ACCOUNT_NAME',  getenv('COUNCIL_ACCOUNT_NAME') ?: '');

function calculateFees($gradYear, $currentYear) {
    $diff = $currentYear - $gradYear;
    if ($diff == 0)                    $appFee = 10000;
    elseif ($diff >= 2 && $diff < 5)  $appFee = 20000;
    elseif ($diff >= 5)               $appFee = 50000;
    else                              $appFee = 10000;

    if ($currentYear == $gradYear) {
        $dues = 0;
    } else {
        $startArrears    = $gradYear + 1;
        $endArrears      = $currentYear - 1;
        $numArrearsYears = ($endArrears >= $startArrears) ? ($endArrears - $startArrears) + 1 : 0;
        $dues = ($numArrearsYears * 10000) + 6000;
    }

    return [
        'application'     => $appFee,
        'dues'            => $dues,
        'platform_charge' => PLATFORM_CHARGE,
    ];
}
?>
