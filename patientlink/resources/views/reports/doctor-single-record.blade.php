<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: 'Helvetica', sans-serif; color: #1a1a1a; font-size: 12px; }
  .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f3d3e; padding-bottom: 10px; }
  .header h1 { color: #0f3d3e; margin: 0; font-size: 18px; }
  .header p { margin: 4px 0 0; color: #6c757d; font-size: 11px; }
  .section { margin-bottom: 16px; }
  .section h2 { font-size: 13px; color: #0f3d3e; border-bottom: 1px solid #dee2e6; padding-bottom: 4px; margin-bottom: 8px; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 6px 10px; font-size: 11px; vertical-align: top; border: 1px solid #dee2e6; }
  td:first-child { color: #6c757d; width: 30%; text-transform: uppercase; font-size: 10px; background: #f8f9fa; }
  .notes { background: #f8f9fa; padding: 10px 12px; font-size: 11px; margin-top: 12px; border-left: 3px solid #0f3d3e; line-height: 1.6; }
  .footer { margin-top: 30px; font-size: 9px; color: #6c757d; text-align: center; border-top: 1px solid #dee2e6; padding-top: 10px; }
</style>
</head>
<body>

  <div class="header">
    <h1>PatientLink — Clinical Visit Record</h1>
    <p>Patient: {{ $patientName }} ({{ $patientNupi }}) &nbsp;|&nbsp; Doctor: {{ $doctorName }} &nbsp;|&nbsp; Generated: {{ $generatedAt }}</p>
  </div>

  <div class="section">
    <h2>Visit Details</h2>
    <table>
      <tr><td>Date</td><td>{{ $record['date'] }}</td></tr>
      <tr><td>Diagnosis</td><td>{{ $record['diagnosis'] }}</td></tr>
      <tr><td>Allergies</td><td>{{ $record['allergies'] }}</td></tr>
      <tr><td>Facility</td><td>{{ $record['facility'] }}</td></tr>
    </table>
  </div>

  @if($record['clinical_notes'] !== '-')
  <div class="section">
    <h2>Clinical Notes</h2>
    <div class="notes">{{ $record['clinical_notes'] }}</div>
  </div>
  @endif

  <div class="footer">
    This document was generated from PatientLink by {{ $doctorName }} and is intended for authorized clinical use only.
  </div>

</body>
</html>