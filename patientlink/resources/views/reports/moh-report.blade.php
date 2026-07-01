<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: 'Helvetica', sans-serif; color: #1a1a1a; font-size: 12px; }
  .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f3d3e; padding-bottom: 10px; }
  .header h1 { color: #0f3d3e; margin: 0; font-size: 20px; }
  .header p { margin: 4px 0 0; color: #6c757d; font-size: 11px; }
  .section { margin-bottom: 20px; }
  .section h2 { font-size: 14px; color: #0f3d3e; border-bottom: 1px solid #dee2e6; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th, td { border: 1px solid #dee2e6; padding: 6px 10px; text-align: left; font-size: 11px; }
  th { background: #f4f6f9; color: #0f3d3e; }
  .stat-grid { display: table; width: 100%; }
  .stat-cell { display: table-cell; width: 25%; padding: 10px; text-align: center; border: 1px solid #dee2e6; }
  .stat-number { font-size: 18px; font-weight: bold; color: #0f3d3e; }
  .stat-label { font-size: 9px; color: #6c757d; text-transform: uppercase; }
  .footer { margin-top: 30px; font-size: 9px; color: #6c757d; text-align: center; border-top: 1px solid #dee2e6; padding-top: 10px; }
</style>
</head>
<body>

  <div class="header">
    <h1>PatientLink — System Health Report</h1>
    <p>Ministry of Health | Generated {{ $generatedAt }}</p>
  </div>

  <div class="section">
    <h2>System Overview</h2>
    <div class="stat-grid">
      <div class="stat-cell"><div class="stat-number">{{ $totals['patients'] }}</div><div class="stat-label">Total Patients</div></div>
      <div class="stat-cell"><div class="stat-number">{{ $totals['doctors'] }}</div><div class="stat-label">Total Doctors</div></div>
      <div class="stat-cell"><div class="stat-number">{{ $totals['facilities'] }}</div><div class="stat-label">Facilities</div></div>
      <div class="stat-cell"><div class="stat-number">{{ $activeUsers }}</div><div class="stat-label">Active Users</div></div>
    </div>
  </div>

  <div class="section">
    <h2>Consent Records</h2>
    <table>
      <tr><th>Status</th><th>Count</th></tr>
      <tr><td>Approved</td><td>{{ $consents['approved'] }}</td></tr>
      <tr><td>Pending</td><td>{{ $consents['pending'] }}</td></tr>
      <tr><td>Rejected</td><td>{{ $consents['rejected'] }}</td></tr>
      <tr><td>Expired</td><td>{{ $consents['expired'] }}</td></tr>
    </table>
  </div>

  <div class="section">
    <h2>Recent System Activity</h2>
    <table>
      <tr><th>Timestamp</th><th>Actor</th><th>Role</th><th>Action</th><th>Outcome</th></tr>
      @foreach($recentActivity as $log)
      <tr>
        <td>{{ $log['timestamp'] }}</td>
        <td>{{ $log['actor'] }}</td>
        <td>{{ $log['role'] }}</td>
        <td>{{ $log['action'] }}</td>
        <td>{{ $log['outcome'] }}</td>
      </tr>
      @endforeach
    </table>
  </div>

  <div class="footer">
    This report was generated automatically by the PatientLink system for policy and planning purposes.
  </div>

</body>
</html>