create table `scope` (id int not null auto_increment, filename varchar(255), namespace varchar(255) not null, start int not null, end int not null, primary key(id));

create table `function`  (id int not null auto_increment, name varchar(255) not null, scope int not null, comment text, `return` int, primary key(id));
create table `class`     (id int not null auto_increment, name varchar(255) not null, scope int not null, comment text, abstract tinyint not null default 0, final tinyint not null default 0, primary key(id));
create table `interface` (id int not null auto_increment, name varchar(255) not null, scope int not null, comment text, primary key(id));
create table `trait`     (id int not null auto_increment, name varchar(255) not null, scope int not null, comment text, primary key(id));

create table `class-method`     (id int not null auto_increment, name varchar(255), class     int not null, comment text, start int not null, end int not null, visibility varchar(255) not null, static tinyint not null default 0, abstract tinyint not null default 0, final tinyint not null default 0, primary key(id));
create table `interface-method` (id int not null auto_increment, name varchar(255), interface int not null, comment text, start int not null, end int not null, static tinyint not null default 0, primary key(id));
create table `trait-method`     (id int not null auto_increment, name varchar(255), trait     int not null, comment text, start int not null, end int not null, visibility varchar(255) not null, static tinyint not null default 0, abstract tinyint not null default 0, final tinyint not null default 0, primary key(id));

create table `class-property` (id int not null auto_increment, name varchar(255), class int not null, comment text, line int not null, visibility varchar(255) not null, static tinyint not null default 0, const tinyint not null default 0, primary key(id));
create table `trait-property` (id int not null auto_increment, name varchar(255), trait int not null, comment text, line int not null, visibility varchar(255) not null, static tinyint not null default 0, const tinyint not null default 0, primary key(id));

create table `function-parameter`         (id int not null auto_increment, name varchar(255), function int not null, type varchar(255), `default` varchar(255), variadic tinyint not null default 0, primary key(id));
create table `class-method-parameter`     (id int not null auto_increment, name varchar(255), method   int not null, type varchar(255), `default` varchar(255), variadic tinyint not null default 0, primary key(id));
create table `interface-method-parameter` (id int not null auto_increment, name varchar(255), method   int not null, type varchar(255), `default` varchar(255), variadic tinyint not null default 0, primary key(id));
create table `trait-method-parameter`     (id int not null auto_increment, name varchar(255), method   int not null, type varchar(255), `default` varchar(255), variadic tinyint not null default 0, primary key(id));

create table global (id int not null auto_increment, name varchar(255), const tinyint not null default 0, comment text, primary key(id));
