(function(){
  $.fn.extend({
    iShare:function(param){
      var that = $(this);
      var ShareWeb = function(){
        var arr = [];
        that.find("a").each(function(index){
          arr.push({
            name:$(this).attr("data-ishare"),
            ele:$(this)
          });
        });
        return arr;
      }();
      
      var ShareCoding = function(obj,argObj){
        return obj.url+"&"+$.param(argObj);
      };
      var latestQqGroup = 0000;
      var ShareObj = {
        qqkongjian:{
          //qq空间 encodeURIComponent
          url:'http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?summary=&site=',
          coding : "encodeURIComponent",
          param:function(arg){
            var argObj = {
              url : arg.url,
              title : arg.title,
              desc : arg.content,
              pics : arg.image
            };
            this.url = ShareCoding(this,argObj);
          }
        },
        sinaweibo:{
          //新浪微博 encodeURIComponent
          url : 'http://service.weibo.com/share/share.php?content=',
          coding : "encodeURIComponent",
          param:function(arg){
            var argObj = {
              url : arg.url.indexOf("?") >= 1 ? arg.url+"&fr=&src=weibo":arg.url+"?fr&src=weibo",
              title : arg.content,
              pic : arg.image
            };
            this.url = ShareCoding(this,argObj);
          }
        },
        qqweibo:{
          //腾讯微博 encodeURI
          url : 'http://share.v.t.qq.com/index.php?c=share&a=index&appkey=',
          coding : "encodeURI",
          param:function(arg){
            var argObj = {
              url : arg.url,
              title : arg.content,
              pic : arg.image
            };
            this.url = ShareCoding(this,argObj);
          }
        },
        renren:{
          //人人网 encodeURIComponent
          url : 'http://widget.renren.com/dialog/share?srcUrl=',
          coding : "encodeURIComponent",
          param:function(arg){
            var argObj = {
              resourceUrl : arg.url,
              title : arg.title,
              message:arg.content,
              description:arg.content,
              pic : arg.image
            };
            this.url = ShareCoding(this,argObj);
          }
        },
        qqhaoyou:{
          //QQ好友 encodeURIComponent
          url : 'http://connect.qq.com/widget/shareqq/index.html?url=&title=&desc=&summary=&site=baidu&pics=',
          coding : "encodeURIComponent"
        },
        sohuweibo:{
          //搜狐微博 unescape title encodeURIComponen
          url : 'http://t.sohu.com/third/post.jsp?url=&title=&pic=',
          coding : ["escape",[{name:"title",coding:"encodeURIComponent"}]]
        },
        wangyiweibo:{
          //网易微博 encodeURIComponent
          url : 'http://t.163.com/article/user/checkLogin.do?info=&source=&images=&togImg=true',
          coding : "encodeURIComponent"
        },
        kaixinwang:{
          //开心网 encodeURIComponent
          url : 'http://www.kaixin001.com/rest/records.php?url=&style=11&content=&stime=&sig=',
          coding : "encodeURIComponent"
        },
        baidukongjian:{
          //百度空间 encodeURIComponent
          url : 'http://hi.baidu.com/pub/show/share?url=&title=&content=&linkid=',
          coding : "encodeURIComponent"
        },
        hexunwang:{
          //和讯网 unescape  title encodeURIComponent
          url : 'http://bookmark.hexun.com/post.aspx?url=&title=',
          coding : ["escape",[{name:"title",coding:"encodeURIComponent"}]]
        },
        hexunweibo:{
          //和讯微博 unescape title encodeURIComponent
          url : 'http://t.hexun.com/channel/shareweb.aspx?appkey=',
          coding : ["escape",[{name:"title",coding:"encodeURIComponent"}]],
          param:function(arg){
            var argObj = {
              url : arg.url,
              title : arg.content
            };
            this.url = ShareCoding(this,argObj);
          }
        }
      };
      
      var ShareSet = function(){
        var obj = {};
        for(var i = 0 ; i < ShareWeb.length ; i ++){
          var name = ShareWeb[i].name;
          obj[name] = ShareObj[name];
          obj[name]['ele'] = ShareWeb[i].ele;
          if(typeof obj[name]['param'] == 'function'){
            obj[name]['param'](param);
            obj[name].ele.attr("href",obj[name].url);
          }
        }
        return obj;
      };
      
      var ShareHandler = function(){
        if(that.find("a").length > 0){
          that.find("a").click(function(){
            window.open (this.href,'newwindow','height=640,width=640,top='+(window.screen.availHeight-30-640)/2+',left='+(window.screen.availWidth-10-640)/2+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');
            return false; 
          })
        }
      };
      
      var ShareInit = function(){
        var curObj = ShareSet();
        ShareHandler();
      };
      
      ShareInit();
    }
  });
})();
