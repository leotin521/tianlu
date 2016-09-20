解压文件到C盘根目录下

在要启用Rewrite的IIS站点的Isapi上添加这个筛选器

筛选器名称Rewrite

可执行文件选择 c:\Rewrite\Rewrite.dll　即可以了

httpd.ini是配置文件
如果您要对站点做防盗链处理，把以下代码加到httpd.ini后面。
RewriteCond Host: (.+)

RewriteCond Referer: (?!http://\\1.*).*

RewriteRule .*\.(?:gif|jpg|png|) /block.gif [I,O]

上面的代码意思是对站点进行防盗链处理。

如果您要对某一个或者某几个站点不进行防盗链，修改：RewriteCond Referer: (?!http://\\1.*).* 语句
例如修改为
RewriteCond Referer: (?!http://(?:u\.discuz\.net|www\.discuz\.net)).+

上面这个代码的意思就是除了http://u.discuz.net以及www.discuz.net这两个站点，在其它网站上盗链全部拒绝！

然后在网站根目录下建立block.gif文件 

盗链的网站显示的就是这个图片了

