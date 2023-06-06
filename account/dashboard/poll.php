<?php
include("../../db/conn.php");
include("../../misc/safe.php");
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

$created = false;

if (isset($_POST['question']) && isset($_POST['options'])) {
	$question = safevar($_POST['question']);
	$options = array_map('safevar', $_POST['options']);
	$answers = array();
	foreach ($options as $option) {
		$answers[$option] = 0;
	}

	if (isset($_POST['maxResponses']) && $_POST['maxResponses'] > 0) {
		$max = safevar($_POST['maxResponses']);
	} else {
		$max = 1;
	}
	if (isset($_POST['backgroundColor'])) {
		$bgc = safevar($_POST['backgroundColor']);
	} else {
		$bgc = '#228C22';
	}
	if (isset($_POST['foregroundColor'])) {
		$fgc = safevar($_POST['foregroundColor']);
	} else {
		$fgc = '#613B16';
	}
	if (isset($_POST['textColor'])) {
		$textc = safevar($_POST['textColor']);
	} else {
		$textc = '#fff';
	}
	$stmt = $pdo->prepare("INSERT INTO poll (question, options, owner, bgc, fgc, textc, max) VALUES (?, ?, ?, ?, ?, ?, ?)");
	$stmt->execute([$question, serialize($answers), $_SESSION['user'], $bgc, $fgc, $textc, $max]);
	$created = true;

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
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Create Poll</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
		integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
		crossorigin="anonymous" referrerpolicy="no-referrer" />
	<style>
		body {
			font-family: Arial, sans-serif;
			background-color: #f2f2f2;
			background-color: #EEEEEE;
		}

		.container {
			max-width: 500px;
			margin: 0 auto;
			padding: 20px;
			background-color: #fff;
			border-radius: 5px;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
		}

		h1 {
			text-align: center;
			margin-bottom: 20px;
			color: #333;
		}

		.form-group {
			margin-bottom: 20px;
		}

		label {
			display: block;
			font-weight: bold;
			margin-bottom: 5px;
			color: #666;
		}

		input[type="text"],
		input[type="radio"] {
			margin-top: 5px;
			width: 100%;
			padding: 8px;
			border: 1px solid #ccc;
			border-radius: 3px;
			box-sizing: border-box;
		}

		.option-group {
			margin-bottom: 10px;
			display: flex;
			align-items: center;
		}

		.option-group input[type="text"] {
			margin-right: 10px;
		}

		.add-option-btn {
			display: inline-block;
			margin-top: 5px;
			cursor: pointer;
			color: #3498db;
		}

		.settings-group {
			margin-bottom: 20px;
		}

		.settings-group label {
			margin-bottom: 10px;
		}

		.create-poll-btn {
			display: block;
			margin: 20px auto;
			padding: 10px 20px;
			background-color: #3498db;
			color: #fff;
			border: none;
			border-radius: 5px;
			font-size: 16px;
			cursor: pointer;
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

		.settings-form {
			max-width: 500px;
			margin: 0 auto;
		}

		.settings-form label {
			display: block;
			margin-bottom: 10px;
			font-weight: bold;
		}

		.settings-form input[type="text"],
		.settings-form input[type="password"] {
			width: 100%;
			padding: 10px;
			border: 1px solid #ccc;
			border-radius: 4px;
			margin-bottom: 20px;
		}

		.settings-form button {
			padding: 10px 20px;
			background-color: #333;
			color: #fff;
			border: none;
			border-radius: 4px;
			cursor: pointer;
		}

		.menu-button {
			color: #333;
			font-size: 24px;
			cursor: pointer;
			position: fixed;
			left: 20px;
			top: 20px;
			z-index: 999;
		}
	</style>
</head>

<body>
	<div class="sidebar">
		<h3 style="position: absolute; top: 5px; left: 5px;">SimplePolls</h3>
		<ul>
			<li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
			<li><a href="poll.php"><i class="fas fa-plus"></i> New Poll</a></li>
			<li><a href=""><i class="fas fa-cog"></i> Account Settings</a></li>
			<li><a href="#"><i class="fas fa-trash-alt"></i> Delete Account</a></li>
			<li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
		</ul>
	</div>
	<?php if ($created) { ?>
		<div class="container">
			<h1>Poll Created Successfully</h1>
			<p>Your poll has been created successfully.</p>
			<p>Link to your poll:</p>
			<a href="../../polls/?pid=<?php echo $pdo->lastInsertId(); ?>">../../polls/?pid=<?php echo $pdo->lastInsertId(); ?></a>
		</div>

		<?php
		exit();
	}
	?>
	<div class="container">
		<h1>Create Poll</h1>
		<form id="pollForm" action="#" method="POST">
			<div class="form-group">
				<label for="question">Question:</label>
				<input type="text" id="question" name="question" required>
				<br><br>
				<label for="options">Options:</label>
			</div>
			<div class="form-group">
				<div class="option-group">
					<span class="add-option-btn" onclick="addOption()">+ Add Option</span>
				</div>
			</div>
			<div class="settings-group">
				<label for="maxResponses">Max Responses per User:</label>
				<input type="number" id="maxResponses" name="maxResponses" min="1" value="1" required>
			</div>
			<div class="settings-group">
				<label for="backgroundColor">Background Color:</label>
				<input type="color" id="backgroundColor" name="backgroundColor" value="#228C22" required>
			</div>
			<div class="settings-group">
				<label for="foregroundColor">Foreground Color:</label>
				<input type="color" id="foregroundColor" name="foregroundColor" value="#613B16" required>
			</div>
			<div class="settings-group">
				<label for="textColor">Text Color:</label>
				<input type="color" id="textColor" name="textColor" value="#ffffff" required>
			</div>
			<button type="submit" class="create-poll-btn">Create Poll</button>
		</form>
	</div>

	<script>
		function addOption() {
			const optionCount = document.querySelectorAll('.option-group').length;

			if (optionCount < 5) {
				const optionGroup = document.createElement('div');
				optionGroup.className = 'option-group';
				optionGroup.innerHTML = `
		  <input type="text" class="option" name="options[]" required>
		`;
				const optionsContainer = document.querySelector('.form-group');
				optionsContainer.appendChild(optionGroup);
			}
		}
	</script>
</body>

</html>