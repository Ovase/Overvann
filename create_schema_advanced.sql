
create database if not exists pedervl_overvann_wiki_db;

use pedervl_overvann_wiki_db;

drop table if exists yp_persons;

create table if not exists yp_persons(
   	userId integer primary key auto_increment,
   	username varchar(255) not null unique,
   	password varchar(100) not null,
   	name varchar(255) not null,
   	title varchar(255),
   	company varchar(255),
   	phonenumber varchar(20),
   	address varchar(255),
    industry varchar(255),
    workarea varchar(255),
    img_name varchar(255)
)engine=innodb;