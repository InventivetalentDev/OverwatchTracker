<?php
include __DIR__."./../db_stuff.php";

$names=array();

$stmt = $conn->prepare("select distinct name from overwatch_levels");
$stmt->execute();
$stmt->bind_result($name);
while ($row = $stmt->fetch()) {
    $names[]=$name;
}
$stmt->close();
unset($stmt);

$date = date("Y-m-d");

foreach ($names as $name) {
    echo $name;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://ow-api.com/v1/stats/pc/eu/".str_replace("#","-",$name)."/profile" );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $res = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($res, true);
    print_r($json);

    if ($json["private"] === false) {
        for ($i = 0; $i < sizeof($json["ratings"]); $i++) {
            $r = $json["ratings"][$i];

            $stmt = $conn->prepare("INSERT IGNORE INTO overwatch_ratings (name,role,rating,date) VALUES(?,?,?,?)");
            $stmt->bind_param("ssis", $name,$r["role"], $r["level"], $date);
            $stmt->execute();
            $stmt->close();
            unset($stmt);
        }
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO overwatch_levels (name,prestige,level,date) VALUES(?,?,?,?)");
    $stmt->bind_param("siis", $name,$json["prestige"], $json["level"], $date);
    $stmt->execute();
    $stmt->close();
    unset($stmt);



}

$conn->close();
unset($conn);
