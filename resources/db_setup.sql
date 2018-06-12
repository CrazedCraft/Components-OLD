CREATE TABLE IF NOT EXISTS auth (
	id INTEGER AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(16),
	hash CHAR(128),
	email VARCHAR(32) DEFAULT '',
	lastip VARCHAR(50) DEFAULT '0.0.0.0',
	islocked INT DEFAULT 0,
	lockreason VARCHAR(128) DEFAULT '',
	lang CHAR(6) DEFAULT 'en',
	timeplayed INT DEFAULT 0,
	lastlogin INT DEFAULT 0,
	registerdate INT DEFAULT 0,
	coins INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS bans (
	id INT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(16),
	ip VARCHAR(50) DEFAULT '0.0.0.0',
	uid VARCHAR(128),
	expires INT DEFAULT 0,
	created INT DEFAULT 0,
	reason VARCHAR(256),
	issuer_name VARCHAR(16),
	valid BIT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS network_nodes (
	id INT AUTO_INCREMENT PRIMARY KEY,
	node_name VARCHAR(64) NOT NULL,
	node_display VARCHAR(64) NOT NULL,
	max_servers INT DEFAULT 12
);

CREATE TABLE IF NOT EXISTS network_servers (
	id INT AUTO_INCREMENT PRIMARY KEY,
	server_motd VARCHAR(32) DEFAULT 'CrazedCraft: Server',
	node_id INT NOT NULL,
	node VARCHAR(6) NOT NULL,
	address VARCHAR(45) DEFAULT '0.0.0.0',
	server_port INT DEFAULT 19132,
	online_players INT DEFAULT 0,
	max_players INT DEFAULT 100,
	player_list TEXT DEFAULT '[]',
	online BIT DEFAULT 0,
	last_sync INT DEFAULT 0
);