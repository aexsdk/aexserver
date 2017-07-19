<?php
	include_once 'pdo_db.php';
	$db = new pdo_db ( );
	
/*	if (isset($_POST['validuser'])) {
		$str = $_POST['validuser'];
		$sql = "SELECT * FROM ez_wfs_db.sp_wfs_judge_email_uniqueness( '$str')";
		$rows = $db->query ( $sql );
		$n_return_value = intval($rows[0]['n_return_value']);
		if ($n_return_value == 1) {
			echo 1;
		} else {
			echo 0;
		}
	}*/
	
	if (isset($_POST['validmail'])) {
		$str = $_POST['validmail'];
		$sql = "SELECT * FROM ez_wfs_db.sp_wfs_judge_email_uniqueness('$str')";
		$rows = $db->query ( $sql );
		$n_return_value = intval($rows[0]['n_return_value']);
		if ($n_return_value == 1) {
			echo 1;
		} else {
			echo 0;
		}
	}
	
?>