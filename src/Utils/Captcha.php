<?php

/**
 * Captcha challenge
 */

namespace Clickio\Utils;

use Clickio\Db\ModelFactory;
use Clickio\Db\Models\Captcha as ModelCaptcha;

/**
 * Generate and validate captcha
 *
 * @package Utils
 */
class Captcha
{

    /**
     * Path to font
     *
     * @var string
     */
    protected $font = CLICKIO_PLUGIN_DIR.'/src/static/open-sans/OpenSans-Regular.ttf';

    /**
     * Sing letter size
     *
     * @var int
     */
    protected $letter_size = 18;

    /**
     * Letter padding
     *
     * @var int
     */
    protected $letter_padding = 16;

    /**
     * Generated text
     *
     * @var array
     */
    protected $letters = [];

    /**
     * Generate captcha and encode to base64
     *
     * @return array
     */
    public static function generateBase64(): array
    {
        $obj = new static();
        $img = $obj->generate();

        if (empty($img)) {
            return ["letters" => '', "img" => ''];
        }

        $stream = fopen('php://memory', 'r+');
        imagepng($img, $stream);
        rewind($stream);
        $img_bin = stream_get_contents($stream);
        fclose($stream);


        $encoded = base64_encode($img_bin);
        imagedestroy($img);

        $letters = implode('', $obj->getLetters());
        return ["letters" => strtolower($letters), "img" => $encoded];
    }

    /**
     * Verify captcha
     *
     * @param string $sign captcha_hash
     * @param string $input user input
     *
     * @return bool
     */
    public static function verify(string $sign, string $input): bool
    {
        $model = ModelFactory::create(ModelCaptcha::class);

        $row = $model->selectRow('captcha_hash', $sign);
        if (empty($row)) {
            return false;
        }
        $row_value = $row['captcha_val'];

        return strtolower($row_value) == strtolower($input);
    }

    /**
     * Generate captcha
     *
     * @return resource
     */
    public function generate()
    {
        if (!static::gdLibInstalled()) {
            return null;
        }

        $length = rand(5, 7);
        $this->letters = $this->getCaptchaLetters($length);
        $width = $length * ($this->letter_size + $this->letter_padding);
        $height = $this->letter_size + $this->letter_padding;
        $captcha_img = imagecreate($width, $height);
        $background = imagecolorallocate($captcha_img, 255, 255, 255);
        imagefill($captcha_img, 0, 0, $background);
        $dst_x = 0;
        $dst_y = 0;
        $letter_size = ($this->letter_size + $this->letter_padding);

        // header("Content-Type: image/png");
        foreach ($this->letters as $letter) {
            $letter_img = $this->drawSingleLetter($letter);
            imagecopy($captcha_img, $letter_img, $dst_x, $dst_y, 0, 0, $letter_size, $letter_size);

            $dst_x += $letter_size;
            imagedestroy($letter_img);
        }
        // imagejpeg($captcha_img);
        // imagedestroy($captcha_img);

        return $captcha_img;
    }

    /**
     * Draw single charecter
     *
     * @param string $letter letter
     *
     * @return resource
     */
    protected function drawSingleLetter(string $letter)
    {
        $angle = rand(-25, 25);
        $letter_width = ($this->letter_size + $this->letter_padding);
        $letter_img = imagecreate($letter_width, $letter_width);
        $background = imagecolorallocatealpha($letter_img, 255, 255, 255, 127);
        imagefill($letter_img, 0, 0, $background);
        $text_color = imagecolorallocate($letter_img, rand(0, 255), rand(0, 255), rand(0, 255));
        $location_x = $this->letter_padding / 2;
        $location_y = $this->letter_size + $this->letter_padding / 2;
        imagettftext(
            $letter_img,
            $this->letter_size,
            $angle,
            $location_x,
            $location_y,
            $text_color,
            $this->font,
            $letter
        );
        // $filters = [
        //     0,
        //     \IMG_FILTER_EMBOSS,
        //     \IMG_FILTER_GAUSSIAN_BLUR,
        //     \IMG_FILTER_MEAN_REMOVAL,
        //     \IMG_FILTER_EDGEDETECT,
        //     \IMG_FILTER_CONTRAST
        // ];
        // $filter = $filters[array_rand($filters, 1)];
        // switch($filter){
        //     case 0:
        //         break;
        //     case \IMG_FILTER_EMBOSS:
        //         imagefilter($letter_img, \IMG_FILTER_EMBOSS);
        //         break;
        //     case \IMG_FILTER_GAUSSIAN_BLUR:
        //         imagefilter($letter_img, \IMG_FILTER_GAUSSIAN_BLUR);
        //         break;
        //     case \IMG_FILTER_MEAN_REMOVAL:
        //         imagefilter($letter_img, \IMG_FILTER_MEAN_REMOVAL);
        //         break;
        //     case \IMG_FILTER_EDGEDETECT:
        //         imagefilter($letter_img, \IMG_FILTER_EDGEDETECT);
        //         break;
        //     case \IMG_FILTER_CONTRAST:
        //         imagefilter($letter_img, \IMG_FILTER_CONTRAST, rand(5, 20));
        //         break;
        // }
        // imagecolorallocate($letter_img, 255, 255, 255);
        return $letter_img;
    }

    /**
     * Generate captcha text
     *
     * @param int $length letters count
     *
     * @return array
     */
    protected function getCaptchaLetters(int $length): array
    {
        $letters = array_merge(range(0, 9), range('A', 'Z'));
        $l_range = (count($letters) - 1);
        $str = [];
        foreach (range(0, ($length - 1)) as $iter) {
            $letter_idx = rand(0, $l_range);
            $str[] = $letters[$letter_idx];
        }
        return $str;
    }

    /**
     * Letters getter
     *
     * @return array
     */
    public function getLetters(): array
    {
        return $this->letters;
    }

    /**
     * Check gd extension
     *
     * @return bool
     */
    public static function gdLibInstalled(): bool
    {
        return extension_loaded('gd');
    }
}
