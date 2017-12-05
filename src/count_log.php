<?php
// REST API Формирование журнала посещений страниц веб-сайта
function count_log($params, $page, $days, $ip) {
    $dt = date("Y-m-d");
    if (empty($ip)) $ip = $_SERVER['REMOTE_ADDR'];
    if (empty($days)) $days = '0';
    try {
        $pdo = new \PDO('mysql:host=' . $params['host'] . ';dbname=' . $params['database'], $params['username'], $params['password']);
    } catch (\PDOException $e) {
        $rdata = [[ 'error'=>'Нет соезинения с БД ' . $params['database']]];
        return $rdata;
    }
    if ($page === 'count_log') {                 
        $sql = 'SELECT DATE_FORMAT(dt,"%d.%m.%Y") dt,page,view,ip FROM count_log WHERE dt >= DATE_SUB(CURDATE(), Interval ' . $days . ' DAY)';
    } else {
        $sql = 'SELECT id FROM count_log WHERE dt = "' . $dt . '" AND ip = "' . $ip . '" AND page = "' . $page . '"';
    }
    $rv = $pdo->query($sql);
    if (!$rv) {
        $rdata = [[ 'error'=>'Ошибка выполнения запроса: ' . $sql]];
        return $rdata;
    }
    $rdata = $rv->fetchAll(PDO::FETCH_ASSOC);
    if (count($rdata) < 1 && $page === 'count_log') {
        $rdata = [[ 'dt'=>'За ' . date("Y-m-d") . ' данных не найдено']];
        return $rdata;
    }
    if (!$rdata && $page !== 'count_log') {
        $rv = $pdo->exec('INSERT INTO count_log(dt, ip, page, view) VALUES("' . $dt . '", "' . $ip . '", "' . $page . '",1)');
        $rdata = [['INSERT'=>$rv]];
    } else if ($page !== 'count_log') {
        $rv = $pdo->exec('UPDATE count_log SET view = view + 1 WHERE id = ' . $rdata[0]['id']);
        $rdata = [['UPDATE'=>$rv]];
    }
    $pdo = null;
    return $rdata;
}

$params = require('params.php');
$token = (string)filter_input(INPUT_GET, 'token');
$page = (string)filter_input(INPUT_GET, 'page');
$ip = (string)filter_input(INPUT_GET, 'ip');
$days = (string)filter_input(INPUT_GET, 'days');
if ( ($token === $params['token'] || $page === 'count_log') && !empty($page)) {
    header('Access-Control-Allow-Origin: *');
    echo json_encode(count_log($params, $page, $days, $ip), JSON_UNESCAPED_UNICODE);
}
