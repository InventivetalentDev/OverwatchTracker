<?php
include __DIR__ . "./../db_stuff.php";

$names = array();

$stmt = $conn->prepare("select distinct name from overwatch_levels");
$stmt->execute();
$stmt->bind_result($name);
while ($row = $stmt->fetch()) {
    $names[] = $name;
}
$stmt->close();
unset($stmt);

$date = date("Y-m-d");
$time = date("H:i:s");

foreach ($names as $name) {
    echo $name;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://ow-api.com/v1/stats/pc/eu/" . str_replace("#", "-", $name) . "/profile");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    $res = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($res, true);
    print_r($json);

    $winRateComp = 0;
    $winRateQuick = 0;
    if ($json["private"] === false) {
        for ($i = 0; $i < sizeof($json["ratings"]); $i++) {
            $r = $json["ratings"][$i];

            $stmt = $conn->prepare("INSERT IGNORE INTO overwatch_ratings (name,role,rating,date,time) VALUES(?,?,?,?,?)");
            $stmt->bind_param("ssiss", $name, $r["role"], $r["level"], $date, $time);
            echo $stmt->execute();
            $stmt->close();
            unset($stmt);
            echo "\n";
        }

        $compGames = $json["competitiveStats"]["games"];
        if($compGames["played"]>0)$winRateComp =round( $compGames["won"] / $compGames["played"],4);
        $quickGames = $json["quickPlayStats"]["games"];
        if($quickGames["played"]>0)$winRateQuick = round($quickGames["won"] / $quickGames["played"],4);
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO overwatch_levels (name,prestige,level,winRateComp,winRateQuick,date,time) VALUES(?,?,?,?,?,?,?)");
     $stmt->bind_param("siiddss", $name, $json["prestige"], $json["level"], $winRateComp, $winRateQuick, $date, $time);
    echo $stmt->execute();
    $stmt->close();
    unset($stmt);

    echo "\n\n";
}

$conn->close();
unset($conn);
