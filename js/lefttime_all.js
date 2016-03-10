
var cur_date = new Date();
var startTime = cur_date.getTime();

var showClassTime = "leftTimeAll";
var calssTimeAttr = 'time';

var _day = 'day';
var _hour = 'hour';
var _minute = 'minute';
var _second = 'second';
var _end = 'end';

var _day = 'day';
var _hour = 'hour';
var _minute = 'minute';
var _second = 'second';
var _end = 'end';

function getLeftTimeStr(endTimeLine){
  if (endTimeLine > 0)
  {
    var tmp_val = parseInt(endTimeLine) - parseInt(cur_date.getTime() / 1000 + cur_date.getTimezoneOffset() * 60);

    now = new Date();
    var ts = parseInt((startTime - now.getTime()) / 1000) + tmp_val;
    var dateLeft = 0;
    var hourLeft = 0;
    var minuteLeft = 0;
    var secondLeft = 0;
    var hourZero = '';
    var minuteZero = '';
    var secondZero = '';
    if (ts < 0)
    {
      ts = 0;
      CurHour = 0;
      CurMinute = 0;
      CurSecond = 0;
    }
    else
    {
      dateLeft = parseInt(ts / 86400);
      ts = ts - dateLeft * 86400;
      hourLeft = parseInt(ts / 3600);
      ts = ts - hourLeft * 3600;
      minuteLeft = parseInt(ts / 60);
      secondLeft = ts - minuteLeft * 60;
    }

    if (hourLeft < 10)
    {
      hourZero = '0';
    }
    if (minuteLeft < 10)
    {
      minuteZero = '0';
    }
    if (secondLeft < 10)
    {
      secondZero = '0';
    }

    if (dateLeft > 0)
    {
      Temp = dateLeft + _day + hourZero + hourLeft + _hour + minuteZero + minuteLeft + _minute + secondZero + secondLeft + _second;
    }
    else
    {
      if (hourLeft > 0)
      {
        Temp = hourLeft + _hour + minuteZero + minuteLeft + _minute + secondZero + secondLeft + _second;
      }
      else
      {
        if (minuteLeft > 0)
        {
          Temp = minuteLeft + _minute + secondZero + secondLeft + _second;
        }
        else
        {
          if (secondLeft > 0)
          {
            Temp = secondLeft + _second;
          }
          else
          {
            Temp = '';
          }
        }
      }
    }

    if (tmp_val <= 0 || Temp == '')
    {
      Temp = "<strong>" + _end + "</strong>";
    }

    return Temp;

  }
}

function createShowTimeStr(endTimeLine,obj){
  var TempStr = getLeftTimeStr(endTimeLine);
  var leftTimeAll = document.getElementsByClassName(showClassTime);
  obj.innerHTML = TempStr;
}

function onload_leftTime_All(){
  var leftTimeAll = document.getElementsByClassName(showClassTime);
  // 剩余时间
  _day = day;
  _hour = hour;
  _minute = minute;
  _second = second;
  _end = end;

  for(var item in leftTimeAll) {
    if (item * 1 >= 0) {
      var endTimeLine = leftTimeAll[item].getAttribute(calssTimeAttr);
      // console.dir(endTimeLine);
      // 获取 剩余时间 显示的文字
      createShowTimeStr(endTimeLine, leftTimeAll[item]);
    }

  }
  
  timerID = setTimeout("onload_leftTime_All()", 1000);
}

