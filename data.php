<?php
$db = new PDO('sqlite:/tmp/info.db');
if (checkIfDataExists())
{
    prepareDatabase();
}
else
{
    showMessage();
}

function checkIfDataExists()
{

    try
    {
        global $db;
        $results = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='facts'");
        $answer = $results->fetchAll();
        return empty($answer);
    }
    catch(PDOException $e)
    {
        echo 'Caught exception: ', $e->getMessage() , "\n";
    }

}

function showMessage()
{
    global $db;
    $info = $db->query("SELECT fact_string FROM facts WHERE DATE(date) BETWEEN DATE( 'now','-1 days') AND DATE( 'now')");
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
    global $db;
    $db->exec("CREATE TABLE facts ( fact_id INTEGER PRIMARY KEY AUTOINCREMENT, fact_string VARCHAR(255),date DATETIME )");
}

function prepareFromJson()
{
    global $db;
    $jsonString = file_get_contents("facts.json");
    $jsonString = str_replace('\"', '\\\\\"', $jsonString);
    $fields = json_decode($jsonString);

    foreach ($fields as $value)
    {
        $ex = $db->prepare("INSERT INTO facts (fact_string) VALUES (?)");
        $ex->execute([$value->fact]);
    }
}

function prepareFromText()
{
    global $db;
    $factsTxt = file_get_contents("facts.txt");
    $factsArray = explode(chr(10) , $factsTxt);
    foreach ($factsArray as $fact)
    {
        $ex = $db->prepare("INSERT INTO facts (fact_string) VALUES (?)");
        $ex->execute([$fact]);
    }

}

function prepareFromPhp()
{
    global $db;
    $pattern = '/"([^"]+)"/';
    $factsPhp = file_get_contents("facts.php");
    if (preg_match_all($pattern, $factsPhp, $match))
    {
        foreach ($match[0] as $fact)
        {
            $ex = $db->prepare("INSERT INTO facts (fact_string) VALUES (?)");
            $ex->execute([$fact]);
        }
    }
}
function updateDate()
{
    global $db;
    $info = $db->query(" SELECT COUNT(*) FROM facts ");
    $str = $info->fetchAll();
    $numberOfRows = $str[0]['COUNT(*)'];
    $date = date('Y-m-d h:m:s');
    for ($i = 1;$i <= $numberOfRows;$i++)
    {
        $ex = $db->prepare("UPDATE facts SET date = ?  WHERE fact_id = ?");
        $ex->execute([$date, $i]);
        $date = date('Y-m-d h:m:s', strtotime("1 day", strtotime($date)));
    }
}
?>
