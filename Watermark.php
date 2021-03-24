<?php

namespace Shiningw;

class Watermark
{
    protected $fontFile = "/usr/share/fonts/truetype/yahei.ttf";
    public $text = '';
    public $bboxAngle = 0;
    public $widthPadding = 30, $heightPadding = 20;
    public $textPos;
    //main image source
    protected $im;
    //watermark image source
    protected $textImg;

    public function __construct($rawImg, $text = null, $pos = 'left', $fontFile = null)
    {
        if (isset($fontFile)) {
            $this->fontFile = $fontFile;
        }
        $this->rawImg = $rawImg;
        $imageType = exif_imagetype($rawImg);
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $this->im = ImageCreateFromjpeg($this->rawImg);
                break;
            case IMAGETYPE_PNG:
                $this->im = ImageCreateFrompng($this->rawImg);
                break;
            case IMAGETYPE_GIF:
                $this->im = ImageCreateFromgif($this->rawImg);
                break;
        }
        $this->imWidth = imagesx($this->im);
        $this->imHeight = imagesy($this->im);
        $this->text = $text;
        $this->textPos = $pos;
        $this->targetDir = dirname($rawImg) . '/watermark';
        $this->init();

    }

    protected function init()
    {
        if (!is_dir($this->targetDir)) {
            mkdir($this->targetDir, 0777, true);
        }

        if (!isset($this->text)) {
            $this->text = self::getPicTime($this->rawImg);
        }

        putenv('GDFONTPATH=' . realpath('.'));
        $this->setFontSize();
        $this->setTextColor();
        $this->setBgColor(0x00);
        $this->setBboxBorderColor();
    }

    //this positions the text image box within the image that is to be watermarked
    private function calTextPos()
    {
        $padding = 10;
        if (in_array(strtolower($this->textPos), array("right", "rightalign"))) {
            $this->dstImg_x = $this->imWidth - $this->textWidth;
            $this->dstImg_y = $this->imHeight - $this->textHeight - $padding;
        } else if (in_array(strtolower($this->textPos), array("left", "leftalign"))) {
            $this->dstImg_x = $padding;
            $this->dstImg_y = $this->imHeight - $this->textHeight - $padding;
        } else if (in_array(strtolower($this->textPos), array("topleft", "topleftalign"))) {
            $this->dstImg_x = $padding;
            $this->dstImg_y = $padding;
        } else if (in_array(strtolower($this->textPos), array("topright", "toprightalign"))) {
            $this->dstImg_x = $this->imWidth - $this->textWidth;
            $this->dstImg_y = $padding;
        }

        //  printf("im x: %s im y: %s dst x: %s dst y: %s text width: %s\n",$this->imWidth,$this->imHeight, $this->dstImg_x,$this->dstImg_y,$this->textWidth);

    }
    public function setFontSize($size = 20)
    {
        $this->fontSize = $size;
        $this->setTextBoxSize();
    }

    public function setTextBoxSize($width = null, $height = null)
    {
        if (isset($widht) && isset($height)) {
            $this->textWidth = $width;
            $this->textHeight = $height;
        } else {
            list($this->bboxWidth, $this->bbboxHeight) = $this->bboxSize();
            //make sure that image box is large enough to contain the text;
            $this->textWidth = $this->bboxWidth + $this->widthPadding;
            $this->textHeight = $this->bbboxHeight + $this->heightPadding;
        }

        if ($this->textWidth > $this->imWidth || $this->textHeight > $this->imHeight) {
            print "width: $this->textWidth, height: $this->textHeight text image is probably too big.it is going to adjust the font size to fit into the image \n";
            $this->fontSize -= 10;
            $this->setFontSize($this->fontSize);
        }

        $this->calTextPos();

        return $this;
    }

    public function setFontFile($ttf)
    {
        $this->fontFile = $ttf;
    }
    //default to white
    public function setTextColor($color = 0xfffff)
    {
        $color = $this->convertColor($color);
        $this->textColor = $color;
    }
    // the background color of watermark box
    public function setBgColor($color = 0x00)
    {
        $color = $this->convertColor($color);
        $this->bgColor = $color;
    }

    public function setBboxBorderColor($color = 0x8b4141)
    {
        $color = $this->convertColor($color);
        $this->borderColor = $color;
    }

    public function rgb2hex($color = array())
    {
        list($red, $green, $blue) = $color;
        $red = dechex($red);
        $green = dechex($green);
        $blue = dechex($blue);
        $number = sprintf("%s%s%s", $red, $green, $blue);
        return hexdec($number);
    }

    public function hex2rgb($hex = null)
    {
        $hex = hexdec($hex);
        $color = array();
        $color['red'] = 255 & ($hex >> 16);
        $color['green'] = 255 & ($hex >> 8);
        $color['blue'] = 255 & $hex;
        return $color;
    }

    public function convertColor($color = null)
    {
        //return sscanf($color, "#%02x%02x%02x");
        $data = $this->colorPalete();
        $color = trim($color);
        if (array_key_exists(strtolower($color), $data)) {
            $color = $data[$color];
        }
        if (is_string($color)) {
            if (($pos = strpos($color, '#')) !== false) {
                $color = substr($color, $pos + 1);
            }

            $color = hexdec($color);
        }
        if (is_array($color)) {
            $color = $this->rgb2hex($color);
        }
        return $color;
    }

    public function colorPalete()
    {
        $data = array(
            'white' => '#FFFFFF',
            'black' => '#000000',
            'red' => '#FF0000',
            'green' => '#00FF00',
            'blue' => '#0000ff',
            'cyan' => '#00FFFF',
            'pink' => '#FFC0CB',
            'yellow' => '#FFFF00',
            'silver' => '#C0C0C0',
            'grey' => '#808080',
            'gray' => '#808080',
            'purpgle' => '#800080',
            'orange' => '#FFA500',
            'gold' => '#FFD700',
            'tomato' => '#FF6347',
            'lightyellow' => '#FFFFE0',
            'deeppink' => '#FF1493',
            'darkcyan' => '#008B8B',
            'lightcyan' => '#E0FFFF',
            'lightblue' => '#ADD8E6',
            'darkblue' => '#00008B',
            'navy' => '#000080',
            'maroon' => '#800000',
            'brown' => '#A52A2A',
            'crimson' => '#DC143C',
        );
        return $data;
    }
    protected function save($im, $path = null)
    {
        if (!isset($path)) {
            $path = $this->targetDir . '/' . basename($this->rawImg);
        }
        Imagejpeg($im, $path, 100);
        ImageDestroy($im);
    }

    public function execute()
    {

        if (!file_exists($this->fontFile)) {
            throw new \Exception($this->fontFile . " not found! \n");
        }
        $this->createTextImage();
        // Merge the text onto our photo with an opacity of 50%
        imagecopymerge($this->im, $this->textImg, $this->dstImg_x, $this->dstImg_y, 0, 0, $this->textWidth, $this->textHeight, 50);
        $this->save($this->im);
        ImageDestroy($this->textImg);
    }

    public function createTextImage()
    {
        $textImg = imagecreatetruecolor($this->textWidth, $this->textHeight);
        $im_width = imagesx($textImg);
        $im_height = imagesy($textImg);
        //create a colored border
        imagefilledrectangle($textImg, 0, 0, $this->textWidth, $this->textHeight, $this->borderColor);
        //create a colored content area
        imagefilledrectangle($textImg, 4, 4, $this->textWidth - 4, $this->textHeight - 4, $this->bgColor);

        $font_x = $this->widthPadding / 2;
        //the larger this value,the closer to the bottom
        $font_y = $im_height - ($this->heightPadding / 2);

        //print "$this->heightPadding text width: $this->bboxWidth text height: $this->bbboxHeight image w: $im_width image h:$im_height X POINT IS $font_x and Y point is $font_y \n";

        Imagettftext($textImg, $this->fontSize, 0, $font_x, $font_y, $this->textColor, $this->fontFile, $this->text);
        $this->textImg = $textImg;
        return $this;
    }

    public static function getBaseFilename($filepath)
    {
        $array = explode('.', $filepath);
        array_pop($array);
        return implode('.', $array);
    }

    public function bboxSize()
    {
        $textbox = \imagettfbbox($this->fontSize, $this->bboxAngle, $this->fontFile, $this->text);
        //0 == lower left corner X point, 2== lower right corner X point
        // 7 == upper left corner, Y position, 1 == lower left corner, Y position

        $min_x = min(array($textbox[0], $textbox[2], $textbox[4], $textbox[6]));
        $max_x = max(array($textbox[0], $textbox[2], $textbox[4], $textbox[6]));
        $min_y = min(array($textbox[1], $textbox[3], $textbox[5], $textbox[7]));
        $max_y = max(array($textbox[1], $textbox[3], $textbox[5], $textbox[7]));

        $width = $max_x - $min_x;
        $height = $max_y - $min_y;
        return array($width, $height);
    }

    public static function getPicTime($file)
    {
        $pic_meta = \exif_read_data($file);
        switch ($pic_meta) {
            case (isset($pic_meta['DateTimeOriginal'])):
                $text = $pic_meta['DateTimeOriginal'];
                break;
            case (isset($pic_meta['DateTime'])):
                $text = $pic_meta['DateTime'];
                break;
            default:
                $text = date('Y:m:d H:i:s', $pic_meta['FileDateTime']);
        }
        $text = preg_split('/\s+/', $text);
        list($date, $time) = $text;
        return $date;
    }

}
