<?php
if (!defined('isHttps') && !isset($Reflector)) {
    // Protezione da accesso diretto non autorizzato
}

// Verifica dello stato del servizio xlxd tramite systemctl o pgrep
$xlxd_status = false;
$output = [];
$return_var = 0;
@exec("systemctl is-active xlxd", $output, $return_var);
if ($return_var === 0 && isset($output[0]) && trim($output[0]) === 'active') {
    $xlxd_status = true;
} else {
    @exec("pgrep -x xlxd", $output_pgrep);
    if (!empty($output_pgrep)) {
        $xlxd_status = true;
    }
}

// Verifica dello stato del server AMBE sul Raspberry via WireGuard
$ambe_host = "10.0.0.2"; // IP WireGuard del Raspberry Pi
$ambe_port = 10100;    // Porta standard del daemon ambed
$ambe_status = false;

$socket = @fsockopen("udp://" . $ambe_host, $ambe_port, $errno, $errstr, 1);
if ($socket) {
    $ambe_status = true;
    @fclose($socket);
} else {
    $output_ambe = [];
    @exec("nc -z -u -w1 $ambe_host $ambe_port", $output_ambe, $return_nc);
    if ($return_nc === 0) {
        $ambe_status = true;
    }
}

// Recupero Uptime VPS
$vps_uptime = @exec("uptime -p");
if (empty($vps_uptime)) {
    $vps_uptime = "N/A";
}

// Load Average arrotondato a 2 decimali
$load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
$load_formatted = number_format($load[0], 2) . ' / ' . number_format($load[1], 2) . ' / ' . number_format($load[2], 2);

// Utilizzo RAM
$free_output = [];
@exec("free -m", $free_output);
$mem_total = 0;
$mem_used = 0;
if (count($free_output) >= 2) {
    $mem_parts = preg_split('/\s+/', $free_output[1]);
    if (isset($mem_parts[1]) && isset($mem_parts[2])) {
        $mem_total = $mem_parts[1];
        $mem_used = $mem_parts[2];
    }
}

// Utilizzo Disco (Root /)
$disk_total = @disk_total_space("/");
$disk_free = @disk_free_space("/");
$disk_used = $disk_total - $disk_free;
$disk_total_gb = $disk_total ? round($disk_total / 1024 / 1024 / 1024, 2) : 0;
$disk_used_gb = $disk_total ? round($disk_used / 1024 / 1024 / 1024, 2) : 0;
$disk_percent = $disk_total ? round(($disk_used / $disk_total) * 100, 1) : 0;
?>

