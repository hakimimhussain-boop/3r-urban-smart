<?php
/**
 * Template Name: Admin — Alert Thresholds
 */
defined( 'ABSPATH' ) || exit;
ThreeR_Admin_Guard::require_admin();

$admin_page_title = 'Alert Thresholds';
$saved = false;

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tru_admin_nonce']) ) {
    ThreeR_Admin_Guard::verify_nonce( $_POST['tru_admin_nonce'], 'tru_admin_thresholds' );
    ThreeR_Admin_Data::save_thresholds( $_POST );
    $saved = true;
}

$t = ThreeR_Admin_Data::get_thresholds();
$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'admin-header.php';
?>

<?php if ($saved) : ?>
  <div class="tru-admin-flash tru-flash-ok">✅ Thresholds saved. New alerts will use these values.</div>
<?php endif; ?>

<form method="POST" class="tru-admin-thresholds-form">
  <?php wp_nonce_field('tru_admin_thresholds','tru_admin_nonce'); ?>

  <!-- TEMPERATURE -->
  <div class="tru-threshold-section">
    <div class="tru-threshold-section-header">
      <div class="tru-threshold-icon">🌡️</div>
      <div>
        <div class="tru-threshold-section-title">Temperature</div>
        <div class="tru-threshold-section-sub">Compost core temperature from PLC sensor (°C)</div>
      </div>
    </div>
    <div class="tru-threshold-grid">
      <div class="tru-threshold-card tru-tc-warning">
        <div class="tru-tc-label">Warning threshold</div>
        <div class="tru-tc-desc">Alert triggered when temp exceeds this value</div>
        <div class="tru-tc-input-wrap">
          <input type="range" name="temp_warning" min="50" max="90" step="1"
            value="<?php echo (int)$t['temp_warning']; ?>"
            class="tru-range" id="tempWarnRange"
            oninput="document.getElementById('tempWarnVal').value=this.value"/>
          <div class="tru-tc-number-wrap">
            <input type="number" id="tempWarnVal" min="50" max="90"
              value="<?php echo (int)$t['temp_warning']; ?>"
              class="tru-tc-number"
              oninput="document.getElementById('tempWarnRange').value=this.value"/>
            <span class="tru-tc-unit">°C</span>
          </div>
        </div>
      </div>
      <div class="tru-threshold-card tru-tc-critical">
        <div class="tru-tc-label">Critical threshold</div>
        <div class="tru-tc-desc">Escalated alert — immediate attention required</div>
        <div class="tru-tc-input-wrap">
          <input type="range" name="temp_critical" min="60" max="120" step="1"
            value="<?php echo (int)$t['temp_critical']; ?>"
            class="tru-range" id="tempCritRange"
            oninput="document.getElementById('tempCritVal').value=this.value"/>
          <div class="tru-tc-number-wrap">
            <input type="number" id="tempCritVal" min="60" max="120"
              value="<?php echo (int)$t['temp_critical']; ?>"
              class="tru-tc-number"
              oninput="document.getElementById('tempCritRange').value=this.value"/>
            <span class="tru-tc-unit">°C</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- WEIGHT / LOAD -->
  <div class="tru-threshold-section">
    <div class="tru-threshold-section-header">
      <div class="tru-threshold-icon">⚖️</div>
      <div>
        <div class="tru-threshold-section-title">Load / Weight</div>
        <div class="tru-threshold-section-sub">Compost mass measured by load cell (kg)</div>
      </div>
    </div>
    <div class="tru-threshold-grid">
      <div class="tru-threshold-card tru-tc-warning">
        <div class="tru-tc-label">Minimum load warning</div>
        <div class="tru-tc-desc">Alert if load drops below this — may indicate empty or sensor fault</div>
        <div class="tru-tc-input-wrap">
          <input type="range" name="weight_min" min="0" max="200" step="5"
            value="<?php echo (int)$t['weight_min']; ?>"
            class="tru-range" id="weightMinRange"
            oninput="document.getElementById('weightMinVal').value=this.value"/>
          <div class="tru-tc-number-wrap">
            <input type="number" id="weightMinVal" min="0" max="200"
              value="<?php echo (int)$t['weight_min']; ?>"
              class="tru-tc-number"
              oninput="document.getElementById('weightMinRange').value=this.value"/>
            <span class="tru-tc-unit">kg</span>
          </div>
        </div>
      </div>
      <div class="tru-threshold-card tru-tc-critical">
        <div class="tru-tc-label">Maximum load critical</div>
        <div class="tru-tc-desc">Alert if load exceeds this — overflow risk</div>
        <div class="tru-tc-input-wrap">
          <input type="range" name="weight_max" min="400" max="1200" step="10"
            value="<?php echo (int)$t['weight_max']; ?>"
            class="tru-range" id="weightMaxRange"
            oninput="document.getElementById('weightMaxVal').value=this.value"/>
          <div class="tru-tc-number-wrap">
            <input type="number" id="weightMaxVal" min="400" max="1200"
              value="<?php echo (int)$t['weight_max']; ?>"
              class="tru-tc-number"
              oninput="document.getElementById('weightMaxRange').value=this.value"/>
            <span class="tru-tc-unit">kg</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MOTOR -->
  <div class="tru-threshold-section">
    <div class="tru-threshold-section-header">
      <div class="tru-threshold-icon">⚙️</div>
      <div>
        <div class="tru-threshold-section-title">Motor Off Duration</div>
        <div class="tru-threshold-section-sub">Alert if motor has been off for longer than threshold (minutes)</div>
      </div>
    </div>
    <div class="tru-threshold-grid">
      <div class="tru-threshold-card tru-tc-warning">
        <div class="tru-tc-label">Warning</div>
        <div class="tru-tc-desc">Motor off for longer than this → warning alert</div>
        <div class="tru-tc-input-wrap">
          <input type="range" name="motor_off_warn" min="10" max="120" step="5"
            value="<?php echo (int)$t['motor_off_warn']; ?>"
            class="tru-range" id="motorWarnRange"
            oninput="document.getElementById('motorWarnVal').value=this.value"/>
          <div class="tru-tc-number-wrap">
            <input type="number" id="motorWarnVal" min="10" max="120"
              value="<?php echo (int)$t['motor_off_warn']; ?>"
              class="tru-tc-number"
              oninput="document.getElementById('motorWarnRange').value=this.value"/>
            <span class="tru-tc-unit">min</span>
          </div>
        </div>
      </div>
      <div class="tru-threshold-card tru-tc-critical">
        <div class="tru-tc-label">Critical</div>
        <div class="tru-tc-desc">Motor off for longer than this → critical alert + email</div>
        <div class="tru-tc-input-wrap">
          <input type="range" name="motor_off_crit" min="30" max="480" step="10"
            value="<?php echo (int)$t['motor_off_crit']; ?>"
            class="tru-range" id="motorCritRange"
            oninput="document.getElementById('motorCritVal').value=this.value"/>
          <div class="tru-tc-number-wrap">
            <input type="number" id="motorCritVal" min="30" max="480"
              value="<?php echo (int)$t['motor_off_crit']; ?>"
              class="tru-tc-number"
              oninput="document.getElementById('motorCritRange').value=this.value"/>
            <span class="tru-tc-unit">min</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- OFFLINE -->
  <div class="tru-threshold-section">
    <div class="tru-threshold-section-header">
      <div class="tru-threshold-icon">📶</div>
      <div>
        <div class="tru-threshold-section-title">Machine Offline</div>
        <div class="tru-threshold-section-sub">Alert if no MQTT data received within threshold (minutes)</div>
      </div>
    </div>
    <div class="tru-threshold-grid">
      <div class="tru-threshold-card tru-tc-warning">
        <div class="tru-tc-label">Warning</div>
        <div class="tru-tc-desc">No data received for longer than this → warning</div>
        <div class="tru-tc-input-wrap">
          <input type="range" name="offline_warn" min="15" max="120" step="5"
            value="<?php echo (int)$t['offline_warn']; ?>"
            class="tru-range" id="offlineWarnRange"
            oninput="document.getElementById('offlineWarnVal').value=this.value"/>
          <div class="tru-tc-number-wrap">
            <input type="number" id="offlineWarnVal" min="15" max="120"
              value="<?php echo (int)$t['offline_warn']; ?>"
              class="tru-tc-number"
              oninput="document.getElementById('offlineWarnRange').value=this.value"/>
            <span class="tru-tc-unit">min</span>
          </div>
        </div>
      </div>
      <div class="tru-threshold-card tru-tc-critical">
        <div class="tru-tc-label">Critical</div>
        <div class="tru-tc-desc">No data for longer than this → critical + email</div>
        <div class="tru-tc-input-wrap">
          <input type="range" name="offline_crit" min="60" max="480" step="10"
            value="<?php echo (int)$t['offline_crit']; ?>"
            class="tru-range" id="offlineCritRange"
            oninput="document.getElementById('offlineCritVal').value=this.value"/>
          <div class="tru-tc-number-wrap">
            <input type="number" id="offlineCritVal" min="60" max="480"
              value="<?php echo (int)$t['offline_crit']; ?>"
              class="tru-tc-number"
              oninput="document.getElementById('offlineCritRange').value=this.value"/>
            <span class="tru-tc-unit">min</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="tru-thresholds-footer">
    <button type="submit" class="tru-admin-btn-primary tru-btn-lg">💾 Save Thresholds</button>
    <span class="tru-muted" style="font-size:13px">Changes apply to all new alerts from the Node.js middleware.</span>
  </div>

</form>

<?php include $partials . 'admin-footer.php'; ?>
