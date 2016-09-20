itz.repay = {
},
itz.repay.init = function (repayData) {
  this.date(repayData)
},
itz.repay.date = function (repayData) {
  function dateArg(year, month) {
    var playDate = new Date;
    Date.MONTH_DAYS = [
      31,
      0,
      31,
      30,
      31,
      30,
      31,
      31,
      30,
      31,
      30,
      31
    ],
    Date.getMonthDays = function (year, month) {
      return year -= 0,
      month -= 0,
      1 == month ? 0 != year % 4 || 0 == year % 100 && 0 != year % 400 ? 28 : 29 : Date.MONTH_DAYS[month]
    },
    Date.getMonthWeek = function (year, month) {
      return playDate.setFullYear(year),
      playDate.setMonth(month),
      playDate.setDate(0),
      playDate.getDay()
    };
    var days = Date.getMonthDays(year, month),
    week = Date.getMonthWeek(year, month);
    return {
      arg: [
        days,
        week,
        Math.ceil((week + days) / 7),
        week + days,
        7 * Math.ceil((week + days) / 7)
      ]
    }
  }
  function datePlot(year, month) {
    for (var argement = dateArg(year, month), html = '', i = 0, g = argement.arg[3], j = argement.arg[4], w = argement.arg[1]; j > i; i++) html += w > i ? '<span class="item-day item-day-w"></span>' : g > i ? '<span class="item-day item-day-d item-day-' + (i - w + 1) + '" ><span class="item-day-txt">' + (i - w + 1) + '<br/><strong class="ffA fw-n">￥0.00</strong></span></span>' : '<span class="item-day item-day-w"></span>';
    return html
  }
  function sortNumber(a, b) {
    return a - b
  }
  var curDate = new Date,
  selectYear = $('#selectYear');
  curDate.setFullYear(repayData.date.year),
  curDate.setMonth(repayData.date.month),
  curDate.setDate(repayData.date.day);
  var dateEle = $('#date .date-con'),
  dateJsonFun = function (curDate) {
    return {
      year: curDate.getFullYear(),
      month: curDate.getMonth(),
      date: curDate.getDate(),
      day: curDate.getDay()
    }
  },
  curDataJson = dateJsonFun(curDate);
  if (repayData.argDate.year && (repayData.argDate.month || 0 == repayData.argDate.month)) var year = repayData.argDate.year,
  month = repayData.argDate.month;
   else var year = curDataJson.year,
  month = curDataJson.month;
  var system = (curDataJson.day, [
  ]),
  monthSystem = function (year, month) {
    var myDate = new Date(year, month, 1);
    return {
      year: myDate.getFullYear(),
      month: myDate.getMonth()
    }
  },
  ajax = 0,
  ajaxFun = function (year, month, fn) {
    ajax && ajax.abort(),
    ajax = $.ajax({
      url: '/Member/Borrowin/ajaxrepay',
      type: 'POST',
      //beforeSend: function () {
        //$('.user-box-con-2').css('position', 'relative').append('<img class="loadimg" src="' + window.loadimgUrl + '" style="position:absolute;top:50%;left:50%"/>')
      //},
      data: {
        year: year,
        month: month
      },
      timeout: 20000,
      success: function (data) {
        //$('.loadimg').remove(),
    	var sArr=data.split("KecretKey")
        ajax = 0,
        $('#ajaxList').html(sArr[0]);
        $('.ffA').html(sArr[1]);
        //fn(year, month, data)
      }
    })
  },
  staticYear = !0,
  pageState = !0,
  init = function (year1, month1) {
    year = year1,
    month = month1,
    dateEle.html(datePlot(year, month)),
    ajaxFun(year, month + 1, function (year, month, data) {
      $.ajax({
        url: '',
        type: 'GET',
        dataType: 'jsonp',
        jsonp: 'jsoncallback',
        success: function (data) {
 //         0 != data.code && (window.location.href = '/newuser/index/login?ret_url=' + repayData.headUrl)
        }
      });
      var monthMoney = data.data.monthMoney ? itz.numFormat(parseFloat(data.data.monthMoney).toFixed(2))  : '0.00';
      if ($('.user-box-title-more-2 strong').text(monthMoney), 1 == data.code) return $('#ajaxList').html('<td style="text-align:center; line-height:50px; border-bottom:none;">当月暂无还款明细</td>'),
      $('.pagination').hide(),
      month - 1 == curDataJson.month && year == curDataJson.year && dateEle.find('span.item-day-' + curDataJson.date).addClass('curDay'),
      !1;
      var data = data.data,
      day = data.day,
      caseObj = {
      },
      sum = 0,
      li = '',
      cssClass = '',
      name = '',
      type = '',
      static = '',
      expectedMoney = '',
      tr = [
      ],
      pcount = 0,
      actualMoney = '',
      newDay = [
      ],
      o = 0,
      formYear = 0,
      borrow_url = '';
      for (var k in day) newDay.push(k);
      newDay.sort(sortNumber);
      for (var z = 0, v = newDay.length; v > z; z++) {
        o = newDay[z],
        caseObj = day[o]['case'];
        for (var i = 0, j = caseObj.length; j > i; i++) name = caseObj[i].name,
        type = caseObj[i].type,
        static = caseObj[i].static,
        expectedMoney = parseFloat(caseObj[i].expectedMoney),
        actualMoney = '-' == caseObj[i].actualMoney ? [
          caseObj[i].actualMoney,
          'text-align:center;'
        ] : [
          itz.numFormat(caseObj[i].actualMoney) + '元',
          'text-align:right;'
        ],
        borrow_url = caseObj[i].borrow_url,
        pcount++,
        cssClass = i + 1 == j ? 'class="bbn"' : '',
        sum += parseFloat(expectedMoney),
        tr.push('<tr><td width="200" class="tal pdl-20"><span ><a target="_blank" href="' + borrow_url + '">' + (name.length > 14 ? name.substring(0, 14) + '...' : name) + '</a></span></td><td width="68" style="padding-right:12px; text-align:right;">' + type + '</td><td width="113" class="tar ffa" style="padding-right:27px;">' + itz.numFormat(expectedMoney.toFixed(2)) + ' 元</td><td width="95 ffa" style="padding-right:0px;">' + caseObj[i].expectedTime + '</td><td width="115" class="ffa">' + caseObj[i].actualTime + '</td><td width="95" class="' + ('已支付' == static ? 'color-fbf' : '') + '">' + static + '</td></tr>'),
        li += '<li ' + cssClass + '><span class="tips-list-name"><a target="_blank" href="' + borrow_url + '">' + (name.length > 8 ? name.substring(0, 8) + '...' : name) + '</a></span><span class="tips-list-type">' + type + '</span><span class="tips-list-money">' + itz.numFormat(expectedMoney.toFixed(2)) + '元</span><span class="tips-list-static ' + ('已支付' == static ? 'color-fbf' : '') + '" >' + static + '</span></li>';
        dateEle.find('.item-day-' + parseInt(o, 10)).addClass('item-tips').attr('_title', '<ul class="tips-list clearfix">' + li + '</ul>').find('strong').addClass('em').text('￥' + itz.numFormat(sum.toFixed(2))),
        li = '',
        sum = 0
      }
      $('.item-tips').tip({
        words_per_line: 100000,
        tip_top: 10,
        direction: ''
      }),
      $('.item-tips').hover(function () {
        setTimeout(function () {
          $('#title_show').addClass('tips-box')
        }, 100)
      }, function () {
        setTimeout(function () {
          $('#title_show').addClass('tips-box')
        }, 100)
      });
      for (var pageNum = 10, pageItem = Math.ceil(pcount / pageNum), html = '', i = 0; pageItem > i; i++) {
        html += '<table class=\'table-style-2 page-' + i + '\'>';
        for (var j = 0; pageNum > j; j++) tr[0] && (html += tr[0]),
        tr.shift();
        html += '</table>'
      }
      if ($('#ajaxListClone').html(html), $('.pager-content').pagination(pcount, {
        callback: function (page_index) {
          $.ajax({
            url: '',
            type: 'GET',
            dataType: 'jsonp',
            jsonp: 'jsoncallback',
            success: function (data) {
    //          0 != data.code && (window.location.href = '/newuser/index/login?ret_url=' + repayData.headUrl)
            }
          }),
          0 == data.code && $('.pagination').show();
          var new_content = $('#ajaxListClone table:eq(' + page_index + ')').clone();
          return $('#ajaxList').empty().html(new_content.html()),
          pageItem > 1 && pageState && $('.pagination').append('<strong class=\'pageState\'>共 ' + pageItem + ' 页</strong>'),
          !1
        },
        prev_text: '« 上一页',
        next_text: '下一页 »',
        items_per_page: pageNum,
        num_edge_entries: 3,
        num_display_entries: 6,
        ellipse_text: '…',
        current_page: 0
      }), staticYear) {
        formYear = data.endYear - data.fromYear,
        selectYear.empty();
        for (var h = 0; formYear >= h; h++) {
          var hyear = parseInt(data.fromYear) + h;
          selectYear.append('<option value=' + hyear + '>' + hyear + '</option>')
        }
        selectYear.val(year),
        staticYear = !1
      }
      month - 1 == curDataJson.month && year == curDataJson.year && dateEle.find('span.item-day-' + curDataJson.date).addClass('curDay')
    });
    var YearAddStatic = !1;
    $('#show').text(year + '年' + (month + 1) + '月'),
    selectYear.find('option[value=' + year + ']').length > 0 ? selectYear.val(year)  : (selectYear.find('option').filter(function () {
      YearAddStatic = year > $(this).val() ? !0 : !1
    }), YearAddStatic ? selectYear.append('<option value=' + year + '>' + year + '</option>')  : selectYear.prepend('<option value=' + year + '>' + year + '</option>'), selectYear.val(year)),
    $('#selectMonth').val(month + 1)
  };
  $('.preMonth').click(function () {
    system = monthSystem(year, month - 1, 'pre'),
    init(system.year, system.month)
  }),
  $('.nextMonth').click(function () {
    system = monthSystem(year, month + 1, 'next'),
    init(system.year, system.month)
  }),
  selectYear.change(function () {
    init($(this).val(), parseInt($('#selectMonth').val()) - 1)
  }),
  $('#selectMonth').change(function () {
    init(selectYear.val(), parseInt($(this).val()) - 1)
  }),
  $('#curMonth').click(function () {
    init(curDataJson.year, curDataJson.month)
  }),
  init(year, month)
};
