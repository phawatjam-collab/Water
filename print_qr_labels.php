<?php
/**
 * หน้าพิมพ์สติกเกอร์ QR Code สำหรับติดมิเตอร์น้ำประจำบ้าน (แบบ ป.17)
 * สิทธิ์การใช้งาน: เจ้าหน้าที่ (staff) และ ผู้ดูแลระบบ (admin)
 */
require_once __DIR__ . '/auth.php';
requireRole(['staff', 'admin']);
require_once __DIR__ . '/api/db.php';

// ดึงรายชื่อลูกบ้านทั้งหมด
$stmt = $pdo->query("SELECT * FROM customers ORDER BY seq_no ASC");
$customers = $stmt->fetchAll();

// ดึงรายชื่อโซน/คุ้มทั้งหมด
$zones = array_unique(array_filter(array_column($customers, 'zone')));
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>พิมพ์สติกเกอร์ QR Code ติดมิเตอร์น้ำ - การประปาหมู่บ้านวังยาง</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <script src="assets/qrcode.min.js"></script>
  <style>
    body {
      background: #f1f5f9;
      font-family: 'Sarabun', 'Prompt', sans-serif;
    }
    .print-control-bar {
      background: #0f172a;
      color: #fff;
      padding: 14px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .labels-container {
      max-width: 1100px;
      margin: 24px auto;
      padding: 0 16px;
    }
    .label-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 16px;
    }
    .qr-card {
      background: #fff;
      border: 2px dashed #94a3b8;
      border-radius: 10px;
      padding: 16px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.04);
      position: relative;
      break-inside: avoid;
      page-break-inside: avoid;
    }
    .qr-card-header {
      width: 100%;
      border-bottom: 2px solid #0284c7;
      padding-bottom: 8px;
      margin-bottom: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .qr-badge {
      background: #0284c7;
      color: #fff;
      font-size: 11.5px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 4px;
    }
    .qr-holder {
      margin: 10px auto;
      background: #fff;
      padding: 8px;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      display: inline-block;
    }
    .qr-code-text {
      font-family: monospace;
      font-size: 22px;
      font-weight: 800;
      color: #0369a1;
      letter-spacing: 1px;
      margin: 4px 0;
    }
    .customer-info-box {
      width: 100%;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 8px 10px;
      margin-top: 8px;
      font-size: 13.5px;
      text-align: left;
    }

    @media print {
      body {
        background: #fff !important;
      }
      .no-print {
        display: none !important;
      }
      .labels-container {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
      }
      .label-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 12px !important;
      }
      .qr-card {
        border: 1.5px solid #333 !important;
        box-shadow: none !important;
        padding: 10px !important;
      }
    }
  </style>
</head>
<body>

  <!-- Top Controller Bar (Hidden in Print) -->
  <div class="print-control-bar no-print">
    <div style="display: flex; align-items: center; gap: 12px;">
      <a href="meter_reading.php" style="color: #94a3b8; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
        &larr; กลับหน้าสมุดจดมิเตอร์
      </a>
      <span style="color: #475569;">|</span>
      <h2 style="font-size: 17px; margin: 0; font-family: 'Prompt', sans-serif; color: #38bdf8;">
        🏷️ แบบพิมพ์สติกเกอร์ QR Code ติดมิเตอร์น้ำ (สำหรับพนักงานจดภาคสนาม)
      </h2>
    </div>

    <div style="display: flex; gap: 12px; align-items: center;">
      <select id="filter-zone" class="form-select" style="padding: 7px 12px; font-size: 13.5px; border-radius: 6px;" onchange="filterCards()">
        <option value="">-- แสดงทุกลูกบ้าน / ทุกโซน (<?php echo count($customers); ?> หลัง) --</option>
        <?php foreach ($zones as $z): ?>
          <option value="<?php echo htmlspecialchars($z); ?>"><?php echo htmlspecialchars($z); ?></option>
        <?php endforeach; ?>
      </select>

      <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight: 600; padding: 8px 18px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
        🖨️ สั่งพิมพ์สติกเกอร์ (Print A4)
      </button>
    </div>
  </div>

  <div class="labels-container">
    <div class="label-grid" id="label-grid">
      <?php foreach ($customers as $c): ?>
        <div class="qr-card" data-zone="<?php echo htmlspecialchars($c['zone']); ?>">
          <div class="qr-card-header">
            <span style="font-size: 12px; font-weight: 700; color: #0f172a;">💧 การประปาหมู่บ้านวังยาง</span>
            <span class="qr-badge">ลำดับ #<?php echo $c['seq_no']; ?></span>
          </div>

          <div class="qr-holder" id="qr-<?php echo htmlspecialchars($c['customer_code']); ?>"></div>
          <div class="qr-code-text"><?php echo htmlspecialchars($c['customer_code']); ?></div>

          <div class="customer-info-box">
            <div style="display: flex; justify-content: space-between;">
              <strong>บ้านเลขที่: <?php echo htmlspecialchars($c['house_no']); ?></strong>
              <span style="color: #64748b; font-size: 12px;"><?php echo htmlspecialchars($c['zone']); ?></span>
            </div>
            <div style="margin-top: 2px;">
              ชื่อ: <strong><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></strong>
            </div>
            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
              เลขมิเตอร์: <code style="color: #0369a1; font-weight: 700;"><?php echo htmlspecialchars($c['meter_serial'] ?? '-'); ?></code>
            </div>
          </div>
          
          <div style="font-size: 10.5px; color: #64748b; margin-top: 8px;">
            * ใช้สแกนด้วยแอปประปาวังยางเพื่อจดมิเตอร์ทันที
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    // สร้าง QR Code สำหรับแต่ละลูกบ้าน
    const customers = <?php echo json_encode(array_map(function($c) {
      return [
        'code' => $c['customer_code'],
        'house' => $c['house_no']
      ];
    }, $customers)); ?>;

    document.addEventListener('DOMContentLoaded', () => {
      customers.forEach(c => {
        const el = document.getElementById('qr-' + c.code);
        if (el) {
          new QRCode(el, {
            text: c.code,
            width: 120,
            height: 120,
            colorDark: '#0f172a',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
          });
        }
      });
    });

    function filterCards() {
      const zone = document.getElementById('filter-zone').value;
      const cards = document.querySelectorAll('.qr-card');
      cards.forEach(card => {
        if (!zone || card.dataset.zone === zone) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
