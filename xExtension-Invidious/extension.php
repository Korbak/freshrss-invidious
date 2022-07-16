<?php

/**
 * Class InvidiousExtension
 *
 * Latest version can be found at https://github.com/Korbak/freshrss-invidious
 *
 * @author Korbak forking Kevin Papst
 */
class InvidiousExtension extends Minz_Extension
{
    /**
     * Video player width
     * @var int
     */
    protected $width = 560;
    /**
     * Video player height
     * @var int
     */
    protected $height = 315;
    /**
     * Whether we display the original feed content
     * @var bool
     */
    protected $showContent = false;
    /**
     * Invidious instance to use
     * @var string
     */
    protected $instance = 'invidio.us';

    /**
     * Initialize this extension
     */
    public function init()
    {
        $this->registerHook('entry_before_display', array($this, 'embedInvidiousVideo'));
        $this->registerTranslates();
    }

    /**
     * Initializes the extension configuration, if the user context is available.
     * Do not call that in your extensions init() method, it can't be used there.
     */
    public function loadConfigValues()
    {
        if (!class_exists('FreshRSS_Context', false) || null === FreshRSS_Context::$user_conf) {
            return;
        }

        if (FreshRSS_Context::$user_conf->in_player_width != '') {
            $this->width = FreshRSS_Context::$user_conf->in_player_width;
        }
        if (FreshRSS_Context::$user_conf->in_player_height != '') {
            $this->height = FreshRSS_Context::$user_conf->in_player_height;
        }
        if (FreshRSS_Context::$user_conf->in_show_content != '') {
            $this->showContent = (bool)FreshRSS_Context::$user_conf->in_show_content;
        }
        if (FreshRSS_Context::$user_conf->in_player_instance != '') {
            $this->instance = FreshRSS_Context::$user_conf->in_player_instance;
        }
    }

    /**
     * Returns the width in pixel for the invidious player iframe.
     * You have to call loadConfigValues() before this one, otherwise you get default values.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Returns the height in pixel for the invidious player iframe.
     * You have to call loadConfigValues() before this one, otherwise you get default values.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Returns whether this extensions displays the content of the invidious feed.
     * You have to call loadConfigValues() before this one, otherwise you get default values.
     *
     * @return bool
     */
    public function isShowContent()
    {
        return $this->showContent;
    }
    
    /**
     * Returns which invidious instance is used by the extension.
     * You have to call loadConfigValues() before this one, otherwise you get default values.
     *
     * @return string
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * Inserts the Invidious video iframe into the content of an entry, if the entries link points to a Invidious watch URL.
     *
     * @param FreshRSS_Entry $entry
     * @return mixed
     */
    public function embedInvidiousVideo($entry)
    {
        $link = $entry->link();

        $html = $this->getIFrameForLink($link);
        if ($html === null) {
            return $entry;
        }

        if ($this->showContent) {
            $html .= $entry->content();
        }

        $entry->_content($html);
        $in_url = $this->youtubeToInvidious($entry);
        $entry->_link($in_url);
        return $entry;
    }

    /**
     * Replaces all YouTube href links for Invidious links in RSS feeds
     *
     * @param FreshRSS_Feed $feed
     * @return $in_url 
     */
    public function youtubeToInvidious($entry)
    {
        $yt_url = $entry->link();
        if (stripos($yt_url, 'www.youtube.com') != false) {
            $in_url = str_replace('//www.youtube.com/', '//' . $this->instance . '/', $yt_url);
        } else if (stripos($yt_url, 'invidio.us') != false) {
            $in_url = str_replace('//invidio.us/', '//' . $this->instance . '/', $yt_url);
        }
        $in_url = str_replace('http://', 'https://', $in_url);
        return $in_url;
    }

    /**
     * Returns an HTML <iframe> for a given Youtube watch URL (www.youtube.com/watch?v=)
     *
     * @param string $link
     * @return string
     */
    public function getIFrameForLink($link)
    {
        $this->loadConfigValues();

        if (stripos($link, 'www.youtube.com/watch?v=') != false) {
            $url = str_replace('//www.youtube.com/watch?v=', '//' . $this->instance . '/embed/', $link);
        } else if (stripos($link, 'invidio.us/watch?v=') != false) {
            $url = str_replace('//invidio.us/watch?v=', '//' . $this->instance . '/embed/', $link);
        } else {
            return null;
        }
        $url = str_replace('http://', 'https://', $url);
        $html = $this->getIFrameHtml($url);

        return $html;
    }

    /**
     * Returns an HTML <iframe> for a given URL for the configured width and height.
     *
     * @param string $url
     * @return string
     */
    public function getIFrameHtml($url)
    {
        return '<iframe 
                style="height: ' . $this->height . 'px; width: ' . $this->width . 'px;" 
                width="' . $this->width . '" 
                height="' . $this->height . '" 
                src="' . $url . '" 
                frameborder="0" 
                allowfullscreen></iframe>';
    }

    /**
     * Saves the user settings for this extension.
     */
    public function handleConfigureAction()
    {
        $this->registerTranslates();
        $this->loadConfigValues();

        if (Minz_Request::isPost()) {
            FreshRSS_Context::$user_conf->in_player_height = (int)Minz_Request::param('in_height', '');
            FreshRSS_Context::$user_conf->in_player_width = (int)Minz_Request::param('in_width', '');
            FreshRSS_Context::$user_conf->in_show_content = (int)Minz_Request::param('in_show_content', 0);
            FreshRSS_Context::$user_conf->in_player_instance = (string)Minz_Request::param('in_instance', '');
            FreshRSS_Context::$user_conf->save();
        }
    }
}
