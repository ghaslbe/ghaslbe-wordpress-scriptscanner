<?php
/*
Plugin Name: Enhanced DB Script and Eval Searcher
Description: search all Wordpress Tables for script , eval and atob
Version: 1.0
Author: guenther haslbeck
*/


#
function search_db_for_script_and_eval() {
    global $wpdb;

    // Holen Sie sich alle Tabellennamen in der Datenbank
    $tables = $wpdb->get_col("SHOW TABLES");

    $matches = [];

    foreach ($tables as $table) {
        // Holen Sie alle Spalten in der Tabelle
        $columns = $wpdb->get_col("DESCRIBE $table");
        $uniqueColumns = $wpdb->get_results("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'", ARRAY_A);
        $dateColumns = $wpdb->get_results("SHOW COLUMNS FROM $table WHERE DATA_TYPE IN ('DATE', 'DATETIME', 'TIMESTAMP', 'TIME')", ARRAY_A);

        foreach ($columns as $column) {
            $query = $wpdb->prepare(
                "SELECT * FROM `$table` WHERE `$column` LIKE '%s' OR `$column` LIKE '%s' OR `$column` LIKE '%s' ",
                '%<script%',
                '%eval\(%',
                '%atob\(%'
            );

            $results = $wpdb->get_results($query, ARRAY_A);

            if ($results) {
                foreach ($results as $result) {

		$content = $result[$column];
		$ersetzt = str_replace(['<', '>'], ['&lt;', '&gt;'], $content);

		$matchInfo = "Table: $table, Column: $column, Value: <textarea style=\"min-width: 600px;min-height: 231px;\">" . $ersetzt."</textarea><br>";


                $matchInfo .= ", Keys of db line <source>";
		foreach ($result as $key => $value) {
			if ($key <> $column){
				$matchInfo .= "'" . $key ."'='". $value . "', ";
			}
		}
                $matchInfo .= "</source>";

                    // Datumsfelder hinzufügen, sofern vorhanden
                    foreach ($dateColumns as $dateColumn) {
                        $columnName = $dateColumn["Field"];
                        $matchInfo .= ", Date ($columnName): " . $result[$columnName];
                    }
		    $matchInfo .= '<hr>';
                    $matches[] = $matchInfo;
                }
            }
        }
    }

    if ($matches) {
        echo "Found: <br><br>" . implode('<br>', $matches);
    } else {
        echo "Mothing found.";
    }
}

function add_db_searcher_to_menu() {
    add_submenu_page('tools.php', 'Enhanced DB Script and Eval Searcher', 'Enhanced DB Script and Eval Searcher', 'manage_options', 'enhanced-db-searcher', 'search_db_for_script_and_eval');
}

add_action('admin_menu', 'add_db_searcher_to_menu');
?>

