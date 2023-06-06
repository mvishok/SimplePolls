<?php
include("../../db/conn.php");
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../');
    exit();
} else {
    $stmt = $pdo->prepare("SELECT `status` FROM account WHERE user=?");
    $stmt->execute([$_SESSION['user']]);
    if ($stmt->fetchAll()[0]['status'] == 'not_verified') {
        header("Location: ../mail/verify.php");
        exit();
    }
}

$stmt = $pdo->prepare("SELECT views, options, question, id FROM poll WHERE `owner`=?");
$stmt->execute([$_SESSION['user']]);
$data = $stmt->fetchAll();
$views = 0;
$sum = 0;
$polls = count($data);

foreach ($data as $i) {
    $views += $i[0];
    $sum += array_sum(unserialize($i[1]));
}
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <meta charset="UTF-8">
    <title>SimplePolls Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #EEEEEE;
            margin: 0;
        }

        .sidebar {
            width: 200px;
            background-color: #333;
            color: #fff;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 60px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            padding: 10px;
        }

        .sidebar a {
            color: #fff;
            text-decoration: none;
        }

        .sidebar a:hover {
            opacity: 0.8;
        }

        .dashboard {
            margin-left: 200px;
            padding: 20px;
        }

        .dashboard h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .dashboard .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .dashboard .stats .stat {
            flex-basis: 30%;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 4px;
            text-align: center;
        }

        .dashboard .stats .stat h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .dashboard .stats .stat p {
            font-size: 14px;
            color: #666;
            margin-bottom: 0;
        }

        .dashboard .polls {
            margin-bottom: 20px;
        }

        .dashboard .poll {
            background-color: #f9f9f9;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard .poll .question {
            flex-basis: 70%;
        }

        .dashboard .poll .actions {
            flex-basis: 30%;
            text-align: right;
        }

        .dashboard .poll .actions i {
            font-size: 20px;
            cursor: pointer;
            margin-right: 10px;
        }

        .dashboard .poll .actions i:hover {
            opacity: 0.8;
        }

        .btn {
            box-sizing: border-box;
            appearance: none;
            background-color: #613B16;
            border: 2px solid white;
            border-radius: 0.6em;
            color: white;
            cursor: pointer;
            display: flex;
            align-self: center;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1;
            margin: 20px;
            padding: 0.6em 1.4em;
            text-decoration: none;
            text-align: center;
            text-transform: uppercase;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        .btn:hover,
        .btn:focus {
            color: white;
            outline: 0;
        }

        .third {
            border-color: #228C22;
            background-color: #228C22;
            color: white;
            box-shadow: 0 0 40px 40px #228C22 inset, 0 0 0 0 #228C22;
            transition: all 150ms ease-in-out;
            position: absolute;
            right: 1px;
            top: 0px;
            float: right;
        }

        .third:hover {
            box-shadow: 0 0 10px 0 #228C22 inset, 0 0 10px 4px #228C22;
        }
    </style>

</head>

<body>
    <div class="sidebar">
        <h3 style="position: absolute; top: 5px;left: 5px;">SimplePolls</h3>
        <ul>
            <li><a href=""><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="poll.php"><i class="fas fa-plus"></i> New Poll</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Account Settings</a></li>
            <li><a href="javascript:void(0);" onclick="javascript:deleteAction('acc')"><i class="fas fa-trash-alt"></i>
                    Delete
                    Account</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <div class="dashboard">

        <a href="poll.php"><button class="btn third">Create New Poll</button></a>

        <h2>Dashboard</h2>

        <div class="stats">
            <div class="stat">
                <h3>Total Views</h3>
                <p>
                    <?php echo $views; ?> views
                </p>
            </div>
            <div class="stat">
                <h3>Total Votes</h3>
                <p>
                    <?php echo $sum; ?> votes
                </p>
            </div>
            <div class="stat">
                <h3>Total Polls</h3>
                <p>
                    <?php echo $polls; ?> polls
                </p>
            </div>
        </div>
        <?php
        foreach ($data as $i) { ?>
            <div class="polls">
                <div class="poll">
                    <div class="question">
                        <p>
                            <?php echo $i[3] . ": " . $i[2]; ?>
                        </p>
                    </div>
                    <div class="actions">
                        <i onClick="copy(<?php echo $i[3]; ?>)" class="fas fa-link"></i> <!-- # Icon -->
                        <i onClick="deleteAction('poll', <?php echo $i[3]; ?>)" class="fas fa-trash-alt"></i> <!-- $ Icon -->
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <script>
        function deleteAction(action, pid = 0) {
            var answer = confirm('This action cannot be undone. Are you sure you want to perform this action?');
            if (answer) {
                if (action == "acc") {
                    location.href = 'delete.php?action=acc';
                } else if (action == "poll") {
                    location.href = 'delete.php?action=poll&pid=' + pid;
                }
            }
        }

        function copy(data) {
            navigator.clipboard.writeText("https://polls.vishok.tech/polls?pid=" + data);
            alert("Link copied to clipboard");
        }
    </script>
</body>

</html>