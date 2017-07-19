<?php
	$dbArr = parse_ini_file('conf.ini',true);
	$db_type = $dbArr['PGDatabase']['db.adapter'];//数据库类型 Oracle 用ODI,对于开发者来说，使用不同的数据库，只要改这个
	$db_host = $dbArr['PGDatabase']['db.config.host'];//数据库主机名
	$db_name = $dbArr['PGDatabase']['db.config.dbname'];//数据库名称
	$db_user = $dbArr['PGDatabase']['db.config.username'];//数据库连接用户名
	$db_pass = $dbArr['PGDatabase']['db.config.password'];//数据库密码
	
	$db_dsn = "$db_type:host=$db_host;dbname=$db_name";
	
	class pdo_db extends PDO {
		public function __construct() {
			try {
				parent::__construct("$GLOBALS[db_dsn]",$GLOBALS['db_user'],$GLOBALS['db_pass']);
			} catch (PDOException $e) {
				die("Error: " . $e->__toString() . "<br />");
			}
		}
		
		public final function query($sql) {
			try {
				//PDO::setAttribute(PDO::ATTR_CASE,PDO::CASE_UPPER);
				$rs = parent::query($this->setSql($sql));
				$rs->setFetchMode(PDO::FETCH_ASSOC);
				return $rs->fetchAll();
			} catch (PDOException $e) {
				die("Error: " . $e->__toString() . "<br />");
			}
		}
		
		private final function setSql ($sql) {
			//处理 过滤SQL
			return $sql;
		}
		
		public final function exec($sql) {
			try {
				$rs = parent::exec($sql);
				return $rs;
			} catch (PDOException $e) {
				die("Error: " . $e->__toString() . "<br />");
			}
		}
	}
	
?>