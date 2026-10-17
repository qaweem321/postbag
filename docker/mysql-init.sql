-- Extra databases for local development: tests and the demo WordPress site.
CREATE DATABASE IF NOT EXISTS postbag_test;
GRANT ALL PRIVILEGES ON postbag_test.* TO 'postbag'@'%';
CREATE DATABASE IF NOT EXISTS wordpress;
CREATE USER IF NOT EXISTS 'wordpress'@'%' IDENTIFIED BY 'wordpress';
GRANT ALL PRIVILEGES ON wordpress.* TO 'wordpress'@'%';
