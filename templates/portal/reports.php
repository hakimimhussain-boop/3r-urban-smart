<?php
/**
 * Template Name: Portal — Reports
 */
defined( 'ABSPATH' ) || exit;
ThreeR_Portal_Auth::require_login();

$page_title  = 'Reports';
$customer_id = ThreeR_Portal_Auth::current_customer_id();
$reports     = ThreeR_Portal_Data::get_reports( $customer_id );
$machines    = ThreeR_Portal_Data::get_machines( $customer_id );

$partials = plugin_dir_path( __FILE__ ) . '../partials/';
include $partials . 'portal-header.php';
?>

<div class="tru-reports-intro">
  <p>Reports are generated automatically. Monthly summaries are ready on the 1st of each month. Weekly digests every Monday.</p>
</div>

<!-- Report type overview -->
<div class="tru-report-types">
  <div class="tru-report-type-card">
    <div class="tru-rt-icon">📊</div>
    <div class="tru-rt-title">Monthly Summary</div>
    <div class="tru-rt-desc">Full fleet performance, alert history, uptime, and maintenance flags. Auto-generated on the 1st.</div>
    <div class="tru-rt-badge tru-badge-online">Auto</div>
  </div>
  <div class="tru-report-type-card">
    <div class="tru-rt-icon">📋</div>
    <div class="tru-rt-title">Weekly Digest</div>
    <div class="tru-rt-desc">7-day overview of sensor trends, alerts triggered, and machine health scores.</div>
    <div class="tru-rt-badge tru-badge-online">Auto</div>
  </div>
  <div class="tru-report-type-card">
    <div class="tru-rt-icon">📥</div>
    <div class="tru-rt-title">On-Demand Export</div>
    <div class="tru-rt-desc">Select a machine and date range to generate a custom CSV or PDF export instantly.</div>
    <div class="tru-rt-badge tru-badge-warning">Coming Soon</div>
  </div>
</div>

<!-- Report list -->
<div class="tru-portal-section-header" style="margin-top:40px">
  <h2 class="tru-portal-section-title">Available Reports</h2>
  <div class="tru-portal-section-meta"><?php echo count($reports); ?> reports</div>
</div>

<?php if ( $reports ) : ?>
<div class="tru-reports-list">
  <?php foreach ( $reports as $report ) :
    $has_file = ! empty( $report->file_path ) && $report->file_path !== '#';
  ?>
  <div class="tru-report-row">
    <div class="tru-report-icon">
      <?php echo $report->report_type === 'Weekly Digest' ? '📋' : '📊'; ?>
    </div>
    <div class="tru-report-info">
      <div class="tru-report-title"><?php echo esc_html($report->report_type); ?> — <?php echo esc_html($report->period ?? date('F Y', strtotime($report->created_at))); ?></div>
      <div class="tru-report-meta">
        <span class="tru-td-mono"><?php echo esc_html($report->machine_id); ?></span>
        <span>·</span>
        <span>PDF</span>
        <?php if (!empty($report->file_size)) : ?>
          <span>·</span>
          <span><?php echo esc_html($report->file_size); ?></span>
        <?php endif; ?>
        <span>·</span>
        <span><?php echo esc_html(date('d M Y', strtotime($report->created_at))); ?></span>
      </div>
    </div>
    <div class="tru-report-actions">
      <?php if ($has_file) : ?>
        <a href="<?php echo esc_url($report->file_path); ?>" class="tru-btn-sm tru-btn-download" download>
          ⬇ Download PDF
        </a>
      <?php else : ?>
        <span class="tru-report-unavail">⏳ Generating…</span>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else : ?>
<div class="tru-empty-state">
  <div class="tru-empty-icon">📭</div>
  <div class="tru-empty-title">No reports yet</div>
  <div class="tru-empty-sub">Reports will appear here after your first full month of monitoring.</div>
</div>
<?php endif; ?>

<?php include $partials . 'portal-footer.php'; ?>
