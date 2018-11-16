<?php

ini_set('display_errors','on');
ini_set('display_startup_errors','on');

require_once('config.php');

if ($db['conn']->connect_errno) {
	die('MySQL connection failed: ERR '.$conn->connect_errno);
}

session_start();
$MSG = "";

if (isset($_GET['p'])) {
	$PAGE = $_GET['p'];
} else {
	if (!isset($_SESSION['p_id']) || empty($_SESSION['p_id'])) {
		header('Location: ?p=login');
		die();
  } else {
		$PAGE = "add";
	}
}

// GENERATE OUTPUT
if ($PAGE == "logout") {
	// LOGOUT
	session_destroy();
	header('Location: ?p=login');
	die();
}

if ($PAGE == "login") {
	// "LOGIN" SITE
	// first, check if the form has already been submitted and process data
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		// validate input
		if (isset($_POST['user']) && !empty($_POST['user']) && isset($_POST['pass']) && !empty($_POST['pass'])) {
			$stmt_login = $db['conn']->prepare("SELECT * FROM people WHERE p_name = ?");
			$stmt_login->bind_param('s', $_POST['user']);
			$stmt_login->execute();
			$res_login = $stmt_login->get_result();
			$user = $res_login->fetch_object();
			if ( password_verify( $_POST['pass'], $user->p_pass ) ) {
				$_SESSION['p_id'] = $user->p_id;
				header('Location: ?p=add');
				die();
			} else {
				$MSG .= '<p style="margin-bottom: -20px; font-weight: bold;"><span style="color: red;">Login failed.</span></p>';
			}
		}
	}

	$MAIN = $MSG . '
<h2>login</h2>
<form action="?p=login" class="form" id="form-login" method="POST">
	<table>
		<tr class="form-group">
			<td style="text-align: right;"><label for="user">username:</label></td>
			<td style="text-align: left;"><input type="text" name="user" required></td>
		</tr>
		<tr class="form-group">
			<td style="text-align: right;"><label for="pass">password:</label></td>
			<td style="text-align: left;"><input type="password" name="pass" required></td>
		</tr>
	</table>
	<div class="form-group">
		<button type="submit" id="submit">submit</button>
	</div>
</form>';

} elseif ($PAGE == "settings") {
	// "SETTINGS" SITE
	if (!isset($_SESSION['p_id']) || empty($_SESSION['p_id'])) {
		header('Location: ?p=login');
		die();
	}
	// first, check if the form has already been submitted and process data
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		// open output block for result
		$MSG .= '<p style="margin-bottom: -20px; font-weight: bold;">';

		// validate input
		$ERR = 0;
		if (isset($_POST['user']) && !empty($_POST['user'])) {
			$SETUSER = $db['conn']->real_escape_string($_POST['user']);
		} else {
			$ERR++; $MSG .= '<span style="color: red;">Please specify a valid value for "username".</span> ';
		}
		if (isset($_POST['first']) && !empty($_POST['first'])) {
			$SETFIRST = $db['conn']->real_escape_string($_POST['first']);
		} else {
			$SETFIRST = '';
		}
		if (isset($_POST['last']) && !empty($_POST['last'])) {
			$SETLAST = $db['conn']->real_escape_string($_POST['last']);
		} else {
			$SETLAST = '';
		}
		if (isset($_POST['mail']) && !empty($_POST['mail']) && filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
			$SETMAIL = $db['conn']->real_escape_string($_POST['mail']);
		} else {
			$ERR++; $MSG .= '<span style="color: red;">Please specify a valid value for "mail address".</span> ';
		}
		if (isset($_POST['passnew']) && !empty($_POST['passnew'])) {
			if (isset($_POST['passconfirm']) && !empty($_POST['passconfirm'])) {
				if ($_POST['passnew'] === $_POST['passconfirm']) {
					if (strlen($_POST['passnew']) >= 12 && preg_match('#.*^(?=.{12,})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).*$#',$_POST['passnew'])) {
						$SETPASS = 'p_pass = "' . $db['conn']->real_escape_string(password_hash($_POST['pass'], PASSWORD_DEFAULT)) . '", ';
					} else {
						$ERR++; $MSG .= '<span style="color: red;">Please specify a password with a length of at least 12 characters, consisting of at least one upper and lower case letter and a number.</span> ';
					}
				} else {
					$ERR++; $MSG .= '<span style="color: red;">Passwords didn\'t match.</span> ';
				}
			} else {
				$ERR++; $MSG .= '<span style="color: red;">Please confirm your password.</span> ';
			}
		} else {
			$SETPASS = '';
		}

		if ($ERR === 0) {
			$qry_set = 'UPDATE people SET	p_name = "' . $SETUSER . '", ' . $SETPASS . 'p_first = "' . $SETFIRST . '", p_last = "' . $SETLAST . '", p_mail = "' . $SETMAIL . '" WHERE p_id = ' . $_SESSION['p_id'];
			if ($res_set = $db['conn']->query($qry_set)) {
				$MSG .= '<span style="color: green;">Updated settings successfully.</span>';
			} else {
				$MSG .= '<span style="color: red;">Settings could not be added (MySQL query execution failed).</span>';
			}
		}

		// close output block for result
		$MSG .= '</p>';
	}

	// second, get default values and generate the form
	$qry_setopt = "SELECT p_name,p_first,p_last,p_mail FROM people WHERE p_id = " . $_SESSION['p_id'];
	if (!$res_setopt = $db['conn']->query($qry_setopt)) {
		$MSG .= '<p style="color: red; font-weight: bold;">Couldn\'t get option value data (MySQL query execution failed).</p>';
		$SETOPTUSER = ''; $SETOPTFIRST = ''; $SETOPTLAST = ''; $SETOPTMAIL = '';
	} else {
		while ($option = $res_setopt->fetch_assoc()) {
			if (isset($option['p_name']) && !empty($option['p_name'])) {
				$SETOPTUSER = $option['p_name'];
			} else {
				$SETOPTUSER = '';
			}
			if (isset($option['p_first']) && !empty($option['p_first'])) {
				$SETOPTFIRST = $option['p_first'];
			} else {
				$SETOPTFIRST = '';
			}
			if (isset($option['p_last']) && !empty($option['p_last'])) {
				$SETOPTLAST = $option['p_last'];
			} else {
				$SETOPTLAST = '';
			}
			if (isset($option['p_mail']) && !empty($option['p_mail'])) {
				$SETOPTMAIL = $option['p_mail'];
			} else {
				$SETOPTMAIL = '';
			}
		}
	}

	$MAIN = $MSG . '
