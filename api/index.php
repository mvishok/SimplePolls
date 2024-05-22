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

    case 'verify':
        verifyEmail();
        break;

    case 'signup':
        createAccount();
        break;

    case 'user':
        userDetails();
        break;

    case 'getvote':
        getVote();
        break;

    default:
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['message' => 'error', 'error' => 'Route not found']);
        exit();
}

function checkAcc($user){
    global $pdo;
    $stmt = $pdo->prepare("SELECT `status` FROM account WHERE user=?");
    $stmt->execute([$user]);
    if ($stmt->fetchAll()[0]['status'] == 'not_verified') {
        $response = array(
            'message' => "error",
            'error' => "Account email not verified"
        );

        echo json_encode($response);
        exit();
    }
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
            checkAcc($user);
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
            $stmt = $pdo->prepare("UPDATE poll SET views = views + 1 WHERE id=?");
            $stmt->execute([$pollId]);

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
                'author' => $result['owner']
            );

            echo json_encode($response);
        } else {
            $response = array(
                'message' => "error",
                'error' => "Poll not found"
            );

            echo json_encode($response);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['key'])) {
        $key = safevar($_POST['key']);
        $stmt = $pdo->prepare("SELECT user FROM account WHERE api=?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $user = $result['user'];

        $stmt = $pdo->prepare("SELECT * FROM poll WHERE owner=?");
        $stmt->execute([$user]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = array();
        $response['message'] = "success";
        foreach ($result as $row){
            $response[$row['id']] = array(
                'question' => $row['question'],
                'total_votes' => array_sum(unserialize($row['options'])),
                'views' => $row['views'],
            );
        }
        echo json_encode($response);
    }
    else {
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
            $stmt = $pdo->prepare("SELECT opt FROM votes WHERE ip = ? and poll=? ORDER BY id DESC LIMIT 1");
            $stmt->execute([get_client_ip(), $pid]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $latest = $result['opt'];

            $response = array(
                'message' => "error",
                'error' => "Maximum votes reached",
                'latest' => $latest
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
            $response = array(
                'message' => "success",
            );
            echo json_encode($response);
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pid']) && isset($_POST['key'])) {
        $pid = safevar($_POST['pid']);
        $api_key = safevar($_POST['key']);
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
            checkAcc($user);
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
            checkAcc($user);
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

function verifyEmail(){
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] && isset($_POST['otp']) && isset($_POST['user'])) {
        
        $user = safevar($_POST['user']);
        #Get entered otp
        $inOTP = $_POST['otp'];
      
        #Get existing OTP
        $stmt = $pdo->prepare("SELECT `otp` FROM verify WHERE user=?");
        $stmt->execute([$user]);
        $OTP = $stmt->fetchAll()[0]['otp'];
      
        #Check if they match
        if ($inOTP == $OTP) {
          try {
            $stmt = $pdo->prepare("UPDATE account SET status = 'verified' WHERE user=?");
            $stmt->execute([$user]);
          } catch (Exception $e) {
            echo "Error occured";
            exit();
          }
          $stmt = $pdo->prepare("DELETE FROM verify WHERE user=?");
          $stmt->execute([$user]);
      
          $response = array(
            'message' => "success",
          );
          echo json_encode($response);
          exit();
        } else {
            $response = array(
                'message' => "error",
                'error' => "Invalid OTP"
            );
            echo json_encode($response);
            exit();
        }
      
      } else if ($_POST['user']) {

        $user = safevar($_POST['user']);
      
        #Generate OTP
        $OTP = mt_rand(1111, 9999);
      
        #Delete existing OTP (if exist)
        $stmt = $pdo->prepare("DELETE FROM verify WHERE user=?");
        $stmt->execute([$user]);
      
        #INSERT OTP into db
        try {
          $stmt = $pdo->prepare("INSERT INTO verify VALUES (?, ?)");
          $stmt->execute([$user, $OTP]);
        } catch (Exception $e) {
          echo "Error occured";
          exit();
        }
      
        #Get user's mail id
        $stmt = $pdo->prepare("SELECT `email` FROM account WHERE user=?");
        $stmt->execute([$user]);
        $email = $stmt->fetchAll()[0]['email'];
      
        #Finalize body of verification mail
        $body = file_get_contents('templates/verify.html');
        $body = str_replace("{{user}}", $user, $body);
        $body = str_replace("{{otp}}", $OTP, $body);
      
        #Send it
        sendmail($email, $user, "Verify your SimplePolls Account", $body);
      } else {
        $response = array(
            'message' => "error",
            'error' => "Invalid request"
        );
        echo json_encode($response);
        exit();
      }      
}

function createAccount(){
    global $pdo;
    if (isset($_POST['user']) && isset($_POST['pass']) && isset($_POST['email'])) {
        $user = safevar($_POST['user']);
        $pass = safevar($_POST['pass']);
        $email = safevar($_POST['email']);
    }
    if (empty($user)) {
        $response = array(
            'message' => "error",
            'error' => "Username cannot be empty"
        );
        echo json_encode($response);
        exit();
    } else if (empty($pass)) {
        $response = array(
            'message' => "error",
            'error' => "Password cannot be empty"
        );
        echo json_encode($response);
        exit();
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = array(
            'message' => "error",
            'error' => "Invalid email"
        );
        echo json_encode($response);
        exit();
    } else if (!username($user)) {
        $response = array(
            'message' => "error",
            'error' => "Invalid username"
        );
        echo json_encode($response);
        exit();
    } else {
        $stmt = $pdo->prepare("SELECT user, email FROM account WHERE user=? or email=?");
        $stmt->execute([$user, $email]);
        $row = $stmt->fetchAll();
        $existingUser = $row[0]['user'];
        $existingEmail = $row[0]['email'];
    
        $apiKey = base64_encode(random_bytes(32));
    
        if ($existingUser or $existingEmail) {
            $response = array(
                'message' => "error",
                'error' => "Username or email already exists"
            );
            echo json_encode($response);
            exit();
        } else {
    
            try {
                $stmt = $pdo->prepare("INSERT INTO account (user, pass, email, api) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $email, $apiKey]);
                $response = array(
                    'message' => "success",
                    'user' => $user,
                );
                echo json_encode($response);
                exit();
            } catch (Exception $e) {
                $response = array(
                    'message' => "error",
                    'error' => "Error creating account"
                );
                echo json_encode($response);
                exit();
            }
        }
    
    
    }
    
}

function userDetails() {
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['key']) && $_POST['key']!="") {
        $key = safevar($_POST['key']);

        try{
        $stmt = $pdo->prepare("SELECT * FROM account WHERE api=?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $response = array(
                'message' => "error",
                'error' => "Invalid API key"
            );

            echo json_encode($response);
            exit();
        }
        $response = array(
            'message' => "success",
            'user' => $result['user'],
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

function getVote(){
    global $pdo;
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['pid'])) {
        $pid = safevar($_GET['pid']);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE ip=? and poll=?");
        $stmt->execute([get_client_ip(), $pid]);
        $votes = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT max FROM poll WHERE id=?");
        $stmt->execute([$pid]);
        $maxip = $stmt->fetchColumn();

        if ($votes>0){
        $stmt = $pdo->prepare("SELECT opt FROM votes WHERE ip=? and poll=?");
        $stmt->execute([get_client_ip(), $pid]);
        $opt = $stmt->fetchColumn();
        } else {
            $response = array(
                'message' => "error",
                'error' => "Not voted yet"
            );
            echo json_encode($response);
            exit();
        }

        if ($votes == $maxip) {
            $response = array(
                'message' => "error",
                'error' => "Maximum votes reached",
                'latest' => $opt
            );
            echo json_encode($response);
            exit();
        } else {
            $response = array(
                'message' => "success",
                'latest' => $opt
            );
            echo json_encode($response);
            exit();
        }
    }
}