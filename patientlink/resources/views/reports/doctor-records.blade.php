<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: 'Helvetica', sans-serif; color: #1a1a1a; font-size: 12px; }
  .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f3d3e; padding-bottom: 10px; }
  .header h1 { color: #0f3d3e; margin: 0; font-size: 18px; }
  .header p { margin: 4px 0 0; color: #6c757d; font-size: 11px; }
  .section h2 { font-size: 13px; color: #0f3d3e; border-bottom: 1px solid #dee2e6; padding-bottom: 4px; }
  .record { border: 1px solid #dee2e6; border-radius: 4px; padding: 10px; margin-bottom: 14px; }
  .record-date { font-size: 10px; color: #6c757d; margin-bottom: 6px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  td { padding: 4px 8px; font-size: 11px; vertical-align: top; }
  td:first-child { color: #6c757d; width: 30%; text-transform: uppercase; font-size: 10px; }
  .notes { background: #f8f9fa; padding: 6px 8px; font-size: 11px; margin-top: 4px; border-left: 3px solid #0f3d3e; }
  .footer { margin-top: 30px; font-size: 9px; color: #6c757d; text-align: center; border-top: 1px solid #dee2e6; padding-top: 10px; }
  .empty { text-align: center; color: #6c757d; padding: 20px; }
</style>
</head>
<body>

  <div class="header">
    <h1>PatientLink — Clinical Records Export</h1>
    <p>Patient: {{ $patientName }} &nbsp;|&nbsp; Doctor: {{ $doctorName }} &nbsp;|&nbsp; Generated: {{ $generatedAt }}</p>
  </div>

  @if(count($records) === 0)
    <div class="empty">No records found for this patient under your account.</div>
  @else
    @foreach($records as $r)
    <div class="record">
      <div class="record-date">DATE: {{ $r['date'] }}</div>
      <table>
        <tr><td>Diagnosis</td><td>{{ $r['diagnosis'] }}</td></tr>
        <tr><td>Allergies</td><td>{{ $r['allergies'] }}</td></tr>
        <tr><td>Facility</td><td>{{ $r['facility'] }}</td></tr>
      </table>
      @if($r['clinical_notes'] !== '-')
      <div class="notes"><strong>Clinical Notes:</strong> {{ $r['clinical_notes'] }}</div>
      @endif
    </div>
    @endforeach
  @endif

  <div class="footer">
    This report was exported from PatientLink and contains records added by {{ $doctorName }} only.
  </div>

</body>
</html>