<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bahtText.php';

$cycle_code = $_GET['cycle'] ?? '8-2567';

$cycleStmt = $pdo->prepare("SELECT * FROM billing_cycles WHERE cycle_code = ?");
$cycleStmt->execute([$cycle_code]);
$cycle = $cycleStmt->fetch();

if (!$cycle) send_json(['error' => 'ไม่พบงวดประจำเดือน'], 404);

// ดึงยอดเก็บได้จริงจากตาราง meter_readings
$paidStmt = $pdo->prepare("
    SELECT 
        SUM(water_charge + maintenance_fee) as current_collected,
        SUM(previous_arrears) as arrears_collected
    FROM meter_readings 
    WHERE billing_cycle_id = ? AND payment_status = 'PAID'
");
$paidStmt->execute([(int)$cycle['id']]);
$paid = $paidStmt->fetch();

$revWater = $paid && $paid['current_collected'] ? (float)$paid['current_collected'] : 24500.00;
$revArrears = $paid && $paid['arrears_collected'] ? (float)$paid['arrears_collected'] : 3200.00;
$revNewMeter = 1500.00;
$revInterest = 125.50;
$revOther = 0.00;

$totalRevenue = round($revWater + $revArrears + $revNewMeter + $revInterest + $revOther, 2);

$collectedWaterTotal = $revWater + $revArrears;
$expCollector = round($collectedWaterTotal * 0.10, 2);
$expCaretaker = 3000.00;
$expCommittee = 2500.00;
$expElectricity = 6840.00;
$expSuppliesRepairs = 1450.00;
$expOther = 300.00;

$totalExpense = round($expCaretaker + $expCommittee + $expCollector + $expElectricity + $expSuppliesRepairs + $expOther, 2);

$netProfitLoss = round($totalRevenue - $totalExpense, 2);
$prevAccumulatedBalance = 145200.00;
$totalAccumulatedBalance = round($prevAccumulatedBalance + $netProfitLoss, 2);

$cashInHand = min(5000.00, max(0.00, $totalAccumulatedBalance));
$bankDeposit = round($totalAccumulatedBalance - $cashInHand, 2);

$report = [
    'billingCycle' => ['cycleCode' => $cycle_code],
    'revenues' => [
        'revWaterMaintenance' => $revWater,
        'revCollectedArrears' => $revArrears,
        'revNewMeterFee' => $revNewMeter,
        'revBankInterest' => $revInterest,
        'revOther' => $revOther,
        'totalRevenue' => $totalRevenue
    ],
    'expenses' => [
        'expCaretaker' => $expCaretaker,
        'expCommittee' => $expCommittee,
        'expCollector' => $expCollector,
        'collectorBasis' => "คิด 10% จากยอดจัดเก็บ " . number_format($collectedWaterTotal, 2) . " บาท",
        'expElectricity' => $expElectricity,
        'expSuppliesRepairs' => $expSuppliesRepairs,
        'expOther' => $expOther,
        'totalExpense' => $totalExpense
    ],
    'summary' => [
        'netProfitLoss' => $netProfitLoss,
        'isProfit' => $netProfitLoss >= 0,
        'prevAccumulatedBalance' => $prevAccumulatedBalance,
        'totalAccumulatedBalance' => $totalAccumulatedBalance,
        'cashInHand' => $cashInHand,
        'bankDeposit' => $bankDeposit,
        'totalAccumulatedTextTh' => baht_text($totalAccumulatedBalance)
    ],
    'operational' => [
        'waterProduction' => 4050.0,
        'waterDistributed' => 3490.0,
        'waterLoss' => 560.0,
        'nrwPct' => 13.83,
        'collectionEfficiency' => 92.5,
        'unitCost' => 4.83,
        'unitPrice' => 7.00
    ],
    'trends' => [
        'months' => ['มี.ค. 67', 'เม.ย. 67', 'พ.ค. 67', 'มิ.ย. 67', 'ก.ค. 67', 'ส.ค. 67'],
        'production' => [4200, 4850, 4600, 4300, 4150, 4050],
        'consumption' => [3620, 4100, 3950, 3720, 3580, 3490],
        'nrw' => [13.8, 15.5, 14.1, 13.5, 13.7, 13.8],
        'revenue' => [27500, 31200, 29800, 28400, 27900, $totalRevenue],
        'expense' => [15900, 17200, 16800, 16400, 16200, $totalExpense],
        'fundBalance' => [118500, 132500, 145500, 157500, 169200, $totalAccumulatedBalance]
    ]
];

send_json($report);
