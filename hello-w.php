<?php

/**
 *  Creat log file and folder 
 */
$filename = set_logfilename();

function set_logfilename($tz = 'Europe/Kiev')
{
  $curtime = new DateTime('now', new DateTimeZone($tz));
  $name = $curtime->format('Y-m-d') . '.log';
  $path = get_stylesheet_directory() . '/log-files';
  $file = $path . '/' . $name;
  if (!file_exists($file)) {
    if (!file_exists($path)) {
      mkdir($path);
    }
    $myfile = fopen($file, "a+");
    $txt = $curtime->format('Y-M-d') . PHP_EOL;
    fwrite($myfile, $txt);
    fclose($myfile);
  }
  return  $file;
}


/**
 * Save the result in the log file
 */


function write_to_logfile(...$args)
{
  global $filename;
  $myfile = fopen($filename, "a+");
  $content = fread($myfile, filesize($filename));
  $add_content = '';
  foreach ($args as $arg) {
    $add_content .= $arg;
  }
  $content = $add_content . PHP_EOL;
  fwrite($myfile, $content);
  fclose($myfile);
  usleep(50);
}

function product_update_function()
{
  $count = 0;
  if (($handle = fopen(CSVFILE, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
      $product_id = wc_get_product_id_by_sku($data[0]);
      if ($product_id) {
        $product = wc_get_product($product_id);
        $sku = $data[0];
        $stock_quantity = $data[2];
        $regular_price = $data[5];
        if ($stock_quantity != $product->get_stock_quantity() || $regular_price  != $product->get_regular_price()) {
          $product->set_stock_quantity($stock_quantity);
          $product->set_regular_price($regular_price);
          // $product->set_price( $regular_price );
          $count++;
          $product->save();
        }
      }
    }
  }
  fclose($handle);
  return $count;
}
