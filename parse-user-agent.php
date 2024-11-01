<?php
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

/**
 * Turn the user agent into something more readible and handle icons
 *
 * @param [string] $u_agent
 * @return array
 */
function blz_fua_parse_user_agent($u_agent = null) {
  $platform = null;
  $browser  = null;
  $browsericon = null;
  $version  = '';
  $imgplatform = null;
  $imgbrowser  = null;
  $empty = array('platform' => $platform, 'browser' => $browser, 'version' => $version);
  if (!$u_agent) return $empty;
  if (preg_match('/\((.*?)\)/m', $u_agent, $parent_matches)) {
    preg_match_all(
      <<<'REGEX'
      /(?P<platform>BB\d+;|Android|Adr|Symbian|Sailfish|CrOS|Tizen|iPhone|iPad|iPod|Linux|(Open|Net|Free)BSD|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(New\ )?Nintendo\ (WiiU?|3?DS|Switch)|Xbox(\ One)?)
      (?:\ [^;]*)?
      (?:;|$)/imx
      REGEX,
      $parent_matches[1],
      $result
    );

    $priority = array('Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11', 'Sailfish');
    $result['platform'] = array_unique($result['platform']);
    if (count($result['platform']) > 1) {
      if ($keys = array_intersect($priority, $result['platform'])) {
        $platform = reset($keys);
      } else {
        $platform = $result['platform'][0];
      }
    } elseif (isset($result['platform'][0])) {
      $platform = $result['platform'][0];
    }
  }
  if (strtolower($platform) == 'macintosh' || strtolower($platform) == 'ipad' || strtolower($platform) == 'ipod' || strtolower($platform) == 'iphone') {
    $imgplatformlogo = 'apple';
  } else {
    $imgplatformlogo = strtolower($platform);
  }
  if ($platform == 'linux-gnu' || $platform == 'X11') {
    $platform = 'Linux';
    $imgplatformlogo = 'linux'; #linux icon
  } elseif ($platform == 'CrOS') {
    $platform = 'Chrome OS';
    $imgplatformlogo = 'chrome'; #chrome icon
  } elseif ($platform == 'Adr') {
    $platform = 'Android';
    $imgplatformlogo = 'android'; #android icon
  }
  preg_match_all(
    <<<'REGEX'
    %(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
    TizenBrowser|(?:Headless)?Chrome|YaBrowser|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|(?-i:Edge)|EdgA?|CriOS|UCBrowser|Puffin|
    OculusBrowser|SamsungBrowser|SailfishBrowser|XiaoMi/MiuiBrowser|
    Baiduspider|Applebot|Facebot|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
    Valve\ Steam\ Tenfoot|
    NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
    \)?;?
    (?:[:/ ](?P<version>[0-9A-Z.]+)|/[A-Z]*)%ix
    REGEX,
    $u_agent,
    $result
  );
  // If nothing matched, return null (to avoid undefined index errors)
  if (!isset($result['browser'][0]) || !isset($result['version'][0])) {
    if (preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result)) {
      if ($result['browser'] == 'Dalvik') {
        $browsericon = 'android';
      }

      if (isset($imgplatformlogo) && !empty($imgplatformlogo)) {
        $imgplatform = plugins_url('/files/img/' . strtolower($imgplatformlogo) . '-logo.png', __FILE__); #platform icon declaration
      } else {
        $imgplatform = '';
      }

      if (isset($browsericon) && !empty($browsericon)) {
        $imgbrowser = plugins_url('/files/img/' . $browsericon . '-logo.png', __FILE__); #browser icon declaration
      } else {
        $imgbrowser = '';
      }

      $version = substr($version, 0, 3);
      return array('platform' => $platform ?: null, 'browser' => $result['browser'], 'version' => empty($result['version']) ? null : $result['version']);
    }
    return $empty;
  }

  if (preg_match('/rv:(?P<version>[0-9A-Z.]+)/i', $u_agent, $rv_result)) {
    $rv_result = $rv_result['version'];
  }

  $browser = $result['browser'][0];
  $version = $result['version'][0];

  $lowerBrowser = array_map('strtolower', $result['browser']);

  $find = function ($search, &$key = null, &$value = null) use ($lowerBrowser) {
    $search = (array)$search;

    foreach ($search as $val) {
      $xkey = array_search(strtolower($val), $lowerBrowser);
      if ($xkey !== false) {
        $value = $val;
        $key   = $xkey;

        return true;
      }
    }

    return false;
  };

  $findT = function (array $search, &$key = null, &$value = null) use ($find) {
    $value2 = null;
    if ($find(array_keys($search), $key, $value2)) {
      $value = $search[$value2];

      return true;
    }

    return false;
  };

  $key = 0;
  $val = '';

  if ($findT(['OPR' => 'Opera', 'Facebot' => 'iMessageBot', 'UCBrowser' => 'UC Browser', 'YaBrowser' => 'Yandex', 'Iceweasel' => 'Firefox', 'Icecat' => 'Firefox', 'CriOS' => 'Chrome', 'Edg' => 'Edge', 'EdgA' => 'Edge', 'XiaoMi/MiuiBrowser' => 'MiuiBrowser'], $key, $browser)) {
    $version = is_numeric(substr($result['version'][$key], 0, 1)) ? $result['version'][$key] : null;
    $browsericon = $result['browser'][$key];
    switch ($browsericon) {
      case "OPR":
        $browsericon = 'opera';
        break;
      case "Facebot":
        $browsericon = '';
        break;
      case "UCBrowser":
        $browsericon = 'UCbrowser';
        break;
      case "YaBrowser":
        $browsericon = '';
        break;
      case "Iceweasel":
        $browsericon = 'firefox';
        break;
      case "Icecat":
        $browsericon = 'firefox';
        break;
      case "CriOS":
        $browsericon = 'chrome';
        break;
      case "Edg":
        $browsericon = 'edge';
        break;
      case "EdgA":
        $browsericon = 'edge';
        break;
      case "XiaoMi/MiuiBrowser":
        $browsericon = '';
        break;
      default:
        $browsericon = '';
    }
  } elseif ($find('Playstation Vita', $key, $platform)) {
    $platform = 'PlayStation Vita';
    $browser  = 'Browser';
    $imgplatformlogo = 'playstation'; #playstation icon
  } elseif ($find(array('Kindle Fire', 'Silk'), $key, $val)) {
    $browser  = $val == 'Silk' ? 'Silk' : 'Kindle';
    $browsericon = 'chrome';
    $platform = 'Kindle Fire';
    $imgplatformlogo = 'amazon'; #amazon icon
    if (!($version = $result['browser'][$key]) || !is_numeric($version[0])) {
      $version = $result['browser'][array_search('Version', $result['browser'])];
    }
  } elseif ($find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS') {
    $browser = 'NintendoBrowser';
    $browsericon = 'nintendo';
    $version = $result['version'][$key];
    $imgplatformlogo = 'nintendo'; #nintendo icon
  } elseif ($find('Kindle', $key, $platform)) {
    $browser = $result['browser'][$key];
    $browsericon = 'amazon';
    $version = $result['version'][$key];
    $imgplatformlogo = 'amazon'; #amazon icon
  } elseif ($find('Opera', $key, $browser)) {
    $find('Version', $key);
    $browsericon = 'opera';
    $version = $result['version'][$key];
    $imgplatformlogo = 'opera'; #opera icon
  } elseif ($find('Puffin', $key, $browser)) {
    $version = $result['version'][$key];
    $browsericon = 'puffin';
    $imgplatformlogo = 'puffin'; #puffin icon
    if (strlen($version) > 3) {
      $part = substr($version, -2);
      if (ctype_upper($part)) {
        $version = substr($version, 0, -2);
        $flags = array('IP' => 'iPhone', 'IT' => 'iPad', 'AP' => 'Android3', 'AT' => 'Android4', 'WP' => 'Windows Phone', 'WT' => 'Windows');
        if (isset($flags[$part])) {
          $platform = $flags[$part];
        }
      }
    }
  } elseif ($find(array('Applebot', 'IEMobile', 'Edge', 'Midori', 'Vivaldi', 'OculusBrowser', 'SamsungBrowser', 'Valve Steam Tenfoot', 'Chrome', 'HeadlessChrome', 'SailfishBrowser'), $key, $browser)) {
    $version = $result['version'][$key];
    if ($browser == "Edge" || $browser == "IEMobile") {
      $browsericon = 'edge';
    } elseif ($browser == "SamsungBrowser") {
      $browsericon = 'samsung';
    } else {
      $browsericon = 'chrome';
    }
  } elseif ($rv_result && $find('Trident')) {
    $browser = 'MSIE';
    $version = $rv_result;
    $browsericon = 'edge';
  } elseif ($browser == 'AppleWebKit') {
    $browsericon = 'apple';
    if ($platform == 'Android') {
      $browser = 'Android Browser';
      $browsericon = 'android';
    } elseif (strpos($platform, 'BB') === 0) {
      $browser  = 'BlackBerry Browser';
      $browsericon = 'blackberry';
      $imgplatformlogo = 'blackberry'; #blackberry icon
    } elseif ($platform == 'BlackBerry' || $platform == 'PlayBook') {
      $browser = 'BlackBerry Browser';
      $browsericon = 'blackberry';
      $imgplatformlogo = 'blackberry'; #blackberry icon
    } else {
      $find('Safari', $key, $browser) || $find('TizenBrowser', $key, $browser);
      $browsericon = 'safari';
    }
    $find('Version', $key);
    $version = $result['version'][$key];
  } elseif ($pKey = preg_grep('/playstation \d/i', $result['browser'])) {
    $pKey = reset($pKey);
    $platform = 'PlayStation ' . preg_replace('/[^\d]/i', '', $pKey);
    $imgplatformlogo = 'playstation'; #playstation icon
    $browser  = 'Chrome';
    $browsericon = 'chrome';
  }

  if (isset($imgplatformlogo) && !empty($imgplatformlogo)) {
    $imgplatform = plugins_url('/files/img/' . strtolower($imgplatformlogo) . '-logo.png', __FILE__); #platfrom icon declaration
  } else {
    $imgplatform = '';
  }

  if (isset($browsericon) && !empty($browsericon)) {
    $imgbrowser = plugins_url('/files/img/' . $browsericon . '-logo.png', __FILE__); #browser icon declaration
  } else {
    $imgbrowser = '';
  }

  $version = substr($version, 0, 3);
  return array('platform' => $platform ?: null, 'browser' => $browser ?: null, 'version' => $version ?: null, 'imgplatform' => $imgplatform ?: null, 'imgbrowser' => $imgbrowser ?: null);
}
