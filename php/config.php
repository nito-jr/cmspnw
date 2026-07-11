<?php
$host = '';
$db   = '';
$user = '';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['status'=>'error','message'=>'DB Connection failed: ' . $conn->connect_error]));
}

// Upload directory: php/ is one level inside cmsp/, uploads/ is at cmsp/uploads/
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PLATFORM_CHARGE', 4000);

// --- MeSomb Mobile Money credentials -----------------------------------
// Get these from your MeSomb dashboard: https://mesomb.hachther.com
// Requires: composer require hachther/mesomb
define('MESOMB_APPLICATION_KEY', 'b27a5d2cf46868eef219b5ab571b7b1075501b6b');
define('MESOMB_ACCESS_KEY',      'sk_live_LsJ6fsXEL_XbgUThwmY2PfluitW7JguFYxrtJJl0qXI');
define('MESOMB_SECRET_KEY',      '47370f7f-4d16-478d-b824-b1048cbfec76');

// Number members can send money to manually (MoMo/OM) if they prefer to pay
// first and upload proof afterwards. Shown on the member dashboard.
define('COUNCIL_MOMO_NUMBER',   '+237 6XX XXX XXX (MTN Mobile Money)');
define('COUNCIL_OM_NUMBER',     '+237 6XX XXX XXX (Orange Money)');
define('COUNCIL_ACCOUNT_NAME',  'CMSP - Council for Medico Sanitary Professionals');

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
