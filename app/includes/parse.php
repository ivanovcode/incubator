<?php
function getListProxy( $cache )
{
    $alt_proxy_servers = array(
        'de693.nordvpn.com:80',
        'de901.nordvpn.com:80'
    );
    $cache->setCache('proxy_array');
    $cache_key = 'nordvpn';

    if (!$cache->isCached($cache_key)) {
        $proxy_string = shell_exec("curl --silent \"https://api.nordvpn.com/v1/servers/recommendations?filters\[servers_groups\]\[identifier\]=legacy_standard&filters\[servers_groups\]\[identifier\]=legacy_obfuscated_servers\" | jq --raw-output --slurp ' .[] | sort_by(.load) | limit(5;.[]) | [.hostname, .load] | \"\(.[0])\"'");
        $proxy_array = array_filter(explode("\n", $proxy_string));
        if(count($proxy_array) == 0) {
            $proxy_array = $alt_proxy_servers;
        }
        $cache->store($cache_key, $proxy_array, 60*60);
    } else {
        $proxy_array = $cache->retrieve($cache_key);
    }
    return $proxy_array;
}

function getWebPage($type = 'curl', $cache, $url, $proxy = false, $service = 'tor')
{
    $header = [];

    $useragent_array = [
        'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0',
        'Mozilla/5.0 (X11; U; Linux Core i7-4980HQ; de; rv:32.0; compatible; JobboerseBot; https://www.jobboerse.com/bot.htm) Gecko/20100101 Firefox/38.0',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:62.0) Gecko/20100101 Firefox/62.0',
        'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.10) Gecko/20050716 Firefox/1.0.6',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:44.0) Gecko/20100101 Firefox/44.0',
        'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0',
        'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:57.0) Gecko/20100101 Firefox/57.0',
        'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:63.0) Gecko/20100101 Firefox/63.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:65.0) Gecko/20100101 Firefox/65.0 ',
        'Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0',
        'Mozilla/5.0 (Windows NT 5.1; rv:6.0.2) Gecko/20100101 Firefox/6.0.2',
        'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:63.0) Gecko/20100101 Firefox/63.0',
        'Mozilla/5.0 (Windows NT 5.1; rv:29.0) Gecko/20100101 Firefox/29.0',
        'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)'];

    $referer_array = [
        'https://yandex.ru/',
        'https://www.google.com/',
        'https://www.google.com/',
        'https://www.rambler.ru/',
        'https://www.google.com/',
        'https://www.bing.com/'
    ];

    $proxy_array = getListProxy($cache);
    $proxy_auth = 'profidela.com@gmail.com:huj2ov4f';

    $proxy_server = $proxy_array[array_rand($proxy_array, 1)].':80';

    $proxy_cmd = ($proxy && $service == 'vpn' ? "--proxy=".$proxy_server." --proxy-auth=" . $proxy_auth .' ' : '');
    $proxy_cmd = ($proxy && $service == 'tor' ? "--proxy=127.0.0.1:9050 --proxy-type=socks5" . ' ' : '');

    $header['proxy'] = '';

    if($type == 'phantom') {
        //--web-security=no
        $output = shell_exec("killall -HUP tor && export OPENSSL_CONF=/etc/ssl/ && phantomjs --ignore-ssl-errors=true --ssl-protocol=any ".$proxy_cmd."./app/extraction.phantom.js " .$url);
        $header['content'] = $output;
        if($proxy) {
            $header['proxy'] = $proxy_server;
        }
        return $header;
    }

    if($type == 'casper') {
        $output = shell_exec("killall -HUP tor && export OPENSSL_CONF=/etc/ssl/ && casperjs --ignore-ssl-errors=true --ssl-protocol=any ".$proxy_cmd."./app/extraction.casper.js " .$url);
        $header['content'] = $output;
        if($proxy) {
            $header['proxy'] = $proxy_server;
        }
        return $header;
    }

    /*if($type == 'casper') {
        $casper = new Casper();

        $casper->setOptions([
            'ignore-ssl-errors' => 'yes'
        ]);

        $casper->setUserAgent($useragent_array[array_rand($useragent_array, 1)]);

        $casper->start($url);
        $casper->setViewPort(1280, 1024);
        $casper->wait(5000);
        $casper->capture(
            array(
                'top' => 0,
                'left' => 0,
                'width' => 1280,
                'height' => 1024
            ),
            __DIR__  . '/app/capture/screen.png'
        );
        $casper->run();

        $output = $casper->getHTML();

        $header['content'] = $output;
        return $header;
    }*/

    $options = array(
        //CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0',
        //CURLOPT_ENCODING=>'gzip, deflate',
        //CURLOPT_HTTPHEADER=>array(
        //    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        //    'Accept-Language: en-US,en;q=0.5',
        //    'Accept-Encoding: gzip, deflate',
        //    'Connection: keep-alive',
        //    'Upgrade-Insecure-Requests: 1',
        //),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CUSTOMREQUEST  => "GET",
        CURLOPT_POST           => false,
        CURLOPT_USERAGENT      => $useragent_array[array_rand($useragent_array, 1)],
        CURLOPT_REFERER        => $referer_array[array_rand($referer_array, 1)],
        CURLOPT_COOKIEFILE     =>"cookie.txt",
        CURLOPT_COOKIEJAR      =>"cookie.txt",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING       => "UTF-8",
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_FOLLOWLOCATION => true
    );

    if($proxy) {
        switch ($service) {
            default:
            case 'tor':
                $options = $options + array(
                        CURLOPT_HEADER => 1,
                        CURLOPT_HTTPPROXYTUNNEL => 1,
                        CURLOPT_PROXY => 'socks5h://127.0.0.1:9050'
                    );
                $header['proxy'] = 'tor';
                $output = shell_exec("killall -HUP tor");
                break;
            case 'vpn':
                $options = $options + array(
                        CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
                        CURLOPT_PROXY => $proxy_server,
                        CURLOPT_PROXYUSERPWD => $proxy_auth
                    );
                $header['proxy'] = $proxy_server;
                break;
        }
    }

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $head  = curl_getinfo( $ch );
    curl_close( $ch );

    $head['errno']   = $err;
    $head['errmsg']  = $errmsg;
    $head['content'] = $content;
    $head['proxy'] = $header['proxy'];
    return $head;
}
?>

