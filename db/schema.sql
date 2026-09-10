create table users (
  id int unsigned not null auto_increment primary key,
  first_name varchar(100) not null,
  last_name varchar(100) not null,
  email varchar(255) not null unique,
  password_hash varchar(255) not null
) engine=innodb default charset=utf8mb4 collate=utf8mb4_0900_ai_ci;

create table interest_groups (
  id int unsigned not null auto_increment primary key,
  name varchar(200) not null
) engine=innodb default charset=utf8mb4 collate=utf8mb4_0900_ai_ci;

create table memberships (
  group_id int unsigned not null,
  user_id int unsigned not null,
  role enum('member', 'administrator') not null,
  primary key (group_id, user_id),
  foreign key (group_id) references interest_groups (id),
  foreign key (user_id) references users (id)
) engine=innodb default charset=utf8mb4 collate=utf8mb4_0900_ai_ci;

create table membership_requests (
  group_id int unsigned not null,
  user_id int unsigned not null,
  primary key (group_id, user_id),
  foreign key (group_id) references interest_groups (id),
  foreign key (user_id) references users (id)
) engine=innodb default charset=utf8mb4 collate=utf8mb4_0900_ai_ci;

create table invitations (
  token_hash char(64) character set ascii collate ascii_bin not null primary key,
  group_id int unsigned not null,
  expires_at timestamp not null default (current_timestamp + interval 24 hour),
  foreign key (group_id) references interest_groups (id)
) engine=innodb default charset=utf8mb4 collate=utf8mb4_0900_ai_ci;

create table discussions (
  id int unsigned not null auto_increment primary key,
  group_id int unsigned not null,
  subject varchar(200) not null,
  unique (id, group_id),
  foreign key (group_id) references interest_groups (id)
) engine=innodb default charset=utf8mb4 collate=utf8mb4_0900_ai_ci;

create table posts (
  id int unsigned not null auto_increment primary key,
  discussion_id int unsigned not null,
  group_id int unsigned not null,
  user_id int unsigned not null,
  body text not null,
  foreign key (discussion_id, group_id) references discussions (id, group_id),
  foreign key (group_id, user_id) references memberships (group_id, user_id)
) engine=innodb default charset=utf8mb4 collate=utf8mb4_0900_ai_ci;