<div style="max-width: 900px; margin: 0 auto; text-align: left; background-color: #0f172a; color: #e2e8f0; padding: 20px; border-radius: 12px;">
   <!-- Tabella Informazioni VPS -->
   <table class="listingtable" style="width: 100%; margin-bottom: 30px; border-collapse: collapse; background-color: #1e293b; border-radius: 8px; overflow: hidden;">
      <thead>
         <tr>
            <th colspan="2" style="text-align: left; padding: 12px 15px; font-size: 16px; background-color: #0f172a; border-bottom: 1px solid #334155; color: #38bdf8;">
               <svg style="width:18px;height:18px;vertical-align:middle;margin-right:8px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>VPS & Hardware Status
            </th>
         </tr>
      </thead>
      <tbody>
         <tr>
            <td style="width: 35%; padding: 12px 15px; border-bottom: 1px solid #334155; color: #94a3b8;">VPS Uptime</td>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; font-weight: 600; color: #e2e8f0;"><?php echo htmlspecialchars($vps_uptime); ?></td>
         </tr>
         <tr>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; color: #94a3b8;">Load Average (1, 5, 15 min)</td>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; font-weight: 600; color: #e2e8f0;"><?php echo $load_formatted; ?></td>
         </tr>
         <tr>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; color: #94a3b8;">RAM Usage</td>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; font-weight: 600; color: #e2e8f0;">
               <?php echo "{$mem_used} MB / {$mem_total} MB (" . ($mem_total > 0 ? round(($mem_used/$mem_total)*100, 1) : 0) . "%)"; ?>
            </td>
         </tr>
         <tr>
            <td style="padding: 12px 15px; color: #94a3b8;">Disk Usage (Root /)</td>
            <td style="padding: 12px 15px; font-weight: 600; color: #e2e8f0;">
               <?php echo "{$disk_used_gb} GB / {$disk_total_gb} GB ({$disk_percent}%)"; ?>
            </td>
         </tr>
      </tbody>
   </table>

   <!-- Tabella Stato XLXD, AMBE Server e Dashboard -->
   <table class="listingtable" style="width: 100%; border-collapse: collapse; background-color: #1e293b; border-radius: 8px; overflow: hidden;">
      <thead>
         <tr>
            <th colspan="2" style="text-align: left; padding: 12px 15px; font-size: 16px; background-color: #0f172a; border-bottom: 1px solid #334155; color: #38bdf8;">
               <svg style="width:18px;height:18px;vertical-align:middle;margin-right:8px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg>Reflector, AMBE Server & Dashboard Info
            </th>
         </tr>
      </thead>
      <tbody>
         <tr>
            <td style="width: 35%; padding: 12px 15px; border-bottom: 1px solid #334155; color: #94a3b8;">XLXD Service Status</td>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; font-weight: 600; display: flex; align-items: center; gap: 10px;">
               <!-- LED Indicator -->
               <span style="height: 14px; width: 14px; background-color: <?php echo $xlxd_status ? '#22c55e' : '#ef4444'; ?>; border-radius: 50%; display: inline-block; box-shadow: 0 0 10px <?php echo $xlxd_status ? '#22c55e' : '#ef4444'; ?>;"></span>
               <span style="color: <?php echo $xlxd_status ? '#4ade80' : '#f87171'; ?>;"><?php echo $xlxd_status ? 'Running (In esecuzione)' : 'Stopped (Fermo)'; ?></span>
            </td>
         </tr>
         <tr>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; color: #94a3b8;">AMBE Server</td>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; font-weight: 600; display: flex; align-items: center; gap: 10px;">
               <!-- LED Indicator AMBE -->
               <span style="height: 14px; width: 14px; background-color: <?php echo $ambe_status ? '#22c55e' : '#ef4444'; ?>; border-radius: 50%; display: inline-block; box-shadow: 0 0 10px <?php echo $ambe_status ? '#22c55e' : '#ef4444'; ?>;"></span>
               <span style="color: <?php echo $ambe_status ? '#4ade80' : '#f87171'; ?>;"><?php echo $ambe_status ? 'Connected (Connesso)' : 'Unreachable (Irraggiungibile)'; ?></span>
            </td>
         </tr>
         <tr>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; color: #94a3b8;">XLXD Software Version</td>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; font-weight: 600; color: #e2e8f0;">
               v<?php echo htmlspecialchars($Reflector->GetVersion()); ?>
            </td>
         </tr>
         <tr>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; color: #94a3b8;">Service Uptime</td>
            <td style="padding: 12px 15px; border-bottom: 1px solid #334155; font-weight: 600; color: #e2e8f0;">
               <?php echo FormatSeconds($Reflector->GetServiceUptime()); ?>
            </td>
         </tr>
         <tr>
            <td style="padding: 12px 15px; color: #94a3b8;">Dashboard Version</td>
            <td style="padding: 12px 15px; font-weight: 600; color: #e2e8f0;">
               v<?php echo htmlspecialchars($PageOptions['DashboardVersion']); ?> <span style="font-size: 13px; background-color: #334155; color: #38bdf8; padding: 3px 10px; border-radius: 12px; margin-left: 10px; font-weight: 600;">Dashboard Dark Mode</span>
            </td>
         </tr>
      </tbody>
   </table>
</div>