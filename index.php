<?php
    if(!include_once("config.php"))
    {
        header('Location: installer.php');
        exit();
    }
    
    if(!Config::$site_available)
    {
        exit(Config::$unavailable_text);
    }
    else
    {
        if(!Config::$debug_mode) mysqli_report(MYSQLI_REPORT_STRICT);
        
        try
        {
            $con = new mysqli(Config::$master_db_server, Config::$master_db_user, Config::$master_db_pass, Config::$master_db_name);
        }
        catch(Exception $e)
        {
            if(Config::$debug_mode)
            {
                echo '<pre>';
                echo 'Kod błędu: '.$e->getCode()."\n";
                echo 'Komunikat: '.$e->getMessage();
                echo '</pre>';
            }
            exit(Config::$unavailable_text);
        }
        
echo<<<END
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
END;
        echo '<title>'.Config::$sitename.'</title>';
        echo '<meta name="description" content="'.Config::$description.'">';
        echo '<meta name="keywords" content="'.Config::$keywords.'">';
        echo '<meta name="robots" content="'.Config::$robots.'">';

echo<<<END
    </head>
    <body>
    <div id="header">
END;
        echo '<h1>'.Config::$sitename.'</h1>';
        
echo<<<END
        <table border="0">
        <tr>
END;
        
        $query = "SELECT text, link FROM %smenu WHERE active=1";
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix)));
        
        foreach($result as $row)
        {
            echo '<td><a href="'.$row['link'].'">'.$row['text'].'</a></td>';
        }
        
echo<<<END
        </tr>
        </table>
        <hr>
    </div>
    <div id="main">
END;
        $pageid = !isset($_GET['pageid']) ? 1 : $_GET['pageid'];
        $query = "SELECT text FROM ".Config::$table_prefix."pages WHERE id=".$pageid." AND active=1";
        $result = $con->query(sprintf($query, $con->real_escape_string(Config::$table_prefix), $con->real_escape_string($pageid)));
        
        if($result->num_rows > 0)
        {
            $row = $result->fetch_assoc();
            echo $row["text"];
        }
        else
        {
            echo Config::$lackingpage_text;
        }
        
echo<<<END
    </div>
    <div id="footer">
    <hr>
END;
        echo Config::$footer_text;
echo<<<END
    </div>
    </body>
    </html>
END;
    }

?>
