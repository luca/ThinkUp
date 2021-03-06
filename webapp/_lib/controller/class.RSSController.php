<?php
/**
 * RSS Controller
 *
 * Launch the crawler, if the last updated date is older than X minutes, then return a valid RSS feed.
 * This will allow users to crawl their ThinkUp instances by subscribing to their ThinkUp RSS feed in any RSS reader.
 *
 * @author Guillaume Boudreau <gboudreau@pommepause.com>
 */
class RSSController extends ThinkUpAuthAPIController {

    /**
     * Launch the crawler, if the latest crawler_last_run date is older than X minutes, then return a valid RSS feed.
     * @return string rendered view markup
     */
    public function authControl() {
        Utils::defineConstants();
        $this->setContentType('application/rss+xml; charset=UTF-8');
        $this->setViewTemplate('rss.tpl');
        
        $config = Config::getInstance();
        $rss_crawler_refresh_rate = $config->getValue('rss_crawler_refresh_rate');
        if (empty($rss_crawler_refresh_rate)) {
            $rss_crawler_refresh_rate = 20; // minutes
        }

        $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $base_url = "$protocol://".$_SERVER['HTTP_HOST'].THINKUP_BASE_URL;
        
        $crawler_launched = false;
        $instance_dao = DAOFactory::getDAO('InstanceDAO');
        $freshest_instance = $instance_dao->getInstanceFreshestOne();
        $crawler_last_run = strtotime($freshest_instance->crawler_last_run);
        if ($crawler_last_run < time() - $rss_crawler_refresh_rate*60) {
            $crawler_run_url = $base_url.'run.php?'.ThinkUpAuthAPIController::getAuthParameters(
            $this->getLoggedInUser());
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $crawler_run_url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // seconds
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // seconds
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            $body = substr($result, strpos($result, "\r\n\r\n")+4); 
            if (strpos($result, 'Content-Type: application/json') && function_exists('json_decode')) {
                $json = json_decode($body);
                if (isset($json->error)) {
                    $crawler_launched = false;
                } else if (isset($json->result) && $json->result == 'success') {
                    $crawler_launched = true;
                }
            } else if (strpos($body, 'Error starting crawler') !== FALSE) {
                $crawler_launched = false;
            } else {
                $crawler_launched = true;
            }
        }
        
        $items = array();
        if ($crawler_launched) {
            $title = 'ThinkUp crawl started on ' . date('Y-m-d H:i:s');
            $link = $base_url.'rss.php?d='.urlencode(date('Y-m-d H:i:s'));
            $description = "Last ThinkUp crawl ended on $freshest_instance->crawler_last_run<br />A new crawl ".
            "was started just now, since it's been more than $rss_crawler_refresh_rate minutes since the last run.";
            $items[] = self::createRSSItem($title, $link, $description);
        }
        $items = array_merge($items, $this->getAdditionalItems($base_url));
        $this->addToView('items', $items);
        $this->addToView('logged_in_user', htmlspecialchars($this->getLoggedInUser()));
        $this->addToView('rss_crawler_refresh_rate', htmlspecialchars($rss_crawler_refresh_rate));
        
        return $this->generateView();
    }

    /**
     * Add extra RSS items to the feed, if necessary.
     * @param string $base_url The base URL to use in items' links.
     * @return array RSS items to add into the RSS feed
     */
    private function getAdditionalItems($base_url) {
        $items = array();
        // Make sure the crawler log, if specified, is writable; add an item if not
        $config = Config::getInstance();
        $log_location = $config->getValue('log_location');
        if ($log_location !== FALSE && !is_writable($log_location) && 
        (file_exists($log_location) || !is_writable(dirname($log_location)))) {
            $title = 'Error: crawler log is not writable';
            $link = $base_url.'rss.php?e=1&d='.urlencode(date('Y-m-d H:i:s'));
            $description = "The crawler log specified as <em>log_location</em> in config.inc.php<br/>".
            "&nbsp;&nbsp;&nbsp;&nbsp;<strong>$log_location</strong><br />".
            "is not writable by the user running your web server.<br />".
            "That means that all crawls launched by this RSS feed will not log anything in that file.<br />".
            "You should chown or chmod that file to insure it's being written to during those crawls.";
            $items[] = self::createRSSItem($title, $link, $description);
        }
        return $items;
    }
    
    /**
     * Build an RSS item from a title, link and description.
     * @param string $title
     * @param string $link
     * @param string $description
     * @return array RSS item
     */
    private static function createRSSItem($title, $link, $description) {
        return array(
            'title'       => htmlspecialchars($title),
            'link'        => htmlspecialchars($link),
            'description' => htmlspecialchars($description),
            'pubDate'     => htmlspecialchars(date('D, d M Y H:i:s T')),
            'guid'        => htmlspecialchars($link)
        );
    }
}
