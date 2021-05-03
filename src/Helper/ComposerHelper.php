<?php


namespace MarekNocon\ComposerCheckout\Helper;


class ComposerHelper
{
    public static function getGitHubToken(): ?string
    {
        $output = [];
        $result_code = 0;
        exec('composer config github-oauth.github.com 2> /dev/null', $output, $result_code);

        if ($result_code === 0) {
            return $output[0];
        }

        exec('composer config github-oauth.github.com --global 2> /dev/null', $output, $result_code);

        return $result_code === 0 ? $output[0] : null;
    }
}