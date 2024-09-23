<?php

namespace Inavii\Instagram\Media;

class DownloadRemoteMedia extends Media
{
    /**
     *
     * @param string $imageUrl
     * @param $postID
     *
     * @return string
     * @throws \RuntimeException
     */
    public function save(string $imageUrl, $postID): string
    {
        if (empty($imageUrl)) {
            return $this->getImageUrl($postID);
        }

        if (file_exists($this->getImageDir($postID) . '-m' . self::IMAGE_TYPE)) {
            return $this->getImageDir($postID);
        }

        $filepath = $this->getImageDir($postID) . self::IMAGE_TYPE;

        $curl = curl_init($imageUrl);

        if (!$curl) {
            throw new \RuntimeException(
                'was unable to initialize curl. Please check if the curl extension is enabled.'
            );
        }

        $file = @fopen($filepath, 'wb');

        if (!$file) {
            throw new \RuntimeException(
                'was unable to create the file: ' . $filepath
            );
        }
        curl_setopt($curl, CURLOPT_FILE, $file);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }

        $success = curl_exec($curl);

        if (!$success) {
            throw new \RuntimeException(
                'failed to get the media data from Instagram: ' . curl_error($curl)
            );
        }

        curl_close($curl);
        fclose($file);

        return $this->getImageUrl($postID);
    }
}
