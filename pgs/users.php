<?php

if (!isset($_SESSION['FilterCallSign'])) {
   $_SESSION['FilterCallSign'] = null;
}

if (!isset($_SESSION['FilterModule'])) {
   $_SESSION['FilterModule'] = null;
}

if (isset($_POST['do'])) {
   if ($_POST['do'] == 'SetFilter') {
      
      if (isset($_POST['txtSetCallsignFilter'])) {
         $_POST['txtSetCallsignFilter'] = trim($_POST['txtSetCallsignFilter']);
         if ($_POST['txtSetCallsignFilter'] == "") {
            $_SESSION['FilterCallSign'] = null;
         }
         else {
            $_SESSION['FilterCallSign'] = $_POST['txtSetCallsignFilter'];
            if (strpos($_SESSION['FilterCallSign'], "*") === false) {
               $_SESSION['FilterCallSign'] = "*".$_SESSION['FilterCallSign']."*";
            }
         }
         
      }
      
      if (isset($_POST['txtSetModuleFilter'])) {
         $_POST['txtSetModuleFilter'] = trim($_POST['txtSetModuleFilter']);
         if ($_POST['txtSetModuleFilter'] == "") {
            $_SESSION['FilterModule'] = null;
         }
         else {
            $_SESSION['FilterModule'] = $_POST['txtSetModuleFilter'];
         }
         
      }
   }
}

if (isset($_GET['do'])) {
   if ($_GET['do'] == "resetfilter") {
      $_SESSION['FilterModule'] = null;
      $_SESSION['FilterCallSign'] = null;
   }
}

// Validate filter inputs
if (isset($_SESSION['FilterCallSign']) && $_SESSION['FilterCallSign'] !== null) {
    $_SESSION['FilterCallSign'] = preg_replace('/[^A-Z0-9\*\-\/\s]/i', '', $_SESSION['FilterCallSign']);
}
if (isset($_SESSION['FilterModule']) && $_SESSION['FilterModule'] !== null) {
    $_SESSION['FilterModule'] = validate_module($_SESSION['FilterModule']);
}

?>

<!-- Stile CSS per il lampeggio esclusivo del testo TXing e scroll dei moduli -->
<style>
@keyframes txingBlink {
   0%, 100% { opacity: 1; }
   50% { opacity: 0.25; }
}
.txing-pulsing-badge {
   animation: txingBlink 1s infinite ease-in-out;
   display: inline-block;
   color: #facc15 !important;
   font-weight: bold;
   text-shadow: 0 1px 2px rgba(0,0,0,0.6);
}
.modules-container::-webkit-scrollbar {
   height: 8px;
}
.modules-container::-webkit-scrollbar-track {
   background: #0f172a;
   border-radius: 4px;
}
.modules-container::-webkit-scrollbar-thumb {
   background: #334155;
   border-radius: 4px;
}
.modules-container::-webkit-scrollbar-thumb:hover {
   background: #475569;
}
</style>

<!-- Contenitore principale flessibile -->
<div style="display: flex; justify-content: center; align-items: flex-start; gap: 20px; width: 100%; flex-wrap: wrap; box-sizing: border-box;">

   <!-- CARD SINISTRA: Transiti / Utenti (Fissa, rigida e compatta) -->
   <div style="background-color: #1e293b; border: 1px solid #334155; border-radius: 10px; overflow: hidden; flex-shrink: 0;">
      <table class="listingtable" style="margin: 0; border: none; background: transparent;">
<?php
  
