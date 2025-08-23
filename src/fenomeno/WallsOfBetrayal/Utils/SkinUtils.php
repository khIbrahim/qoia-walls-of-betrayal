<?php
namespace fenomeno\WallsOfBetrayal\Utils;

use GdImage;
use JsonException;
use pocketmine\entity\Skin;
use pocketmine\player\Player;
use pocketmine\utils\BinaryStream;

class SkinUtils {

    public static function layerSkin(Skin $skin, string $cosmeticPng, string $cosmeticGeo, string $cosmeticGeoFile) : Skin{
        if($cosmeticPng === "") return $skin;
        $layer2 = self::skinToPNG($skin->getSkinData());
        $layer1 = imagecreatefrompng($cosmeticPng);

        imagepalettetotruecolor($layer2);
        imagepalettetotruecolor($layer1);
        imagesavealpha($layer1, true);
        imagealphablending($layer2, true);
        imagesavealpha($layer2, true);
        imagecopy($layer2, $layer1, 0, 0, 0, 0, 64, 64);

        return new Skin("Custom", self::getImageData($layer2), "", $cosmeticGeo, file_get_contents($cosmeticGeoFile));
    }

    public static function skinToPNG(string $skinData) : bool|GdImage
    {
        $img = imagecreatetruecolor(64, 64);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $stream = new BinaryStream($skinData);

        for($y = 0; $y < 64; ++$y){
            for($x = 0; $x < 64; ++$x){
                $r = $stream->getByte();
                $g = $stream->getByte();
                $b = $stream->getByte();
                $a = 127 - (int) floor($stream->getByte() / 2);

                $colour = imagecolorallocatealpha($img, $r, $g, $b, $a);
                imagesetpixel($img, $x, $y, $colour);
            }
        }

        return $img;
    }

    public static function getImageData($image) : string{
        $skinbytes = "";
        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $colorat = @imagecolorat($image, $x, $y);
                $a = ((~($colorat >> 24)) << 1) & 0xff;
                $r = ($colorat >> 16) & 0xff;
                $g = ($colorat >> 8) & 0xff;
                $b = $colorat & 0xff;
                $skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        imagedestroy($image);

        return $skinbytes;
    }

    /** @throws JsonException */
    public static function resetCapeAndGeo(Player $player) : void{
        $skin = $player->getSkin();
        $defaultPlayerSkin = new Skin($skin->getSkinId(), $skin->getSkinData());
        $player->setSkin($defaultPlayerSkin);
        $player->sendSkin();
    }

    /**
     * @param string $url
     * @param Player $fallback
     * @return Skin
     */
    public static function fromUrlOrPlayer(string $url, Player $fallback): Skin {
        try{
            $bytes = self::fetchPngBytes($url);
            $skinData = self::pngToSkinBytes($bytes, 64, 64);
            return new Skin($fallback->getSkin()->getSkinId(), $skinData, $fallback->getSkin()->getCapeData(), $fallback->getSkin()->getGeometryName(), $fallback->getSkin()->getGeometryData());
        }catch(\Throwable){
            return $fallback->getSkin();
        }
    }

    private static function fetchPngBytes(string $url): string {
        $ctx = stream_context_create(["http" => ["timeout" => 5]]);
        $data = @file_get_contents($url, false, $ctx);
        if($data === false) throw new \RuntimeException("Failed to fetch skin url");
        return $data;
    }

    private static function pngToSkinBytes(string $pngBytes, int $expectedW, int $expectedH): string {
        $im = @imagecreatefromstring($pngBytes);
        if(!$im) throw new \RuntimeException("Invalid PNG image");
        $w = imagesx($im);
        $h = imagesy($im);

        if($w !== $expectedW || $h !== $expectedH){
            $res = imagecreatetruecolor($expectedW, $expectedH);
            imagesavealpha($res, true);
            imagealphablending($res, false);
            $transparent = imagecolorallocatealpha($res, 0, 0, 0, 127);
            imagefilledrectangle($res, 0, 0, $expectedW, $expectedH, $transparent);
            imagecopyresampled($res, $im, 0, 0, 0, 0, $expectedW, $expectedH, $w, $h);
            imagedestroy($im);
            $im = $res;
        }

        $skin = "";
        for($y=0; $y<$expectedH; $y++){
            for($x=0; $x<$expectedW; $x++){
                $rgba = imagecolorat($im, $x, $y);
                $a = ((~($rgba >> 24)) & 0xFF);
                $r = (($rgba >> 16) & 0xFF);
                $g = (($rgba >> 8) & 0xFF);
                $b = ($rgba & 0xFF);
                $skin .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        imagedestroy($im);
        return $skin;
    }

}