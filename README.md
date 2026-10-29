# 📻 Dashboard DarkMode per XLX Reflector
<br>
<br>
<br>
Passaggi per installazione della Dashboard :
<br>
1. Clona la repository dalla directory /var/www/html (o dalla cartella desiderata)
<br>
cd /var/www/html
<br>
sudo git clone https://github.com/iz1mlf595/xlx-darkdashboard-mode.git
<br>
2. Entra nella cartella appena creata
<br>
cd /var/www/html
<br>
3. Assegna i permessi corretti ad Apache
<br>
sudo chown -R www-data:www-data /var/www/html/
<br>
sudo find /var/www/html/ysf-dashboard-modern -type d -exec chmod 755 {} \;
<br>
sudo find /var/www/html/ysf-dashboard-modern -type f -exec chmod 644 {} \; 
<br>
<br>
<br>
<br>
<h1 class="text-2xl md:text-2xl font-bold">Anteprima grafica della dashboard:
<br>
<br>
<br>
<br>
<img width="1821" height="883" alt="Anteprima SvxLink" src="https://github.com/user-attachments/assets/bf424c55-a4f4-4c52-bbae-be65e856820b" />
<br>
<br>
Anteprima grafica della gestione dei Moduli che vengono visualizzati solo i moduli realmente attivi e vengono mostrati in automatico se aggiunti dalla pagina config.inc.php , i moduli con nodi connessi vengono evidenziati con il banner verde.
<br>
<br>
<img width="1915" height="887" alt="Image" src="https://github.com/user-attachments/assets/8352d2ca-137b-4f2f-94a3-43aac904a256" />
