<?php
include('../db/conn.php');
include('../misc/safe.php');
include('../misc/client.php');

ini_set('display_errors', 1);
error_reporting(0);

$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api';
$routeWithQuery = trim(str_replace($basePath, '', $requestUri), '/');
$route = strtok($routeWithQuery, '?');

switch ($route) {
    case 'create':
        createPoll();
        break;

    case 'poll':
        getPoll();
        break;

    case 'vote':
        vote();
        break;

    case 'delete':
        delete();
        break;

    case 'key':
        getKey();
        break;

    default:
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['message' => 'error', 'error' => 'Route not found']);
        exit();
}

function createPoll()
{
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['question']) && isset($_GET['options']) && isset($_GET['background']) && isset($_GET['foreground']) && isset($_GET['text']) && isset($_GET['max']) && isset($_GET['api_key'])) {
        $api_key = safevar($_GET['api_key']);
        try {
            $stmt = $pdo->prepare("SELECT user FROM account WHERE api=?");
            $stmt->execute([$api_key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $user = $result['user'];
        } catch (PDOException $e) {
            $response = array(
                'message' => "error",
                'error' => "Invalid API key"
            );

            echo json_encode($response);
            exit();
        }
        
        $question = safevar($_GET['question']);
        $options = array_map('safevar', explode(',', $_GET['options']));
        $answers = array();
        foreach ($options as $option) {
            $answers[$option] = 0;
        }

        $max = safevar($_GET['max']);
        $bgc = safevar($_GET['background']);
        $fgc = safevar($_GET['foreground']);
        $textc = safevar($_GET['text']);

        try {
            $stmt = $pdo->prepare("INSERT INTO poll (question, options, owner, bgc, fgc, textc, max) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$question, serialize($answers), $user, $bgc, $fgc, $textc, $max]);
        } catch (PDOException $e) {
            $response = array(
                'message' => "error",
                'error' => "Error creating poll"
            );

            echo json_encode($response);
            exit();
        }

        $response = array(
            'message' => "success",
            'id' => $pdo->lastInsertId()
        );
        echo json_encode($response);

    } else {
        $response = array(
            'message' => "error",
            'error' => "Invalid request"
        );

        echo json_encode($response);
        exit();
    }
}

function getPoll()
{
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['pid'])) {
        $pollId = safevar($_GET['pid']);
        $stmt = $pdo->prepare("SELECT * FROM poll WHERE id=?");
        $stmt->execute([$pollId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $response = array(
                'message' => "success",
                'id' => $result['id'],
                'question' => $result['question'],
                'options' => array_keys(unserialize($result['options'])),
                'count' => array_values(unserialize($result['options'])),
                'total_votes' => array_sum(unserialize($result['options'])),
                'background' => $result['bgc'],
                'foreground' => $result['fgc'],
                'text' => $result['textc'],
                'views' => $result['views'],
                'max_ip' => $result['max'],
            );

            echo json_encode($response);
        } else {
            $response = array(
                'message' => "error",
                'error' => "Poll not found"
            );

            echo json_encode($response);
        }
    } else {
        $response = array(
            'message' => "error",
            'error' => "Invalid request"
        );

        echo json_encode($response);
    }
}

function vote()
{
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['pid']) && isset($_GET['option'])) {
        $pid = safevar($_GET['pid']);
        $opt = intval($_GET['option']);
        $stmt = $pdo->prepare("SELECT IF((SELECT COUNT(*) FROM votes WHERE ip = ? and poll=?) >= (SELECT `max` FROM poll WHERE id = ?), FALSE, TRUE) AS result;");
        $stmt->execute([get_client_ip(), $pid, $pid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result['result']) {
            $response = array(
                'message' => "error",
                'error' => "Maximum votes reached"
            );

            echo json_encode($response);
            exit();
        }

        try {
            $stmt = $pdo->prepare("SELECT options FROM poll WHERE id=?");
            $stmt->execute([$pid]);
        } catch (PDOException $e) {
            $response = array(
                'message' => "error",
                'error' => "Poll not found"
            );

            echo json_encode($response);
            exit();
        }

        $options = $stmt->fetchColumn();
        $options = unserialize($options);

        if ($opt > count($options) || $opt < 1) {
            $response = array(
                'message' => "error",
                'error' => "Invalid option"
            );

            echo json_encode($response);
            exit();
        }

        $options[array_keys($options)[$opt - 1]] += 1;
        $options = serialize($options);

        try {
            $stmt = $pdo->prepare("INSERT INTO votes (ip, opt, poll) VALUES (?, ?, ?)");
            $stmt->execute([get_client_ip(), $opt, $pid]);
            $stmt = $pdo->prepare("UPDATE poll SET options = ? WHERE id = ?");
            $stmt->execute([$options, $pid]);
        } catch (PDOException $e) {
            $response = array(
                'message' => "error",
                'error' => "Error setting vote"
            );

            echo json_encode($response);
            exit();
        }
    } else {
        $response = array(
            'message' => "error",
            'error' => "Invalid request"
        );

        echo json_encode($response);
        exit();
    }
}

function delete(){
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['pid']) && isset($_GET['api_key'])) {
        $pid = safevar($_GET['pid']);
        $api_key = safevar($_GET['api_key']);
        try {
            $stmt = $pdo->prepare("SELECT user FROM account WHERE api=?");
            $stmt->execute([$api_key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $user = $result['user'];
        } catch (PDOException $e) {
            $response = array(
                'message' => "error",
                'error' => "Invalid API key"
            );

            echo json_encode($response);
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM poll WHERE `owner`=? AND id=?");
            $stmt->execute([$user, $pid]);
        } catch (PDOException $e) {
            $response = array(
                'message' => "error",
                'error' => "Error deleting poll"
            );

            echo json_encode($response);
            exit();
        }

        $response = array(
            'message' => "success",
        );

        echo json_encode($response);
        exit();
    } else {
        $response = array(
            'message' => "error",
            'error' => "Invalid request"
        );

        echo json_encode($response);
        exit();       
    }
}

function getKey(){
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user']) && isset($_POST['pass'])) {
        $user = safevar($_POST['user']);
        $pass = safevar($_POST['pass']);
        try {
            $stmt = $pdo->prepare("SELECT pass FROM account WHERE user=?");
            $stmt->execute([$user]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $hash = $result['pass'];
        } catch (PDOException $e) {
            $response = array(
                'message' => "error",
                'error' => "Invalid username"
            );

            echo json_encode($response);
            exit();
        }
        if (password_verify($pass, $hash)){
            $stmt = $pdo->prepare("SELECT api FROM account WHERE user=?");
            $stmt->execute([$user]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $api = $result['api'];
            $response = array(
                'message' => "success",
                'api_key' => $api
            );
            echo json_encode($response);
            exit();
        } else {
            $response = array(
                'message' => "error",
                'error' => "Invalid password"
            );
            echo json_encode($response);
            exit();
        }
    } else {
        $response = array(
            'message' => "error",
            'error' => "Invalid request"
        );
        echo json_encode($response);
        exit();
    }
}