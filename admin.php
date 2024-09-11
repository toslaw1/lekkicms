<?php
    /*
     * DO PARAMETRYZACJI ZAPYTAŃ:
     * $query = "SELECT X FROM Y WHERE a='%s' AND b='%s'";
     * $con->query(sprintf($query, $con->real_escape_string($a), $con->real_escape_string($b)));
     * Parametryzować wszystko, nawet Config::$table_prefix
     */
    
    if(!include_once("config.php"))
    {
        header('Location: installer.php');
        exit();
    }
    
    session_start();
    
    // Połączenie z bazą danych
    
    if(!Config::$debug_mode) mysqli_report(MYSQLI_REPORT_STRICT);
    
    try
    {
        $con = new mysqli(Config::$master_db_server, Config::$master_db_user, Config::$master_db_pass, Config::$master_db_name);
    }
    catch(Exception $e)
    {
        exit(Config::$unavailable_text);
    }
    
    if(isset($_GET['module']))
    {
        $module = $_GET['module'];
    }
    else
    {
        $module = 0;
    }
    
    // Wykonanie operacji zleconych wcześniej
    
    if(isset($_GET['logout']))
    {
        session_unset();
        header('Location: admin.php');
    }
    
    if(isset($_POST['m0loginsubmit']))
    {
        // Logowanie
        
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $query = "SELECT id, username, password, editor, admin, active FROM %susers WHERE username='%s'";
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($username)));
        
        if($result->num_rows > 0)
        {
            // Jest taki użytkownik
            
            $row = $result->fetch_assoc();
            
            // Sprawdzanie, czy hasło się zgadza
            
            if(password_verify($password, $row['password']))
            {
                // Hasło się zgadza
                
                // Sprawdzanie, czy konto jest włączone
                
                if($row['active'] == 1)
                {
                    // Udane logowanie - konto jest włączone
                    
                    $_SESSION['userid'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['editor'] = $row['editor'];
                    $_SESSION['admin'] = $row['admin'];
                }
                else
                {
                    // Konto jest wyłączone
                    
                    $m0loginerror = "Konto jest wyłączone";
                }
            }
            else
            {
                // Hasło się nie zgadza
                
                $m0loginerror = "Błędne hasło";
            }
        }
        else
        {
            // Nie ma takiego użytkownika
            
            $m0loginerror = "Błędna nazwa użytkownika";
        }
    }
    else if(isset($_POST['m0passchsubmit']))
    {
        // Zmiana hasła
        
        $currentpass = $_POST['currentpass'];
        $newpass1 = $_POST['newpass1'];
        $newpass2 = $_POST['newpass2'];
        
        // Sprawdzenie, czy nowe hasła się zgadzają
        
        if($newpass1 == $newpass2)
        {
            // Zgadzają się
            
            // Sprawdzanie, czy hasło spełnia wymagania co do złożoności
            
            if((!preg_match('/[a-z]/', $newpass1) || !preg_match('/[A-Z]/', $newpass1) || !preg_match('/[0-9]/', $newpass1) || strlen($newpass1) < 12) && !Config::$allow_unsafe_passwords)
            {
                // Nie spełnia, nie zmieniać
                $m0passcherror = "Nowe hasło nie spełnia wymagań co do złożoności";
            }
            else
            {
                // Spełnia, można zmienić
                
                $query = "SELECT id, password FROM %susers WHERE username='%s'";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['username'])));
                
                if($result->num_rows > 0)
                {
                    // Znaleziono użytkownika
                    
                    $row = $result->fetch_assoc();
                    
                    // Sprawdzenie, czy obecne hasło jest poprawne
                    
                    if(password_verify($currentpass, $row['password']))
                    {
                        // Poprawne, można zmienić
                        
                        $password = password_hash($newpass1, PASSWORD_DEFAULT);
                        
                        $query = "UPDATE %susers SET password='%s' WHERE id=%s";
                        
                        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($password), $con->real_escape_string($row['id'])));
                        
                        $m0passchsuccess = "Hasło zostało zmienione";
                    }
                    else
                    {
                        // Niepoprawne, nie zmieniać
                        
                        $m0passcherror = "Obecne hasło jest błędne";
                    }
                }
                else
                {
                    
                    $m0passcherror = "Nie znaleziono użytkownika w bazie";
                }
            }
        }
        else
        {
            // Nie zgadzają się
            
            $m0passcherror = "Nowe hasła nie są takie same";
        }
    }
    else if(isset($_POST['m1crsubmit']))
    {
        // Tworzenie podstrony
        
        // Sprawdzanie uprawnień z bazą danych
        
        $query = "SELECT editor FROM %susers WHERE id=%s";
        
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['userid'])));
        
        if($result->num_rows > 0)
        {
            // Znaleziono użytkownika
            
            $row = $result->fetch_assoc();
            
            if($row['editor'] == 1)
            {
                // Użytkownik posiada uprawnienia, można wykonać
                $text = $_POST['text'];
                $active = isset($_POST['active']) ? 1 : 0;
                
                $query = "INSERT INTO %spages (text, active) VALUES ('%s', %s)";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($text), $con->real_escape_string($active)));
                
                header('Location: admin.php?module=1&crpagesucc');
                exit();
            }
            else
            {
                // Użytkownik nie posiada uprawnień, nie wykonywać
                
                header('Location: admin.php?module=1&crpagefail');
                exit();
            }
        }
        else
        {
            // Nie znaleziono użytkownika, nie wykonywać
            header('Location: admin.php?module=1&crpagefail');
            exit();
            
        }
    }
    else if(isset($_POST['m1edsubmit']))
    {
        // Edycja podstrony
        
        // Sprawdzanie uprawnień z bazą danych
        
        $query = "SELECT editor FROM %susers WHERE id=%s";
        
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['userid'])));
        
        if($result->num_rows > 0)
        {
            // Znaleziono użytkownika
            
            $row = $result->fetch_assoc();
            
            if($row['editor'] == 1)
            {
                // Użytkownik posiada uprawnienia, można wykonać
                $text = $_POST['text'];
                $active = isset($_POST['active']) ? 1 : 0;
                $pageid = $_POST['pageid'];
                
                $query = "UPDATE %spages SET text='%s', active=%s WHERE id=%s";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($text), $con->real_escape_string($active), $con->real_escape_string($pageid)));
                
                header('Location: admin.php?module=1&edpagesucc');
                exit();
            }
            else
            {
                // Użytkownik nie posiada uprawnień, nie wykonywać
                
                header('Location: admin.php?module=1&edpagefail');
                exit();
            }
        }
        else
        {
            // Nie znaleziono użytkownika, nie wykonywać
            header('Location: admin.php?module=1&edpagefail');
            exit();
            
        }
    }
    else if(isset($_POST["m1delsubmit"]))
    {
        // Usuwanie podstrony
        
        // Sprawdzanie uprawnień z bazą danych
        
        $query = "SELECT editor FROM %susers WHERE id=%s";
        
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['userid'])));
        
        if($result->num_rows > 0)
        {
            // Znaleziono użytkownika
            
            $row = $result->fetch_assoc();
            
            if($row['editor'] == 1)
            {
                // Użytkownik posiada uprawnienia, można wykonać
                $query = "DELETE FROM %spages WHERE id=%s";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_POST['pageid'])));
                
                header('Location: admin.php?module=1&delpagesucc');
                exit();
            }
            else
            {
                // Użytkownik nie posiada uprawnień, nie wykonywać
                
                header('Location: admin.php?module=1&delpagefail');
                exit();
            }
        }
        else
        {
            // Nie znaleziono użytkownika, nie wykonywać
            header('Location: admin.php?module=1&delpagefail');
            exit();
        }
    }
    else if(isset($_POST['m2crsubmit']))
    {
        // Tworzenie użytkownika
        
        // Sprawdzanie uprawnień z bazą danych
        
        $query = "SELECT admin FROM %susers WHERE id=%s";
        
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['userid'])));
        
        if($result->num_rows > 0)
        {
            // Znaleziono użytkownika
            
            $row = $result->fetch_assoc();
            
            if($row['admin'] == 1)
            {
                // Użytkownik posiada uprawnienia, można wykonać
                
                $username = $_POST['username'];
                $pass1 = $_POST['pass1'];
                $pass2 = $_POST['pass2'];
                $editor = isset($_POST['editor']) ? 1 : 0;
                $admin = isset($_POST['admin']) ? 1 : 0;
                $active = isset($_POST['active']) ? 1 : 0;
                
                // Sprawdzanie, czy hasło spełnia wymagania co do złożoności
                
                if((!preg_match('/[a-z]/', $pass1) || !preg_match('/[A-Z]/', $pass1) || !preg_match('/[0-9]/', $pass1) || strlen($pass1) < 12) && !Config::$allow_unsafe_passwords)
                {
                    // Nie spełnia, nie tworzyć konta
                    header('Location: admin.php?module=2&userpass2fail');
                    exit();
                }
                
                // Sprawdzanie, czy hasła są takie same
                
                if($pass1 == $pass2)
                {
                    
                    // Hasła są takie same, można hashować
                    
                    $password = password_hash($pass1, PASSWORD_DEFAULT);
                    
                    // Sprawdzanie, czy użytkownik o podanej nazwie już istnieje
                    
                    $query = "SELECT id FROM %susers WHERE username='%s'";
                    
                    $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($username)));
                    
                    if($result->num_rows == 0)
                    {
                        // Nie ma takiego użytkownika, można tworzyć
                        
                        $query = "INSERT INTO %susers (username, password, editor, admin, active) VALUES ('%s', '%s', %s, %s, %s)";
                        
                        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($username), $con->real_escape_string($password),
                                                      $con->real_escape_string($editor), $con->real_escape_string($admin), $con->real_escape_string($active)));
                        
                        header('Location: admin.php?module=2&crusersucc');
                        exit();
                    }
                    else
                    {
                        // Nazwa zajęta
                        
                        header('Location: admin.php?module=2&usernamefail');
                        exit();
                    }
                }
                else
                {
                    // Hasła są różne, nie można dodać użytkownika
                    
                    header('Location: admin.php?module=2&userpassfail');
                    exit();
                }
            }
            else
            {
                // Użytkownik nie posiada uprawnień, nie wykonywać
                
                header('Location: admin.php?module=2&cruserfail');
                exit();
            }
        }
        else
        {
            // Nie znaleziono użytkownika wykonującego operację, nie wykonywać
            
            header('Location: admin.php?module=1&cruserfail');
            exit();
        }
    }
    else if(isset($_POST['m2ed1submit']))
    {
        // Zmiana danych innego użytkownika (z wyjątkiem hasła)
        
        // Sprawdzanie uprawnień z bazą danych
        
        $query = "SELECT admin FROM %susers WHERE id=%s";
        
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['userid'])));
        
        if($result->num_rows > 0)
        {
            // Znaleziono użytkownika
            
            $row = $result->fetch_assoc();
            
            if($row['admin'] == 1)
            {
                // Użytkownik posiada uprawnienia, można wykonać
                
                $userid = $_POST['userid'];
                $username = $_POST['username'];
                $editor = isset($_POST['editor']) ? 1 : 0;
                $admin = isset($_POST['admin']) ? 1 : 0;
                $active = isset($_POST['active']) ? 1 : 0;
                
                // Sprawdzanie, czy użytkownik o podanej nazwie już istnieje
                    
                $query = "SELECT id, admin FROM %susers WHERE username='%s' AND id!=%s";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($username), $con->real_escape_string($userid)));
                
                if($result->num_rows == 0)
                {
                    // Nie ma takiego użytkownika lub jest to ten sam, co edytowany
                    
                    $row = $result->fetch_assoc();
                    
                    // Sprawdzanie, czy ktoś nie próbuje sobie samemu odebrać uprawnień administratora/wyłączyć własnego konta
                    
                    if(($_SESSION['userid'] != $userid || $admin != $row['admin']) && ($_SESSION['userid'] != $userid || $active == 1))
                    {
                        // Nikt nie próbuje sobie zabrać uprawnień administratora/wyłączyć własnego konta, można wykonać
                        
                        $query = "UPDATE %susers SET username='%s', editor=%s, admin=%s, active=%s WHERE id=%s";
                        
                        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($username), $con->real_escape_string($editor),
                                                      $con->real_escape_string($admin), $con->real_escape_string($active), $con->real_escape_string($userid)));
                        
                        header('Location: admin.php?module=2&edusersucc');
                        exit();
                        
                    }
                    else
                    {
                        // Ktoś próbuje sobie zabrać uprawnienia administratora/wyłączyć własne konto, nie wykonywać
                        
                        header('Location: admin.php?module=2&selfdestructfail');
                        exit();
                    }
                }
                else
                {
                    // Nazwa zajęta
                        
                    header('Location: admin.php?module=2&usernamefail');
                    exit();
                }
            }
            else
            {
                // Użytkownik nie posiada uprawnień, nie wykonywać
                
                header('Location: admin.php?module=2&eduserfail');
                exit();
            }
        }
        else
        {
            // Nie znaleziono użytkownika wykonującego operację, nie wykonywać
            header('Location: admin.php?module=1&eduserfail');
            exit();
        }
    }
    else if(isset($_POST['m2ed2submit']))
    {
        // Zmiana hasła innego użytkownika
        
        // Sprawdzanie uprawnień z bazą danych
        
        $query = "SELECT admin FROM %susers WHERE id=%s";
        
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['userid'])));
        
        if($result->num_rows > 0)
        {
            // Znaleziono użytkownika
            
            $row = $result->fetch_assoc();
            
            if($row['admin'] == 1)
            {
                // Użytkownik posiada uprawnienia
                
                $userid = $_POST['userid'];
                $newpass1 = $_POST['newpass1'];
                $newpass2 = $_POST['newpass2'];
                
                // Sprawdzanie, czy hasło spełnia wymagania co do złożoności
                
                if((!preg_match('/[a-z]/', $newpass1) || !preg_match('/[A-Z]/', $newpass1) || !preg_match('/[0-9]/', $newpass1) || strlen($newpass1) < 12) && !Config::$allow_unsafe_passwords)
                {
                    // Nie spełnia, nie zmieniać
                    header('Location: admin.php?module=2&userpass2fail');
                    exit();
                }
                
                // Sprawdzenie, czy hasła są takie same
                
                if($newpass1 == $newpass2)
                {
                    // Są takie same, można wykonać
                    
                    $password = password_hash($newpass1, PASSWORD_DEFAULT);
                    
                    $query = "UPDATE %susers SET password='%s' WHERE id=%s";
                    
                    $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($password), $con->real_escape_string($userid)));
                    
                    header('Location: admin.php?module=2&edusersucc');
                    exit();
                }
                else
                {
                    // Nie są takie same, nie wykonywać
                    
                    header('Location: admin.php?module=2&userpassfail');
                    exit();
                }
            }
            else
            {
                // Użytkownik nie posiada uprawnień, nie wykonywać
                
                header('Location: admin.php?module=2&eduserfail');
                exit();
            }
        }
        else
        {
            // Nie znaleziono użytkownika wykonującego operację, nie wykonywać
            header('Location: admin.php?module=1&eduserfail');
            exit();
        }
        
    }
    else if(isset($_POST['m2delsubmit']))
    {
        // Usuwanie użytkownika
        
        // Sprawdzanie uprawnień z bazą danych
        
        $query = "SELECT admin FROM %susers WHERE id=%s";
        
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['userid'])));;
        
        if($result->num_rows > 0)
        {
            // Znaleziono użytkownika
            
            $row = $result->fetch_assoc();
            
            if($row['admin'] == 1)
            {
                // Użytkownik posiada uprawnienia
                
                $userid = $_POST['userid'];
                
                // Sprawdzenie, czy użytkownik nie próbuje usunąć samego siebie
                
                if($userid != $_SESSION['userid'])
                {
                    // Inny użytkownik, można usunąć
                    
                    $query = "DELETE FROM %susers WHERE id=%s";
                    
                    $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($userid)));
                    
                    header('Location: admin.php?module=2&delusersucc');
                    exit();
                }
                else
                {
                    header('Location: admin.php?module=2&selfdestructfail');
                    exit();
                }
            }
            else
            {
                // Użytkownik nie posiada uprawnień, nie wykonywać
                
                header('Location: admin.php?module=2&deluserfail');
                exit();
            }
        }
        else
        {
            // Nie znaleziono użytkownika wykonującego operację, nie wykonywać
            header('Location: admin.php?module=1&deluserfail');
            exit();
        }
    }
    else if(isset($_POST['m3crsubmit']))
    {
        // Tworzenie wpisu w menu
        
        // Sprawdzanie uprawnień z bazą danych
        
        $query = "SELECT admin FROM %susers WHERE id=%s";
        
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['userid'])));
        
        if($result->num_rows > 0)
        {
            // Znaleziono użytkownika
            
            $row = $result->fetch_assoc();
            
            if($row['admin'] == 1)
            {
                // Użytkownik posiada uprawnienia, można utworzyć wpis
                
                $text = $_POST['text'];
                $link = $_POST['link'];
                $active = isset($_POST['active']) ? 1 : 0;
                
                $query = "INSERT INTO %smenu (text, link, active) VALUES ('%s', '%s', %s)";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($text), $con->real_escape_string($link), $con->real_escape_string($active)));
                
                header('Location: admin.php?module=3&crentrysucc');
                exit();
                
            }
            else
            {
                // Użytkownik nie posiada uprawnień, nie wykonywać
                
                header('Location: admin.php?module=3&crentryfail');
                exit();
            }
        }
        else
        {
            // Nie znaleziono użytkownika wykonującego operację, nie wykonywać
            
            header('Location: admin.php?module=1&crentryfail');
            exit();
        }
    }
    else if(isset($_POST['m3edsubmit']))
    {
        // Modyfikowanie wpisu w menu
        
        // Sprawdzanie uprawnień z bazą danych
        
        $query = "SELECT admin FROM %susers WHERE id=%s";
        
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['userid'])));
        
        if($result->num_rows > 0)
        {
            // Znaleziono użytkownika
            
            $row = $result->fetch_assoc();
            
            if($row['admin'] == 1)
            {
                // Użytkownik posiada uprawnienia, można zmodyfikować wpis
                
                $text = $_POST['text'];
                $link = $_POST['link'];
                $active = isset($_POST['active']) ? 1 : 0;
                $editentry = $_POST['editentry'];
                
                $query = "UPDATE %smenu SET text='%s', link='%s', active=%s WHERE id=%s";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($text), $con->real_escape_string($link),
                                              $con->real_escape_string($active), $con->real_escape_string($editentry)));
                
                header('Location: admin.php?module=3&edentrysucc');
                exit();
                
            }
            else
            {
                // Użytkownik nie posiada uprawnień, nie wykonywać
                
                header('Location: admin.php?module=3&edentryfail');
                exit();
            }
        }
        else
        {
            // Nie znaleziono użytkownika wykonującego operację, nie wykonywać
            
            header('Location: admin.php?module=1&edentryfail');
            exit();
        }
    }
    else if(isset($_POST['m3delsubmit']))
    {
        // Usuwanie wpisu w menu
        
        // Sprawdzanie uprawnień z bazą danych
        
        $query = "SELECT admin FROM %susers WHERE id=%s";
        
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_SESSION['userid'])));
        
        if($result->num_rows > 0)
        {
            // Znaleziono użytkownika
            
            $row = $result->fetch_assoc();
            
            if($row['admin'] == 1)
            {
                // Użytkownik posiada uprawnienia, można utworzyć wpis
                
                $entryid = $_POST['entryid'];
                
                $query = "DELETE FROM %smenu WHERE id=%s";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($entryid)));
                
                header('Location: admin.php?module=3&delentrysucc');
                exit();
                
            }
            else
            {
                // Użytkownik nie posiada uprawnień, nie wykonywać
                
                header('Location: admin.php?module=3&delentryfail');
                exit();
            }
        }
        else
        {
            // Nie znaleziono użytkownika wykonującego operację, nie wykonywać
            
            header('Location: admin.php?module=1&delentryfail');
            exit();
        }
    }
    
    // Strona
    
