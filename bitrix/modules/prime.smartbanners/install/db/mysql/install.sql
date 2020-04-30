create table if not exists b_prime_smartbanners
(
	ID int(18) not null auto_increment,
	SID text,
	NAME varchar(255),
	URL varchar(255),
	ACTIVE char(1) not null DEFAULT 'Y',
	IMAGE_ID int(18),
	PAUSE varchar(255),
	SHOW_FROM date,
	SHOW_TO date,
	SHOW_OFF text,
	SHOW_POSITION text,
	SHOW_OUT_SITE char(1),
	SHOW_TIME char(1),
	OPEN_NEW_WINDOW char(1),
	SHOWS int(18) not null DEFAULT 0,
	CLICKS int(18) not null DEFAULT 0,
	primary key (ID)
);
