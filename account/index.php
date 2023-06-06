<?php

include("../db/conn.php");
include("../misc/safe.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

if (isset($_SESSION['user'])) {
  $stmt = $pdo->prepare("SELECT `status` FROM account WHERE user=?");
  $stmt->execute([$_SESSION['user']]);
  if ($stmt->fetchAll()[0]['status'] == 'not_verified') {
    header("Location: ../mail/verify.php");
    exit();
  } else {
    header("Location: dashboard");
    exit();
  }

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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="64d58efce2.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="style.css" />
  <title>SimplePolls - Account</title>
</head>

<body>
  <div class="container">
    <div class="forms-container">
      <div class="signin-signup">
        <form action="login.php" method="POST" class="sign-in-form">
          <h2 class="title">Sign in</h2>
          <p id="loginmsg"></p>
          <div class="input-field">
            <i class="fas fa-user"></i>
            <input name='user' type="text" placeholder="Username" />
          </div>
          <div class="input-field">
            <i class="fas fa-lock"></i>
            <input name="pass" type="password" placeholder="Password" />
          </div>
          <input type="submit" value="Login" class="btn solid" />
        </form>
        <form action="signup.php" method="POST" class="sign-up-form">
          <h2 class="title">Sign up</h2>
          <p id="signupmsg"></p>
          <div class="input-field">
            <i class="fas fa-user"></i>
            <input name="user" type="text" placeholder="Username" />
          </div>
          <div class="input-field">
            <i class="fas fa-envelope"></i>
            <input name="email" type="email" placeholder="Email" />
          </div>
          <div class="input-field">
            <i class="fas fa-lock"></i>
            <input name="pass" type="password" placeholder="Password" />
          </div>
          <input type="submit" class="btn" value="Sign up" />
        </form>
      </div>
    </div>

    <div class="panels-container">
      <div class="panel left-panel">
        <div class="content">
          <h3>New here ?</h3>
          <p>
            Creating your SimplePolls account is as simple as 1, 2, 3! Join us today!
          </p>
          <button class="btn transparent" id="sign-up-btn">
            Sign up
          </button>
        </div>
        <img src="img/log.svg" class="image" alt="" />
      </div>
      <div class="panel right-panel">
        <div class="content">
          <h3>One of us ?</h3>
          <p>
            Welcome back! Log in to your SimplePolls account and continue sharing your opinions with ease!
          </p>
          <button class="btn transparent" id="sign-in-btn">
            Sign in
          </button>
        </div>
        <img src="img/register.svg" class="image" alt="" />
      </div>
    </div>
  </div>

  <script>
    const sign_in_btn = document.querySelector("#sign-in-btn");
    const sign_up_btn = document.querySelector("#sign-up-btn");
    const container = document.querySelector(".container");

    sign_up_btn.addEventListener("click", () => {
      container.classList.add("sign-up-mode");
    });

    sign_in_btn.addEventListener("click", () => {
      container.classList.remove("sign-up-mode");
    });

    var urlParams = new URLSearchParams(window.location.search);
    var loginMsg = document.getElementById('loginmsg');

    if (urlParams.get('code') == '0L') {
      loginMsg.textContent = 'Successfully logged in. Redirecting to dashboard...';
      loginMsg.style.color = 'green';
      window.setTimeout(function () { window.location.href = "dashboard"; }, 1000);
    } else if (urlParams.get('code') == '1L') {
      loginMsg.textContent = 'ERROR: Username cannot be empty';
      loginMsg.style.color = 'red';
    } else if (urlParams.get('code') == '2L') {
      loginMsg.textContent = 'ERROR: Password cannot be empty';
      loginMsg.style.color = 'red';
    } else if (urlParams.get('code') == '3L') {
      loginMsg.textContent = 'ERROR: Username or password is incorrect';
      loginMsg.style.color = 'red';
    }

    var signupMsg = document.getElementById('signupmsg');

    if (urlParams.get('code') == '0S') {
      container.classList.add("sign-up-mode");
      signupMsg.textContent = 'Successfully registered! You can now login to your account';
      signupMsg.style.color = 'green';
      window.setTimeout(function () { container.classList.remove("sign-up-mode"); }, 1000);
    } else if (urlParams.get('code') == '1S') {
      container.classList.add("sign-up-mode");
      signupMsg.textContent = 'ERROR: Username cannot be empty';
      signupMsg.style.color = 'red';
    } else if (urlParams.get('code') == '2S') {
      container.classList.add("sign-up-mode");
      signupMsg.textContent = 'ERROR: Password cannot be empty';
      signupMsg.style.color = 'red';
    } else if (urlParams.get('code') == '3S') {
      container.classList.add("sign-up-mode");
      signupMsg.textContent = 'ERROR: Invalid email address';
      signupMsg.style.color = 'red';
    } else if (urlParams.get('code') == '4S') {
      container.classList.add("sign-up-mode");
      signupMsg.textContent = 'ERROR: Invalid username';
      signupMsg.style.color = 'red';
    } else if (urlParams.get('code') == '5S') {
      container.classList.add("sign-up-mode");
      signupMsg.textContent = 'ERROR: Username or email address already exists';
      signupMsg.style.color = 'red';
    } else if (urlParams.get('code') == '6S') {
      container.classList.add("sign-up-mode");
      signupMsg.textContent = 'ERROR: An error occured. Please contact support';
      signupMsg.style.color = 'red';
    }

    if (urlParams.get('code') == 'signup') {
      container.classList.add("sign-up-mode");
    }
  </script>
</body>

</html>