echo<<<END
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Panel administratora - LekkiCMS</title>
    </head>
    <body>
END;
    
    // Jeśli włączony jest tryb debugowania, wyświetl zawartość tablicy $_SESSION
    
    if(Config::$debug_mode)
    {
        echo '<pre>';
        var_dump($_SESSION);
        echo '</pre>';
    }
    
echo<<<END
    <div id="header">
        <h1>Panel administratora - LekkiCMS</h1>
        <table border="0">
        <tr>
        <td><a href="index.php">Strona główna</a></td>
END;

    if(isset($_SESSION['userid']))
    {
        echo '<td><a href="admin.php?module=0">Konto</a></td>';
        
        if($_SESSION['editor'])
        {
            echo '<td><a href="admin.php?module=1">Lista podstron</a></td>';
        }
        
        if($_SESSION['admin'])
        {
            echo '<td><a href="admin.php?module=2">Zarządzanie użytkownikami</a></td>';
            echo '<td><a href="admin.php?module=3">Zarządzanie wpisami w menu</a></td>';
        }
        
        echo '<td><a href="admin.php?logout">Wyloguj się</a></td>';
    }
    
echo<<<END
        </tr>
        </table>
        <hr>
    </div>
    <div id="main">
END;

    /* Część główna strony - wyświetla się odpowiedni moduł:
     * 0 - konto użytkownika - logowanie, zarządzanie własnym kontem
     * 1 - edytor - wyświetlanie listy, tworzenie, edytowanie i usuwanie podstron (CRUD)
     * 2 - zarządzanie użytkownikami - wyświetlanie listy, zarządzanie, dodawanie, usuwanie
     */
    
    if($module == 0)
    {
        // Moduł konta użytkownika
        
        if(!isset($_SESSION['userid']))
        {
            // Logowanie
echo<<<END
        <h2>Logowanie do panelu administratora</h2>
        <form method="post" action="">
        Nazwa użytkownika:<br>
        <input type="text" name="username"><br>
        Hasło:<br>
        <input type="password" name="password"><br>
        <input type="submit" name="m0loginsubmit" value="Zaloguj">
END;
            if(isset($m0loginerror)) echo '<p><font color="red">'.$m0loginerror.'</font></p>';
echo<<<END
        </form>
END;
        }
        else
        {
            // Zarządzanie własnym kontem
echo<<<END
        <h2>Konto</h2>
        <h3>Zmiana hasła</h3>
        <p>
        Hasło musi mieć co najmniej 12 znaków, w tym wielkie i małe litery, cyfry i znaki specjalne.
        </p>
        <form method="post" action="">
        Obecne hasło:<br>
        <input type="password" name="currentpass"><br>
        Nowe hasło:<br>
        <input type="password" name="newpass1"><br>
        Powtórz nowe hasło:<br>
        <input type="password" name="newpass2"><br>
        <input type="submit" name="m0passchsubmit" value="Zmień hasło">
END;
            if(isset($m0passchsuccess)) echo '<p><font color="green">'.$m0passchsuccess.'</font></p>';
            if(isset($m0passcherror)) echo '<p><font color="red">'.$m0passcherror.'</font></p>';
echo<<<END
        </form>
END;
        }
    }
    else if($module == 1)
    {
        // Moduł edytowania (dostępny dla edytorów)
        
        if(!$_SESSION['editor'])
        {
            // Użytkownik nie jest edytorem
            
            echo '<p><font color="red">Odmowa dostępu.</font></p>'; 
        }
        else
        {
            // Użytkownik jest edytorem
            
            if(isset($_GET['edit']))
            {
                // Tryb edycji
                
                if($_GET['edit'] == 0)
                {
                    // Tworzenie nowej podstrony
echo<<<END
        <h2>Tworzenie nowej podstrony</h2>
        <form method="post" action="">
        <textarea name="text" cols="100" rows="30"></textarea><br>
        <label for="active"><input type="checkbox" id="active" name="active">Włącz podstronę</label><br>
        <input type="submit" name="m1crsubmit" value="Utwórz">
        </form>
END;
                }
                else
                {
                    // Edycja istniejącej podstrony
                    
                    $pageid = $_GET['edit'];
                    
                    $query = "SELECT text, active FROM %spages WHERE id=%s";
                    
                    $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($pageid)));
                    
                    if($result->num_rows > 0)
                    {
                        // Podstrona istnieje, można edytować
                        
                        $row = $result->fetch_assoc(); 
echo<<<END
        <h2>Edycja podstrony</h2>
        <form method="post" action="">
END;
        echo '<textarea name="text" cols="100" rows="30">'.$row['text'].'</textarea><br>';
        echo '<label for="active"><input type="checkbox" id="active" name="active"';
        
        if($row['active'] == 1)
        {
            echo ' checked';
        }
        
        echo '>Włącz podstronę</label><br>';
        echo '<input type="hidden" name="pageid" value="'.$_GET['edit'].'">';
echo<<<END
        <input type="submit" name="m1edsubmit" value="Zapisz">
        </form>
END;
                    }
                    else
                    {
                        // Nie ma takiej podstrony, nie można edytować
                        
                        echo '<p><font color="red">Podstrona o podanym ID nie istnieje</font></p>';
                    }
                }
            }
            else if(isset($_GET['delpage']))
            {
                // Potwierdzenie usunięcia strony
                
                $pageid = $_GET['delpage'];
                
                $query = "SELECT text FROM %spages WHERE id=%s";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($pageid)));
                
                if($result->num_rows > 0)
                {
                    // Podana strona istnieje, można usunąć
                    
                    $row = $result->fetch_assoc();
                    $text = $row['text'];
echo<<<END
        <h2>Usuwanie podstrony</h2>
        <form method="post" action="">
        Czy na pewno chcesz usunąć podstronę o ID $pageid?<br>
        Treść podstrony: <br>
        <pre>
        $text
        </pre> <br>
        <input type="hidden" name="pageid" value="$pageid">
        <input type="submit" name="m1delsubmit" value="Usuń"><br>
        <a href="admin.php?module=1">Anuluj</a>
        </form>
END;
                }
                else
                {
                    // Nie ma takiej podstrony, nie można usunąć
                        
                    echo '<p><font color="red">Podstrona o podanym ID nie istnieje</font></p>';
                }
            }
            else
            {
                // Lista podstron
                
                if(isset($_GET['crpagesucc'])) echo '<p><font color="green">Utworzono podstronę</font></p>';
                if(isset($_GET['edpagesucc'])) echo '<p><font color="green">Zmodyfikowano podstronę</font></p>';
                if(isset($_GET['delpagesucc'])) echo '<p><font color="green">Usunięto podstronę</font></p>';
                
                if(isset($_GET['crpagefail'])) echo '<p><font color="red">Nie udało się utworzyć podstrony</font></p>';
                if(isset($_GET['edpagefail'])) echo '<p><font color="red">Nie udało się zmodyfikować podstrony</font></p>';
                if(isset($_GET['delpagefail'])) echo '<p><font color="red">Nie udało się usunąć podstrony</font></p>';
                
echo<<<END
        <h2>Lista podstron</h2>
        <a href="admin.php?module=1&edit=0">Utwórz podstronę</a>
        <table border="1" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Treść</th>
            <th>Włączone</th>
            <th>Edytuj</th>
            <th>Usuń</th>
        </tr>
END;
                
                $query = "SELECT id, text, active FROM %spages";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix)));
                
                foreach($result as $row)
                {
                    $partoftext = substr($row["text"], 0, 30);
                    
                    echo '<tr>';
                    echo '<td>'.$row['id'].'</td>';
                    echo '<td><pre>'.$partoftext.'</pre></td>';
                    echo '<td>'.$row['active'].'</td>';
                    echo '<td><a href="admin.php?module=1&edit='.$row['id'].'">Edytuj</a></td>';
                    echo '<td><a href="admin.php?module=1&delpage='.$row['id'].'">Usuń</a></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
        }
    }
    else if($module == 2)
    {
        // Moduł zarządzania użytkownikami (dostępny dla adminów)
        
        if(!$_SESSION['admin'])
        {
            // Użytkownik nie jest adminem
            echo '<p><font color="red">Odmowa dostępu.</font></p>'; 
        }
        else
        {
            if(isset($_GET['createuser']))
            {
                // Tworzenie użytkownika
                
echo<<<END
        <h2>Tworzenie użytkownika</h2>
        <form method="post" action="">
        Nazwa użytkownika: <br>
        <input type="text" name="username"><br>
        Hasło:<br>
        <input type="password" name="pass1"><br>
        Powtórz hasło:<br>
        <input type="password" name="pass2"></br>
        Uprawnienia:<br>
        <label for="editor"><input type="checkbox" id="editor" name="editor">Edytor</label><br>
        <label for="admin"><input type="checkbox" id="admin" name="admin">Admin</label><br>
        Inne:<br>
        <label for="active"><input type="checkbox" id="active" name="active">Konto włączone</label><br>
        <input type="submit" name="m2crsubmit" value="Utwórz użytkownika">
        </form>
END;
                
            }
            else if(isset($_GET['edituser']))
            {
                // Edytowanie użytkownika
                
                $query = "SELECT username, editor, admin, active FROM %susers WHERE id=%s";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_GET['edituser'])));
                
                if($result->num_rows > 0)
                {
                    // Użytkownik o podanym ID istnieje, można edytować
                    
                    $row = $result->fetch_assoc();
                    
                    // Zmiana nazwy użytkownika, uprawnień i statusu włączenia konta
echo<<<END
        <h2>Edycja użytkownika</h2>
        <form method="post" action="">
        <h3>Parametry podstawowe</h3>
END;
                    echo 'Nazwa użytkownika:<br>';
                    echo '<input type="text" name="username" value="'.$row['username'].'"><br>';
                    echo 'Uprawnienia:<br>';
                    
                    echo '<label for="editor"><input type="checkbox" id="editor" name="editor"';
                    if($row['editor'] == 1) echo ' checked';
                    echo '>Edytor</label><br>';
                    
                    echo '<label for="admin"><input type="checkbox" id="admin" name="admin"';
                    if($row['admin'] == 1) echo ' checked';
                    echo '>Admin</label><br>';
                    
                    echo 'Inne:<br>';
                    echo '<label for="active"><input type="checkbox" id="active" name="active"';
                    if($row['active'] == 1) echo ' checked';
                    echo '>Konto włączone</label><br>';
                    
                    echo '<input type="hidden" name="userid" value="'.$_GET['edituser'].'">';
echo<<<END
        <input type="submit" name="m2ed1submit" value="Zapisz">
        </form>
        
        <form method="post" action="">
        <h3>Zmiana hasła</h3>
        Hasło:<br>
        <input type="password" name="newpass1"><br>
        Powtórz hasło:<br>
        <input type="password" name="newpass2"><br>
END;
                    echo '<input type="hidden" name="userid" value="'.$_GET['edituser'].'">';
echo<<<END
        <input type="submit" name="m2ed2submit" value="Zmień hasło">
        </form>
END;
                    
                }
                else
                {
                    // Nie ma użytkownika o takim ID, wyświetl błąd
                    
                    echo '<p><font color="red">Użytkownik o podanym ID nie istnieje</font></p>';
                }
                
            }
            else if(isset($_GET['deluser']))
            {
                // Potwierdzenie usunięcia użytkownika
                
                $userid = $_GET['deluser'];
                
                $query = "SELECT username FROM %susers WHERE id=%s";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($userid)));
                
                if($result->num_rows > 0)
                {
                    // Użytkownik o danym ID istnieje, można usunąć
                    
                    $row = $result->fetch_assoc();
                    $username = $row['username'];
                    
echo<<<END
        <h2>Usuwanie użytkownika</h2>
        <form method="post" action="">
        Czy na pewno chcesz usunąć użytkownika o nazwie $username?<br>
        <input type="hidden" name="userid" value="$userid">
        <input type="submit" name="m2delsubmit" value="Usuń"><br>
        <a href="admin.php?module=2">Anuluj</a>
        </form>
END;
                }
                else
                {
                    // Nie ma takiego użytkownika, nie można usunąć
                        
                    echo '<p><font color="red">Użytkownik o podanym ID nie istnieje</font></p>';
                }
            }
            else
            {
                // Lista użytkowników
                
                if(isset($_GET['crusersucc'])) echo '<p><font color="green">Utworzono użytkownika</font></p>';
                if(isset($_GET['edusersucc'])) echo '<p><font color="green">Zmodyfikowano użytkownika</font></p>';
                if(isset($_GET['delusersucc'])) echo '<p><font color="green">Usunięto użytkownika</font></p>';
                
                if(isset($_GET['cruserfail'])) echo '<p><font color="red">Nie udało się utworzyć użytkownika</font></p>';
                if(isset($_GET['usernamefail'])) echo '<p><font color="red">Użytkownik o podanej nazwie już istnieje</font></p>';
                if(isset($_GET['userpassfail'])) echo '<p><font color="red">Hasła nie są takie same</font></p>';
                if(isset($_GET['userpass2fail'])) echo '<p><font color="red">Nowe hasło nie spełnia wymagań co do złożoności</font></p>';
                if(isset($_GET['eduserfail'])) echo '<p><font color="red">Nie udało się zmodyfikować użytkownika</font></p>';
                if(isset($_GET['deluserfail'])) echo '<p><font color="red">Nie udało się usunąć użytkownika</font></p>';
                if(isset($_GET['selfdestructfail'])) echo '<p><font color="red">Nie można sobie samemu odebrać uprawnień administratora ani usunąć lub wyłączyć własnego konta</font></p>';
                
echo<<<END
        <h2>Lista użytkowników</h2>
        <a href="admin.php?module=2&createuser">Utwórz użytkownika</a>
        <table border="1" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Nazwa użytkownika</th>
            <th>Uprawnienia</th>
            <th>Włączony</th>
            <th>Edytuj</th>
            <th>Usuń</th>
        </tr>
END;
                
                $query = "SELECT id, username, editor, admin, active FROM %susers";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix)));
                
                foreach($result as $row)
                {
                    echo '<tr>';
                    echo '<td>'.$row['id'].'</td>';
                    echo '<td>'.$row['username'].'</td>';
                    
                    echo '<td>';
                    if($row['editor'] == 1) echo 'Edytor<br>';
                    if($row['admin'] == 1) echo 'Admin';
                    echo '</td>';
                    
                    echo '<td>'.$row['active'].'</td>';
                    echo '<td><a href="admin.php?module=2&edituser='.$row['id'].'">Edytuj</a></td>';
                    echo '<td><a href="admin.php?module=2&deluser='.$row['id'].'">Usuń</a></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
        }
    }
    else if($module == 3)
    {
        // Moduł zarządzania wpisami w menu (dostępny dla adminów)
        
        if(!$_SESSION['admin'])
        {
            // Użytkownik nie jest adminem
            echo '<p><font color="red">Odmowa dostępu.</font></p>'; 
        }
        else
        {
            if(isset($_GET['createentry']))
            {
                // Tworzenie wpisu w menu
                
echo<<<END
        <h2>Tworzenie wpisu w menu</h2>
        <form method="post" action="">
        Treść linku:<br>
        <input type="text" name="text"><br>
        Cel (atrybut href):<br>
        <input type="text" name="link"><br>
        <label for="active"><input type="checkbox" id="active" name="active">Wpis włączony</label><br>
        <input type="submit" name="m3crsubmit" value="Utwórz wpis">
        </form>
END;
                
            }
            else if(isset($_GET['editentry']))
            {
                // Edytowanie wpisu w menu
                
                $query = "SELECT text, link, active FROM %smenu WHERE id=%s";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($_GET['editentry'])));
                
                if($result->num_rows > 0)
                {
                    // Wpis o podanym ID istnieje, można edytować
                    
                    $row = $result->fetch_assoc();
                    
                    $text = $row['text'];
                    $link = $row['link'];
                    $active = $row['active'];
                    
                    $editentry = $_GET['editentry'];
                    
                    // Modyfikacja wpisu w menu
                    
echo<<<END
        <h2>Modyfikacja wpisu w menu</h2>
        <form method="post" action="">
        Treść linku:<br>
        <input type="text" name="text" value="$text"><br>
        Cel (atrybut href):<br>
        <input type="text" name="link" value="$link"><br>
        <label for="active"><input type="checkbox" id="active" name="active" 
END;
                if($active == 1) echo 'checked';
echo<<<END
        >Wpis włączony</label><br>
        <input type="hidden" name="editentry" value="$editentry">
        <input type="submit" name="m3edsubmit" value="Zapisz wpis">
        </form>
END;

                    
                }
                else
                {
                    // Nie ma wpisu o takim ID, wyświetl błąd
                    
                    echo '<p><font color="red">Wpis o podanym ID nie istnieje</font></p>';
                }
                
            }
            else if(isset($_GET['delentry']))
            {
                // Potwierdzenie usunięcia wpisu
                
                $entryid = $_GET['delentry'];
                
                $query = "SELECT text, link FROM %smenu WHERE id=%s";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($entryid)));
                
                if($result->num_rows > 0)
                {
                    // Wpis o danym ID istnieje, można usunąć
                    
                    $row = $result->fetch_assoc();
                    $text = $row['text'];
                    $link = $row['link'];
                    
echo<<<END
        <h2>Usuwanie użytkownika</h2>
        <form method="post" action="">
        Czy na pewno chcesz usunąć wpis w menu o treści "$text" prowadzący do "$link"?<br>
        <input type="hidden" name="entryid" value="$entryid">
        <input type="submit" name="m3delsubmit" value="Usuń"><br>
        <a href="admin.php?module=3">Anuluj</a>
        </form>
END;
                }
                else
                {
                    // Nie ma takiego wpisu, nie można usunąć
                        
                    echo '<p><font color="red">Wpis o podanym ID nie istnieje</font></p>';
                }
            }
            else
            {
                // Lista wpisów
                
                if(isset($_GET['crentrysucc'])) echo '<p><font color="green">Utworzono wpis w menu</font></p>';
                if(isset($_GET['edentrysucc'])) echo '<p><font color="green">Zmodyfikowano wpis w menu</font></p>';
                if(isset($_GET['delentrysucc'])) echo '<p><font color="green">Usunięto wpis w menu</font></p>';
                
                if(isset($_GET['crentryfail'])) echo '<p><font color="red">Nie udało się utworzyć wpisu w menu</font></p>';
                if(isset($_GET['edentryfail'])) echo '<p><font color="red">Nie udało się zmodyfikować wpisu w menu</font></p>';
                if(isset($_GET['delentryfail'])) echo '<p><font color="red">Nie udało się usunąć wpisu w menu</font></p>';
                
echo<<<END
        <h2>Lista wpisów w menu</h2>
        <a href="admin.php?module=3&createentry">Utwórz wpis w menu</a>
        <table border="1" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Treść</th>
            <th>Cel (atrybut href)</th>
            <th>Włączony</th>
            <th>Edytuj</th>
            <th>Usuń</th>
        </tr>
END;
                
                $query = "SELECT id, text, link, active FROM %smenu";
                
                $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix)));
                
                foreach($result as $row)
                {
                    echo '<tr>';
                    echo '<td>'.$row['id'].'</td>';
                    echo '<td>'.$row['text'].'</td>';
                    echo '<td>'.$row['link'].'</td>';
                    echo '<td>'.$row['active'].'</td>';
                    echo '<td><a href="admin.php?module=3&editentry='.$row['id'].'">Edytuj</a></td>';
                    echo '<td><a href="admin.php?module=3&delentry='.$row['id'].'">Usuń</a></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
        }
        
    }
    
echo<<<END
    </div>
    <div id="footer">
    <hr>
    <p>
    Panel administratora LekkiegoCMSa od CIST
    </p>
    </div>
    </body>
    </html>
END;
?>
