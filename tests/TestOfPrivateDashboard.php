<?php
require_once dirname(__FILE__).'/init.tests.php';
require_once THINKUP_ROOT_PATH.'webapp/_lib/extlib/simpletest/autorun.php';
require_once THINKUP_ROOT_PATH.'webapp/config.inc.php';

class TestOfPrivateDashboard extends ThinkUpWebTestCase {

    public function setUp() {
        parent::setUp();

        //Add owner
        $session = new Session();
        $cryptpass = $session->pwdcrypt("secretpassword");
        $q = "INSERT INTO tu_owners (id, email, pwd, is_activated, is_admin) VALUES (1, 'me@example.com', '".
        $cryptpass."', 1, 1)";
        $this->db->exec($q);

        //Add instance
        $q = "INSERT INTO tu_instances (id, network_user_id, network_username, is_public) VALUES (1, 1234,
        'thinkupapp', 1)";
        $this->db->exec($q);

        //Add instance_owner
        $q = "INSERT INTO tu_owner_instances (owner_id, instance_id) VALUES (1, 1)";
        $this->db->exec($q);

        //Insert test data into test table
        $q = "INSERT INTO tu_users (user_id, user_name, full_name, avatar) VALUES (12, 'jack', 'Jack Dorsey',
        'avatar.jpg');";
        $this->db->exec($q);

        $q = "INSERT INTO tu_users (user_id, user_name, full_name, avatar, last_updated, network) VALUES (13, 'ev',
        'Ev Williams', 'avatar.jpg', '1/1/2005', 'twitter');";
        $this->db->exec($q);

        $q = "INSERT INTO tu_users (user_id, user_name, full_name, avatar, is_protected) VALUES (16, 'private',
        'Private Poster', 'avatar.jpg', 1);";
        $this->db->exec($q);

        $q = "INSERT INTO tu_users (user_id, user_name, full_name, avatar, is_protected, follower_count) VALUES (17,
        'thinkupapp', 'ThinkUpers', 'avatar.jpg', 0, 10);";
        $this->db->exec($q);

        $q = "INSERT INTO tu_users (user_id, user_name, full_name, avatar, is_protected, follower_count) VALUES (18,
        'shutterbug', 'Shutter Bug', 'avatar.jpg', 0, 10);";
        $this->db->exec($q);

        $q = "INSERT INTO tu_users (user_id, user_name, full_name, avatar, is_protected, follower_count) VALUES (19,
        'linkbaiter', 'Link Baiter', 'avatar.jpg', 0, 10);";
        $this->db->exec($q);

        $q = "INSERT INTO tu_user_errors (user_id, error_code, error_text, error_issued_to_user_id) VALUES (15, 404,
        'User not found', 13);";
        $this->db->exec($q);

        $q = "INSERT INTO tu_follows (user_id, follower_id, last_seen) VALUES (13, 12, '1/1/2006');";
        $this->db->exec($q);

        $q = "INSERT INTO tu_follows (user_id, follower_id, last_seen) VALUES (13, 14, '1/1/2006');";
        $this->db->exec($q);

        $q = "INSERT INTO tu_follows (user_id, follower_id, last_seen) VALUES (13, 15, '1/1/2006');";
        $this->db->exec($q);

        $q = "INSERT INTO tu_follows (user_id, follower_id, last_seen) VALUES (13, 16, '1/1/2006');";
        $this->db->exec($q);

        $q = "INSERT INTO tu_follows (user_id, follower_id, last_seen) VALUES (16, 12, '1/1/2006');";
        $this->db->exec($q);

        $q = "INSERT INTO tu_instances (network_user_id, network_username, is_public) VALUES (13, 'ev', 1);";
        $this->db->exec($q);

        $q = "INSERT INTO tu_instances (network_user_id, network_username, is_public) VALUES (18, 'shutterbug', 1);";
        $this->db->exec($q);

        $q = "INSERT INTO tu_instances (network_user_id, network_username, is_public) VALUES (19, 'linkbaiter', 1);";
        $this->db->exec($q);

        $counter = 0;
        while ($counter < 40) {
            $reply_or_forward_count = $counter + 200;
            $pseudo_minute = str_pad($counter, 2, "0", STR_PAD_LEFT);
            $q = "INSERT INTO tu_posts (post_id, author_user_id, author_username, author_fullname, author_avatar,
            post_text, source, pub_date, reply_count_cache, retweet_count_cache) VALUES ($counter, 13, 'ev', 
            'Ev Williams', 'avatar.jpg', 'This is post $counter', 'web', '2006-01-01 00:$pseudo_minute:00', 
            $reply_or_forward_count, $reply_or_forward_count);";
            $this->db->exec($q);

            $counter++;
        }

        $counter = 0;
        while ($counter < 40) {
            $post_id = $counter + 40;
            $pseudo_minute = str_pad($counter, 2, "0", STR_PAD_LEFT);
            $q = "INSERT INTO tu_posts (post_id, author_user_id, author_username, author_fullname, author_avatar,
            post_text, source, pub_date, reply_count_cache, retweet_count_cache) VALUES ($post_id, 18, 'shutterbug', 
            'Shutter Bug', 'avatar.jpg', 'This is image post $counter', 'web', 
            '2006-01-02 00:$pseudo_minute:00', 0, 0);";
            $this->db->exec($q);

            $q = "INSERT INTO tu_links (url, expanded_url, title, clicks, post_id, is_image)
            VALUES ('http://example.com/".$counter."', 'http://example.com/".$counter.".jpg', '', 0, $post_id, 1);";
            $this->db->exec($q);

            $counter++;
        }

        $counter = 0;
        while ($counter < 40) {
            $post_id = $counter + 80;
            $pseudo_minute = str_pad(($counter), 2, "0", STR_PAD_LEFT);
            $q = "INSERT INTO tu_posts (post_id, author_user_id, author_username, author_fullname, author_avatar,
            post_text, source, pub_date, reply_count_cache, retweet_count_cache) VALUES ($post_id, 19, 'linkbaiter', 
            'Link Baiter', 'avatar.jpg', 'This is link post $counter', 'web', 
            '2006-03-01 00:$pseudo_minute:00', 0, 0);";
            $this->db->exec($q);

            $q = "INSERT INTO tu_links (url, expanded_url, title, clicks, post_id, is_image) VALUES
            ('http://example.com/".$counter."', 'http://example.com/".$counter.".html', 'Link $counter', 0, 
            $post_id, 0);";
            $this->db->exec($q);

            $counter++;
        }
        $counter = 0;
        while ($counter < 10) {
            $post_id = $counter + 120;
            $pseudo_minute = str_pad(($counter), 2, "0", STR_PAD_LEFT);
            $q = "INSERT INTO tu_posts (post_id, author_user_id, author_username, author_fullname, author_avatar,
            post_text, source, pub_date, reply_count_cache, retweet_count_cache) VALUES ($post_id, 1234, 
            'thinkupapp', 'thinkupapp', 'avatar.jpg', 'This is test post $counter', 'web', 
            '2006-03-01 00:$pseudo_minute:00', 0, 0);";
            $this->db->exec($q);
            $counter++;
        }


    }

    public function tearDown() {
        parent::tearDown();
    }

    public function testDashboardWithPosts() {
        $this->get($this->url.'/session/login.php');
        $this->setField('email', 'me@example.com');
        $this->setField('pwd', 'secretpassword');

        $this->click("Log In");
        $this->assertTitle('Private Dashboard | ThinkUp');
        $this->assertText('Logged in as: me@example.com');
        $this->assertText('thinkupapp');
    }

    public function testUserPage() {
        $this->get($this->url.'/session/login.php');
        $this->setField('email', 'me@example.com');
        $this->setField('pwd', 'secretpassword');

        $this->click("Log In");
        $this->assertTitle('Private Dashboard | ThinkUp');

        $this->get($this->url.'/user/index.php?i=thinkupapp&u=ev&n=twitter');
        $this->assertTitle('User Details: ev | ThinkUp');
        $this->assertText('Logged in as: me@example.com');
        $this->assertText('ev');

        $this->get($this->url.'/user/index.php?i=thinkupapp&u=usernotinsystem');
        $this->assertText('User and network not specified.');
    }

    public function testConfiguration() {
        $this->get($this->url.'/session/login.php');
        $this->setField('email', 'me@example.com');
        $this->setField('pwd', 'secretpassword');

        $this->click("Log In");
        $this->assertTitle('Private Dashboard | ThinkUp');

        $this->click("Configuration");
        $this->assertTitle('Configure Your Account | ThinkUp');
        $this->assertText('configure');
        $this->assertText('Expand URLs');

        $this->click("Twitter");
        $this->assertText('Configure the Twitter Plugin');
    }

    public function testExport() {
        $this->get($this->url.'/session/login.php');
        $this->setField('email', 'me@example.com');
        $this->setField('pwd', 'secretpassword');

        $this->click("Log In");
        $this->assertTitle('Private Dashboard | ThinkUp');
        $this->assertText('Export');

        $this->click("Export");
        $this->assertText('This is test post');
    }
}
