<?php
class ImageException extends BensonPhpException {
    
}
class ImageNotFoundException extends ImageException {
    
}
class ImageNotSupportedException extends ImageException {
    
}

/**
 * Basic image manipulation.
 */
class Image {
    private $imageType;
    private $filename;
    private $image;
    private $width;
    private $height;
    
    /**
     * Create a new image with a given filename and get the image type.
     * @param type $filename The filename
     */
    public function __construct($filename) {
        if (is_file($filename)) {
            $this->imageType = exif_imagetype($filename);
            $this->filename = $filename;
            $this->image = $this->loadImageResource();
            $this->width = imagesx($this->image);
            $this->height = imagesy($this->image);
        } else {
            throw new ImageNotFoundException("There is not a file at the location \"$filename\"");
        }
    }
    
    /**
     * Checks whether a file is an image.
     * @return True on success, false on failure.
     */
    public function isImage() {
        if ($this->imageType === false) {
            return false;
        }
        return true;
    }
    /**
     * Resize the image to the given size.
     * @param int $width The width of the new image.
     * @param int $height The height of the new image.
     * @return bool True on success, or false on failure.
     */
    public function resizeImage($width, $height) {
        $newImage = imagecreatetruecolor($width, $height);
        $result = imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
        if ($result === true) {
            $this->image = $newImage;
        }
        return $result;
    }
    /**
     * Resize the image to the given height and maintain the aspect ratio for the width.
     * @param int $height The height of the new image.
     * @return bool True on success, or false on failure.
     */
    public function resizeImageFixedHeight($height) {
        // Find the aspect ratio times the height by the ratio
        $ratio = $this->width / $this->height;
        $width = $height * $ratio;
        return $this->resizeImage($width, $height);
    }
    /**
     * Resize the image to the given width and maintain the aspect ratio for the height.
     * @param int $width The width of the new image.
     * @return bool True on success, or false on failure.
     */
    public function resizeImageFixedWidth($width) {
        // Find the aspect ratio times the width by the ratio
        $ratio = $this->height / $this->width;
        $height = $width * $ratio;
        return $this->resizeImage($width, $height);
    }
    
    /**
     * Writes the image resource to a given filename, detecting the image type automatically.
     * @param string $filename The filename to write the image to.
     * @return bool True on success or false on failure.
     */
    public function save($filename) {
        switch ($this->imageType) {
            case IMAGETYPE_JPEG :
                if (!Util::endsWith($filename, ".jpg")) { $filename .= ".jpg"; }
                return imagejpeg($this->image, $filename);
            case IMAGETYPE_BMP :
                if (!Util::endsWith($filename, ".bmp")) { $filename .= ".bmp"; }
                return imagewbmp($this->image, $filename . "");
            case IMAGETYPE_PNG :
                if (!Util::endsWith($filename, ".png")) { $filename .= ".png"; }
               return imagepng($this->image, $filename);
            case IMAGETYPE_GIF :
                if (!Util::endsWith($filename, ".gif")) { $filename .= ".gif"; }
               return imagegif($this->image, $filename);
            default :
                throw new ImageNotSupportedException("Image ".$this->filename." not supported");
        }
        
    }
    
    public function readImage($cache = false) {
        header("Content-Type: " . image_type_to_mime_type($this->imageType));
        readfile($this->filename);
    }
    
    /**
     * Checks whether an image extension has a header content-type string.
     * @param string $extension The image extension to check for.
     * @return boolean True on success, false on failure.
     */
    public function isSupported($extension) {
        return array_key_exists($extension, $this->_mimeTypes);
    }
    
    /**
     * Creates an image resource from the image filename for common file types
     * 
     * @returns mixed The image resource or false on error.
     */
    private function loadImageResource() {
        switch ($this->imageType) {
            case IMAGETYPE_JPEG :
                return imagecreatefromjpeg($this->filename);
            case IMAGETYPE_BMP :
                return imagecreatefromwbmp($this->filename);
            case IMAGETYPE_PNG :
                return imagecreatefrompng($this->filename);
            case IMAGETYPE_GIF :
                return imagecreatefromgif($this->filename);
            default :
                throw new ImageNotSupportedException("Image ".$this->filename." not supported");
        }
    }
}

