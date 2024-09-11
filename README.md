# LekkiCMS
A lightweight CMS (Content Management System)

## VERY IMPORTANT NOTE
I made everything in Polish here. The project is so simple so I didn't make any support for translations (yet). The only way to translate the project is to edit the hard-coded strings into your preffered language. Thanks for understanding

## [EN] What is this project about?
I wanted to create a lightweight content management system that follows those guidelines:
- being easy on server resources
- working properly on any client (not freezing your Windows 95 machine or confusing your old Firefox that just happens to still support Windows XP)
- having very simple and clear navigation between the most important pages
- being customizable - letting you do your work with HTML
- being friendly for documentation purposes
So I created this CMS that follows those guidelines. Note that this CMS is not begineer friendly. If you want to be able to write pages with it, you should at least know and understand HTML.

## [PL] O co chodzi w tym projekcie?
Chciałem stworzyć lekki system do zarządzania treścią spełniający następujące wymagania:
- zużywanie niewielkiej ilości zasobów serwera
- poprawne działanie na każdym kliencie (żeby nie mroziło twojego komputera z Windowsem 95 oraz nie dezorientowało twojego starego Firefoxa który akurat jeszcze wspiera Windowsa XP)
- posiadanie bardzo prostej i przejrzystej nawigacji między najważniejszymi podstronami
- bycie dostosowywalnym - wszystko możesz zrobić HTMLem
- bycie przyjaznym dla celów dokumentacji
No więc stworzyłem tego oto CMSa, który spełnia te wymagania. Należy pamiętać, że nie jest on przyjazny dla nowicjuszy. Jeśli chcesz tworzyć w nim podstrony, powinieneś przynajmniej znać i rozumieć HTMLa.

## [EN] How to get started?
First of all, download the CMS. You'll want to put it on your server (you'll need an Apache server with PHP 8 and MySQLi installed). Note that you also need a MySQL 8 server (older versions or MariaDB might work, didn't check). Run installer.php from your browser. Fill in the form with correct data. Start the installation. You'll see the installation log. If there are no errors, we're home. Follow the instructions - you'll need to copy the content of config.php file and put it on your WWW server in the same folder as the rest of your files. You should also copy the database script and run it on your server. If you can't, contact your server's administrator.  
Once installed, you can delete installer.php. You can then login to the admin panel (admin.php). Take your time to look around. Those are the things you might want to know:
- the admin panel lets you manage your account, manage the menu, manage the pages and manage the users
- there are two permissions in the panel - editor, which is required for page management, and admin, which is required for user and menu management
- all the pages are written directly in HTML, so your editors will have to know HTML
- if you want to put a link to a specific page in your menu, the link should lead to `index.php?pageid=X` where X is the ID of your page

## [PL] Od czego zacząć?
Po pierwsze, pobierz tego CMSa. Będziesz musiał umieścić jego pliki na serwerze (wymagany jest serwer Apache z PHP 8 i MySQLi). Pamiętaj, że będziesz również potrzebował serwera MySQL 8 (starsze wersje lub MariaDB mogą działać, ale nie sprawdzałem). Włącz w przeglądarce plik installer.php. Wypełnij formularz właściwymi danymi. Rozpocznij instalację. Po zakończeniu zobaczysz log instalacji. Jeśli nie ma żadnych błędów, jesteśmy w domu. Postępuj zgodnie z instrukcjami - będziesz musiał skopiować treść pliku config.php i umieścić ją na serwerze WWW w tym samym folderze, w którym znajduje się reszta plików. Powinieneś także skopiować skrypt bazy danych i uruchomić go na serwerze. Jeśli nie możesz, skontaktuj się z administratorem serwera.  
Po instalacji możesz usunąć plik installer.php. Możesz się zalogować do panelu administratora (admin.php). Poświęć trochę czasu na rozejrzenie się dokoła. Tutaj masz kilka rzeczy, które mogą Ci się przydać:
- panel administratora pozwala na zarządzanie twoim kontem, zarządzanie wpisami w menu, zarządzanie podstronami i zarządzanie użytkownikami
- są dwa uprawnienia w panelu - edytor, które jest wymagane do zarządzania podstronami, a także admin, które jest wymagane do zarządzania użytkownikami i wpisami w menu
- wszystkie podstrony pisane są bezpośrednio w HTMLu, więc twoi edytorzy powinni znać HTMLa
- jeśli chcesz umieścić w menu link do konkretnej podstrony, powinien on prowadzić do `index.php?pageid=X`, gdzie X to ID wybranej strony
