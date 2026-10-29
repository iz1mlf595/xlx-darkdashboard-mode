<table class="listingtable">
 <tr>
   <th width="80" rowspan="2">Module</th>
   <th width="150" rowspan="2">Name</th>
   <th width="80" rowspan="2">Users</th>
   <th colspan="2">DPlus</th>
   <th colspan="2">DExtra</th>
   <th colspan="2">DCS</th>
 </tr>
 <tr>
   <th width="110">URCALL</th>
   <th width="110">DTMF</th>
   <th width="110">URCALL</th>
   <th width="110">DTMF</th>
   <th width="110">URCALL</th>
   <th width="110">DTMF</th>
 </tr>
<?php

$ReflectorNumber = substr($Reflector->GetReflectorName(), 3, 3);
$NumberOfModules = isset($PageOptions['NumberOfModules']) ? min(max($PageOptions['NumberOfModules'],0),26) : 26;

$odd = "";

for ($i = 1; $i <= $NumberOfModules; $i++) {

   $module = chr(ord('A')+($i-1));

   // Controlla se il modulo ha una descrizione valida (diversa da vuota o '-')
   $moduleName = isset($PageOptions['ModuleNames'][$module]) ? trim($PageOptions['ModuleNames'][$module]) : '';
   if (empty($moduleName) || $moduleName === '-') {
       continue; // Salta i moduli senza descrizione e passa al successivo
   }

   // Verifica se il modulo ha utenti connessi
   $connectedUsers = count($Reflector->GetNodesInModulesByID($module));
   $isActive = ($connectedUsers > 0);

   if ($odd == "#FFFFFF") { $odd = "#F1FAFA"; } else { $odd = "#FFFFFF"; }

   // Se il modulo è attivo, usiamo il verde smeraldo brillante (#10b981)
   $cellStyle = $isActive ? 'style="background-color: #10b981 !important; color: #ffffff !important;"' : 'bgcolor="'.$odd.'"';
   $mouseEvents = $isActive ? 'onMouseOver="this.style.backgroundColor=\'#059669\';" onMouseOut="this.style.backgroundColor=\'#10b981\';"' : 'onMouseOver="this.bgColor=\'#FFFFCA\';" onMouseOut="this.bgColor=\''.$odd.'\';"';

   // D-STAR ITALY NORD MP rimane SEMPRE rosso, in grassetto e corsivo
   if (strcasecmp($moduleName, 'D-STAR ITALY NORD MP') === 0) {
       $displayName = '<span style="color: #ef4444 !important; font-weight: bold; font-style: italic;">' . sanitize_output($moduleName) . '</span>';
   } else {
       $displayName = '<strong style="color: ' . ($isActive ? '#ffffff' : 'inherit') . ';">' . sanitize_output($moduleName) . '</strong>';
   }

   echo '
 <tr height="30" '.$mouseEvents.'>
   <td align="center" '.$cellStyle.'><strong>'. sanitize_output($module) .'</strong></td>
   <td align="center" '.$cellStyle.'>'. $displayName .'</td>
   <td align="center" '.$cellStyle.'><strong>'. $connectedUsers .'</strong></td>
   <td align="center" '.$cellStyle.'>'. sanitize_output('REF' . $ReflectorNumber . $module . 'L') .'</td>
   <td align="center" '.$cellStyle.'>'. sanitize_output(is_numeric($ReflectorNumber) ? '*' . sprintf('%01d',$ReflectorNumber) . (($i<=4)?$module:sprintf('%02d',$i)) : '-') .'</td>
   <td align="center" '.$cellStyle.'>'. sanitize_output('XRF' . $ReflectorNumber . $module . 'L') .'</td>
   <td align="center" '.$cellStyle.'>'. sanitize_output(is_numeric($ReflectorNumber) ? 'B' . sprintf('%01d',$ReflectorNumber) . (($i<=4)?$module:sprintf('%02d',$i)) : '-') .'</td>
   <td align="center" '.$cellStyle.'>'. sanitize_output('DCS' . $ReflectorNumber . $module . 'L') .'</td>
   <td align="center" '.$cellStyle.'>'. sanitize_output(is_numeric($ReflectorNumber) ? 'D' . sprintf('%01d',$ReflectorNumber) . (($i<=4)?$module:sprintf('%02d',$i)) : '-') .'</td>
 </tr>';
}

?>

</table>