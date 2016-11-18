<?php

namespace Bru\Google\Seo\Tools;

class Tools_Google_Seo
{
    /**
     * Returns the domain of the context send in argument or of the current context without any subdomain
     *
     * @return String
     */
    public static function getDomain($context = '') {
        if (empty($context)) $context = \Nos\Nos::main_controller()->getContext();
        $url = \Nos\Tools_Url::context($context);
        $sHttpProtocol = 'http://';
        if (\Str::starts_with($url, $sHttpProtocol)) $url = substr($url, strlen($sHttpProtocol));
        preg_match('#^[\w.-]*\.(\w+\.[a-z]{2,6})[\w/._-]*$#',$url,$match);
        $domain = isset($match[1]) ? $match[1] : '';
        return $domain;
    }

    /**
     * Returns the script tag wich contain the google analytics javascript
     *
     * @return string
     */
    public static function getAnalyticsTrackingScript() {

        $config = Controller_Admin_Config::getOptions();
        $current_context = \Nos\Nos::main_controller()->getPage()->page_context;
        $config = \Arr::get($config, $current_context);
        if (empty($config)) {
            return '';
        }

        $full_script = '';
        if ($config['full_script'] != '') {
            if (\Str::starts_with($config['full_script'], '<script')) {
                $full_script = $config['full_script'];
            } else {
                $full_script =  '<script type="text/javascript">'.$config['full_script'].'</script>';
            }
        } else {
            $tag = \Arr::get($config, 'google_analytics_tag');
            if (empty($tag)) return '';

            if (\Arr::get($config, 'use_universal_analytics')) {
                $view = 'bru_google_seo_tools::js_tag_universal_analitycs';
                $datas = array(
                    'tag' => $tag,
                    'domain' => self::getDomain($current_context),
                );
            } else {
                $view = 'bru_google_seo_tools::js_tag';
                $datas = array(
                    'tag' => $tag,
                );
            }
            $full_script = \View::forge($view, $datas);
        }

        if ($full_script === '') {
            return '';
        } else {
            self::_doNotTrack($full_script);
            return (string)$full_script;
        }
    }

    /**
     * @return string
     */
    public static function getTrackingCookieName() {
        //Search the context's config. If there is not : do nothing
        $config = Controller_Admin_Config::getOptions();
        $current_context = \Nos\Nos::main_controller()->getPage()->page_context;
        $config = \Arr::get($config, $current_context);
        if (empty($config)) {
            return '';
        }

        //Check if a tracking cookie name is set
        $cookie_name = \Arr::get($config, 'tracking_cookie_name', '');
        return $cookie_name;
    }

    /**
     * Return the script embed in html comments if we are in a case that user do not want the page to appears in Google Analytics
     * @return string
     */
    protected  static function _doNotTrack(&$full_script) {
        //Search the context's config. If there is not : do nothing
        $config = Controller_Admin_Config::getOptions();
        $current_context = \Nos\Nos::main_controller()->getPage()->page_context;
        $config = \Arr::get($config, $current_context);

        //No tracking if it's a preview
        if (\Nos\Nos::main_controller()->isPreview()) {
            $full_script = '<!--'.$full_script.'-->';
            return $full_script;
        }

        //No script if we do not want logged in users to be tracked
        if (\Arr::get($config, 'do_not_track_logged_user', 0) && \Nos\Auth::check()) {
            $full_script = '<!--'.$full_script.'-->';
            return $full_script;
        }

        //Pas de tracking en local ou en préprod
        if (\Fuel::$env !== \Fuel::PRODUCTION && !\Arr::get($config, 'track_dev', false)) {
            $full_script = '<!--'.$full_script.'-->';
            return $full_script;
        }

    }

    /**
     * Check if the tracking script must be add after the cache
     * @return bool
     */
    public static function trackingAfterCache() {
        $config = Controller_Admin_Config::getOptions();
        $current_context = \Nos\Nos::main_controller()->getPage()->page_context;
        $config = \Arr::get($config, $current_context);
        if (empty($config)) {
            return false;
        }

        //Check if a tracking cookie name is set
        $cookie_name = \Bru\Google\Seo\Tools\Tools_Google_Seo::getTrackingCookieName();
        //If $cookie_name is set, the tracking script is add after the cache, because we need to check the user's cookie
        if (!empty($cookie_name)) return true;

        //If we don't want users to be tracked, we must out of the cache if the current user is logged in or not
        if (\Arr::get($config, 'do_not_track_logged_user', 0)) {
            return true;
        }

        return false;
    }
}
