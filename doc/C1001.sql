
create table C1001_ImageInfo(
Id INT not null AUTO_INCREMENT,
Url VARCHAR(128) NOT NULL,
MaxNum      int(11) default 0,
ClickNum    int(11) unsigned  default 0,
InsertTime  int(11) unsigned  default 0,
DelFlag     int default 0,
ImageType   int default 0,0-群二维码 1-公众号
ImageName   VARCHAR(128) NOT NULL
primary key (Id),
UNIQUE KEY unique_key (`InsertTime`,`ClickNum`,`DelFlag`)
)ENGINE=InnoDB AUTO_INCREMENT=10000 DEFAULT CHARSET=gbk;

alter table C1001_ImageInfo add MaxNum    int(11) default 0;
alter table C1001_ImageInfo add ImageType int default 0;
alter table C1001_ImageInfo add ImageName VARCHAR(128) NOT NULL;

insert into C1001_ImageInfo(Url,MaxNum,ClickNum,InsertTime,DelFlag) values('http://wy626.com/c1001/wy1.png',8,0,UNIX_TIMESTAMP(),0);
insert into C1001_ImageInfo(Url,MaxNum,ClickNum,InsertTime,DelFlag) values('http://wy626.com/c1001/wy2.jpg',4,0,UNIX_TIMESTAMP(),0);

select Id,Url from C1001_ImageInfo where ClickNum < MaxNum and DelFlag = 0 order by InsertTime asc limit 1

select Id,Url from C1001_ImageInfowhere ClickNum < MaxNum and DelFlag = 0 order by InsertTime asc limit 1;

update C1001_ImageInfo set ImageType=2 where id = 10001;