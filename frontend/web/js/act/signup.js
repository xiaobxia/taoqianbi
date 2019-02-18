$(function () {
  var close = function (e) {
    $(this).parents('.popup').hide()
    return false
  }

  var getUrlParam = function (name) {
    var requestParameters = new Object()

    var url = window.location.href
    var urlArr = url.split('?')
    if (urlArr[1]) {
      var urlParameters = urlArr[1].split('#')[0]
      if (urlParameters.indexOf('?') == -1) {
        var parameters = decodeURI(urlParameters)
        parameterArray = parameters.split('&')
        for (var i = 0; i < parameterArray.length; i++) {
          requestParameters[parameterArray[i].split('=')[0]] = (parameterArray[i].split('=')[1])
        }
      }
    }
    return requestParameters
  }
  var tag = getUrlParam().source_tag.slice(0, 8)
  if (tag === 'H5-wacai') {
    $('.pop').find('a').attr('href', 'https://h5.xianjincard.com/mobile/index.html?source_tag=' + getUrlParam().source_tag.slice(3))
  }

  $('.popup .close').click(close)
  $('.popup a.yes').click(close)
  $('.popup .overlay').click(close)
  $('.other label i').show()
  var show = function (msg) {
    $('.tips').show().find('h2').text(msg)
  }

  var pop = function (msg, type) {
    $('.pop').show().find('h2').text(msg)

    // type true 新注册用户弹层点击下载
    if (type) {
      $('.pop').find('a').click(function () {
        // ga('send', 'event', 'Registration', 'Download', 'RegSuccessDownloadApp');
      })
    }
  }

  var verifyPhone = function () {
    var reg = /^1(3|4|5|7|8)\d{9}$/
    var tel = $('input[name=phone]').val()
    if (tel === '') {
      show('请输入手机号')
      return false
    }

    if (!reg.test(tel)) {
      show('手机号不正确')
      return false
    }
    return tel
  }

  var verify = function () {
    var pwd = $('input[name=password]').val()
    var code = $('input[name=code]').val()
    var tel = verifyPhone()
    if (!tel) {
      return false
    }

    if (pwd === '') {
      show('请设置登录密码')
      return false
    }

    if (pwd.length < 6) {
      show('密码长度不能少于6')
      return false
    }

    if (code === '') {
      show('验证码不能为空')
      return false
    }

    if (!$('.other label i').is(':visible')) {
      show('请选择同意信合宝使用协议》')
      return false
    }

    return {
      'phone': tel,
      'password': pwd,
      'code': code,
      'source': 21,
      'invite_code': source_id
    }
  }

  var yes = true
  $('.special>button').click(function () {
    var tel = verifyPhone()
    if (tel && yes) {
      var time = 60
      $('.popup-spin').show()
      $.post(codeurl, {
        phone: tel,
        source: 21
      }, function (data) {
        $('.popup-spin').hide()
        if (data.code == 1000) {
          if (tag === 'H5-wacai') {
            pop('您已注册请直接使用')
            $('.pop').find('a').text('立即查看')
          } else {
            pop('您已经注册请下载登录')
          }
        } else if (data.code == 0) {
          timing = setInterval(function () {
            time--
            $('.special>button').html(time + 's')
            if (time < 0) {
              clearInterval(timing)
              time = 60
              $('.special>button').removeAttr('disabled').html('获取验证码')
              yes = true
            }
          }, 1000)
        }
      })
      yes = false
    }

    return false
  })

  var action = true
  $('form>button').click(function (e) {
    // ga('send', 'event', 'Registration', 'Apply', 'RegSuccess');

    var params = verify()
    if (!params) {
      return false
    }
    if (action) {
      action = false
      $('.popup-spin').show()
      $.post(signup, params, function (data) {
        $('.popup-spin').hide()
        if (data.code == 0) {
          // ga('send', 'event', 'Registration', 'Confirm', 'RegSuccess');
          if (tag === 'H5-wacai') {
            pop('恭喜你成功注册信合宝', true)
            $('.pop').find('a').text('立即借钱')
          } else {
            pop('您已成功注册信合宝', true)
          }
        } else {
          action = true
          show(data.message)
        }
      })
    }
    return false
  })

  // 直接下载执行
  $('p.other>a').click(function () {
    // ga('send', 'event', 'Registration', 'Download', 'RegDownloadApp');
  })
})
