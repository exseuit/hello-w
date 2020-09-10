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
        if ($stock_quantity != $product->get_stock_quantity() || $regular_price  != $product->get_regular_price() || $free_stock_quantity != $product->get_meta('_free_stock')) {
          $product->set_stock_quantity($stock_quantity);
          $product->set_regular_price($regular_price);
          // $product->set_price( $regular_price );
          update_post_meta($product_id, '_free_stock', $free_stock_quantity);

          // if ($free_stock_quantity != $product->get_meta('_free_stock')) {
          //   update_post_meta($product_id, '_free_stock', $free_stock_quantity);
          // }

          if ($product_sku != $product->get_slug()) {
            $product->set_slug($product_sku);
          }
          if ($product_title != $product->get_name()) {
            $product->set_name($product_title);
          }
          if (!$product->get_manage_stock()) {
            $product->set_manage_stock(true);
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

function csv_export()
{

  $upload = wp_upload_dir();
  $upload_dir = $upload['basedir'];
  $upload_dir = $upload_dir . '/csv';

  if (!wp_mkdir_p($upload_dir)) {
    echo "Не удалось создать каталог $upload_dir";
  }


  $args =  array(
    'post_type'       => 'product',
    'status'          => 'publish',
    'stock_status'    => 'instock',
    'limit'           => -1,


    // 'orderby'     => 'menu_order',
    'orderby'     => 'title',
    'order'       => 'ASC',
  );

  $csvfile = $upload_dir  . '/stock.csv';
  $file = fopen($csvfile, "w+");

  fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
  fputcsv($file, array('SKU',  'Product name', 'Price',  'Qty',  'FreeQty'));
  $products = wc_get_products($args);

  foreach ($products as $product) {

    $result = array($product->get_sku(), $product->get_name(), $product->get_regular_price(), $product->get_stock_quantity(), $product->get_meta('_free_stock'));
    fputcsv($file, $result);
  }

  // return $csvfile;
  // return includes_url() . 'csv/stock.csv';
  return site_url() . '/wp-content/uploads/csv/stock.csv';
}