if ($PageOptions['UserPage']['ShowFilter']) {
   echo '
       <tr>
         <th colspan="8">
            <table width="100%" border="0" style="background: transparent; border: none;">
               <tr>
                  <td align="left" style="background: transparent; border: none;">
                     <form name="frmFilterCallSign" method="post" action="./index.php">
                        <input type="hidden" name="do" value="SetFilter" />
                        <input type="text" class="FilterField" value="'.sanitize_attribute($_SESSION['FilterCallSign']).'" name="txtSetCallsignFilter" placeholder="Callsign" onfocus="SuspendPageRefresh();" onblur="setTimeout(ReloadPage, '.$PageOptions['PageRefreshDelay'].');" />
                        <input type="submit" value="Apply" class="FilterSubmit" />
                     </form>
                  </td>';
   if (($_SESSION['FilterModule'] != null) || ($_SESSION['FilterCallSign'] != null)) {               
      echo '
               <td style="background: transparent; border: none;"><a href="./index.php?do=resetfilter" class="smalllink">Disable filters</a></td>';
   }  
   echo '            
                  <td align="right" style="padding-right:3px; background: transparent; border: none;">
                     <form name="frmFilterModule" method="post" action="./index.php">
                        <input type="hidden" name="do" value="SetFilter" />
                        <input type="text" class="FilterField" value="'.sanitize_attribute($_SESSION['FilterModule']).'" name="txtSetModuleFilter" placeholder="Module" onfocus="SuspendPageRefresh();" onblur="setTimeout(ReloadPage, '.$PageOptions['PageRefreshDelay'].');" />
                        <input type="submit" value="Apply" class="FilterSubmit" />
                     </form>
                  </td>
            </table>
         </th>
      </tr>';
}
                
   
?>   
       <tr>   
         <th>#</th>
         <th>Flag</th>
         <th>Callsign</th>
         <th>Suffix</th>
         <th>DPRS</th>
         <th>Via / Peer</th>
         <th>Last heard</th>
         <th align="center" valign="middle"><img src="./img/ear.png" alt="Listening on" /></th>
       </tr>
<?php

$Reflector->LoadFlags();
for ($i=0;$i<$Reflector->StationCount();$i++) {
   $ShowThisStation = true;
   if ($PageOptions['UserPage']['ShowFilter']) {
      $CS = true;
      if ($_SESSION['FilterCallSign'] != null) {
         if (!fnmatch($_SESSION['FilterCallSign'], $Reflector->Stations[$i]->GetCallSign(), FNM_CASEFOLD)) {
            $CS = false;
         }
      }
      $MO = true;
      if ($_SESSION['FilterModule'] != null) {
         if (trim(strtolower($_SESSION['FilterModule'])) != strtolower($Reflector->Stations[$i]->GetModule())) {
            $MO = false;
         }
      }
      
      $ShowThisStation = ($CS && $MO);
   }
      
      
   if ($ShowThisStation) {   
      // Controllo trasmissione attiva sulla prima riga impostato a 60 secondi
      $isTransmitting = ($i == 0 && $Reflector->Stations[$i]->GetLastHeardTime() > (time() - 60));
      
      // Sfondo rosso condizionale
      $cellStyle = $isTransmitting ? 'style="background-color: #dc2626 !important;"' : '';

      echo '
      <tr height="30">';
         
         // 1. Colonna #
         echo '<td align="center" valign="middle" width="35" '.$cellStyle.'><span style="color: #ffffff;">'.($i+1).'</span></td>';

         // 2. Colonna Flag
         echo '<td align="center" width="55" '.$cellStyle.'>';
         list ($Flag, $Name) = $Reflector->GetFlag($Reflector->Stations[$i]->GetCallSign());
         if (file_exists("./img/flags/".$Flag.".png")) {
            echo '<img src="./img/flags/'.sanitize_attribute($Flag).'.png" height="15" alt="'.sanitize_attribute($Name).'" />';
         }
         echo '</td>';

         // 3. Colonna Callsign
         echo '<td width="70" '.$cellStyle.'><a href="https://www.qrz.com/db/'.sanitize_attribute($Reflector->Stations[$i]->GetCallsignOnly()).'" class="pl" target="_blank" style="color: #38bdf8;"><strong>'.sanitize_output($Reflector->Stations[$i]->GetCallsignOnly()).'</strong></a></td>';

         // 4. Colonna Suffix
         echo '<td width="55" '.$cellStyle.' style="color: #ffffff;">'.sanitize_output($Reflector->Stations[$i]->GetSuffix()).'</td>';

         // 5. Colonna DPRS
         echo '<td width="45" align="center" '.$cellStyle.'><a href="http://www.aprs.fi/'.sanitize_attribute($Reflector->Stations[$i]->GetCallsignOnly()).'" class="pl" target="_blank"><img src="./img/sat.png" /></a></td>';

         // 6. Colonna Via / Peer (Ripristinata in modalità originale nativa)
         echo '<td width="140" '.$cellStyle.' style="color: #ffffff;">'.sanitize_output($Reflector->Stations[$i]->GetVia());
         if ($Reflector->Stations[$i]->GetPeer() != $Reflector->GetReflectorName()) {
            echo ' / '.sanitize_output($Reflector->Stations[$i]->GetPeer());
         }
         echo '</td>';

         // 7. Colonna Last heard (TXing lampeggiante senza secondi, data/ora normale altrimenti)
         echo '<td width="140" '.$cellStyle.'>';
         if ($isTransmitting) {
            echo '<span class="txing-pulsing-badge">TXing</span>';
         } else {
            echo '<span style="color: #ffffff;">'.sanitize_output(@date("d.m.Y H:i", $Reflector->Stations[$i]->GetLastHeardTime())).'</span>';
         }
         echo '</td>';

         // 8. Colonna Module
         echo '<td align="center" width="30" '.$cellStyle.' style="color: #ffffff;">'.sanitize_output($Reflector->Stations[$i]->GetModule()).'</td>';

      echo '</tr>';
   }
   if ($i == $PageOptions['LastHeardPage']['LimitTo']) { $i = $Reflector->StationCount()+1; }
}

