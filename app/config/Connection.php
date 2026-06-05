<?php

require_once "global.php";
class Connection
{
    private static $dbOverride = null;

    public static function setDatabase($dbName)
    {
        self::$dbOverride = $dbName;
    }

    public static function getDatabase()
    {
        return self::$dbOverride ?? DB_NAME;
    }

    private static function dbName()
    {
        return self::$dbOverride ?? DB_NAME;
    }

    // Escapa un valor para usarlo de forma segura dentro de comillas en una query.
    public static function escape($str)
    {
        $link = @mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, self::dbName());
        if (!$link) return addslashes((string) $str);
        mysqli_set_charset($link, "utf8mb4");
        $e = mysqli_real_escape_string($link, (string) $str);
        mysqli_close($link);
        return $e;
    }

public static function runQuery($query)
	{
        $link = mysqli_connect(DB_HOST,DB_USERNAME,DB_PASSWORD,self::dbName());

        if (!$link) {
            echo "Error: connect a MySQL." . PHP_EOL;
            echo "error: " . mysqli_connect_errno() . PHP_EOL;

            exit;
        }
        mysqli_set_charset($link, "utf8mb4");
        // Compat MySQL 8: quitar ONLY_FULL_GROUP_BY para las queries legacy (error 1055).
        mysqli_query($link, "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        $result = mysqli_query($link, $query);
        if ($result === false) {
            $err = mysqli_error($link);
            mysqli_close($link);
            throw new RuntimeException('[Connection::runQuery] ' . $err . ' | SQL: ' . substr($query, 0, 200));
        }

        mysqli_close($link);
        return  $result;

	}

public static function runQueryID($query)
	{
	//"localhost","root","olivetti24","chatbot"
	$link = mysqli_connect(DB_HOST,DB_USERNAME,DB_PASSWORD,self::dbName());

		if (!$link) {
			echo "Error: connect a MySQL." . PHP_EOL;
			echo "error: " . mysqli_connect_errno() . PHP_EOL;

			exit;
		}
        mysqli_set_charset($link, "utf8mb4");
        // Compat MySQL 8: quitar ONLY_FULL_GROUP_BY para las queries legacy (error 1055).
        mysqli_query($link, "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
	     mysqli_query($link ,$query);
		 $result = mysqli_insert_id($link);

	    mysqli_close($link);

		return $result;
	}	
	


}
?>