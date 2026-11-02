# 📻 Dashboard DarkMode per XLX Reflector
<br>
<br>
<br>
Passaggi per installazione della Dashboard :
<br>
Prima di effettuare la clonazione , ricordati di effettuare una copia di backup del file presente in /pgs/config.inc.php , una volta clonata la nuova dashboard ricopialo dentro la cartella /pgs
<br>
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
Per modificare l'intestazione della dashboard , utilizzare il file all'interno della cartella PGS/config.dashboard.php
<br>
<br>
<br>
<h1 class="text-1xl md:text-1xl font-bold">Anteprima grafica della dashboard:
<br>
<br>
<br>
<br>
<img width="1901" height="941" alt="Image" src="https://github.com/user-attachments/assets/9e66666d-b445-44e1-a92c-70c493fca947" />
<br>
<br>
Anteprima grafica della gestione dei Moduli che vengono visualizzati solo i moduli realmente attivi e vengono mostrati in automatico se aggiunti dalla pagina config.inc.php , i moduli con nodi connessi vengono evidenziati con il banner verde.
<br>
<br>
<img width="1900" height="943" alt="Image" src="https://github.com/user-attachments/assets/1bf7fb68-f8ed-46f7-95f4-4ea81459815d" />
