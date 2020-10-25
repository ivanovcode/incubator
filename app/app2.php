<?php
error_reporting(0);
set_time_limit(0);

define('DIR', __DIR__);
define('APP', __DIR__  . '/app');
define('SITE', 'https://lustron.ru/');

foreach (glob(APP . '/include/*.php') as $file) {
    require_once($file);
}

$config = parse_ini_file(APP . '/app.ini', true);
$storage = new SQLite3(APP . '/data.db');

$cache = new Cache();
$cache->setCachePath(APP . '/cache/');

$method = 'phantom';
$proxy = true;
$proxy_service = 'tor';

$result = [];
$tasks = false;

while ($tasks == false):
    //$storage->exec("begin;");
    $tasks = $storage->query("
        SELECT id, url, `type`, `repeat` FROM tasks WHERE status = 'pending' GROUP BY url ORDER BY `type` DESC LIMIT 1
    ");
    $tasks = $tasks->fetchArray(SQLITE3_ASSOC);
    $storage->exec("UPDATE tasks SET status = 'block' WHERE id = '" . $tasks['id'] . "'");
    //$storage->exec("commit;");
    if(!$tasks) {
        $categories = $storage->query("SELECT id, url, pages, title FROM categories WHERE parent = 0");
        while ($row = $categories->fetchArray(SQLITE3_ASSOC)) {
            for ($i = 0; $i <= intval($row['pages']); $i++) {
                $result['url'] = SITE . $row['url'] . "?p=" . $i;
                $result['status'] = "pending";
                $result['type'] = "product";
                $stm = $storage->prepare("INSERT INTO tasks(url, status, `type`) VALUES (?, ?, ?)");
                $stm->bindParam(1, $result['url']);
                $stm->bindParam(2, $result['status']);
                $stm->bindParam(3, $result['type']);
                $stm->execute();
            }
        }
    }
endwhile;

while ($row = $tasks) {
    $response = getWebPage($method, $cache, $row['url'], $proxy, $proxy_service);
    $output = $response['content'];

    $unlock = false;
    $unlock = getUnlockUrl($output, 'window.location.href = \'', '\';');

    if($unlock !== false) {
        $info = array(
            'method' => $method,
            'proxy' => $response['proxy'],
            'proxy_service' => $proxy_service
        );
        $storage->exec("UPDATE tasks SET status = 'pending', info = '" . json_encode($info, true) . "' WHERE id = '" . $row['id'] . "'");
        file_put_contents(APP . '/progress.log', 'block!'.PHP_EOL, FILE_APPEND);
        die();
    }

    $tmp_file = APP . '/cache/' . substr(md5(openssl_random_pseudo_bytes(20)),- 32) . ".html";
    file_put_contents($tmp_file, $output);
    $dom = file_get_html($tmp_file);
    unlink($tmp_file);

    $result = [];
    $repeat = intval($row['repeat']) + 1;

    if($row['type'] == 'product') {
        $empty = 0;
        foreach($dom->find('div.product-block') as $item) {
            $result = [];
            foreach($item->find('a.product-name') as $a) {
                $result['url'] = str_replace("//lustron.ru/", "", $a->href);
                break;
            }
            if (!empty($result)) {
                $empty++;
                $result['status'] = "pending";
                $result['type'] = "card";
                $result['url'] = SITE . $row['url'];

                $stm = $storage->prepare("INSERT INTO tasks(url, status, `type`) VALUES (?, ?, ?)");
                $stm->bindParam(1, $result['url']);
                $stm->bindParam(2, $result['status']);
                $stm->bindParam(3, $result['type']);
                $stm->execute();

                file_put_contents(APP . '/progress.log', $result['url'].PHP_EOL, FILE_APPEND);
            }
        }
        if($empty > 6) {
            $storage->exec("UPDATE tasks SET status = 'completed' WHERE id = '" . $row['id'] . "'");
        } else {
            $storage->exec("UPDATE tasks SET status = 'pending', repeat = " . strval($repeat) . " WHERE id = '" . $row['id'] . "'");
        }
        if($empty <= 6 && $repeat == 3) {
            $storage->exec("UPDATE tasks SET status = 'rejected' WHERE id = '" . $row['id'] . "'");
            file_put_contents(APP . '/progress.log', 'rejected'.PHP_EOL, FILE_APPEND);
        }
    }

    if($row['type'] == 'card') {
        foreach ($dom->find('div.card-product-name') as $title_el) {
            $result['title'] = $title_el->innertext;
            break;
        }

        foreach ($dom->find('div.code-product-text') as $code_el) {
            $result['code'] = str_replace("Код товара: ", "", $code_el->innertext);
            break;
        }

        foreach ($dom->find('div.artikul_item') as $article_el) {
            $result['article'] = str_replace("Артикул: ", "", $article_el->innertext);
        }

        $result['breadcrumbs'] = '';
        foreach ($dom->find('div.bread-crumb-block') as $crumb_el) {
            foreach ($crumb_el->find('a') as $crumb_value_el) {
                $result['breadcrumbs'] .= $crumb_value_el->innertext . '/';
                break;
            }
        }

        foreach ($dom->find('div.stock-quantity-text') as $quantity_el) {
            $result['quantity'] = $quantity_el->innertext;
            break;
        }

        foreach ($dom->find('span.bread-crumb-select') as $crumb_el) {
            $result['breadcrumbs'] .= $crumb_el->innertext . '/';
        }

        foreach ($dom->find('div.new-price') as $price_el) {
            foreach ($price_el->find('span.item_itemprop') as $price_value_el) {
                $result['price'] = $price_value_el->innertext;
                break;
            }
        }

        foreach ($dom->find('div.card-about-code-text') as $vendor_el) {
            foreach ($vendor_el->find('a.blue-link') as $vendor_value_el) {
                $result['vendor'] = $vendor_value_el->innertext;
                break;
            }
        }

        foreach ($dom->find('div.product-main-image') as $image_el) {
            foreach ($image_el->find('img') as $image_img_el) {
                $result['image'] = $image_img_el->src;
                break;
            }
            break;
        }

        foreach ($dom->find('div.product-preview-image-slider') as $thumbnails_el) {
            $thumbnails = [];
            $thumbnails_count = 0;
            foreach ($thumbnails_el->find('a') as $thumbnail_el) {
                $thumbnails_count++;
                array_push($thumbnails, $thumbnail_el->href);
            }
            $result['thumbnails'] = $thumbnails;
            $result['thumbnails_count'] = count($thumbnails);
        }

        $attributes = [];
        foreach ($dom->find('div.characteristics-block-data') as $attribute_el) {
            $attribute = [];
            foreach ($attribute_el->find('div.name-characteristics') as $name_el) {
                $attribute['key'] = trim(strip_tags($name_el->innertext));
            }

            foreach ($attribute_el->find('div.marks-characteristics') as $value_el) {
                $attribute['value'] = trim(strip_tags($value_el->innertext));
            }

            if (!empty($attribute['key']) && !empty($attribute['value'])) {
                array_push($attributes, $attribute);
            }
        }
        $result['attributes'] = $attributes;

        if (!empty($result)) {
            $stm = $storage->prepare("INSERT INTO products(title, code, article, price, vendor, image, thumbnails, attributes, breadcrumbs, task_id, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stm->bindParam(1, $result['title']);
            $stm->bindParam(2, $result['code']);
            $stm->bindParam(3, $result['article']);
            $stm->bindParam(4, $result['price']);
            $stm->bindParam(5, $result['vendor']);
            $stm->bindParam(6, $result['image']);
            $stm->bindParam(7, $result['thumbnails_count']);
            $stm->bindParam(8, json_encode($result['attributes'], JSON_UNESCAPED_UNICODE));
            $stm->bindParam(9, $result['breadcrumbs']);
            $stm->bindParam(10, $row['id']);
            $stm->bindParam(11, $result['quantity']);
            $stm->execute();
            $product_id = $storage->lastInsertRowID();

            $storage->exec("UPDATE tasks SET status = 'completed' WHERE id = '" . $row['id'] . "'");
            file_put_contents(APP . '/progress.log', $product_id.PHP_EOL, FILE_APPEND);
        }

        if (!empty($result) && strval($product_id) !== '0' && count($result['thumbnails']) > 0) {
            foreach ($result['thumbnails'] as $thumbnail) {
                $stm = $storage->prepare("INSERT INTO thumbnails(source, product_id) VALUES (?, ?)");
                $stm->bindParam(1, $thumbnail);
                $stm->bindParam(2, $product_id);
                $stm->execute();
            }
        }

        if(!empty($result) && strval($product_id) !== '0') {
            $storage->exec("UPDATE tasks SET status = 'completed' WHERE id = '" . $row['id'] . "'");
        } else {
            if ($repeat == 3) {
                $storage->exec("UPDATE tasks SET status = 'rejected' WHERE id = '" . $row['id'] . "'");
            } else {
                $storage->exec("UPDATE tasks SET status = 'pending', repeat = " . strval($repeat) . " WHERE id = '" . $row['id'] . "'");
            }
        }
    }
    die();
}

