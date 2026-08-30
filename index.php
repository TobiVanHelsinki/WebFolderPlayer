<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="folder.css?v=<?php echo filemtime(__DIR__ . '/folder.css'); ?>">
    <link rel="stylesheet" href="common.css?v=<?php echo filemtime(__DIR__ . '/common.css'); ?>">
    <?php if (file_exists(__DIR__ . '/custom.css')): ?>
    <link rel="stylesheet" href="custom.css?v=<?php echo filemtime(__DIR__ . '/custom.css'); ?>">
    <?php endif; ?>
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><rect width='24' height='24' rx='5' fill='%231a1a1a'/><path d='M9 7l8 5-8 5V7z' fill='%23b06ab3'/></svg>">
    <title>
        <?php echo htmlspecialchars(basename(__DIR__)); ?>
    </title>
</head>

<body>
    <?php
  function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
    {
      $files = array_merge([], ...[$files, rglob($dir . "/" . basename($pattern), $flags)]);
    }
    return $files;
  }
  function compareTreePaths($a, $b) {
    $segA = explode('/', $a);
    $segB = explode('/', $b);
    $n = min(count($segA), count($segB));
    for ($i = 0; $i < $n; $i++)
    {
      $aIsFile = ($i === count($segA) - 1);
      $bIsFile = ($i === count($segB) - 1);
      if ($aIsFile !== $bIsFile) return $aIsFile ? 1 : -1;
      $cmp = strcasecmp($segA[$i], $segB[$i]);
      if ($cmp !== 0) return $cmp;
    }
    return count($segA) <=> count($segB);
  }
  function isHiddenDir($dir, $baseDir) {
    $dir  = rtrim($dir, '/');
    $base = rtrim($baseDir, '/');
    while (strlen($dir) >= strlen($base))
    {
      if (file_exists($dir . '/.hidden')) return true;
      if ($dir === $base) break;
      $dir = dirname($dir);
    }
    return false;
  }
  $baseDirMedia = 'media/';
  $readmeContent = null;
  foreach (scandir($baseDirMedia) as $entry)
  {
    if (preg_match('/^readme(\.(md|txt))?$/i', $entry) && is_file($baseDirMedia . $entry))
    {
      $readmeContent = file_get_contents($baseDirMedia . $entry);
      break;
    }
  }
  $allFiles     = rglob('./' . $baseDirMedia . "*.*", 0);
  $allFiles     = array_filter($allFiles, function ($f)
  {
    return preg_match('/\.(mp4|webm|webp|mkv|jpeg|jpg|png|gif|bmp)$/i', $f);
  });
  $files        = array_values(array_diff($allFiles, array('.', '..')));
  foreach ($files as $i => $f)
    $files[$i] = substr($f, 8, 999);
  usort($files, 'compareTreePaths');
  $hiddenMap = [];
  foreach ($files as $file)
    $hiddenMap[$file] = isHiddenDir('./' . $baseDirMedia . dirname($file), './' . $baseDirMedia);
  ?>

    <div class="app">

        <div class="sidebar">
            <?php
      echo "<div class=\"contentroot\">";
      $openStack         = [];
      $folderHiddenCache = [];
      foreach ($files as $file)
      {
        $fullfile = $baseDirMedia . $file;
        if (is_dir($fullfile)) continue;
        $escapedfile = addslashes($file);
        $label       = basename($file);
        $hiddenClass = $hiddenMap[$file] ? ' hidden-item' : '';

        $parentfolder = dirname($file);
        $segments     = ($parentfolder === '.') ? [] : explode('/', $parentfolder);

        $common = 0;
        while ($common < count($openStack) && $common < count($segments) && $openStack[$common] === $segments[$common])
          $common++;

        while (count($openStack) > $common)
        {
          array_pop($openStack);
          echo "</div>";
        }

        for ($i = $common; $i < count($segments); $i++)
        {
          $openStack[]  = $segments[$i];
          $folderPath   = implode('/', array_slice($segments, 0, $i + 1));
          $folderLabel  = $segments[$i];
          if (!isset($folderHiddenCache[$folderPath]))
            $folderHiddenCache[$folderPath] = isHiddenDir('./' . $baseDirMedia . $folderPath, './' . $baseDirMedia);
          $folderHiddenClass = $folderHiddenCache[$folderPath] ? ' hidden-item' : '';
          echo "<button class=\"collapsible$folderHiddenClass\" id=\"$folderPath\" onclick=\"toggleCollapsible(this)\">$folderLabel</button>";
          echo "<div class=\"content$folderHiddenClass\">";
        }

        echo "<button class=\"videobtn$hiddenClass\" type=\"button\" onclick=\"setSrcAndPlay('$escapedfile')\" id=\"$file\">$label</button>";
      }
      while (count($openStack) > 0)
      {
        array_pop($openStack);
        echo "</div>";
      }
      echo "</div>";
      ?>
        </div>

        <div class="main-content">
            <div class="control-panel">
                <button class="cp-btn" onclick="playPrev()">&#9664;&#9664;</button>
                <button class="cp-btn" onclick="playNext()">&#9654;&#9654;</button>
                <button class="cp-btn" onclick="shuffleAndGo()">Shuffle &amp; Go</button>
                <span class="now-playing-label" id="nowPlayingLabel">No video selected</span>
                <label class="cp-label">
                    <input type="checkbox" id="randomModeToggle" onchange="setRandomMode(this.checked)">
                    Shuffle
                </label>
                <label class="cp-label">
                    <input type="checkbox" id="loopModeToggle" onchange="loopMode = this.checked">
                    Repeat
                </label>
                <label class="cp-label">
                    <input type="checkbox" id="autoFullscreenToggle" checked onchange="autoFullscreen = this.checked">
                    Auto Fullscreen
                </label>
                <label class="cp-label">
                    <input type="checkbox" id="showHiddenToggle" onchange="setShowHidden(this.checked)">
                    Show hidden
                </label>
            </div>
            <div class="video-wrap<?php echo $readmeContent !== null ? ' pre-play' : ''; ?>" id="videowrap">
                <?php if ($readmeContent !== null): ?>
                <pre class="readme-panel" id="readmePanel"><?php echo htmlspecialchars($readmeContent); ?></pre>
                <?php endif; ?>
                <video id="myvideo" controls preload poster="posterT.png"></video>
                <div class="now-playing-toast" id="nowPlayingToast"></div>
            </div>
        </div>

    </div>

    <script type="text/javascript">
    var baseDirMedia = '<?php echo $baseDirMedia; ?>';
    var myvidsAll = <?php echo json_encode(array_values($files)); ?>;
    var hiddenMap = <?php echo json_encode($hiddenMap); ?>;
    var showHidden = false;
    var myvidsOriginal = myvidsAll.filter(function(v) {
        return showHidden || !hiddenMap[v];
    });
    var myvids = myvidsOriginal.slice();
    var randomMode = false;
    var loopMode = false;
    var autoFullscreen = true;
    var index = -1;
    var myvid = document.getElementById('myvideo');
    var videowrap = document.getElementById('videowrap');
    var sidebar = document.querySelector('.sidebar');
    var nowPlayingLabel = document.getElementById('nowPlayingLabel');
    var nowPlayingToast = document.getElementById('nowPlayingToast');
    var toastTimer = null;

    function showNowPlayingToast(text) {
        nowPlayingToast.textContent = text;
        nowPlayingToast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function() {
            nowPlayingToast.classList.remove('show');
        }, 5000);
    }

    function toggleCollapsible(btn) {
        btn.classList.toggle('active');
        var content = btn.nextElementSibling;
        content.style.maxHeight = content.style.maxHeight ? null : content.scrollHeight + 'px';
    }

    document.addEventListener('transitionend', function(e) {
        if (e.propertyName !== 'max-height' || !e.target.classList.contains('content')) return;
        var parent = e.target.parentElement ? e.target.parentElement.closest('.content') : null;
        if (parent && parent.style.maxHeight && parent.style.maxHeight !== 'none') {
            parent.style.maxHeight = parent.scrollHeight + 'px';
        }
    });

    function shuffleArray(arr) {
        for (var i = arr.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var t = arr[i];
            arr[i] = arr[j];
            arr[j] = t;
        }
        return arr;
    }

    function setRandomMode(enabled) {
        randomMode = enabled;
        var cur = myvids[index];
        myvids = enabled ? shuffleArray(myvidsOriginal.slice()) : myvidsOriginal.slice();
        index = myvids.indexOf(cur);
        if (index < 0) index = 0;
    }

    function setShowHidden(enabled) {
        showHidden = enabled;
        sidebar.classList.toggle('show-hidden', enabled);
        var cur = myvids[index];
        myvidsOriginal = myvidsAll.filter(function(v) {
            return showHidden || !hiddenMap[v];
        });
        myvids = randomMode ? shuffleArray(myvidsOriginal.slice()) : myvidsOriginal.slice();
        index = myvids.indexOf(cur);
        if (index < 0) index = 0;
    }

    function shuffleAndGo() {
        document.getElementById('randomModeToggle').checked = true;
        setRandomMode(true);
        index = 0;
        while (index < myvids.length && isImage(myvids[index])) index++;
        if (index >= myvids.length) index = 0;
        setSrc(myvids[index], true);
        myvid.play();
    }

    function isImage(v) {
        return /\.(png|jpg|jpeg|gif|webp|bmp)$/i.test(v);
    }

    function setActiveButton(nextvid, expand) {
        var prev = document.querySelector('.videobtn.now-playing');
        if (prev) prev.classList.remove('now-playing');
        var btn = document.getElementById(nextvid);
        if (!btn) return;
        btn.classList.add('now-playing');
        if (expand) {
            var content = btn.parentElement.closest('.content');
            while (content) {
                content.style.maxHeight = 'none';
                var collBtn = content.previousElementSibling;
                if (collBtn && collBtn.classList.contains('collapsible')) collBtn.classList.add('active');
                content = content.parentElement.closest('.content');
            }
        }
        btn.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }

    function setSrc(nextvid, expand) {
        videowrap.classList.remove('pre-play');
        var label = nextvid.split('/').pop();
        nowPlayingLabel.textContent = label;
        if (autoFullscreen && !isImage(nextvid)) showNowPlayingToast(label);
        if (isImage(nextvid)) {
            videowrap.style.backgroundImage = "url('" + baseDirMedia + nextvid + "')";
            videowrap.style.backgroundPosition = "center";
            videowrap.style.backgroundSize = "contain";
        } else {
            videowrap.style.backgroundImage = '';
            myvid.pause();
            myvid.src = baseDirMedia + nextvid;
            document.title = nextvid;
            localStorage.setItem('lastPlayedVideo', nextvid);
        }
        setActiveButton(nextvid, !!expand);
    }

    function playNext() {
        index++;
        while (index < myvids.length && isImage(myvids[index])) index++;
        if (index >= myvids.length) index = 0;
        setSrc(myvids[index], true);
        myvid.play();
    }

    function playPrev() {
        index--;
        while (index >= 0 && isImage(myvids[index])) index--;
        if (index < 0) index = myvids.length - 1;
        while (index >= 0 && isImage(myvids[index])) index--;
        if (index < 0) index = 0;
        setSrc(myvids[index], true);
        myvid.play();
    }

    function setSrcAndPlay(nextvid) {
        index = myvids.indexOf(nextvid);
        if (index < 0) index = 0;
        setSrc(nextvid);
        setActiveButton(nextvid, true);
        if (!isImage(nextvid)) myvid.play();
    }

    myvid.addEventListener('timeupdate', function() {
        if (myvid.duration && myvid.currentTime / myvid.duration >= 0.9) {
            var btn = document.getElementById(myvids[index]);
            if (btn) btn.classList.add('played');
        }
    });

    myvid.addEventListener('ended', function() {
        if (loopMode) {
            setTimeout(function() {
                myvid.play();
            }, 500);
            return;
        }
        index++;
        while (index < myvids.length && isImage(myvids[index])) index++;
        if (index >= myvids.length) index = 0;
        setSrc(myvids[index], true);
        setTimeout(function() {
            myvid.play();
        }, 1000);
    });

    myvid.addEventListener('play', function() {
        if (!autoFullscreen) return;
        var fs = videowrap.requestFullscreen || videowrap.webkitRequestFullscreen;
        if (fs) fs.call(videowrap);
    });

    myvid.addEventListener('pause', function() {
        if (!autoFullscreen) return;
        if (myvid.duration && myvid.currentTime >= myvid.duration - 0.25) return;
        var ef = document.exitFullscreen || document.webkitExitFullscreen;
        if (ef) ef.call(document);
    });
    </script>
</body>

</html>