<h2>user settings</h2>
<form action="?p=settings" class="form" id="form-login" method="POST">
	<table>
		<tr class="form-group">
			<td style="text-align: right;"><label for="user">username*:</label></td>
			<td style="text-align: left;"><input type="text" name="user" value="' . $SETOPTUSER . '" required></td>
		</tr>
		<tr class="form-group">
			<td style="text-align: right;"><label for="first">first name:</label></td>
			<td style="text-align: left;"><input type="text" name="first" value="' . $SETOPTFIRST . '"></td>
		</tr>
		<tr class="form-group">
			<td style="text-align: right;"><label for="last">last name:</label></td>
			<td style="text-align: left;"><input type="text" name="last" value="' . $SETOPTLAST . '"></td>
		</tr>
		<tr class="form-group">
			<td style="text-align: right;"><label for="mail">mail address*:</label></td>
			<td style="text-align: left;"><input type="email" name="mail" value="' . $SETOPTMAIL . '" required></td>
		</tr>
		<tr class="form-group topboing">
			<td style="text-align: right;"><label for="passnew">new password:</label></td>
			<td style="text-align: left;"><input type="password" name="passnew"></td>
		</tr>
		<tr class="form-group">
			<td style="text-align: right;"><label for="passconfirm">confirm password:</label></td>
			<td style="text-align: left;"><input type="password" name="passconfirm"></td>
		</tr>
	</table>
	<div class="form-group">
		<button type="submit" id="submit">submit</button>
	</div>
