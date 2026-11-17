<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');
if(is_file(dirname(__DIR__).'/.env')) foreach(file(dirname(__DIR__).'/.env', FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line){if(str_starts_with(trim($line),'#')||!str_contains($line,'='))continue;[$k,$v]=explode('=',$line,2);$_ENV[trim($k)]=trim(trim($v)," \"'");}
function envv(string $k,string $d=''):string{$v=getenv($k);return (string)($v!==false?$v:($_ENV[$k]??$d));}
function db():PDO{static $p;if(!$p){$p=new PDO('mysql:host='.envv('DB_HOST','127.0.0.1').';port='.envv('DB_PORT','3306').';dbname='.envv('DB_DATABASE','mis_logbook').';charset=utf8mb4',envv('DB_USERNAME','root'),envv('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);$p->exec("SET time_zone = '+07:00'");}return $p;}
function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function go(string $p):never{header('Location: ?page='.rawurlencode($p));exit;}
function csrf():string{if(empty($_SESSION['_csrf']))$_SESSION['_csrf']=bin2hex(random_bytes(32));return $_SESSION['_csrf'];}
function verify_csrf():void{if(!hash_equals(csrf(),(string)($_POST['_csrf']??''))){http_response_code(419);exit('Sesi formulir kedaluwarsa. Muat ulang halaman.');}}
function admin_ip_allowed():bool{$client=$_SERVER['REMOTE_ADDR']??'';$address=@inet_pton($client);if($address===false)return false;foreach(explode(',',envv('ADMIN_ALLOWED_CIDRS')) as $range){$range=trim($range);if($range==='')continue;$parts=explode('/',$range,2);if(count($parts)>2||(isset($parts[1])&&!ctype_digit($parts[1])))continue;$network=@inet_pton(trim($parts[0]));if($network===false||strlen($network)!==strlen($address))continue;$max=strlen($address)*8;$bits=isset($parts[1])?(int)$parts[1]:$max;if($bits<0||$bits>$max)continue;$bytes=intdiv($bits,8);$remaining=$bits%8;if(substr($address,0,$bytes)!==substr($network,0,$bytes))continue;if($remaining===0||((ord($address[$bytes])^ord($network[$bytes]))&(0xff<<(8-$remaining)))===0)return true;}return false;}
require_once __DIR__ . '/views.php';
require_once __DIR__ . '/history.php';
function visit_data():array{
    $d=[];
    foreach(['visit_date','visitor_name','company','person_to_meet','purpose','check_in_time','check_out_time'] as $key){
        $d[$key]=trim((string)($_POST[$key]??''));
    }
    $date=DateTime::createFromFormat('!Y-m-d',$d['visit_date']);
    $validTime=static fn(string $time):bool=>(bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$time);
    if(!$date||$date->format('Y-m-d')!==$d['visit_date']||$d['visitor_name']===''||$d['person_to_meet']===''||$d['purpose']===''||!$validTime($d['check_in_time'])||!$validTime($d['check_out_time'])){
        throw new InvalidArgumentException('Lengkapi tanggal, identitas, keperluan, jam masuk, dan jam keluar dengan format yang benar.');
    }
    if($d['check_out_time']<$d['check_in_time']){
        throw new InvalidArgumentException('Jam keluar tidak boleh mendahului jam masuk pada tanggal kunjungan yang sama.');
    }
    foreach(['visitor_name'=>150,'company'=>150,'person_to_meet'=>150,'purpose'=>3000] as $key=>$max){
        if(mb_strlen($d[$key])>$max)throw new InvalidArgumentException('Isian terlalu panjang.');
    }
    $d['company']=$d['company']?:null;
    $d['check_in_time'].=':00';
    $d['check_out_time'].=':00';
    return $d;
}
