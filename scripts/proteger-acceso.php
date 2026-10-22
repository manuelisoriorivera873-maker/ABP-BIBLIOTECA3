<?php
// CLI only: additive repair of the verified legacy access schema.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();
if (DB::connection()->getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'biblioteca2') {
    throw new RuntimeException('Unexpected database; refusing repair.');
}
$checks = [
    'duplicate usernames' => 'SELECT COUNT(*) n FROM (SELECT nombre_usuario FROM cuentas_acceso GROUP BY nombre_usuario HAVING COUNT(*) > 1) x',
    'duplicate reader links' => 'SELECT COUNT(*) n FROM (SELECT id_usuario FROM cuentas_acceso WHERE id_usuario IS NOT NULL GROUP BY id_usuario HAVING COUNT(*) > 1) x',
    'duplicate staff links' => 'SELECT COUNT(*) n FROM (SELECT id_bibliotecario FROM cuentas_acceso WHERE id_bibliotecario IS NOT NULL GROUP BY id_bibliotecario HAVING COUNT(*) > 1) x',
    'orphan readers' => 'SELECT COUNT(*) n FROM cuentas_acceso c LEFT JOIN usuarios u ON c.id_usuario=u.id_usuario WHERE c.id_usuario IS NOT NULL AND u.id_usuario IS NULL',
    'orphan staff' => 'SELECT COUNT(*) n FROM cuentas_acceso c LEFT JOIN bibliotecarios b ON c.id_bibliotecario=b.id_bibliotecario WHERE c.id_bibliotecario IS NOT NULL AND b.id_bibliotecario IS NULL',
];
foreach ($checks as $label => $sql) {
    if ((int) $pdo->query($sql)->fetchColumn() !== 0) { throw new RuntimeException('Precondition failed: '.$label); }
}
$indexes = $pdo->query('SHOW INDEX FROM cuentas_acceso')->fetchAll(PDO::FETCH_ASSOC);
$clauses = [];
foreach (['nombre_usuario', 'id_usuario', 'id_bibliotecario'] as $column) {
    $unique = array_filter($indexes, fn ($i) => $i['Column_name'] === $column && !$i['Non_unique'] && $i['Seq_in_index'] == 1);
    if (!$unique) { $clauses[] = 'ADD UNIQUE KEY `uq_acceso_'.$column.'` (`'.$column.'`)'; }
}
$foreign = $pdo->query("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='cuentas_acceso' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
foreach (['id_usuario' => ['usuarios', 'id_usuario'], 'id_bibliotecario' => ['bibliotecarios', 'id_bibliotecario']] as $column => [$table, $key]) {
    if (!in_array($column, $foreign, true)) {
        $clauses[] = "ADD CONSTRAINT `fk_acceso_$column` FOREIGN KEY (`$column`) REFERENCES `$table` (`$key`) ON DELETE RESTRICT ON UPDATE CASCADE";
    }
}
if (!$clauses) { echo "Schema already protected.\n"; exit; }
$sql = 'ALTER TABLE cuentas_acceso '.implode(', ', $clauses);
echo $sql.";\n";
if (!in_array('--apply', $argv, true)) { echo "Preconditions passed; use --apply to create verified backup and apply.\n"; exit; }
$directory = storage_path('app/private/backups');
if (!is_dir($directory)) { mkdir($directory, 0700, true); }
$file = $directory.'/biblioteca2-'.date('Ymd-His').'.sql';
$dump = "-- Full backup before additive access constraints\nSET FOREIGN_KEY_CHECKS=0;\n";
$counts = [];
DB::beginTransaction();
try {
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $dump .= $pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1].";\n";
        $rows = $pdo->query('SELECT * FROM `'.$table.'`')->fetchAll(PDO::FETCH_ASSOC);
        $counts[$table] = count($rows);
        foreach ($rows as $row) {
            $values = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($row));
            $dump .= 'INSERT INTO `'.$table.'` (`'.implode('`,`', array_keys($row)).'`) VALUES ('.implode(',', $values).");\n";
        }
    }
    DB::commit();
} catch (Throwable $e) { DB::rollBack(); throw $e; }
$dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
if (file_put_contents($file, $dump) !== strlen($dump) || hash_file('sha256', $file) !== hash('sha256', $dump)) {
    throw new RuntimeException('Backup verification failed. Schema unchanged.');
}
file_put_contents($file.'.sha256', hash('sha256', $dump));
$pdo->exec($sql);
foreach ($counts as $table => $count) {
    if ((int) $pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn() !== $count) {
        throw new RuntimeException('Row count changed: '.$table);
    }
}
echo "Backup verified: ".$file."\nConstraints applied; all table counts preserved.\n";