</form>';

} elseif ($PAGE == "add") {
	// "ADD" SITE
	if (!isset($_SESSION['p_id']) || empty($_SESSION['p_id'])) {
		header('Location: ?p=login');
		die();
	}
	// first, check if the form has already been submitted and process data
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		// open output block for result
		$MSG .= '<p style="margin-bottom: -20px; font-weight: bold;">';

		// validate input
		$ERR = 0;
		if (isset($_POST['date']) && !empty($_POST['date']) && isset($_POST['time']) && !empty($_POST['time'])) {
			$WHEN = $_POST['date'] . " " . $_POST['time'];
			if (!preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $WHEN)) {
				$ERR++; $MSG .= '<span style="color: red;">Please specify a valid value for "when" or leave empty to use current date and time.</span> ';
			}
		} else {
			$WHEN = date('Y-m-d H:i:s');
		}
		if (isset($_POST['who']) && is_numeric($_POST['who'])) {
			$WHO = (int)$_POST['who'];
		} else {
			$ERR++; $MSG .= '<span style="color: red;">Please specify a valid value for "who".</span> ';
		}
		if (isset($_POST['amount']) && is_numeric($_POST['amount'])) {
			$AMOUNT = (float)$_POST['amount'];
		} else {
			$ERR++; $MSG .= '<span style="color: red;">Please specify a valid value for "amount".</span> ';
		}
		if (isset($_POST['comment'])) {
			$COMMENT = $db['conn']->real_escape_string($_POST['comment']);
		} else {
			$ERR++; $MSG .= '<span style="color: red;">Please specify a valid value for "comment".</span> ';
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

	// second, get default values and generate the form
	$qry_addopt = "SELECT p_id,p_name FROM people";
	if (!$res_addopt = $db['conn']->query($qry_addopt)) {
		$MAIN = $MSG . '<p style="color: red; font-weight: bold;">Couldn\'t get option value data (MySQL query execution failed).</p>';
	} else {
		$DEFAULTVALUE = $_SESSION['p_id'];
		$OPT = '';
		while ($option = $res_addopt->fetch_assoc()) {
			if ( $DEFAULTVALUE == $option['p_id'] ) {
				$OPTSELECT = " selected";
			} else {
				$OPTSELECT = "";
			}
			$OPT .= '<option value="' . $option['p_id'] . '"' . $OPTSELECT . '>' . $option['p_name'] . '</option>';
		}
		$DEFAULTDATE = date('Y-m-d');
		$DEFAULTTIME = date('H:i:s');
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
			<td style="text-align: left;"><input type="date" name="date" value="' . $DEFAULTDATE . '" style="width: 50%;"><input type="time" step="1" name="time" value="' . $DEFAULTTIME . '" class="without-ampm" style="width: 45%;"></td>
		</tr>
		<tr class="form-group">
			<td style="text-align: right;"><label for="amount">amount:</label></td>
			<td style="text-align: left;"><input type="number" step="0.01" name="amount" placeholder="123.45" style="width: 90%;" required> €</td>
		</tr>
		<tr class="form-group">
			<td style="text-align: right;"><label for="comment">comment:</label></td>
			<td style="text-align: left;"><input type="text" name="comment" placeholder="ie. \'rewe\' or \'amazon - spices\'" required></td>
		</tr>
	</table>
	<div class="form-group">
		<button type="submit" id="submit">submit</button>
	</div>
</form>';
	}

} elseif ($PAGE == "overview") {
	// "OVERVIEW" SITE
	if (!isset($_SESSION['p_id']) || empty($_SESSION['p_id'])) {
		header('Location: ?p=login');
		die();
	}

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

	$RESULTS = 0;
	$SUM = 0.00;
	$qry_list = "SELECT people.p_name AS who, expenses.e_time AS whengrml, expenses.e_value AS amount, expenses.e_comment AS comment FROM people INNER JOIN expenses ON people.p_id = expenses.e_pid ORDER BY whengrml DESC, expenses.e_id DESC";
	if (!$res_list = $db['conn']->query($qry_list)) {
		$MAIN .= '<tr><td>?</td><td>?</td><td>?</td><td>?</td></tr>';
	} else {
		while ($row_list = $res_list->fetch_assoc()) {
			$MAIN .= '<tr><td>' . $row_list['who'] . '</td><td>' . $row_list['whengrml'] . '</td><td style="text-align: left;">' . $row_list['amount'] . '€</td><td style="text-align: left;">' . $row_list['comment'] . '</td></tr>';
			$RESULTS++;
			$SUM += $row_list['amount'];
		}
	}
	$MAIN .= '</table><p><strong id="results">' . $RESULTS . '</strong> results | sum: <strong id="sum">' . $SUM . '</strong>€</p>';
} else {
	if (!isset($_SESSION['p_id']) || empty($_SESSION['p_id'])) {
		header('Location: ?p=login');
		die();
	} else {
		header('Location: ?p=overview');
		die();
	}
}

$MENUADMIN='';
if (isset($_SESSION['p_id'])) {
	$MENUITEMS='<li><a href="?p=overview">overview</a></li><li><a href="?p=add">add</a></li><li><a href="?p=settings">settings</a></li>';
	$MENULOGIN='<li><a href="?p=logout">logout</a></li>';
} else {
	$MENUITEMS='';
	$MENULOGIN='<li><a href="?p=login">login</a></li>';
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
		<meta name="keywords" content="expense,management,summary,calculation" />
		<meta name="description" content="shared household expense management - expense overview and summary calculation" />
		<meta name="author" content="David Winterstein" />
		<title>shem</title>
		<script src="inc/js/filter.js"></script>
	</head>
	<body>
		<div id="wrapper">
			<header>
				<nav>
					<ul id="menu_main">
					' . $MENUITEMS . $MENUADMIN . $MENULOGIN . '
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
