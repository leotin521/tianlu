<?php
// 头像剪切
class ShearModel extends Model {
   //no.1
   /**
    * 图像裁剪
    * @param $title string 原图路径
    * @param $content string 需要裁剪的宽
    * @param $encode string 需要裁剪的高
    */
    function imagecropper($source_path, $target_width, $target_height)
    {
        $source_info = getimagesize($source_path);
        $source_width = $source_info[0];
        $source_height = $source_info[1];
        $source_mime = $source_info['mime'];
        $source_ratio = $source_height / $source_width;
        $target_ratio = $target_height / $target_width;
    
        // 源图过高
        if ($source_ratio > $target_ratio)
        {
            $cropped_width = $source_width;
            $cropped_height = $source_width * $target_ratio;
            $source_x = 0;
            $source_y = ($source_height - $cropped_height) / 2;
        }
        // 源图过宽
        elseif ($source_ratio < $target_ratio)
        {
            $cropped_width = $source_height / $target_ratio;
            $cropped_height = $source_height;
            $source_x = ($source_width - $cropped_width) / 2;
            $source_y = 0;
        }
        // 源图适中
        else
        {
            $cropped_width = $source_width;
            $cropped_height = $source_height;
            $source_x = 0;
            $source_y = 0;
        }
    
        switch ($source_mime)
        {
            case 'image/gif':
                $source_image = imagecreatefromgif($source_path);
                break;
            
            case 'image/jpg':
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($source_path);
                break;

            case 'image/png':
                $source_image = imagecreatefrompng($source_path);
                break;
    
            default:
                return false;
                break;
        }
    
        $target_image = imagecreatetruecolor($target_width, $target_height);
        $cropped_image = imagecreatetruecolor($cropped_width, $cropped_height);
        
        //背景色
        $white = imagecolorallocate($cropped_image, 255, 255, 255);
        imagefill($cropped_image, 0, 0, $white);
        
        // 裁剪
        imagecopy($cropped_image, $source_image, 0, 0, $source_x, $source_y, $cropped_width, $cropped_height);
        
        // 缩放
        imagecopyresampled($target_image, $cropped_image, 0, 0, 0, 0, $target_width, $target_height, $cropped_width, $cropped_height);
        
        //类型
        $type = end(explode(".",$source_path));
        
        //保存图片到本地
        $fileName =  $source_path."_".$target_width."x".$target_height.".".$type;

        switch($source_mime) {
            case 'image/jpg' :
            case 'image/jpeg' :
                imagejpeg($target_image,'./'.$fileName,100); // 存储图像
                break;
            case 'image/png' :
                imagepng($target_image,'./'.$fileName,9);
                break;
            case 'image/gif' :
                imagegif($target_image,'./'.$fileName,100);
                break;
            default:
                break;
        }
        
        imagedestroy($target_image);
    }
}
?>