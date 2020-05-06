<?php
// Initialize the session
session_start();

require_once "config.php";
require_once "games.php";

function is_valid($link, $score_one, $score_two, $game_id)
{
    $not_enough_points = "<p>Wenigstens ein Spieler sollte 11 Punkte erreichen, ihr Opfer.</p>";
    $difference_too_narrow = "<p>Über 11 Punkte aber keine Differenz von 2. Nochmal!</p>";
    if ($score_one > $score_two)
    {
        if ($score_one < 11)
        {
            echo $not_enough_points;
            return false;
        }
        else if ($score_two > $score_one - 2)
        {
            echo $difference_too_narrow;
            return false;
        }
    }
    else if ($score_two > $score_one)
    {
        if ($score_two < 11)
        {
            echo $not_enough_points;
            return false;
        }
        if ($score_one > $score_two - 2)
        {
            echo $difference_too_narrow;
            return false;
        }
    }
    else
    {
        echo "<p>Unentschieden? Weiter machen!</p>";
        return false;
    }
    $previous_game = getPreviousGame($link);
    if (!empty($previous_game) && $previous_game['game_id'] + 1 != $game_id)
    {
        echo "<p>Du bist zu langsam, das Spiel wurde bereits eingetragen.</p>";
        return false;
    }
    
    return true;

}

