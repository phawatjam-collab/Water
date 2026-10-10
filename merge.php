<?php
// script to merge portal_citizen.php into index.php
$index = file_get_contents('d:/Games/xamppp/htdocs/plumber/index.php');
$portal = file_get_contents('d:/Games/xamppp/htdocs/plumber/portal_citizen.php');

// 1. Extract CSS from portal_citizen
preg_match('/\.bill-result-card\s*\{.*?\}\s*\}\s*<\/style>/s', $portal, $cssMatch);
if ($cssMatch) {
    // Replace </style> in index.php with the extracted CSS + </style>
    $extractedCss = "\n    " . $cssMatch[0]; // from .bill-result-card down to </style>
    $index = str_replace('</style>', $extractedCss, $index);
}

// 2. Replace Hero Search Widget and add Bill Result Container
$heroSearchReplacement = <<<'HTML'
        <!-- Hero Water Bill Search Widget -->
        <div class="hero-search-box">
          <div class="hero-search-bar" style="margin-bottom: 10px;">
            <div class="search-input-wrapper" style="flex: 1; position: relative;">
              <input type="text" id="citizen-search-input" class="hero-search-input" inputmode="search" autocomplete="off" placeholder="พิมพ์เบอร์โทรศัพท์ (เช่น 081-234-5678) หรือรหัสผู้ใช้น้ำ...">
              <div id="citizen-search-dropdown" class="search-autocomplete-dropdown" style="display: none;"></div>
            </div>
            <button type="button" id="btn-search-bill" class="hero-search-btn">
              <span>🔎</span> ค้นหาบิล
            </button>
          </div>
          <div class="quick-examples" style="color: rgba(255,255,255,0.9); font-size: 13.5px; display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap;">
            <span>💡 ตัวอย่างค้นหา:</span>
            <button type="button" class="quick-chip" onclick="setSearchDemo('0812345678')" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 9999px; cursor: pointer; transition: all 0.15s ease;">📱 081-234-5678</button>
            <button type="button" class="quick-chip" onclick="setSearchDemo('WY-001')" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 9999px; cursor: pointer; transition: all 0.15s ease;">🏷️ WY-001</button>
          </div>
        </div>
      </section>

      <!-- Search Result Area -->
      <div id="bill-result-container" style="display: none; max-width: 1000px; margin: 0 auto 30px auto;">
        <!-- Dynamically populated bill details -->
      </div>
HTML;

$index = preg_replace('/<!-- Hero Water Bill Search Widget -->.*?<\/section>/s', $heroSearchReplacement, $index);

// 3. Extract Citizen Services and Service Request Modal from portal_citizen
preg_match('/<!-- Additional Public Citizen Services \(Service Grid\) -->(.*?)<\/main>/s', $portal, $servicesMatch);
if ($servicesMatch) {
    // Insert just before Footer in index.php
    $servicesHtml = trim($servicesMatch[0]);
    // Remove </main> from the end of it
    $servicesHtml = str_replace('</main>', '', $servicesHtml);
    
    // Also grab modal
    preg_match('/<!-- Service Request Modal -->(.*?)<!-- Scripts -->/s', $portal, $modalMatch);
    $modalHtml = $modalMatch ? $modalMatch[1] : '';
    
    // index.php footer starts with <!-- Footer Contact & Committee -->
    $index = str_replace('<!-- Footer Contact & Committee -->', $servicesHtml . "\n" . '<!-- Footer Contact & Committee -->', $index);
    
    // Insert modal before </body>
    $index = str_replace('</body>', $modalHtml . "\n</body>", $index);
}

// 4. Replace Scripts
preg_match('/<script>\s*const currentCycleCode.*?<\/script>/s', $portal, $scriptMatch);
if ($scriptMatch) {
    // Replace <script>...</script> at the bottom of index.php
    $scriptContent = $scriptMatch[0];
    
    // We need to keep the auto-open login modal check from index.php
    $loginCheck = <<<'JS'
    // Check URL parameters to auto-open login modal if needed
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('login_required') || urlParams.has('open_login') || urlParams.has('switch_role')) {
      if (typeof openLoginModal === 'function') openLoginModal();
    }
JS;

    $scriptContent = str_replace('<script>', "<script>\n" . $loginCheck . "\n", $scriptContent);
    
    $index = preg_replace('/<script>\s*\/\/ Check URL parameters.*?<\/script>/s', $scriptContent, $index);
}

file_put_contents('d:/Games/xamppp/htdocs/plumber/index.php', $index);
echo "Merged successfully.\n";
