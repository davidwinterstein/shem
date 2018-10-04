<?php

require_once('config.php');

if ($db['conn']->connect_errno) {
	die('MySQL connection failed: ERR '.$conn->connect_errno);
}

if (isset($_GET['p'])) {
	$PAGE = $_GET['p'];
} else {
	$PAGE = "add";
}

// GENERATE OUTPUT
if ($PAGE == "add") {
	// "ADD" SITE

	// first, check if the form has already been submitted and process data
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		// open output block for result
		$MSG = '<p style="margin-bottom: -20px; font-weight: bold;">';

		// validate input
		$ERR = 0;
		if (isset($_POST['date']) && !empty($_POST['date']) && isset($_POST['time']) && !empty($_POST['time'])) {
			$WHEN = $_POST['date'] . " " . $_POST['time'];
			if (!preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $WHEN)) {
				$ERR++;
				$MSG .= '<span style="color: red;">Please specify a valid value for "when" or leave empty to use current date and time.</span> ';
			}
		} else {
			$WHEN = date('Y-m-d H:i:s');
		}
		if (isset($_POST['who']) && is_numeric($_POST['who'])) {
			$WHO = (int)$_POST['who'];
		} else {
			$ERR++;
			$MSG .= '<span style="color: red;">Please specify a valid value for "who".</span> ';
		}
		if (isset($_POST['amount']) && is_numeric($_POST['amount'])) {
			$AMOUNT = (float)$_POST['amount'];
		} else {
			$ERR++;
			$MSG .= '<span style="color: red;">Please specify a valid value for "amount".</span> ';
		}
		if (isset($_POST['comment'])) {
			$COMMENT = $db['conn']->real_escape_string($_POST['comment']);
		} else {
			$ERR++;
			$MSG .= '<span style="color: red;">Please specify a valid value for "comment".</span> ';
		}

		if ($ERR === 0) {
			$qry_add = 'INSERT INTO expenses(e_pid,e_time,e_value,e_comment) VALUES(' . $WHO . ',"' . $WHEN . '",' . $AMOUNT . ',"' . $COMMENT . '")';
			if ($res_add = $db['conn']->query($qry_add)) {
				$MSG .= '<span style="color: green;">Expense added successfully.</span>';
			} else {
				$MSG .= '<span style="color: red;">Expense could not be added (MySQL query execution failed).</span>';
			}
		}

		// close output block for result
		$MSG .= '</p>';
	} else {
		$MSG = '';
	}


	// second, output the form
	$qry_opt = "SELECT p_id,p_name FROM people";
	if (!$res_opt = $db['conn']->query($qry_opt)) {
		$MAIN = $MSG . '<p style="color: red; font-weight: bold;">Couldn\'t get option value data (MySQL query execution failed).</p>';
	} else {
		$OPT = '';
		while ($option = $res_opt->fetch_assoc()) {
			$OPT .= '<option value="' . $option['p_id'] . '">' . $option['p_name'] . '</option>';
		}
		$DATEDEFAULT = date('Y-m-d');
		$TIMEDEFAULT = date('H:i:s');
		$MAIN = $MSG . '
<h2>add new expense</h2>
<form action="?p=add" class="form" id="form-add" method="POST">
	<table>
		<tr class="form-group">
			<td style="text-align: right;"><label for="who">who:</label></td>
			<td style="text-align: left;"><select name="who" required>
				' . $OPT . '
			</select></td>
		</tr>
		<tr class="form-group">
			<td style="text-align: right;"><label for="when">when:</label></td>
			<td style="text-align: left;"><input type="date" name="date" value="' . $DATEDEFAULT . '" style="width: 50%;"><input type="time" step="1" name="time" value="' . $TIMEDEFAULT . '" class="without-ampm" style="width: 45%;"></td>
		</tr>
		<tr class="form-group">
			<td style="text-align: right;"><label for="amount">amount:</label></td>
			<td style="text-align: left;"><input type="number" step="0.01" name="amount" placeholder="123.45" style="width: 90%;" required> €</td>
		</tr>
		<tr class="form-group">
			<td style="text-align: right;"><label for="comment">comment:</label></td>
			<td style="text-align: left;"><input type="text" name="comment" placeholder="ie. \'rewe\' or \'amazon: spices\'" required></td>
		</tr>
	</table>
	<div class="form-group">
		<button type="submit" id="submit">submit</button>
	</div>
</form>';
	}

} else {
	// "OVERVIEW" SITE
	$MAIN = '<h2>recap</h2>';

	// first, output recap for all people in 'people' table
	$qry_ppl = "SELECT p_name FROM people";
	if (!$res_ppl = $db['conn']->query($qry_ppl)) {
		$MAIN .= '<p style="color: red; font-weight: bold;">Couldn\'t get recap data (MySQL query execution failed).</p>';
	} else {
		// create table with people names as column heading and sum of expenses als column value
		while ($people = $res_ppl->fetch_assoc()) {
			foreach ($people as $person) {
				$expenses_sum = 0.0;
				$qry_sum = "SELECT expenses.e_value FROM expenses INNER JOIN people ON expenses.e_pid = people.p_id WHERE people.p_name = '" . $person . "'";
				if (!$res_sum = $db['conn']->query($qry_sum)) {
					$RECAP[$person] = "?";
				} else {
					while ($expenses = $res_sum->fetch_assoc()) {
						foreach ($expenses as $expense) {
							$expenses_sum += $expense;
						}
					}
					$RECAP[$person] = $expenses_sum;
				}
			}
		}
		$MAIN .= '<table><tr>';
		foreach ($RECAP as $person => $expenses) {
				$MAIN .= '<th>' . $person . '</th>';
		}
		$MAIN .= '</tr><tr>';
		foreach ($RECAP as $person => $expenses) {
				$MAIN .= '<td>' . $expenses . '€</td>';
		}
		$MAIN .= '</tr></table>';
	}

	// second, output a filterable list of all expenses per person
	$MAIN .= '<h2>full list</h2>
<table id="list">
	<tr>
		<th>who</th>
		<th>when</th>
		<th>amount</th>
		<th>comment</th>
	</tr>
	<tr>
		<th><input type="text" id="who" onkeyup="filter()" placeholder="filter"></th>
		<th><input type="text" id="when" onkeyup="filter()" placeholder="filter"></th>
		<th></th>
		<th><input type="text" id="comment" onkeyup="filter()" placeholder="filter"></th>
	</tr>';

	$qry_list = "SELECT people.p_name AS who, expenses.e_time AS whengrml, expenses.e_value AS amount, expenses.e_comment AS comment FROM people INNER JOIN expenses ON people.p_id = expenses.e_pid ORDER BY whengrml DESC";
	if (!$res_list = $db['conn']->query($qry_list)) {
		$MAIN .= '<tr><td>?</td><td>?</td><td>?</td><td>?</td></tr>';
	} else {
		while ($row_list = $res_list->fetch_assoc()) {
			$MAIN .= '<tr><td>' . $row_list['who'] . '</td><td>' . $row_list['whengrml'] . '</td><td style="text-align: left;">' . $row_list['amount'] . '€</td><td style="text-align: left;">' . $row_list['comment'] . '</td></tr>';
		}
	}
	$MAIN .= '</table>';
}


// HTML OUTPUT STUFF
$HEADER = '
<!DOCTYPE html>
<html>
	<head>
		<link rel="icon" href="favicon.ico" type="image/png" />
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Ubuntu" />
		<link rel="stylesheet" href="inc/css/style.css" />
		<meta name="viewport" content="width=device-width">
		<meta name="viewport" content="initial-scale=1.0">
		<meta charset="UTF-8" />
		<meta name="keywords" content="budget,calculation" />
		<meta name="description" content="overview and budget summary calculation for shared households" />
		<meta name="author" content="David Winterstein" />
		<title>budget calc</title>
		<script src="inc/js/filter.js"></script>
	</head>
	<body>
		<div id="wrapper">
			<header>
				<nav>
					<ul id="menu_main">
					<li><a href="?p=overview">overview</a></li>
					<li><a href="?p=add">add</a></li>
				</ul>
			</nav>
		</header>
		<main>';
$FOOTER = '
		</main>
		<footer>
			<p>powered by <a target="_blank" href="https://www.winterstein.one">winterstein.one</a></p>
		</footer>
	</body>
</html>';

// ECHO COMBINED OUTPUT
echo $HEADER . $MAIN . $FOOTER;
