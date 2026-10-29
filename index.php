<?php
// ==========================================
// CONFIGURAZIONE PERSONALIZZATA INTESTAZIONE
// ==========================================
$CUSTOM_REFLECTOR_TITLE = "XLX MULTIREFLECTOR GATEWAY"; // Modifica qui il nome quando crei un altro reflector


// Check if we are serving HTTPS
function isHttps() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }
    return false;
}

// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();

// Security headers
$isHttps = isHttps();
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

$imgSrc = $isHttps ? "'self' data: https:" : "'self' data: http: https:";

$csp = "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline'; " .
        "style-src 'self' 'unsafe-inline'; " .
        "img-src {$imgSrc}; " .
        "connect-src 'self'; " .
        "frame-ancestors 'self'";

header("Content-Security-Policy: " . $csp);

if ($isHttps) {
        header("Strict-Transport-Security: max-age=31536000");
}

if (file_exists("./pgs/functions.php"))   { require_once("./pgs/functions.php"); } else { die("functions.php does not exist."); }
if (file_exists("./pgs/config.inc.php"))  { require_once("./pgs/config.inc.php"); } else { die("config.inc.php does not exist."); }

if (!class_exists('ParseXML'))   require_once("./pgs/class.parsexml.php");
if (!class_exists('Node'))       require_once("./pgs/class.node.php");
if (!class_exists('xReflector')) require_once("./pgs/class.reflector.php");
if (!class_exists('Station'))    require_once("./pgs/class.station.php");
if (!class_exists('Peer'))       require_once("./pgs/class.peer.php");
if (!class_exists('Interlink'))  require_once("./pgs/class.interlink.php");

$allowed_pages = ['users', 'repeaters', 'liveircddb', 'peers', 'modules', 'reflectors', 'traffic', 'ysfpeers', 'nxdnpeers', 'p25peers', 'sysinfo'];
if (!isset($_GET['show'])) {
    $_GET['show'] = '';
} elseif (!in_array($_GET['show'], $allowed_pages, true)) {
    $_GET['show'] = '';
}

if (isset($_GET['do']) && $_GET['do'] !== 'resetfilter') {
    unset($_GET['do']);
}

