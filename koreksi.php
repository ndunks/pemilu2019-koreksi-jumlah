<?php
/**
 * BARASOFT - Just for fun
 */
if( ! function_exists('curl_exec') )    die("ERROR: Dibutuhkan extensi CURL\n");
if( ! function_exists('json_decode') )  die("ERROR: Dibutuhkan extensi JSON\n");

define( 'CACHE_DIR', "cache" );
define( 'MAX_RETRY', 10 );
define( 'END_POINT', "https://pemilu2019.kpu.go.id/static/json/" );
define( 'VERBOSE', strpos( implode( ' ', array_map( 'strtolower', array_slice($argv, 1) ) ), '-v') !== false );
define( 'CURL_OPT', [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => 'gzip',
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false
]);

if( VERBOSE )
{
    echo "**  VERBOSE MODE ON\n";
}
$result_name = 'result.csv';
$result_error = 0;
$result_col = 6;

if( file_exists($result_name) )
{
    $num         = 0;
    do
    {
        $result_backup = 'result_backup_' . ( ++$num ) . '.csv';
    }while( file_exists($result_backup) );
    // Move old result
    copy($result_name, $result_backup);
}

$result_csv = fopen($result_name, 'w');
fputcsv($result_csv, ['Provinsi', 'Kab/Kota', 'Kel/Kec', 'Desa', 'TPS', 'Kesalahan']);


// ambil data wilayah provinsi
$wilayah_nasional = getData( sprintf('wilayah/%d.json', 0) );
walker( $wilayah_nasional );

echo "DONE, total kesalahan: $result_error\n";
fputcsv($result_csv, ['TOTAL SALAH:', $result_error, '','','',''] );
fclose($result_csv);

// todo: cek luar negeri, kayaknya strukturnya beda

function walker(&$data, $parent = [], $parent_names = [])
{
    $do_verify  = count($parent) >= 3;
    $padding    = str_repeat(' ', 4 * count($parent));
    if( VERBOSE )
    {
        echo $padding . "** DEPTH: " . count($parent) . "\n";
    }
    foreach ($data as $id => &$child)
    {
        echo $padding . $child['nama'] . "\n";
        $child_path   = ltrim( sprintf('%s/%d.json', implode('/', $parent), $id ), "/" );
        $child_sub = getData( 'wilayah/' . $child_path, $padding, 60 * 60 * 24 * 2 );
        $new_parent = array_merge( $parent, [$id]);
        $new_parent_names = array_merge( $parent_names, [$child['nama']]);
        $do_verify ? verify($child_sub, $new_parent, $new_parent_names) :  walker($child_sub, $new_parent, $new_parent_names );
    }
    unset($child);
}

function verify(&$data, $parent, $parent_names)
{
    global $result_csv, $result_col, $result_error;

    $padding    = str_repeat(' ', 4 * count($parent));
    $property_check = [ 'chart', /* 'images', 'pemilih_j', 'pengguna_j',  */ 'suara_sah', 'suara_tidak_sah', 'suara_total' ];
    foreach ($data as $id => &$child)
    {
        $path   = sprintf('hhcw/ppwp/%s/%d.json', implode('/', $parent), $id );
        $tps = getData( $path, $padding );
        echo $padding . $child['nama'] . " :\t";
        foreach ($property_check as $key)
        {
            if( ! array_key_exists($key, $tps) )
            {
                echo "Missing $key \n";
                continue 2;
            }
        }
        // 21 JKW, 22 PBW
        // Hitung total chart dan harus sama dengan suara sah
        $kesalahan = [];
        $total_chart = 0;
        foreach ($tps['chart'] as $paslon_id => $suara)
        {
            $total_chart += intval($suara);
        }
        if( $total_chart != intval($tps['suara_sah']) )
        {
            $kesalahan[]    = 'Suara sah ' . implode(' + ', $tps['chart']) . ' != ' . $tps['suara_sah'];
        }
        if( $total_chart + intval($tps['suara_tidak_sah']) != intval($tps['suara_total']) )
        {
            $kesalahan[]    = 'Total suara ' . $total_chart . ' + ' . intval($tps['suara_tidak_sah']) . ' != ' . intval($tps['suara_total']);
        }
        if( count($kesalahan) )
        {
            echo " SALAH! " . implode( " dan ", $kesalahan );
            $result_error ++;
            $cols = $parent_names;
            while( count($cols) < $result_col - 2 )
            {
                $cols[] = '';
            }
            $cols[] = $child['nama'];
            $cols[] = implode( " dan ", $kesalahan);
            // Tulis dalam file
            fputcsv($result_csv, $cols);
        }else{
            echo "VALID";
        }
        echo "\n";
    }
}

/**
 * get data from web KPU
 * @param path: HTTP URL json end point
 * @param padding: String padding tab
 * @param cache_ttl: Allowed fresh cached file. set zero for disable cache
 * @param is_retry: Internal flag of retried
 */
function getData($path, $padding = '',  $cache_ttl = 7200, $is_retry = 0)
{

    $cache_file = CACHE_DIR . DIRECTORY_SEPARATOR . strtr($path, '/', DIRECTORY_SEPARATOR);
    if( !is_dir( dirname($cache_file) ) && !mkdir(dirname($cache_file), 0755, true))
    {
        die("Tidak bisa bikin folder: " . dirname($cache_file) . "\n");
    }

    if( VERBOSE )
    {
        echo $padding . "**  " . ($is_retry ? "RETRY $is_retry" : "GET") . " $path ";
    }
    // Check is cache fresh?
    if( $cache_ttl > 0 && file_exists( $cache_file ) && filemtime( $cache_file ) + $cache_ttl > time() )
    {
        if( VERBOSE )
        {
            echo "  [ Load from cache ]\n";
        }
        return json_decode( file_get_contents( $cache_file ), true );
    }

    $ch     = curl_init( END_POINT . $path );
    curl_setopt_array( $ch, CURL_OPT );
    $body   = curl_exec( $ch );
    $code   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    if( empty($body) || $code != 200 )
    {
        if( VERBOSE )
        {
            printf("    HTTP %d, %d bytes.\n", $code, strlen($body));
        }
        if( $is_retry + 1 > MAX_RETRY )
        {
            die("HTTP ERROR, Max retry reached. EXITED\n");
        }else{
            return getData( $path, $padding, $cache_ttl, $is_retry + 1);
        }
    }elseif(VERBOSE)
    {
        printf( "   [OK, %d bytes.]\n", strlen($body) );
        file_put_contents( $cache_file, $body );
    }
    return json_decode( $body, true);
}