<!DOCTYPE html>
<html>
    <head>
        <!-- Compiled and minified CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

        <!--Import Google Icon Font-->
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

        <style>
            /* https://coderwall.com/p/hkgamw/creating-full-width-100-container-inside-fixed-width-container */
            .row-full {
                width: 90vw;
                position: relative;
                margin-left: -45vw;
                height: 100px;
                margin-top: 100px;
                left: 50%;
            }
        </style>
    </head>
    <body>
        <?php
        include __DIR__ . "/db_stuff.php";

        $ratings = array();
        $roles = array();
        $names = array();

        $minRating = 9999;
        $maxRating = 1;

        $minLevel = 9999;
        $maxLevel = 1;


        $stmt = $conn->prepare("select name,role,rating,date,time from overwatch_ratings order by date,time asc");
        $stmt->execute();
        $stmt->bind_result($name, $role, $rating, $date, $time);
        while ($row = $stmt->fetch()) {
            if (!isset($ratings[$role])) {
                $ratings[$role] = array();
                $roles[] = $role;
            }
            if (!isset($ratings[$role][$name])) {
                $ratings[$role][$name] = array();
            }
            if (!in_array($name, $names)) {
                $names[] = $name;
            }
            $ratings[$role][$name][] = array(strtotime($date . " " . $time) * 1000, $rating);

            if ($rating < $minRating) {
                $minRating = $rating;
            }
            if ($rating > $maxRating) {
                $maxRating = $rating;
            }
        }
        $stmt->close();
        unset($stmt);


        $levels = array();

        $stmt = $conn->prepare("select name,prestige,level,date,time from overwatch_levels order by date,time asc");
        $stmt->execute();
        $stmt->bind_result($name, $prestige, $level, $date, $time);
        while ($row = $stmt->fetch()) {
            if (!in_array($name, $names)) {
                $names[] = $name;
            }
            if (!isset($levels[$name])) {
                $levels[$name] = array();
            }
            $l = ($prestige * 100 + $level);
            $levels[$name][] = array(strtotime($date . " " . $time) * 1000, $l);

            if ($l < $minLevel) {
                $minLevel = $l;
            }
            if ($l > $maxLevel) {
                $maxLevel = $l;
            }
        }
        $stmt->close();
        unset($stmt);


        $games = array();
        $lossCount = 0;
        $winCount = 0;
        $drawCount = 0;

        $gamesPerMap = array();
        $gamesPerQueue = array();

        $stmt = $conn->prepare("select result,self,enemy,map,time,queue from overwatch_games order by time asc");
        $stmt->execute();
        $stmt->bind_result($result, $self, $enemy, $map, $time, $queue);
        while ($row = $stmt->fetch()) {
            if (!isset($gamesPerMap[$map])) {
                $gamesPerMap[$map] = array(
                    "win" => 0,
                    "loss" => 0,
                    "draw" => 0
                );
            }
            if (!isset($gamesPerQueue[$queue])) {
                $gamesPerQueue[$queue] = array(
                    "win" => 0,
                    "loss" => 0,
                    "draw" => 0
                );
            }

            $y = 0;
            switch ($result) {
                case "LOSS":
                    $lossCount++;
                    $gamesPerMap[$map]["loss"]++;
                    $gamesPerQueue[$queue]["loss"]++;
                    $y = -1;
                    break;
                case "WIN":
                    $winCount++;
                    $gamesPerMap[$map]["win"]++;
                    $gamesPerQueue[$queue]["win"]++;
                    $y = 1;
                    break;
                case "DRAW":
                    $drawCount++;
                    $gamesPerMap[$map]["draw"]++;
                    $gamesPerQueue[$queue]["draw"]++;
                    $y = 0;
                    break;
            }

            $games[] = array(
                "x" => strtotime($time) * 1000,
                "y" => $y,
                "label" => $map . " " . $self . "-" . $enemy
            );
        }
        $stmt->close();
        unset($stmt);

        ?>

        <div class="container">

            <div class="row-full">
                <div class="col s12">
                    <div class="row" id="ratings">
                        <h1>Ratings</h1>

                        <div class="row" id="ratings-role">
                            <?php
                            $ri = 0;
                            foreach ($roles as $r) {
                                ?>
                                <div class="col s12 m6">
                                    <div class="card ">
                                        <div class="card-content">
                                            <span class="card-title role-title-<?php echo $r; ?> role-title-n-<?php echo $ri++; ?>">Role: <?php echo $r; ?></span>
                                            <div class="card-chart" id="rating-role-chart-<?php echo $r; ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>

                        <div class="divider"></div>
                        <br/>

                        <div class="row" id="ratings-player">
                            <?php
                            $ni = 0;
                            foreach ($names as $n) {
                                ?>
                                <div class="col s12 m6">
                                    <div class="card ">
                                        <div class="card-content">
                                            <span class="card-title player-title-<?php echo str_replace("#", "-", $n); ?> player-title-n-<?php echo $ni++; ?>">Player: <?php echo $n; ?></span>
                                            <div class="card-chart" id="rating-name-chart-<?php echo str_replace("#", "-", $n); ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="row" id="levels">
                        <h1>Levels</h1>

                        <div class="row">
                            <div class="col s12 m12">
                                <div class="card ">
                                    <div class="card-content">
                                        <span class="card-title">Levels</span>
                                        <div class="card-chart" id="levels-chart"></div>
                                        <?php
                                        $days = 4;
                                        $pointsPerDay = 4;
                                        ?>
                                        <h4>Average Level Increments over the past <?php echo $days; ?> days</h4>
                                        <?php
                                        foreach ($names as $n) {
                                            $totalLevelIncrease = 0;
                                            $levelSize = sizeof($levels[$n]);
                                            for ($l = 0; $l < $days * $pointsPerDay; $l++) {
                                                $totalLevelIncrease += $levels[$n][$levelSize - $l - 1][1] - $levels[$n][$levelSize - $l - 2][1];
                                            }
                                            $avgLevelIncreasePerDay = $totalLevelIncrease / $days;


                                            ?>
                                            <strong><?php echo $n; ?></strong> <?php echo $avgLevelIncreasePerDay; ?>
                                            <?php

                                            $currentLevel = $levels[$n][$levelSize - 1][1];
                                            $currentLevelFac = $currentLevel % 100;

                                            $futureLevel = $currentLevel;
                                            $levelFac = 99;
                                            $nextPromotionDays = 0;
                                            while (($levelFac = ($futureLevel) % 100) >= $currentLevelFac) {
                                                $futureLevel += $avgLevelIncreasePerDay;
                                                $nextPromotionDays++;
                                            }

                                            ?>
                                            -> Next promotion in about ~<?php echo $nextPromotionDays ?> days<br/>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="levels">
                        <h1>Win Rate</h1>

                        <div class="row">
                            <div class="col s12 m12">
                                <div class="card ">
                                    <div class="card-content">
                                        <span class="card-title">Win Rate</span>
                                        <h3 id="winrate" style="margin-bottom: 0;"><?php echo round(($winCount / ($winCount + $lossCount + $drawCount)) * 100, 2) ?>%</h3>
                                        <span id="winreateSubtitle">(<?php echo $winCount; ?>w <?php echo $lossCount; ?>l <?php echo $drawCount; ?>d)</span>
                                        <br/>
                                        <br/>

                                        <?php
                                        $gamesSortedByTotalGames = array();
                                        foreach ($gamesPerMap as $m => $i) {
                                            $gamesSortedByTotalGames[] = array(
                                                    "m"=>$m,
                                                "w" => $i["win"],
                                                "l" => $i["loss"],
                                                "d" => $i["draw"],
                                                "t" => $i["win"] + $i["loss"] + $i["draw"]
                                            );
                                        }
                                        usort($gamesSortedByTotalGames,function ($a,$b){
                                            if ($a["t"] < $b["t"]) {
                                                return 1;
                                            }else if ($a["t"] > $b["t"]) {
                                                return -1;
                                            }
                                            return 0;
                                        });

                                        echo "<h4>Per Map</h4>";
                                        foreach ($gamesSortedByTotalGames as $i) {
                                            $r = $i["w"] / ($i["t"]);
                                            ?>
                                            <span><strong><?php echo $i["m"]; ?></strong> <?php echo round($r * 100, 2); ?>% (<?php echo $i["w"]; ?>w <?php echo $i["l"]; ?>l <?php echo $i["d"]; ?>d)</span><br/>
                                            <?php
                                        }

                                        echo "<h4>Per Queue Type</h4>";
                                        foreach ($gamesPerQueue as $m => $i) {
                                            $r = $i["win"] / ($i["win"] + $i["loss"] + $i["draw"]);
                                            ?>
                                            <span><strong><?php echo $m; ?></strong> <?php echo round($r*100,2) ?>% (<?php echo $i["win"]; ?>w <?php echo $i["loss"]; ?>l <?php echo $i["draw"]; ?>d)</span><br/>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col s12 m12">
                                <div class="card ">
                                    <div class="card-content">
                                        <span class="card-title">Games</span>
                                        <div class="card-chart" id="games-chart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Compiled and minified JavaScript -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
        <script src="https://code.highcharts.com/stock/highstock.js"></script>
        <script src="https://code.highcharts.com/stock/modules/data.js"></script>
        <script src="https://code.highcharts.com/stock/modules/exporting.js"></script>
        <script src="https://code.highcharts.com/stock/modules/export-data.js"></script>
        <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
        <script>
            <?php
            echo "let roles = " . json_encode($roles) . ";";
            echo "let names = " . json_encode($names) . ";";
            echo "let ratings = " . json_encode($ratings) . ";";
            echo "let levels = " . json_encode($levels) . ";";
            echo "let games = " . json_encode($games) . ";";

            echo "let minRating = " . $minRating . ";";
            echo "let maxRating = " . $maxRating . ";";

            echo "let minLevel = " . $minLevel . ";";
            echo "let maxLevel = " . $maxLevel . ";";

            echo "let wins = " . $winCount . ";";
            echo "let losses = " . $lossCount . ";";
            echo "let draws = " . $drawCount . ";";

            ?>

            const BRONZE = 'rgba(205,127,50,0.5)';
            const SILVER = 'rgba(192,192,192,0.5)';
            const GOLD = 'rgba(255,215,0,0.5)';
            const PLATINUM = 'rgba(229,228,226,0.5)';
            const DIAMOND = 'rgba(185,242,255,0.5)';

            const ratingPlotBands = [
                {
                    label: {text: "Bronze"},
                    color: BRONZE,
                    from: 0,
                    to: 1499
                },
                {
                    label: {text: "Silver"},
                    color: SILVER,
                    from: 1500,
                    to: 1999
                },
                {
                    label: {text: "Gold"},
                    color: GOLD,
                    from: 2000,
                    to: 2499
                },
                {
                    label: {text: "Platinum"},
                    color: PLATINUM,
                    from: 2500,
                    to: 2999
                },
                {
                    label: {text: "Diamond"},
                    color: DIAMOND,
                    from: 3000,
                    to: 3499
                },
                {
                    label: {text: "Master"},
                    color: 'rgba(240,179,77,0.51)',
                    from: 3500,
                    to: 3999
                },
                {
                    label: {text: "Grandmaster"},
                    color: 'rgba(253,255,250,0.5)',
                    from: 4000,
                    to: 6000
                }
            ];


            const levelPlotBands = [
                {
                    label: {text: "Bronze"},
                    color: BRONZE,
                    from: 0,
                    to: 600
                },
                {
                    label: {text: "Silver"},
                    color: SILVER,
                    from: 601,
                    to: 1200
                },
                {
                    label: {text: "Gold"},
                    color: GOLD,
                    from: 1201,
                    to: 1800
                },
                {
                    label: {text: "Platinum"},
                    color: PLATINUM,
                    from: 1801,
                    to: 2400
                },
                {
                    label: {text: "Diamond"},
                    color: DIAMOND,
                    from: 2401,
                    to: 2901
                }
            ];


            const roleColors = {
                "support": '#21a5ff',
                "damage": '#ff212b',
                "tank": '#07cd09'
            };


            const nameColors = [
                '#2288ff',
                '#ff2f0b',
                '#803b0e',
                '#b2206e',
                '#38be1d',
                /* add more colors here if needed */
            ];

            const seasonPlotLines = [
                {
                    label: "Season 19",
                    value: new Date(2019, 11 - 1, 9).getTime()
                },
                {
                    label: "Season 20",
                    value: new Date(2020, 1 - 1, 2).getTime()
                },
                {
                    label: "Season 21",
                    value: Date.parse("2020-03-05T18:00:00Z")
                }
            ];


            const labeledTooltipFormatter = function (tooltip) {
                return '<span style="font-size: 10px">' + Highcharts.dateFormat('%Y-%m-%d %H:%M', this.x) + '</span><br/>' +
                    '<span style="color:' + this.point.color + '">●</span> ' + this.series.name + ': <b>' + (this.point.label || this.y) + '</b><br/>';
            };

            Highcharts.setOptions({
                global: {
                    useUTC: false
                }
            });


            $(document).ready(() => {

                roles.forEach(r => {
                    $(".role-title-" + r).css({color: roleColors[r]});

                    let roleSeries = [];
                    names.forEach((n, i) => {
                        let ratingsCopy = ratings[r][n];
                        addTrendArrows(ratingsCopy);
                        roleSeries.push({
                            name: n,
                            data: ratingsCopy,
                            color: nameColors[i % nameColors.length]
                        });
                    });

                    Highcharts.chart('rating-role-chart-' + r, {
                        chart: {
                            type: 'spline',
                            zoomType: 'xy'
                        },
                        title: {
                            text: 'Rating History per Player'
                        },
                        xAxis: {
                            type: 'datetime',
                            title: {
                                text: 'Date'
                            },
                            plotLines: seasonPlotLines
                        },
                        yAxis: {
                            title: {
                                text: 'Rating'
                            },
                            min: minRating - 100,
                            max: maxRating + 100,
                            startOnTick: false,
                            endOnTick: false,
                            plotBands: ratingPlotBands
                        },
                        tooltip: {
                            formatter: labeledTooltipFormatter
                        },
                        plotOptions: {
                            series: {
                                marker: {
                                    enabled: true
                                }
                            }
                        },
                        series: roleSeries
                    });

                });

                names.forEach((n, i) => {
                    $(".player-title-n-" + i).css({color: nameColors[i % nameColors.length]});

                    let nameSeries = [];
                    roles.forEach(r => {
                        let ratingsCopy = ratings[r][n];
                        addTrendArrows(ratingsCopy);
                        nameSeries.push({
                            name: r,
                            data: ratingsCopy,
                            color: roleColors[r]
                        })
                    });

                    Highcharts.chart('rating-name-chart-' + (n.replace("#", "-")), {
                        chart: {
                            type: 'spline',
                            zoomType: 'xy'
                        },
                        title: {
                            text: 'Rating History per Role'
                        },
                        xAxis: {
                            type: 'datetime',
                            title: {
                                text: 'Date'
                            },
                            plotLines: seasonPlotLines
                        },
                        yAxis: {
                            title: {
                                text: 'Rating'
                            },
                            min: minRating - 100,
                            max: maxRating + 100,
                            startOnTick: false,
                            endOnTick: false,
                            plotBands: ratingPlotBands
                        },
                        tooltip: {
                            formatter: labeledTooltipFormatter
                        },
                        plotOptions: {
                            series: {
                                marker: {
                                    enabled: true
                                }
                            }
                        },
                        series: nameSeries
                    });
                });


                let levelSeries = [];
                names.forEach((n, i) => {
                    let levelsCopy = levels[n];
                    addTrendArrows(levelsCopy);
                    levelSeries.push({
                        name: n,
                        data: levelsCopy,
                        color: nameColors[i % nameColors.length]
                    })
                });
                Highcharts.chart('levels-chart', {
                    chart: {
                        type: 'spline',
                        zoomType: 'xy'
                    },
                    title: {
                        text: 'Player Level History'
                    },
                    xAxis: {
                        type: 'datetime',
                        title: {
                            text: 'Date'
                        }
                    },
                    yAxis: {
                        title: {
                            text: 'Level'
                        },
                        min: minLevel - 100,
                        max: maxLevel + 100,
                        startOnTick: false,
                        endOnTick: false,
                        plotBands: levelPlotBands
                    },
                    tooltip: {
                        formatter: labeledTooltipFormatter
                    },
                    plotOptions: {
                        series: {
                            marker: {
                                enabled: true
                            }
                        }
                    },
                    series: levelSeries
                });

                let winRateData = [];
                let cw  =0;
                let cl = 0;
                let cd =0;
                for (let i = 0; i < games.length; i++) {
                    let time = games[i].x||games[i][0];
                    let result = games[i].y||games[i][1];

                    switch (result) {
                        case -1:
                            cl++;
                            break;
                        case 1:
                            cw++;
                            break;
                        case 0:
                            cd++;
                            break;
                    }

                    let r = Math.fround(cw / (cw + cl + cd));
                    winRateData.push([time, r]);
                }
                Highcharts.chart('games-chart', {
                    chart: {
                        zoomType: 'x'
                    },
                    title: {
                        text: 'Game Results'
                    },
                    xAxis: {
                        type: 'datetime',
                        title: {
                            text: 'Date'
                        }
                    },
                    yAxis: {
                        title: {
                            text: 'Result'
                        },
                        min: -1.5,
                        max: 1.5,
                        startOnTick: false,
                        endOnTick: false,
                        plotBands: [
                            {
                                label: {text: "Win"},
                                color: '#25f407',
                                from: 0.5,
                                to: 2
                            },
                            {
                                label: {text: "Draw"},
                                color: '#d7de17',
                                from: -0.5,
                                to: 0.5
                            },
                            {
                                label: {text: "Loss"},
                                color: '#ff0c16',
                                from: -0.5,
                                to: -2
                            }
                        ]
                    },
                    tooltip: {
                        formatter: labeledTooltipFormatter
                    },
                    plotOptions: {
                        series: {
                            marker: {
                                enabled: true
                            }
                        }
                    },
                    series: [{
                        lineWidth: 0,
                        name: "Game Results",
                        data: games,
                        step: 'left',
                        states: {
                            hover: {
                                lineWidthPlus: 0
                            }
                        }
                    },{
                        name:"Win Rate",
                        data:winRateData
                    }]
                });


                function addTrendArrows(arr) {
                    if (!arr) return;
                    arr.forEach((el, ind, arr) => {
                        if (ind > 0) {
                            let px = arr[ind - 1][0] || arr[ind - 1].x;
                            let py = arr[ind - 1][1] || arr[ind - 1].y;

                            let x = el[0] || el.x;
                            let y = el[1] || el.y;
                            let symbol;
                            if (py < y) {
                                symbol = "url(/icons/trending_up-24px.svg)";
                            } else if (py > y) {
                                symbol = "url(/icons/trending_down-24px.svg)";
                            }
                            if (symbol) {
                                arr[ind] = {
                                    x: x,
                                    y: y,
                                    label: y + (py !== y ? (" (" + (y > py ? "+" : "") + (Math.abs(y) - Math.abs(py)) + ")") : ""),
                                    marker: {
                                        symbol: symbol
                                    }
                                };
                            }
                        }
                    });
                }

            });
        </script>
    </body>
</html>
