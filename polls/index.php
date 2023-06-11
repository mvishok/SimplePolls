<?php
error_reporting(0);

include('../db/conn.php');
include('../misc/safe.php');
include('../misc/client.php');

$pid = safevar($_GET['pid']);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE ip=? and poll=?");
$stmt->execute([get_client_ip(), $pid]);
$votes = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT max FROM poll WHERE id=?");
$stmt->execute([$pid]);
$maxip = $stmt->fetchColumn();

$voted = false;

if ($votes == $maxip) {
    $voted = true;
    $stmt = $pdo->prepare("SELECT opt FROM votes WHERE ip=? and poll=?");
    $stmt->execute([get_client_ip(), $pid]);
    $opt = $stmt->fetchColumn();
}



if ($opt) {
    $voted = true;
} else if (isset($_POST['option'])) {
    $opt = safevar($_POST['option']);
    $stmt = $pdo->prepare("SELECT options FROM poll WHERE id=?");
    $stmt->execute([$pid]);
    $options = $stmt->fetchColumn();
    $options = unserialize($options);
    $options[array_keys($options)[$opt - 1]] += 1;
    $options = serialize($options);
    $stmt = $pdo->prepare("UPDATE poll SET options = ? WHERE id = ?");
    $stmt->execute([$options, $pid]);
    $stmt = $pdo->prepare("INSERT INTO votes (ip, opt, poll) VALUES (?, ?, ?)");
    $stmt->execute([get_client_ip(), $opt, $pid]);
    $voted = true;
}

$stmt = $pdo->prepare("SELECT * FROM poll WHERE id=?");
$stmt->execute([$pid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "Poll not found";
    exit();
}

$question = $row['question'];
$data = unserialize($row['options']);
$sum = array_sum($data);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-CB8848C3QW"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-CB8848C3QW');
    </script>
    <meta charset="UTF-8">
    <title>SimplePolls -
        <?php echo $question; ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css">
    <style>
        .poll-area label:hover {
            border-color:
                <?php echo $row['textc']; ?>
            ;
        }

        label.selected {
            border-color:
                <?php echo $row['bgc']; ?>
                !important;
            pointer-events: none;
        }

        label.selected .row .circle {
            border-color:
                <?php echo $row['bgc']; ?>
            ;
        }

        label .row .circle::after {
            content: "";
            height: 11px;
            width: 11px;
            background:
                <?php echo $row['bgc']; ?>
            ;
            border-radius: inherit;
            position: absolute;
            left: 2px;
            top: 2px;
            display: none;
        }

        label.selected .row .circle::after {
            display: block;
            background:
                <?php echo $row['bgc']; ?>
                !important;
        }

        label.selected .progress::after {
            background:
                <?php echo $row['bgc']; ?>
            ;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"
        integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>

<body style="background:<?php echo $row['bgc'] ?>">
    <!-- partial:index.partial.html -->
    <div class="wrapper" style='background:<?php echo $row['fgc']; ?>'>
        <header style="color:<?php echo $row['textc']; ?>">
            <?php echo $question; ?>
        </header>
        <p style='color:white;'> By:
            <?php echo $row['owner']; ?>
        </p>
        <div class="poll-area">
            <form id="poll" action="#" method="POST">
                <input type="radio" name="option" id="opt-1" value="1">
                <input type="radio" name="option" id="opt-2" value="2">
                <input type="radio" name="option" id="opt-3" value="3">
                <input type="radio" name="option" id="opt-4" value="4">

                <?php
                $i = 1;
                foreach ($data as $option => $count) {
                    if ($voted) {
                        $display = 'display:block;';

                        if ($i == $opt) {
                            $class = "selected";
                        } else {
                            $class = "locked";
                        }
                        $percentage = round($count / $sum * 100);
                        if ($_GET['count'] == 'percent') {
                            $stat = "$percentage%";
                        } else {
                            $stat = $count;
                        }
                    } else {
                        $percentage = 0;
                    }
                    ?>
                    <label style="color:<?php echo $row['textc']; ?>" for="opt-<?php echo $i; ?>"
                        class="opt-<?php echo $i . " " . $class; ?>">
                        <div class="row">
                            <div class="column">
                                <span class="circle"></span>
                                <span class="text">
                                    <?php echo $option; ?>
                                </span>
                            </div>
                            <span class="percent" style="<?php echo $display; ?>">
                                <?php echo $stat; ?>
                            </span>
                        </div>
                        <div class="progress" style='--w:<?php echo "$percentage;$display" ?>'></div>
                    </label>
                    <?php
                    $i++;
                } ?>
        </div>
    </div>
    <!-- partial -->
    <script>
        const options = document.querySelectorAll("label");
        const prog = document.querySelectorAll(".progress");
        const pcent = document.getElementsByClassName("percent");

        for (let i = 0; i < options.length; i++) {
            options[i].addEventListener("click", () => {
                for (let j = 0; j < options.length; j++) {
                    options[j].classList.add("locked");
                }
                options[i].classList.add("selected");
            });
        }

        var radioInputs = document.getElementsByName('option');
        var form = document.getElementById('poll');
        for (var i = 0; i < radioInputs.length; i++) {
            radioInputs[i].addEventListener('click', function () {
                form.submit(); // Submit the form
            });
        }
    </script>

    <p style="position: absolute; bottom: 5px; color: <?php echo $row['textc']; ?>">Powered by SimplePolls<br>©
        Copyright
        2023, <a style="color: <?php echo $row['textc']; ?>" href='https://github.com/mvishok'>Vishok M</a></p>

</body>

</html>
<?php
$stmt = $pdo->prepare("UPDATE poll SET views = views + 1 WHERE id=?");
$stmt->execute([$pid]);
?>