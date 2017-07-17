// vim:ft=javascript
const NEWS_URL = 'https://polling.bbc.co.uk/news/latest_breaking_news_waf'
const WEATHER_URL = 'weather.php'

function get(url, callback) {
  console.log('Getting...')
  var req = new XMLHttpRequest()
  req.onreadystatechange = function () {
    console.log('Got?')
    if (req.readyState === 4) {
      callback(req.responseText)
    }
  }
  req.open('GET', url, true)
  req.send(null)
}

const poll = function () {
  console.log('Polling...')
  get(NEWS_URL, function (body) {
    console.log('Polled.')
    try {
      var data = JSON.parse(body)
    } catch (e) {
      setTimeout(poll, 30000)
    }
    if (data.asset.headline) {
      document.getElementById('js-body').className = 'news'
      document.getElementById('js-headline').innerHTML = data.asset.headline
      document.title = 'BBC News'
    } else {
      document.getElementById('js-body').className = 'clock'
      document.title = 'Clock'
    }
    setTimeout(poll, data.pollPeriod ? data.pollPeriod : 30000)
  })
  get(WEATHER_URL, function (body) {
    var ul = document.getElementById('js-weather')
    try {
      var weather = JSON.parse(body)
    } catch (e) {
      ul.className = ' loading';
      return
    }
    ul.innerHTML = ''
    for (var i = 0; i < weather.forecast.length; i++) {
      var li = document.createElement('li')
      li.innerHTML = li.textContent = weather.forecast[i].replace(/.$/,'')
      ul.appendChild(li)
    }
    ul.className = '';
    console.log(ul)
  })
	get('./refresh.txt', function (newVersion) {
		var currentVersion = '<?php echo rtrim(file_get_contents('refresh.txt')) ?>'
		if (currentVersion + '\n' !== newVersion) {
			location.reload(true)
		}
	})
}

const clockTick = function () {
  var now = new Date();
  document.getElementById('js-clock').innerHTML = now.toLocaleTimeString()
  document.getElementById('js-unix').innerHTML = (now / 1000).toFixed(3)

  if (now.getHours() < 10 || now.getHours() > 14) {
    document.getElementById('js-clock').className = ' not-core'
  } else {
    document.getElementById('js-clock').className = ''
  }

  setTimeout(clockTick, 1)
}

poll()
setTimeout(clockTick, 1000)
