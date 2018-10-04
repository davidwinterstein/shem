DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS people;

CREATE TABLE IF NOT EXISTS people (
	p_id				SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL,
	p_name			VARCHAR(255) NOT NULL,
	PRIMARY KEY	(p_id)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS expenses (
	e_id				INT UNSIGNED AUTO_INCREMENT NOT NULL,
	e_pid				SMALLINT UNSIGNED NOT NULL,
	e_time			DATETIME DEFAULT NULL,
	e_value			FLOAT NOT NULL,
	e_comment		MEDIUMTEXT NOT NULL,
	PRIMARY KEY (e_id),
	FOREIGN KEY (e_pid)
		REFERENCES people(p_id)
			ON UPDATE CASCADE
			ON DELETE CASCADE
) ENGINE=INNODB;

INSERT INTO people VALUES
	(1,'john'),
	(2,'julia'),
	(3,'zac');

INSERT INTO expenses(e_pid,e_time,e_value,e_comment) VALUES
	(1,'2018-07-07 13:10:12',213.46,'rewe'),
	(3,'2018-07-12 00:11:13',64.26,'amazon - water filters'),
	(2,'2018-07-21 14:22:08',39.95,'rewe'),
	(2,'2018-07-26 21:01:37',6.42,'amazon - ash tray'),
	(3,'2018-08-04 19:31:21',83.19,'rewe'),
	(2,'2018-08-10 20:41:00',90.42,'rewe'),
	(3,'2018-08-14 23:09:54',103.99,'amazon - wading pool'),
	(2,'2018-08-24 20:06:11',53.54,'rewe'),
	(1,'2018-09-04 19:44:06',103.21,'rewe'),
	(2,'2018-09-04 19:48:33',23.12,'amazon - office supplies');
