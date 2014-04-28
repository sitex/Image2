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

                // sitex - check file
                if (!file_exists($url)) {
                	if ($return_path) {
                		$return = 'http://placehold.it/'.$width.'x'.$height;
                	} else {
                		$return = $this->Html->image( 'http://placehold.it/'.$width.'x'.$height );
                	}
			return $return;
                }

                if (!($size = getimagesize($url))) // $size[0]:width, [1]:height, [2]:type
                        return; // image doesn't exist


                switch ($method) {

                        case "resizeRatio":
                                if (($size[1]/$height) > ($size[0]/$width))  {
                                        $width = ceil(($size[0]/$size[1]) * $height);
                                } else {
                                        $height = ceil($width / ($size[0]/$size[1]));
                                }
                                $start_x = 0;
                                $start_y = 0;
                                $method_short = 'rr';
                                break;

				case "resizeWidth":
					$test = ceil($width / ($size[0]/$size[1]));
					$start_x = 0;
					$start_y = 0;
					// minheight
					if ($test < $width) {
						$ratio_x = $width / $size[0];
						$ratio_y = $height / $size[1];

						$start_x = round(($size[0] - ($width / $ratio_y)) / 2);
						$start_y = 0;
						$size[0] = round($width / $ratio_y);

						$height = $width;
					} else {
						$height = $test;
					}
					$method_short = 'rw';
					$htmlAttributes = compact('width','height');
                                break;

                        case "resize":
                                $start_x = 0;
                                $start_y = 0;
                                $method_short = 'r';
                                break;

                        case "resizeCrop":
                                $ratio_x = $width / $size[0];
                                $ratio_y = $height / $size[1];
                                if (($ratio_y) > ($ratio_x))  {
                                        $start_x = round(($size[0] - ($width / $ratio_y)) / 2);
                                        $start_y = 0;
                                        $size[0] = round($width / $ratio_y);
                                } else {
                                        $start_x = 0;
                                        $start_y = round(($size[1] - ($height / $ratio_x)) / 2);
                                        $size[1] = round($height / $ratio_x);
                                }
                                $method_short = 'rc';
                                break;

                        case "resizeCropTop":
                                $ratio_x = $width / $size[0];
                                $ratio_y = $height / $size[1];
                                if (($ratio_y) > ($ratio_x))  {
                                        $start_x = round(($size[0] - ($width / $ratio_y)) / 2);
                                        $start_y = 0;
                                        $size[0] = round($width / $ratio_y);
                                } else {
                                        $start_x = 0;
                                        $start_y = 0;
                                        $size[1] = round($height / $ratio_x);
                                }
                                $method_short = 'rct';
                                break;

                        case "crop":
                                $start_x = ($size[0] - $width) / 2;
                                $start_y = ($size[1] - $height) / 2;
                                $size[0] = $width;
                                $size[1] = $height;
                                $method_short = 'c';
                                break;


                }

                // sitex 12 03 2013
                $bn_path = end(explode('/', $path));
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
                                imagecopyresampled ($temp, $image, 0, 0, $start_x, $start_y, $width, $height, $size[0], $size[1]);
                        } else {
                                $temp = imagecreate ($width, $height);
                                imagecopyresized ($temp, $image, 0, 0, $start_x, $start_y, $width, $height, $size[0], $size[1]);
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
