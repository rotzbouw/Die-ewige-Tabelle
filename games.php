<?php

    function getGamesForDate($link, $date)
    {
        $sql = 'SELECT * FROM games WHERE game_time >= ? AND game_time < ? + INTERVAL 1 DAY';
        if ($stmt = mysqli_prepare($link, $sql))
        {
            $formattedDate = $date->format("Y-m-d");
            mysqli_stmt_bind_param($stmt, "ss", $formattedDate, $formattedDate);
            if (mysqli_stmt_execute($stmt))
            {
                $result = $stmt->get_result();
                $results = [];
                while ($row = $result->fetch_assoc())
                {
                    $results[] = $row;
                }
                return $results;
            }
        }
    }

    // Returns all games, sorted by date -> games for that date
    function getAllGames($link)
    {
        $sql = 'SELECT * FROM games ORDER BY id DESC';
        if ($stmt = mysqli_prepare($link, $sql))
        {
            if (mysqli_stmt_execute($stmt))
            {
                $result = $stmt->get_result();
                $results = [];
                while ($row = $result->fetch_assoc())
                {
                    $date = new DateTime($row["game_time"]);
                    $day = $date->format("Y-m-d");
                    if (array_key_exists($day, $results))
                    {
                        array_push($results[$day], $row);
                    }
                    else
                    {
                        $matchday = [$row];
                        $results[$day] = $matchday;
                    }
                }
                return $results;
            }
        }
    }

    function getPreviousGame($link)
    {
        $sql = 'SELECT * FROM games ORDER BY id DESC LIMIT 1';
        $player_one;
        $player_two;
        $game_id;
        if ($result = mysqli_query($link, $sql))
        {
            if (mysqli_num_rows($result) == 1)
            {
                $object = $result->fetch_object();
                $player_one = $object->player_one;
                $player_two = $object->player_two;
                $score_one = $object->score_one;
                $score_two = $object->score_two;
                $game_id = $object->id;

                return array(
                    'player_one' => $player_one,
                    'player_two' => $player_two,
                    'score_one' => $score_one,
                    'score_two' => $score_two,
                    'game_id' => $game_id
                    );
            }
        }

        return array();

    }

    function getNextGame($link)
    {
        $previous_game = getPreviousGame($link);
        $player_one;
        $player_two;
        $game_id;
        if (isset($previous_game['player_one']) && isset($previous_game['player_two']) && isset($previous_game['score_one']) && isset($previous_game['score_two']) && isset($previous_game['game_id']))
        {
            $loser;
            if ($previous_game['score_one'] > $previous_game['score_two'])
            {
                $player_one = $previous_game['player_one'];
                $loser = $previous_game['player_two'];
            } else {
                $player_one = $previous_game['player_two'];
                $loser = $previous_game['player_one'];
            }
            $sql = 'SELECT username FROM players WHERE username NOT IN (?, ?)';
            if ($stmt = mysqli_prepare($link, $sql))
            {
                mysqli_stmt_bind_param($stmt, "ss", $player_one, $loser);
                if (mysqli_stmt_execute($stmt))
                {
                    $result = $stmt->get_result();
                    $object = $result->fetch_object();
                    $player_two = $object->username;
                }
            }
            $game_id = $previous_game['game_id'] + 1;
        }
        else
        {
            $sql = 'SELECT username FROM players';
            if ($result = mysqli_query($link, $sql))
            {
                if (mysqli_num_rows($result) == 3)
                {
                    $players = [];
                    while ($row = $result->fetch_assoc())
                    {
                        $players[] = $row['username'];
                    }
                    $rand_keys = array_rand($players, 2);
                    $player_one = $players[$rand_keys[0]];
                    $player_two = $players[$rand_keys[1]];
                    $game_id = 0;
                }
                else
                {
                    echo "Could only fetch " . mysqli_num_rows($result) . " players:<br />";
                    while ($row = $result->fetch_assoc())
                    {
                        echo $row['username'] . "<br />";
                    }
                    return array();
                }
            }
            else
            {
                echo "Error querying db<br/>";
                return array();
            }
        }

        return array('player_one' => $player_one, 'player_two' => $player_two, 'game_id' => $game_id);

    }

    function getRanking($games)
    {
        $wins = [];
        foreach($games as $game)
        {
            if ($game['score_one'] > $game['score_two'])
            {
                increment($game['player_one'], $wins);
            }
            else
            {
                increment($game['player_two'], $wins);
            }
        }
        array_multisort($wins, SORT_DESC);
        return $wins;
    }

    function increment($player, &$ranking)
    {
        if (array_key_exists($player, $ranking))
        {
            $ranking[$player] = $ranking[$player] + 1;
        }
        else
        {
            $ranking[$player] = 1;
        }
    }
?>