function printScoreTable($game)
{
    if ($game["score_one"] > $game["score_two"]) {
        $score_one = "            <td class=\"scoreCell winner\">" . $game["score_one"] . "</td>\n";
        $score_two = "            <td class=\"scoreCell\">" . $game["score_two"] . "</td>\n";
    } else {
        $score_one = "            <td class=\"scoreCell\">" . $game["score_one"] . "</td>\n";
        $score_two = "            <td class=\"scoreCell winner\">" . $game["score_two"] . "</td>\n";            
    }
    echo "        <tr>\n";
    echo "            <td>" . $game["player_one"] . "</td>\n";
    echo $score_one;
    echo "            <td>-</td>\n";
    echo $score_two;
    echo "            <td>" . $game["player_two"] . "</td>\n";
    echo "        </tr>\n";
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Die Ewige Tabelle</title>
    <style>
        body { font: 14px sans-serif; text-align: center; }
        body h2 { font-size: 1.7em; }
        body h3 { font-size: 1.5em; }
        body h4 { font-size: 1.3em; }
        body h5 { font-size: 1.1em; }
        .games { border-collapse: collapse; margin: 0 auto; font-size: 1em; }
        .games tr:nth-child(even) { background-color: #f2f2f2 }
        .games td { border-bottom: 1px solid #ddd; padding: 5px }
        .games tr td:first-child { text-align: left }
        .games tr td:last-child { text-align: right }
        .games tr td:first-child, .games tr td:last-child { width: 80px; }
        .scoreCell { width: 30px; text-align: center; }
        .winner { font-weight: bolder }
    </style>
</head>
<body>
    <h1>Die Ewige Tabelle</h1>
    <p><img style="margin: 0 auto" src="candy_cigarette_squash.jpg" alt="John Candy playing racquet ball against Tom Hanks in the movie Splash"></p>
    <?php
        if (isset($_POST['score_one']) && isset($_POST['score_two']) && isset($_POST['player_one']) && isset($_POST['player_two']) && isset($_POST['game_id']))
        {
            $player_one = $_POST['player_one'];
            $player_two = $_POST['player_two'];
            $score_one = $_POST['score_one'];
            $score_two = $_POST['score_two'];
            $game_id = $_POST['game_id'];
            if (is_valid($link, $score_one, $score_two, $game_id))
            {
                // TODO confirm first
                $sql = "INSERT INTO games (player_one, player_two, score_one, score_two) VALUES (?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($link, $sql))
                {
                    mysqli_stmt_bind_param($stmt, "ssii", $player_one, $player_two, $score_one, $score_two);
                    if (mysqli_stmt_execute($stmt))
                    {
                        header("location: /");
                        exit;
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }

        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] == true)
        {
            echo "<h2>Neues Spiel</h2>\n";
            $next_game = getNextGame($link);
            if (isset($next_game['player_one']) && isset($next_game['player_two']) && isset($next_game['game_id']))
            {
                echo "<form action=\"/\" method=\"post\">\n";
                echo "  <input name=\"player_one\" type=\"hidden\" value=\"" . $next_game['player_one'] . "\">\n";
                echo "  <input name=\"score_one\" id=\"player_one\" placeholder=\"" . $next_game['player_one'] . "\">\n";
                echo "  <input name=\"player_two\" type=\"hidden\" value=\"" . $next_game['player_two'] . "\">\n";
                echo "  <input name=\"score_two\" id=\"player_two\" placeholder=\"" . $next_game['player_two'] . "\">\n";
                echo "  <input name=\"game_id\" type=\"hidden\" value=\"" . $next_game['game_id'] . "\">\n";
                echo "  <input type=\"submit\" value=\"In die ewige Tabelle eintragen\">\n";
                echo "</form>\n";
            } else {
                echo "<p>Not enough players.</p>";
            }
        }
    ?>
    <!-- 
    TODO
    - start game button
        - start timing
        - timing stopped when score entered
        - use time instead/in addition to datetime
    -->
    
    <h2>Statistiken</h2>
    <h3>Spieltag</h3>
    <?php
        $allGames = getAllGames($link);
        if (count($allGames) > 0)
        {
            $date = new DateTime(array_keys($allGames)[0]);
            echo "<h4>" . $date->format("d. m. Y") . "</h4>\n";
            $lastMatchDay = array_reverse(reset($allGames));
            echo "    <table class=\"games\">\n";
            foreach ($lastMatchDay as $game)
            {
                printScoreTable($game);
            }
            echo "    </table>\n";
            $ranking = getRanking($lastMatchDay);
            echo "    <div style=\"margin: 20px auto; width: 400px; border: 1px solid black; font-size: 1.2em; padding: 5px;\">\n";
            foreach ($ranking as $key => $value)
            {
                echo "        <span style=\"margin-left: 10px;\">" . $key . ":</span><span style=\"margin-left: 20px; margin-right: 20px;\">" . $value . "</span>\n";
            }
            echo "    </div>\n";
        }
        else
        {
            echo "<p>n/a</p>\n";
        }
    ?>
    <h3>Gesamt</h3>
    <h4>Spiele</h4>
    <?php
        if (count($allGames) > 0)
        {
            $ranking = getRanking(array_merge(...array_values($allGames)));
            echo "    <div style=\"margin: 20px auto; width: 400px; border: 1px solid black; font-size: 1.2em; padding: 5px;\">\n";
            foreach ($ranking as $key => $value)
            {
                echo "        <span style=\"margin-left: 10px;\">" . $key . ":</span><span style=\"margin-left: 20px; margin-right: 20px;\">" . $value . "</span>\n";
            }
            echo "    </div>\n";
        }
        else {
            echo "<p>n/a</p>\n";
        }
    ?>
    <h4>Alle Spieltage</h4>
    <?php
        if (count($allGames) > 0)
        {
            if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] == true) {
                    echo "  <a href=\"export.php\">Download as CSV</a>";
            }
            foreach ($allGames as $date => $games)
            {
                $date = new DateTime($date);
                echo "<h5>" . $date->format("d. m. Y") . "</h5>\n";
                $ranking = getRanking($games);
                echo "    <div style=\"margin: 20px auto; width: 400px; border: 1px solid black; font-size: 1.2em; padding: 5px;\">\n";
                foreach ($ranking as $key => $value)
                {
                    echo "        <span style=\"margin-left: 10px;\">" . $key . ":</span><span style=\"margin-left: 20px; margin-right: 20px;\">" . $value . "</span>\n";
                }
                echo "    </div>\n";
                echo "    <table class=\"games\">\n";
                $games = array_reverse($games);
                foreach ($games as $game)
                {
                    printScoreTable($game);
                }
                echo "      </table>\n";
            }
        }
        else
        {
            echo "<p>n/a</p>\n";
        }
    ?>
    <p>
    <?php
    if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] == true) {
    ?>
        <a href="reset-password.php">Passwort zurücksetzen</a>
        <a href="logout.php">Ausloggen</a>
    <?php
    }
    else
    {
    ?>
        <a href="login.php">Einloggen</a>
    <?php
    }
    ?>
    </p>
</body>
</html>

<?php
    mysqli_close($link);
?>
