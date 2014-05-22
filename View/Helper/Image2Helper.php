<?php
/**
 *
 * Modification of orignal Image helper boundled with Croogo
 * added crop and cropResize feature
 *
 * @version 1.1
 * @author Josh Hundley
 * @author Jorge Orpinel <jop@levogiro.net> (changes)
 * @author Juraj Jancuska <jjancuska@gmail.com> (minor changes)
 */
class Image2Helper extends Helper {

        public $helpers = array('Html');
        public $cacheDir = 'resized'; // relative to 'img'.DS

        /**
        * Automatically resize (crop) an image and returns formatted IMG tag
        *
        * @param string $path Path to the image file, relative to the webroot/img/ directory.
        * @param integer $width Image of returned image
        * @param integer $height Height of returned image
        * @param string $method resize method (resize, resizeRatio, resizeCrop, crop)
        * @param array    $htmlAttributes Array of HTML attributes.
        * @param boolean $return Wheter this method should return a value or output it. This overrides AUTO_OUTPUT. (!!! DEPRECATED, NOT USED)
        * @param string $server_path Local server path to file
        * @return mixed    Either string or echos the value, depends on AUTO_OUTPUT and $return.
        * @access public
        */
        public function resize($path, $width, $height, $method = 'resizeRatio', $htmlAttributes = array(), $return = false, $server_path = false, $return_path = false ) {

                $types = array(1 => "gif", "jpeg", "png", "swf", "psd", "wbmp"); // used to determine image type
                // if(empty($htmlAttributes['alt'])) $htmlAttributes['alt'] = 'thumb';  // Ponemos alt default

                // sitex
                $id = '';
                if (strpos($path, '/uploads/source/') === 0) {
                        preg_match('|[0-9]+|',$path,$matches);
                        $id = $matches[0];
                }

                $uploadsDir = 'uploads';

                $fullpath = WWW_ROOT.$uploadsDir.DS; // $fullpath = ROOT.DS.APP_DIR.DS.WEBROOT_DIR.DS.$uploadsDir.DS;
                if (!$server_path) {
                        $url = WWW_ROOT.$path; // $url = ROOT.DS.APP_DIR.DS.WEBROOT_DIR.DS.$path;
                } else {
                        $url = $server_path;
                }

                // sitex - check size
                if ($method == 'resizeHeight') {
                        $width = $height;
                }
                // sitex - check file
                if (!file_exists($url)) {
                	if ($return_path) {
                		$return = 'http://placehold.it/'.$width.'x'.$height;
                	} else {
                		$return = $this->Html->image( 'http://placehold.it/'.$width.'x'.$height );
                	}
			return $return;
                }

                if (!($size = getimagesize($url))) // [0]:width, [1]:height, [2]:type
                        return; // image doesn't exist

                $ex_width  = $size[0];
                $ex_height = $size[1];

                switch ($method) {

                        case "resizeRatio":
                                if (($ex_height/$height) > ($ex_width/$width))  {
                                        $width = ceil(($ex_width/$ex_height) * $height);
                                } else {
                                        $height = ceil($width / ($ex_width/$ex_height));
                                }
                                $start_x = 0;
                                $start_y = 0;
                                $method_short = 'rr';
                                break;

                                case "resizeWidth":
                                        // height undefined
                                        $test = ceil($width / ($ex_width/$ex_height));
                                        $start_x = 0;
                                        $start_y = 0;

                                        if ($test < $width) {
                                                $ratio_x = $width / $ex_width;
                                                $ratio_y = $height / $ex_height;

                                                $start_x = round(($ex_width - ($width / $ratio_y)) / 2);
                                                $start_y = 0;
                                                $ex_width = round($width / $ratio_y);

                                                $height = $width;
                                        } else {
                                                $height = $test;
                                        }
                                        $method_short = 'rw';
                                        $htmlAttributes = compact('width','height');
                                break;

                                case "resizeHeight":
                                        // width undefined
                                        $width = ceil(($ex_width/$ex_height) * $height);
                                        $start_x = 0;
                                        $start_y = 0;

                                        $method_short = 'rh';
                                        $htmlAttributes = compact('width','height');
                                break;

                        case "resize":
                                $start_x = 0;
                                $start_y = 0;
                                $method_short = 'r';
                                break;

                        case "resizeCrop":
                                $ratio_x = $width / $ex_width;
                                $ratio_y = $height / $ex_height;
                                if (($ratio_y) > ($ratio_x))  {
                                        $start_x = round(($ex_width - ($width / $ratio_y)) / 2);
                                        $start_y = 0;
                                        $ex_width = round($width / $ratio_y);
                                } else {
                                        $start_x = 0;
                                        $start_y = round(($ex_height - ($height / $ratio_x)) / 2);
                                        $ex_height = round($height / $ratio_x);
                                }
                                $method_short = 'rc';
                                break;

                        case "resizeCropTop":
                                $ratio_x = $width / $ex_width;
                                $ratio_y = $height / $ex_height;
                                if (($ratio_y) > ($ratio_x))  {
                                        $start_x = round(($ex_width - ($width / $ratio_y)) / 2);
                                        $start_y = 0;
                                        $ex_width = round($width / $ratio_y);
                                } else {
                                        $start_x = 0;
                                        $start_y = 0;
                                        $ex_height = round($height / $ratio_x);
                                }
                                $method_short = 'rct';
                                break;

                        case "crop":
                                $start_x = ($ex_width - $width) / 2;
                                $start_y = ($ex_height - $height) / 2;
                                $ex_width = $width;
                                $ex_height = $height;
                                $method_short = 'c';
                                break;


                }

                // sitex 12 03 2013
                $path_array = explode('/', $path);
                $bn_path = end($path_array);
                $relfile = '/' . $uploadsDir . '/' . $this->cacheDir . '/' . $id . '_' . $method_short . '_' . $width . 'x' . $height . '_' . $bn_path; // relative file
                $cachefile = $fullpath . $this->cacheDir . DS . $id . '_' . $method_short . '_' . $width . 'x' . $height . '_' . $bn_path;  // location on server

                if (file_exists($cachefile)) {
                        $csize = getimagesize($cachefile);
                        $cached = ($csize[0] == $width && $csize[1] == $height); // image is cached
                        if (@filemtime($cachefile) < @filemtime($url)) {// check if up to date
                                $cached = false;
                        }
                } else {
                        $cached = false;
                }

                if (!$cached) {

                        $image = call_user_func('imagecreatefrom'.$types[$size[2]], $url);
                        if (function_exists("imagecreatetruecolor") && ($temp = imagecreatetruecolor ($width, $height))) {
                        	// sitex 20140331 - transparent
				imagealphablending($temp, false);
				imagesavealpha($temp,true);
				$transparent = imagecolorallocatealpha($temp, 255, 255, 255, 127);
				imagefilledrectangle($temp, 0, 0, $width, $height, $transparent);

                                // imagecolortransparent($temp, imagecolorallocate($temp, 0, 0, 0));
                                imagecopyresampled ($temp, $image, 0, 0, $start_x, $start_y, $width, $height, $ex_width, $ex_height);
                        } else {
                                $temp = imagecreate ($width, $height);
                                imagecopyresized ($temp, $image, 0, 0, $start_x, $start_y, $width, $height, $ex_width, $ex_height);
                        }
                        $quality = ($types[$size[2]] == 'png') ? 9 : 90;			// sitex
                        call_user_func("image".$types[$size[2]], $temp, $cachefile, $quality);	// sitex 06062013 JpegQuality
                        imagedestroy ($image);
                        imagedestroy ($temp);
                } else {
                        //copy($url, $cachefile);
                }

                // return image
                $return = $this->Html->image($relfile, $htmlAttributes);
                // return path
                if ($return_path) {
	        	$return = $this->Html->assetUrl($relfile);
                }

                return $return;

        }
}
?>
