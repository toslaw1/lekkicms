<?php

    if(isset($_POST['submit']))
    {
        // Zapisanie do zmiennych wartości pól z formularza
        
        echo 'Trwa instalacja LekkiegoCMSa...<br>';
        
        // Informacje o stronie
        $sitename = $_POST['sitename'];
        $description = $_POST['description'];
        $keywords = $_POST['keywords'];
        $robots = $_POST['robots'];
        $footer_text = $_POST['footer'];
        
        // Dane do połączenia z bazą danych
        $master_db_server = $_POST['dbserver'];
        $master_db_user = $_POST['dbuser'];
        $master_db_pass = $_POST['dbpass'];
        $master_db_name = $_POST['dbname'];
        $table_prefix = $_POST['tableprefix'];
        
        // Ustawienia stanu strony
        $unavailable_text = $_POST['unavailabletext'];
        $lackingpage_text = $_POST['lackingpagetext'];
        
        // Dane konta administratora strony
        $username = $_POST['username'];
        $userpass1 = $_POST['userpass1'];
        $userpass2 = $_POST['userpass2'];
        
        // Generowanie pliku konfiguracyjnego
        
        echo "Generowanie pliku konfiguracyjnego..............................";
        
        // Sprawdzenie poprawności konfiguracji
        
        $config_errors = "";
        $config_warnings = "";
        $config_notices = "";
        
        if(empty($master_db_server) || empty($master_db_user) || empty($master_db_name))
        {
            $config_errors .= '<font color="red">BŁĄÐ: </font>Konfiguracja połączenia z bazą danych jest niekompletna. Strona nie będzie działać prawidłowo do chwili jej uzupełnienia w pliku konfiguracyjnym.<br>';
        }
        
        if(empty($sitename) || empty($footer_text) || empty($unavailable_text) || empty($lackingpage_text))
        {
            $config_warnings .= '<font color="orange">OSTRZEŻENIE: </font>Co najmniej z kluczowych pól w konfiguracji (nazwa strony, tekst stopki, tekst informacji o niedostępności strony, tekst o braku podstrony o danym ID) pozostawione zostało puste. Można to naprawić dopisując treść ręcznie do konfiguracji.<br>';
        }
        
        if(empty($master_db_pass))
        {
            $config_warnings .= '<font color="orange">OSTRZEŻENIE: </font>Hasło do bazy danych pozostawione puste. Nie zaleca się, by pozwalać na dostęp do konta MySQL bez hasła. Aby naprawić należy nadać kontu hasło, a następnie zmodyfikować konfigurację strony.<br>';
        }
        
        if(empty($description) || empty($keywords) || empty($robots))
        {
            $config_notices .= '<font color="blue">INFORMACJA: </font>Co najmniej jedno z mało ważnych pól w konfiguracji (meta description, meta keywords, meta robots) pominięto. Nie są one konieczne, ale gdyby zaszła potrzeba ich dodania, można to zrobić edytując plik konfiguracji.<br>';
        }
        
        if(empty($description) || empty($keywords) || empty($robots))
        {
            $config_notices .= '<font color="blue">INFORMACJA: </font>Co najmniej jedno z mało ważnych pól w konfiguracji (meta description, meta keywords, meta robots) pominięto. Nie są one konieczne, ale gdyby zaszła potrzeba ich dodania, można to zrobić edytując plik konfiguracji.<br>';
        }
        
        if(empty($table_prefix))
        {
            $config_notices .= '<font color="blue">INFORMACJA: </font>Prefiks do tabeli pusty. Może on taki być, jeśli z tej bazy będzie korzystać tylko jedna instancja LekkiegoCMSa. Kolejna instancja będzie wymagała prefiksu. Jeśli tej samej bazy używa więcej aplikacji, warto rozważyć użycie prefiksu. Jeśli chcesz jednak użyć prefiksu, można użyć aktualnego pliku konfiguracyjnego, zmodyfikować w nim prefiks tabeli, a następnie użyć skryptu instalacyjnego z podanym tylko prefiksem tabeli do wygenerowania pliku SQL.<br>';
        }
        
$config=<<<END
&#60?php

    /* Plik konfiguracji LekkiegoCMSa
    * Tutaj znajduje się konfiguracja potrzebna do startu.
    * Ważne informacje:
    * 1.   LekkiCMS działa na PHP w wersji 8.x.
    * 2.   LekkiCMS wymaga zainstalowanej biblioteki MySQLi na serwerze PHP.
    *      Do jego działania niezbędny jest serwer MySQL.
    * 3.   \x36debug_mode ustawiony na true powoduje wyświetlanie informacji technicznych,
    *      np. zawartości tablicy \x36_SESSION w panelu admina czy informacji o błędach
    *      połączenia z bazą danych. W środowisku produkcyjnym to ustawienie powinno
    *      ZAWSZE być ustawione na false.
    */ 

    class Config
    {
        // Informacje o stronie
        public static &#36sitename = '$sitename';
        public static &#36description = '$description';
        public static &#36keywords = '$keywords';
        public static &#36robots = '$robots';
        public static &#36footer_text = '$footer_text';
        
        // Dane do połączenia z bazą danych
        public static &#36master_db_server = '$master_db_server';
        public static &#36master_db_user = '$master_db_user';
        public static &#36master_db_pass = '$master_db_pass';
        public static &#36master_db_name = '$master_db_name';
        public static &#36table_prefix = '$table_prefix';
        
        // Ustawienia stanu strony
        public static &#36site_available = true;
        public static &#36unavailable_text = '$unavailable_text';
        public static &#36lackingpage_text = '$lackingpage_text';
        public static &#36debug_mode = false;
        
        // Ustawienia związane ze złożonością haseł
        public static &#36allow_unsafe_passwords = false;
    }
?&#62
END;
        echo '<font color="green">OK</font><br>';
        echo $config_errors;
        echo $config_warnings;
        echo $config_notices;
        
        echo 'Konfiguracja wygląda następująco:<br>';
        echo '<pre>'.$config.'</pre>';
        echo 'Należy ją zapisać na serwerze w folderze z pozostałymi plikami LekkiegoCMSa.<br>';
        
        // Generowanie pliku SQL
        
        echo 'Generowanie pliku SQL..............................';
        
        if(!preg_match('/[a-z]/', $userpass1) || !preg_match('/[A-Z]/', $userpass1) || !preg_match('/[0-9]/', $userpass1) || strlen($userpass1) < 12)
        {
            // Nie spełnia, zapisać ostrzeżenie
            $password_safe = false;
        }
        else
        {
            $password_safe = true;
        }
        
        // Sprawdzanie, czy hasła są takie same oraz hasło nie jest puste
        
        if(!empty($userpass1) && $userpass1 == $userpass2)
        {
            // Są - można generować skrypt
            
            $password = password_hash($userpass1, PASSWORD_DEFAULT);
        
$sql=<<<END
-- Plik SQL do wykorzystania w bazie danych używanej przez LekkiegoCMSa

CREATE TABLE %smenu(
    id INT AUTO_INCREMENT PRIMARY KEY,
    text VARCHAR(50) NOT NULL,
    link VARCHAR(250) NOT NULL,
    active TINYINT(1) NOT NULL
);

CREATE TABLE %spages(
    id INT AUTO_INCREMENT PRIMARY KEY,
    text TEXT NOT NULL,
    active TINYINT(1) NOT NULL
);

CREATE TABLE %susers(
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(40) NOT NULL,
    password VARCHAR(255) NOT NULL,
    editor TINYINT(1) NOT NULL,
    admin TINYINT(1) NOT NULL,
    active TINYINT(1) NOT NULL
);

INSERT INTO %spages (text, active) VALUES ('&#60h1&#62Strona główna&#60/h1&#62\n&#60p&#62Uzupełnij ją w miarę swoich potrzeb&#60/p&#62', 1);

INSERT INTO %susers (username, password, editor, admin, active) VALUES ('%s', '%s', 1, 1, 1);

END;
            echo '<font color="green">OK</font><br>';
            if(!$password_safe) echo '<font color="yellow">OSTRZEŻENIE: </font>Hasło nie spełnia wymagań co do złożoności. Przy zmianie hasła spełnienie tych wymagań będzie wymuszone, chyba, że ustawisz w konfiguracji flagę $allow_unsafe_passwords na true. Po dokończeniu instalacji rozważ zmianę hasła.<br>';
            echo 'Skrypt SQL wygląda następująco:<br>';
            echo '<pre>'.sprintf($sql, $table_prefix, $table_prefix, $table_prefix, $table_prefix, $table_prefix, $username, $password).'</pre>';
            echo 'Należy go zaimportować do bazy o nazwie podanej w konfiguracji.';
            exit();
        }
        else
        {
            echo '<font color="red">BŁĄÐ</font><br>';
            echo '<font color="red">BŁĄÐ: </font>Hasła nie są takie same lub są puste. Aby wygenerować skrypt SQL ponownie, uruchom ponownie instalator i wypełnij ponownie informacje dotyczące konta administratora strony.';
            exit();
        }
    }
   
