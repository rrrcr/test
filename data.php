<?php

main();

function main()
{
    if (checkIfDataExists())
    {
        showMessage();
    }
    else
    {
        prepareDatabase();
    }
}

function checkIfDataExists()
{

    try
    {
        $db = new PDO('sqlite:/tmp/info.db');
        $results = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{facts}'");
        while ($row = $results->fetch())
        {
            return true;
        }
        return false;
    }
    catch(PDOException $e)
    {
        echo 'Caught exception: ', $e->getMessage() , "\n";
    }

}

function showMessage()
{
    $db = new PDO('sqlite:/tmp/info.db');
    $info = $db->query("SELECT fact_string FROM facts WHERE DATE(date) =DATE( 'now') ");
    $str = $info->fetchAll();
    if (empty($str))
    {
        echo "Sorry, we are looking for new interesting facts. Try checking tomorrow";
    }
    else
    {
        echo $str[0]['fact_string'];
    }
}

function prepareDatabase()
{
    createDatabase();
    prepareFromJson();
    prepareFromText();
    prepareFromPhp();
    updateDate();
    showMessage();
}

function createDatabase()
{
    $db = new PDO('sqlite:/tmp/info.db');
    $db->exec("CREATE TABLE facts ( fact_id INTEGER PRIMARY KEY AUTOINCREMENT, fact_string VARCHAR(255),date DATETIME )");
}

function prepareFromJson()
{
    $db = new PDO('sqlite:/tmp/info.db');
    $jsonString = file_get_contents("facts.json");
    $jsonString = str_replace('\"', '\\\\\"', $jsonString);
    $fields = json_decode($jsonString);
    foreach ($fields as $value)
    {
        $db->exec("INSERT INTO facts (fact_string) VALUES ('" . $value->fact . "')");
    }
}

function prepareFromText()
{
    $db = new PDO('sqlite:/tmp/info.db');
    $factsTxt = file_get_contents("facts.txt");
    $factsArray = explode(". ", $factsTxt);
    foreach ($factsArray as $fact)
    {
        $db->exec("INSERT INTO facts (fact_string) VALUES ('" . $fact . "')");
    }

}

function prepareFromPhp()
{
    $db = new PDO('sqlite:/tmp/info.db');
    $pattern = '/"([^"]+)"/';
    $factsPhp = file_get_contents("facts.php");
    if (preg_match_all($pattern, $factsPhp, $match))
    {
        foreach ($match[0] as $fact)
        {
            $db->exec("INSERT INTO facts (fact_string) VALUES ('" . $fact . "')");
        }
    }
}
function updateDate()
{
    $db = new PDO('sqlite:/tmp/info.db');
    $info = $db->query(" SELECT COUNT(*) FROM facts ");
    $str = $info->fetchAll();
    $numberOfRows = $str[0]['COUNT(*)'];
    $date = date('Y-m-d h:m:s');
    for ($i = 1;$i <= $numberOfRows;$i++)
    {
        $db->exec("UPDATE facts SET date = '" . $date . "' WHERE fact_id = '" . $i . "'");
        $date = date('Y-m-d h:m:s', strtotime("1 day", strtotime($date)));
    }
}
?>