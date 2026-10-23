<?php
// Run only from the private backup directory; never print credentials.
$appPath = $argv[1];
require $appPath.'/vendor/autoload.php';
$app = require $appPath.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = config('database.connections.'.config('database.default'));
if (!in_array(config('database.default'), ['mysql','mariadb'], true)) throw new RuntimeException('Expected production MySQL/MariaDB');
$q = fn($v) => '"'.str_replace(["\\", '"', "\n", "\r"], ["\\\\", '\\"', '\\n', '\\r'], (string)$v).'"';
$cnf = "[client]\n";
foreach (['host'=>'host','port'=>'port','username'=>'user','password'=>'password'] as $key=>$option) $cnf .= $option.'='.$q($c[$key] ?? '')."\n";
// Match PDO's Unix-socket connection for localhost; a port can otherwise make
// mysqldump resolve localhost to IPv6 TCP and fail authentication.
if (($c['host'] ?? '') === 'localhost') {
    $socket = $c['unix_socket'] ?? '';
    if (!$socket) {
        $socket = Illuminate\Support\Facades\DB::selectOne("SHOW VARIABLES LIKE 'socket'")->Value;
    }
    $cnf .= 'protocol=SOCKET'."\n".'socket='.$q($socket)."\n";
} elseif (!empty($c['unix_socket'])) {
    $cnf .= 'socket='.$q($c['unix_socket'])."\n";
}
file_put_contents('database.cnf', $cnf); chmod('database.cnf', 0600);
file_put_contents('database-name.txt', $c['database']);
$tables = Illuminate\Support\Facades\DB::select('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = ?', ['BASE TABLE']);
foreach ($tables as $t) if (strtoupper($t->ENGINE) !== 'INNODB') throw new RuntimeException('Consistent online backup requires InnoDB tables');
file_put_contents('database-metadata.json', json_encode(['tables'=>$tables, 'captured_utc'=>gmdate('c')], JSON_PRETTY_PRINT));