if (isset($_GET['callhome'])) {
    $_GET['callhome'] = filter_var($_GET['callhome'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($_GET['callhome'] === null) {
        unset($_GET['callhome']);
    }
}

$Reflector = new xReflector();
$Reflector->SetFlagFile("./pgs/country.csv");
$Reflector->SetPIDFile($Service['PIDFile']);
$Reflector->SetXMLFile($Service['XMLFile']);

$Reflector->LoadXML();

require_once('./pgs/parse_journal_peers.php');

$InterlinkFile = !empty($CallingHome['InterlinkFile']) ? $CallingHome['InterlinkFile'] : '/xlxd/xlxd.interlink';
foreach (['YSF' => 'YSFPeerPage', 'NXDN' => 'NXDNPeerPage', 'P25' => 'P25PeerPage'] as $PeerType => $PeerPage) {
    if (!empty($PageOptions[$PeerPage]['Show']) && !HasLocalPeer($PeerType, $InterlinkFile)) {
        $PageOptions[$PeerPage]['Show']        = false;
        $PageOptions[$PeerPage]['NoLocalPeer'] = true;
    }
}

$YSFPeers = [];
if (!empty($PageOptions['YSFPeerPage']['Show'])) {
    $YSFPeers = ParseJournalPeers($PageOptions['YSFPeerPage']);
}

$NXDNPeers = [];
if (!empty($PageOptions['NXDNPeerPage']['Show'])) {
    $NXDNPeers = ParseJournalPeers($PageOptions['NXDNPeerPage']);
}

$P25Peers = [];
if (!empty($PageOptions['P25PeerPage']['Show'])) {
    $P25Peers = ParseJournalPeers($PageOptions['P25PeerPage']);
}

if ($CallingHome['Active']) { 
   $CallHomeNow = false;
   $LastSync = 0;
   $Hash = "";
   
   if (!file_exists($CallingHome['HashFile'])) {
      $Ressource = fopen($CallingHome['HashFile'], "w+"); 
      if ($Ressource) { 
         $Hash = CreateCode(16);
		   @fwrite($Ressource, "<?php\n"); 
		   @fwrite($Ressource, "\n".'$Hash = "'.$Hash.'";'); 
		   @fwrite($Ressource, "\n\n".'?>'); 
		   @fflush($Ressource); 
		   @fclose($Ressource); 
		   @chmod($HashFile, 0777); 
		}
   }
   else {
      require_once($CallingHome['HashFile']);
   }
   
   if (@file_exists($CallingHome['LastCallHomefile'])) {
      if (@is_readable($CallingHome['LastCallHomefile'])) {
         $tmp      = @file($CallingHome['LastCallHomefile']);
         if (isset($tmp[0])) {
            $LastSync = $tmp[0];
         }
         unset($tmp);
      }
   }
         
   if ($LastSync < (time() - $CallingHome['PushDelay'])) { 
      $CallHomeNow = true;
      $Ressource = @fopen($CallingHome['LastCallHomefile'], "w+"); 
	   if ($Ressource) { 
	      @fwrite($Ressource, time()); 
		   @fflush($Ressource); 
		   @fclose($Ressource); 
		   @chmod($HashFile, 0777); 
		}
   }  
   
   if ($CallHomeNow || isset($_GET['callhome'])) {
      $Reflector->SetCallingHome($CallingHome, $Hash);
      $Reflector->ReadInterlinkFile();
      $Reflector->PrepareInterlinkXML();
      $Reflector->PrepareReflectorXML();
      $Reflector->CallHome();
   }
}
else {
   $Hash = "";
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
   <meta http-equiv="content-type" content="text/html; charset=utf-8" />
   <meta name="description" content="<?php echo sanitize_attribute($PageOptions['MetaDescription']); ?>" />
   <meta name="keywords"    content="<?php echo sanitize_attribute($PageOptions['MetaKeywords']); ?>" />
   <meta name="author"      content="<?php echo sanitize_attribute($PageOptions['MetaAuthor']); ?>" />
   <meta name="revisit"     content="<?php echo sanitize_attribute($PageOptions['MetaRevisit']); ?>" />
   <meta name="robots"      content="<?php echo sanitize_attribute($PageOptions['MetaRobots']); ?>" />
   
   <title><?php echo sanitize_output($CUSTOM_REFLECTOR_TITLE); ?> Reflector Dashboard</title>
   <link rel="stylesheet" type="text/css" href="./css/layout.css">
   <link rel="icon" href="./favicon.ico" type="image/vnd.microsoft.icon">
   <style type="text/css">
      body {
         background-color: #0f172a !important;
         color: #e2e8f0 !important;
         margin: 0 !important;
         padding: 0 !important;
      }
      
      #top, #menubar, #content {
         position: static !important;
         top: auto !important;
         left: auto !important;
         right: auto !important;
         bottom: auto !important;
         float: none !important;
         transform: none !important;
      }

      /* ANIMAZIONE PULSAZIONE NEON PER IL TITOLO */
      @keyframes neonPulse {
         0%, 100% {
            text-shadow: 0 0 10px rgba(56, 189, 248, 0.4), 0 0 20px rgba(56, 189, 248, 0.2), 0 2px 4px rgba(0,0,0,0.3);
         }
         50% {
            text-shadow: 0 0 20px rgba(56, 189, 248, 0.8), 0 0 30px rgba(56, 189, 248, 0.4), 0 2px 4px rgba(0,0,0,0.3);
         }
      }

      .animated-title {
         animation: neonPulse 3s infinite ease-in-out;
      }

      /* INTESTAZIONE IN ALTO */
      .page-header {
         background-color: #1e293b !important;
         color: #e2e8f0 !important;
         border-bottom: 1px solid #334155 !important;
         padding: 35px 20px !important;
         text-align: center !important;
         width: 100% !important;
         box-sizing: border-box !important;
         display: block !important;
      }
      
      /* BANNER RIDOTTO E CENTRATO PER I PULSANTI */
      .nav-banner {
         background-color: #1e293b !important;
         border: 1px solid #334155 !important;
         border-radius: 12px !important;
         display: inline-block !important;
         margin: 20px auto 10px auto !important;
         padding: 12px 20px !important;
         box-sizing: border-box !important;
         text-align: center !important;
         box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
      }
      .nav-menu {
         display: block !important;
         width: 100% !important;
         text-align: center !important;
      }
      .nav-menu table {
         background-color: transparent !important;
         margin: 0 auto !important;
         border-collapse: separate !important;
         border-spacing: 16px 6px !important;
      }
      .nav-menu td {
         background-color: transparent !important;
         border: none !important;
         padding: 0 !important;
         text-align: center !important;
      }
      
      /* PULSANTI PIÙ GRANDI, CON ICONE E BEN DISTANZIATI */
      .menulink, .nav-menu a {
         color: #cbd5e1 !important;
         background-color: #0f172a !important;
         border: 1px solid #475569 !important;
         border-radius: 8px !important;
         padding: 12px 22px !important;
         display: inline-block !important;
         text-decoration: none !important;
         font-size: 15px !important;
         font-weight: 600 !important;
         transition: all 0.2s ease !important;
         white-space: nowrap !important;
      }
      .menulink:hover, .nav-menu a:hover {
         color: #38bdf8 !important;
         border-color: #38bdf8 !important;
         background-color: #1e293b !important;
      }
      .menulink.active, a.menulink.active {
         color: #38bdf8 !important;
         background-color: #1e293b !important;
         border-color: #38bdf8 !important;
         font-weight: bold !important;
      }
      
      a {
         color: #38bdf8 !important;
      }
      a:hover {
         color: #7dd3fc !important;
      }
      td {
         background-color: #1e293b !important;
         color: #e2e8f0 !important;
         border: 1px solid #334155 !important;
         padding: 6px 10px;
      }
      
      /* TONALITÀ MEDIA EQUILIBRATA (SIMILE AL CAMPIONE) */
      .filtertable, .filterbox, form table, th, .listingtable th {
         background-color: #1c4e75 !important;
         color: #ffffff !important;
         border: 1px solid #28608e !important;
         padding: 6px 10px;
         font-weight: 700 !important;
         background-image: none !important;
      }
      .filtertable td, .filterbox td, form table td {
         background-color: #1c4e75 !important;
         border: none !important;
         color: #ffffff !important;
      }
      
      /* CONTENUTO PRINCIPALE DISTANZIATO DAL BANNER */
      .page-content {
         width: 100% !important;
         box-sizing: border-box !important;
         text-align: center !important;
         margin-top: 35px !important;
         margin-bottom: 50px !important;
         display: block !important;
      }

      .listingtable {
         background-color: #1e293b !important;
         border: 1px solid #334155 !important;
         border-radius: 10px !important;
         overflow: hidden !important;
         border-collapse: collapse !important;
         margin: 0 auto !important;
      }
      
      input, select, textarea {
         background-color: #0f172a !important;
         color: #e2e8f0 !important;
         border: 1px solid #475569 !important;
         border-radius: 4px;
      }
      .error {
         background-color: #7f1d1d !important;
         color: #fca5a5 !important;
         border: 1px solid #991b1b !important;
         border-radius: 6px;
      }
   </style>
   <?php

   if ($PageOptions['PageRefreshActive']) {
      $refreshParams = array();
      if (!empty($_GET['show'])) {
         $refreshParams[] = 'show=' . urlencode($_GET['show']);
      }
      if (!empty($_GET['page']) && intval($_GET['page']) > 1) {
         $refreshParams[] = 'page=' . intval($_GET['page']);
      }
      $refreshUrl = './index.php' . (!empty($refreshParams) ? '?' . implode('&', $refreshParams) : '');

      echo '
   <script src="./js/jquery-1.12.4.min.js"></script>
   <script>
      var PageRefresh;

      function ReloadPage() {
         $.get("'.addslashes($refreshUrl).'", function(data) {
            var BodyStart = data.indexOf("<bo"+"dy");
            var BodyEnd = data.indexOf("</bo"+"dy>");
            if ((BodyStart >= 0) && (BodyEnd > BodyStart)) {
               BodyStart = data.indexOf(">", BodyStart)+1;
               $("body").html(data.substring(BodyStart, BodyEnd));
            }
         })
            .always(function() {
               PageRefresh = setTimeout(ReloadPage, '.$PageOptions['PageRefreshDelay'].');
            });
      }';

     if (!isset($_GET['show']) || (($_GET['show'] != 'liveircddb') && ($_GET['show'] != 'reflectors') && ($_GET['show'] != 'interlinks'))) {
         echo '
      PageRefresh = setTimeout(ReloadPage, '.$PageOptions['PageRefreshDelay'].');';
     }
     echo '

      function SuspendPageRefresh() {
         clearTimeout(PageRefresh);
      }
   </script>';
   }
?>
</head>
<body style="text-align: center;">
   <?php if (file_exists("./tracking.php")) { include_once("tracking.php"); }?>
   
   <!-- INTESTAZIONE PRINCIPALE CON SCRITTA ANIMATA -->
   <div class="page-header">
      <div class="animated-title" style="font-size: 52px; font-weight: 800; color: #38bdf8; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 12px;"><?php echo sanitize_output($CUSTOM_REFLECTOR_TITLE); ?></div>
      <div style="font-size: 14px; color: #94a3b8; font-weight: 500;">
         <?php echo sanitize_output($Reflector->GetReflectorName()); ?>&nbsp;v<?php echo sanitize_output($Reflector->GetVersion()); ?>&nbsp;-&nbsp;Dashboard v<?php echo sanitize_output($PageOptions['DashboardVersion']); ?>&nbsp;<?php echo sanitize_output($PageOptions['CustomTXT']); ?>&nbsp;&nbsp;/&nbsp;&nbsp;Service uptime: <span id="suptime"><?php echo FormatSeconds($Reflector->GetServiceUptime()); ?></span>
      </div>
   </div>

   <!-- BANNER DEI PULSANTI DI NAVIGAZIONE CON ICONE SVG -->
   <div style="width: 100%; text-align: center;">
      <div class="nav-banner">
         <div class="nav-menu">
            <table border="0">
               <tr>
                  <td>
                     <a href="./index.php" class="menulink<?php if (empty($_GET['show'])) { echo ' active'; } ?>">
                        <svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>Users / Modules
                     </a>
                  </td>
                  <td>
                     <a href="./index.php?show=repeaters" class="menulink<?php if ($_GET['show'] == 'repeaters') { echo ' active'; } ?>">
                        <svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg>Repeaters / Nodes (<?php echo intval($Reflector->NodeCount()); ?>)
                     </a>
                  </td>
                  <td>
                     <a href="./index.php?show=peers" class="menulink<?php if ($_GET['show'] == 'peers') { echo ' active'; } ?>">
                        <svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>Peers (<?php echo intval($Reflector->PeerCount()); ?>)
                     </a>
                  </td>
                  <?php

                  if (!empty($PageOptions['YSFPeerPage']['Show'])) {
                      echo '
                  <td><a href="./index.php?show=ysfpeers" class="menulink';
                      if ($_GET['show'] == 'ysfpeers') { echo ' active'; }
                      echo '"><svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>' . sanitize_output($PageOptions['YSFPeerPage']['PageTitle'])
                          . ' (' . intval(count($YSFPeers)) . ')</a></td>';
                  }

                  if (!empty($PageOptions['NXDNPeerPage']['Show'])) {
                      echo '
                  <td><a href="./index.php?show=nxdnpeers" class="menulink';
                      if ($_GET['show'] == 'nxdnpeers') { echo ' active'; }
                      echo '"><svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>' . sanitize_output($PageOptions['NXDNPeerPage']['PageTitle'])
                          . ' (' . intval(count($NXDNPeers)) . ')</a></td>';
                  }

                  if (!empty($PageOptions['P25PeerPage']['Show'])) {
                      echo '
                  <td><a href="./index.php?show=p25peers" class="menulink';
                      if ($_GET['show'] == 'p25peers') { echo ' active'; }
                      echo '"><svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>' . sanitize_output($PageOptions['P25PeerPage']['PageTitle'])
                          . ' (' . intval(count($P25Peers)) . ')</a></td>';
                  }

                  ?>
                  <td>
                     <a href="./index.php?show=modules" class="menulink<?php if ($_GET['show'] == 'modules') { echo ' active'; } ?>">
                        <svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>Modules list
                     </a>
                  </td>
                  <td>
                     <a href="./index.php?show=reflectors" class="menulink<?php if ($_GET['show'] == 'reflectors') { echo ' active'; } ?>">
                        <svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Reflectors list
                     </a>
                  </td>
                  <td>
                     <a href="./index.php?show=sysinfo" class="menulink<?php if ($_GET['show'] == 'sysinfo') { echo ' active'; } ?>">
                        <svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>SysInfo
                     </a>
                  </td>
                  <?php

                  if ($PageOptions['Traffic']['Show']) {
                      echo '
                  <td><a href="./index.php?show=traffic" class="menulink';
                      if ($_GET['show'] == 'traffic') { echo ' active'; }
                      echo '"><svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>Traffic statistics</a></td>';
                  }
   		     if ($PageOptions['IRCDDB']['Show']) {
                      echo '
                  <td><a href="./index.php?show=liveircddb" class="menulink';
                      if ($_GET['show'] == 'liveircddb') { echo ' active'; }
                      echo '"><svg style="width:16px;height:16px;vertical-align:middle;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.07 0a5 5 0 010 7.07M12 13a1 1 0 100-2 1 1 0 000 2z"/></svg>D-Star live</a></td>';
                 }

                  ?>
               </tr>
             </table>
         </div>
      </div>
   </div>
   
   <!-- CONTENUTO PRINCIPALE -->
   <div class="page-content" align="center">

<?php
   if ($CallingHome['Active']) {
      if (!is_readable($CallingHome['HashFile']) && (!is_writeable($CallingHome['HashFile']))) {
         echo '
         <div class="error">
            your private hash in '.sanitize_output($CallingHome['HashFile']).' could not be created, please check your config file and the permissions for the defined folder.
         </div>';
      }
   }

   switch ($_GET['show']) {
      case 'users'      : require_once("./pgs/users.php"); break;
      case 'repeaters'  : require_once("./pgs/repeaters.php"); break;
      case 'liveircddb' : require_once("./pgs/liveircddb.php"); break;
      case 'peers'      : require_once("./pgs/peers.php"); break;
      case 'modules'    : require_once("./pgs/modules.php"); break;
      case 'reflectors' : require_once("./pgs/reflectors.php"); break;
      case 'traffic'		: require_once("./pgs/traffic.php"); break;
      case 'ysfpeers'   : require_once("./pgs/ysfpeers.php"); break;
      case 'nxdnpeers'  : require_once("./pgs/nxdnpeers.php"); break;
      case 'p25peers'   : require_once("./pgs/p25peers.php"); break;
      case 'sysinfo'    : require_once("./pgs/sysinfo.php"); break;
      default           : require_once("./pgs/users.php");
   }

?>

   <div style="width:100%;text-align:center;margin-top:50px;"><a href="mailto:<?php echo sanitize_attribute($PageOptions['ContactEmail']); ?>" style="font-family:verdana;color:#38bdf8;font-size:12pt;text-decoration:none;"><?php echo sanitize_output($PageOptions['ContactEmail']); ?></a></div>

   </div>

</body>
</html>