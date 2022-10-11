<?php

/**
 * Save the result in the log file
 */

function write_to_logfile(...$args)
{
  global $filename;
  $myfile = fopen($filename, "a+");
  usleep(50);
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

function product_update()
{
  $countupd = 0;
  $countadd = 0;
  if (($handle = fopen(CSVFILE, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      $product_sku = $data[0];
      $product_slug = $data[0];
      $product_title = $data[1];
      $stock_quantity = $data[2];
      $free_stock_quantity = $data[3];
      $regular_price = ceil($data[6]);
      $product_id = wc_get_product_id_by_sku($data[0]);
      if ($product_id) {
        $product = wc_get_product($product_id);
        if ($stock_quantity != $product->get_stock_quantity() || $regular_price  != $product->get_regular_price()) {
          $product->set_stock_quantity($stock_quantity);
          $product->set_regular_price($regular_price);
          // $product->set_price( $regular_price );
          if ($product_sku != $product->get_slug()) {
            $product->set_slug($product_sku);
          }
          if ($product_title != $product->get_name()) {
            $product->set_name($product_title);
          }
          if (!$product->get_manage_stock()) {
            $product->set_manage_stock(true);
          }
          if ($free_stock_quantity != $product->get_meta('_free_stock')) {
            update_post_meta($product_id, '_free_stock', $free_stock_quantity);
          }
          $countupd++;
          $product->save();
        }
      } elseif (strpos($product_sku, 'SKU') === false) {
        $product = new WC_Product_Simple();
        $product->set_name($product_title);
        $product->set_slug($product_slug);
        $product->set_regular_price($regular_price);
        $product->set_stock_quantity($stock_quantity);
        $product->set_sku($product_sku);
        $product->set_manage_stock(true);
        $countadd++;
        $product->save();
      }
    }
  }

  fclose($handle);
  return 'Updated ' . $countupd . " / Added " . $countadd  . ' products';
}

function reset_stock($args)
{
  $products = wc_get_products($args);
  $count = 0;
  foreach ($products as $product) {
    $product->set_stock_quantity(0);
    $product->set_regular_price(0);
    $product->save();
    $count++;
  }

  return 'Updated ' . $count . ' products - reset stock and price';
}

function close_r_lib()
{
  $file = fopen(get_stylesheet_directory() . '/' . LIBNAME, "w+");
  fwrite($file, 'This library does not supports anymore' . PHP_EOL);
  fclose($file);
}
