<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'DATABASE '.config('database.connections.mysql.database').PHP_EOL;
foreach (Illuminate\Support\Facades\DB::select('SHOW TABLES') as $row) {
 $table = array_values((array)$row)[0];
 echo Illuminate\Support\Facades\DB::select('SHOW CREATE TABLE `'.$table.'`')[0]->{'Create Table'}.PHP_EOL;
 echo 'COUNT '.Illuminate\Support\Facades\DB::table($table)->count().PHP_EOL;
}