echo<<<END
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Instalator LekkiegoCMSa</title>
    </head>
    <body>
    <h1>Instalator LekkiegoCMSa</h1>
    <p>Witaj w instalatorze LekkiegoCMSa! Poniższy formularz pozwoli Ci wygenerować plik konfiguracyjny (config.php), a także utworzyć tabele w bazie danych. Życzymy miłej pracy z LekkimCMSem!</p>
    <p><b>Uwaga: </b>Pamiętaj, że jeśli do konfiguracji ma trafić znacznik HTML, powinien on być zastąpiony odpowiednim kodem (znaki &# i kod ASCII dziesiętnie).</p>
    <form method="post" action="">
    <fieldset>
        <h3>Informacje o stronie</h3>
        Nazwa strony (wyświetlana na górze, nad menu):<br>
        <input type="text" name="sitename"><br>
        Opis strony (znacznik meta description):<br>
        <input type="text" name="description"><br>
        Słowa kluczowe (znacznik meta keywords):<br>
        <input type="text" name="keywords"><br>
        Informacje dla robotów wyszukiwarek (znacznik meta robots; obecnie chyba nie ma robotów w sieci TOSNET, więc raczej mało ważne):<br>
        <input type="text" name="robots" value="noindex, nofollow"><br>
        Tekst stopki:<br>
        <input type="text" name="footer"><br>
    </fieldset>
    <fieldset>
        <h3>Dane logowania do bazy danych</h3>
        Aby strona działała prawidłowo, konto MySQL użyte na niej powinno posiadać uprawnienia do odczytu, zapisu, zmiany i usuwania danych w tabelach.<br>
        Adres serwera bazy danych:<br>
        <input type="text" name="dbserver"><br>
        Nazwa użytkownika bazy danych:<br>
        <input type="text" name="dbuser"><br>
        Hasło do bazy danych:<br>
        <input type="password" name="dbpass"><br>
        Nazwa bazy danych:<br>
        <input type="text" name="dbname"><br>
        Prefiks do tabel (może być pusty; wymagany w przypadku używania jednej bazy przez kilka instancji LekkiegoCMSa lub inne aplikacje):<br>
        <input type="text" name="tableprefix" value="lcms_"><br>
    </fieldset>
    <fieldset>
        <h3>Teksty informujące o błędach</h3>
        Stronę można wyłączyć w konfiguracji zmieniając wartość atrybutu site_available na false. Można również włączyć tryb debugowania (wyświetlający m. in. zmienne sesyjne w panelu administratora na górze), ustawiając atrybut debug_mode na true. Poniższe pola definiują teksty, które pokażą się, gdy strona będzie wyłączona lub nie będzie się można połączyć z bazą danych, a także przy braku podstrony o danym ID.
        Tekst informujący o tym, że strona jest aktualnie niedostępna:<br>
        <textarea name="unavailabletext" cols="30" rows="5">Strona jest tymczasowo niedostępna.</textarea><br>
        Tekst informujący o braku podstrony o podanym ID:<br>
        <textarea name="lackingpagetext" cols="30" rows="5">Podtrona o podanym ID nie istnieje. &#38#60br&#38#62 &#38#60a href="index.php"&#38#62Powrót do strony głównej&#38#60/a&#38#62</textarea>
    </fieldset>
    <fieldset>
        <h3>Dane konta administratora strony</h3>
        Nazwa użytkownika:<br>
        <input type="text" name="username"><br>
        Hasło:<br>
        <input type="password" name="userpass1"></br>
        Powtórz hasło:<br>
        <input type="password" name="userpass2"></br>
    </fieldset>
    <input type="submit" name="submit" value="Instaluj">
    </form>
END;
?>