?> 
      </table>
   </div>

   <!-- CARD DESTRA: Moduli Connessi -->
   <div class="modules-container" style="background-color: #1e293b; border: 1px solid #334155; border-radius: 10px; overflow-x: auto; max-width: 100%; flex-shrink: 1;">
      <table class="listingtable" style="margin: 0; border: none; background: transparent;">
<?php 

$Modules = $Reflector->GetModules();
sort($Modules, SORT_STRING);
echo '
       <tr>';
for ($i=0;$i<count($Modules);$i++) {
   
   if (isset($PageOptions['ModuleNames'][$Modules[$i]])) {
      $modName = trim($PageOptions['ModuleNames'][$Modules[$i]]);
      echo '
          <th style="width: 130px; min-width: 130px; text-align: center; word-break: break-word; white-space: normal; padding: 12px 6px;">';
      if ($modName != "") {
         echo '<span style="display: block; font-weight: 700; font-size: 15px; color: #ffffff; margin-bottom: 6px; line-height: 1.3;">'.sanitize_output($modName).'</span>';
         echo '<div style="border-top: 1px solid #334155; width: 85%; margin: 0 auto 6px auto;"></div>';
      }
      echo '<span style="display: block; font-size: 17px; font-weight: bold; color: #ffffff;">'.sanitize_output($Modules[$i]).'</span>';
      echo '</th>';
   }
   else {
      echo '
          <th style="width: 130px; min-width: 130px; text-align: center; word-break: break-word; white-space: normal; padding: 12px 6px;">
             <span style="display: block; font-size: 17px; font-weight: bold; color: #38bdf8;">'.sanitize_output($Modules[$i]).'</span>
          </th>';
   }
}

echo '
       </tr>
       <tr style="padding:0px;">';

for ($i=0;$i<count($Modules);$i++) {

   $Users = $Reflector->GetNodesInModulesByID($Modules[$i]);
   echo '
          <td valign="top" style="border:0px;padding:0px; background: transparent;">
             <table width="130" border="0" style="padding:0px;margin:0px; border:none; table-layout: fixed;">';
   
   $UserCheckedArray = array();
   
   for ($j=0;$j<count($Users);$j++) {
      
      $Displayname = $Reflector->GetCallsignAndSuffixByID($Users[$j]);
      echo '
                <tr height="36" onMouseOver="this.style.backgroundColor=\'#334155\';" onMouseOut="this.style.backgroundColor=\'\';">
                   <td valign="top" style="border-bottom:1px solid #334155; padding: 6px; text-align: center; word-break: break-all; overflow: hidden;"><a href="http://www.aprs.fi/'.sanitize_attribute($Displayname).'" class="pl" target="_blank" style="font-size: 16px;"><strong>'.sanitize_output($Displayname).'</strong></a></td>
                </tr>';
      $UserCheckedArray[] = $Users[$j];
   }
   echo '
             </table>
          </td>';
}

echo '
       </tr>';

?>
      </table>
   </div>

</div>