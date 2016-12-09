<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from 202 ecommerce
 * Use, copy, modification or distribution of this source file without written
 * license agreement from 202 ecommerce is strictly forbidden.
 *
 * @author    202 ecommerce <contact@202-ecommerce.com>
 * @copyright Copyright (c) 202 ecommerce 2014
 * @license   Commercial license
 *
 * Support <support@202-ecommerce.com>
 */
if (!defined('_PS_VERSION_')) {
    die(header('HTTP/1.0 404 Not Found'));
}

class CustomMadeGenerateimageModuleFrontController extends ModuleFrontController {

    public function __construct() {
        parent::__construct();
        
        //echo '----' . __LINE__ . '----' . __FILE__;
        $select = "SELECT * FROM "._DB_PREFIX_."options WHERE 1 and status = 'pending' limit 5";
        //$select = "SELECT * FROM "._DB_PREFIX_."options WHERE 1 limit 5";
        $results = Db::getInstance()->ExecuteS($select);
        //echo '----' . __LINE__ . '----' . __FILE__ . '<pre>' . print_r($results, true) . '</pre>';
        foreach($results as $k => $row){
            $id = $row['id'];
            $orderId = $row['order_id'];
            $productId = $row['product_id'];
            $customOptions = json_decode($row['options']);
            //echo '----' . __LINE__ . '----' . __FILE__ . '<pre>' . print_r($customOptions, true) . '</pre>';
            $options = array();
            $options['hd_image_url'] = 'http://localhost/afdc/wallpapper.jpg';
            $options['crop_x'] = $customOptions->x;
            $options['crop_y'] = $customOptions->y;
            $options['width'] = $customOptions->width;
            $options['height'] = $customOptions->height;
            $options['rotate_degree'] = $customOptions->rotate;
            if($customOptions->scaleX == '-1' || $customOptions->scaleY == '-1'){
                $options['mirror_effect'] = 1;
            }else{
                $options['mirror_effect'] = 0;
            }
            
            if(isset($customOptions->stripe) && $customOptions->stripe == '1'){
                $options['stripe_filename'] = 'modules/custommade/stripe.png';
            }
            $options['output_filename'] = 'modules/custommade/output/'.$id.'.png';
            $updateStatus = 'UPDATE '._DB_PREFIX_.'options SET status = "processing" WHERE 1 and id = "'.$id.'"';
                DB::getInstance()->Execute($updateStatus);
            if($this->generateFinalImage($options)){
                $updateStatus = 'UPDATE '._DB_PREFIX_.'options SET status = "completed" WHERE 1 and id = "'.$id.'"';
                DB::getInstance()->Execute($updateStatus);
            }else{
                $updateStatus = 'UPDATE '._DB_PREFIX_.'options SET status = "error" WHERE 1 and id = "'.$id.'"';
                DB::getInstance()->Execute($updateStatus);
            }
            
        }
        //$this->generateFinalImage($options);
        die;
    }

    private function generateFinalImage($config) {
        if (isset($config['hd_image_url']) && filter_var($config['hd_image_url'], FILTER_VALIDATE_URL)) {
            $imageData = file_get_contents($config['hd_image_url']);
            //echo '----' . __LINE__ . '----' . __FILE__ . $imageData;
            //echo '----' . __LINE__ . '----' . __FILE__ . '<pre>' . print_r($config, true) . '</pre>';
            $tmpFileName = 'tmp_image.jpg';
            file_put_contents($tmpFileName, $imageData);
            //$size = 200;
            $im = imagecreatefromjpeg($tmpFileName);
            /* imagealphablending($im, true);
              $transparentcolour = imagecolorallocate($im, 255, 255, 255);
              imagecolortransparent($im, $transparentcolour); */
            //$size = min(imagesx($im), imagesy($im));


            /* Start : Crop Image */
            $im = imagecrop($im, ['x' => $config['crop_x'], 'y' => $config['crop_y'], 'width' => $config['width'], 'height' => $config['height']]);
            /* Stop : Crop Image */
            /* Start : Rotate Image */
            $im = imagerotate($im, $config['rotate_degree'], 0);
            /* Stop : Rotate Image */

            /* Start : Flip Image (Mirror effect) */
            if ($config['mirror_effect']) {
                imageflip($im, IMG_FLIP_HORIZONTAL);
            }
            /* Stop : Flip Image (Mirror effect) */

            /* Start : Merge Stripe */
            if (isset($config['stripe_filename']) && trim($config['stripe_filename']) != '') {

                $sim = imagecreatefrompng($config['stripe_filename']);
                imagecopyresampled($im, $sim, 0, 0, 0, 0, $config['width'], $config['height'], $config['width'], $config['height']);
            }
            /* Stop : Merge Stripe */
            //generate final output image
            if ($im !== FALSE) {
                imagepng($im, $config['output_filename']);
                //echo $config['output_filename'];
                return true;
            }
            return false;
        } else {
            //die('invalid image url');
            return false;
        }
    }

}
