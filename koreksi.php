<?php
if( ! function_exists('curl_exec') )    die("ERROR: Dibutuhkan extensi CURL\n");
if( ! function_exists('json_decode') )  die("ERROR: Dibutuhkan extensi JSON\n");

define( 'CACHE_DIR', "cache" );
define( 'END_POINT', "https://pemilu2019.kpu.go.id/static/json/" );
define( 'VERBOSE', strpos( implode( ' ', array_map( 'strtolower', array_slice($argv, 1) ) ), '-v') !== false );

if( VERBOSE )
{
    echo "** VERBOSE MODE ON\n";
}

// ambil data wilayah provinsi
$wilayah_provinsi = getData( sprintf('wilayah/%d.json', 0) );

/**
 * get data from web KPU
 * @param path: web json end point
 * @param cache_ttl: Allowed fresh cached file. set zero for disable cache
 */
function getData($path, $cache_ttl = 600)
{
    global $CACHE_DIR, $END_POINT;
    $cache_file = $CACHE_DIR . DIRECTORY_SEPARATOR . $path;
    if( !is_dir( dirname($cache_file) ) && !mkdir(dirname($cache_file), 0755, true))
    {
        die("Tidak bisa bikin folder: " . dirname($cache_file));
    }
    // Check is cache fresh?


}