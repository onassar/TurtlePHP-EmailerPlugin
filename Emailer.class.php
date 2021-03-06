<?php

    // namespace
    namespace Plugin;

    // dependency check
    if (class_exists('\\Plugin\\Config') === false) {
        throw new \Exception(
            '*Config* class required. Please see ' .
            'https://github.com/onassar/TurtlePHP-ConfigPlugin'
        );
    }

    // dependency check
    if (class_exists('\\Postmark\\Mail') === false) {
        throw new \Exception(
            '*Mail* class required. Please see ' .
            'https://github.com/Znarkus/postmark-php'
        );
    }

    // dependency check
    if (class_exists('\\PostmarkEmail') === false) {
        throw new \Exception(
            '*PostmarkEmail* class required. Please see ' .
            'https://github.com/onassar/PHP-Email'
        );
    }

    // dependency check
    if (class_exists('\\Mailgun\\Mailgun') === false) {
        throw new \Exception(
            '*Mailgun\\Mailgun* class required. Please see ' .
            'https://github.com/mailgun/mailgun-php'
        );
    }

    // dependency check
    if (class_exists('\\MailgunEmail') === false) {
        throw new \Exception(
            '*MailgunEmail* class required. Please see ' .
            'https://github.com/onassar/PHP-Email'
        );
    }

    /**
     * Emailer
     * 
     * Emailer plugin for TurtlePHP
     * 
     * @author  Oliver Nassar <onassar@gmail.com>
     * @abstract
     */
    abstract class Emailer
    {
        /**
         * _configPath
         *
         * @access  protected
         * @var     string
         * @static
         */
        protected static $_configPath = 'config.default.inc.php';

        /**
         * _initiated
         *
         * @access  protected
         * @var     bool
         * @static
         */
        protected static $_initiated = false;

        /**
         * _resources
         *
         * @access  protected
         * @var     array
         * @static
         */
        protected static $_resources = array(
            'mailgun' => array(),
            'postmark' => array()
        );

        /**
         * _sendThroughMailgun
         * 
         * @access  protected
         * @param   string $recipient (default: Emailer\LOGGING)
         * @param   string $subject (default: '(logging)')
         * @param   string $body (default: '(logging)')
         * @param   string $tag (default: 'logging')
         * @param   bool $sendAsHtml (default: true)
         * @param   bool|array $from (default: false)
         * @param   bool|array $attachments (default: false)
         * @param   bool|string $account (default: false)
         * @param   bool|string $signature (default: false)
         * @param   bool $track (default: true)
         * @return  string|false messageId if sent; false if exception or not
         *          sent at all
         */
        protected static function _sendThroughMailgun(
            $recipient = Emailer\LOGGING,
            $subject = '(logging)',
            $body = '(logging)',
            $tag = 'logging',
            $sendAsHtml = true,
            $from = false,
            $attachments = false,
            $account = false,
            $signature = false,
            $track = true
        ) {
            // Resource loading
            $account = ($account === false ? 'default' : $account);
            if (isset(self::$_resources['mailgun'][$account]) === false) {
                self::$_resources['mailgun'][$account] = new \MailgunEmail(
                    Emailer\getConfig('mailgun', 'accounts', $account, 'apiKey')
                );
            }

            // Send
            $response = self::$_resources['mailgun'][$account]->send(
                $recipient,
                $subject,
                $body,
                $tag,
                $sendAsHtml,
                $from,
                $attachments,
                $account,
                $signature,
                $track
            );

            // Failed
            if (
                is_object($response) === true
                && get_class($response) === 'Exception'
            ) {
                error_log('Could not send through mailgun:');
                error_log($response->getMessage());
                return false;
            }

            // Message Id response
            return $response;
        }

        /**
         * _sendThroughPostmark
         * 
         * @access  protected
         * @param   string|array $recipient (default: Emailer\LOGGING)
         * @param   string $subject (default: '(logging)')
         * @param   string $body (default: '(logging)')
         * @param   string $tag (default: 'logging')
         * @param   bool $sendAsHtml (default: true)
         * @param   bool|array $from (default: false)
         * @param   bool|array $attachments (default: false)
         * @param   bool|string $account (default: false)
         * @param   bool|string $signature (default: false)
         * @param   bool $track (default: true)
         * @return  string|false messageId if sent; false if exception or not
         *          sent at all
         */
        protected static function _sendThroughPostmark(
            $recipient = Emailer\LOGGING,
            $subject = '(logging)',
            $body = '(logging)',
            $tag = 'logging',
            $sendAsHtml = true,
            $from = false,
            $attachments = false,
            $account = false,
            $signature = false,
            $track = true
        ) {
            // Resource loading
            $account = ($account === false ? 'default' : $account);
            if (isset(self::$_resources['postmark'][$account]) === false) {
                self::$_resources['postmark'][$account] = new \PostmarkEmail(
                    Emailer\getConfig('postmark', 'accounts', $account, 'key')
                );
            }

            // Send
            $response = self::$_resources['postmark'][$account]->send(
                $recipient,
                $subject,
                $body,
                $tag,
                $sendAsHtml,
                $from,
                $attachments,
                $account,
                $signature,
                $track
            );

            // Failed
            if (
                is_object($response) === true
                && get_class($response) === 'Exception'
            ) {
                error_log('Could not send through postmark:');
                error_log($response->getMessage());
                return false;
            }

            // Message Id response
            return $response;
        }

        /**
         * init
         * 
         * @access  public
         * @static
         * @return  void
         */
        public static function init()
        {
            if (self::$_initiated === false) {
                self::$_initiated = true;
                require_once self::$_configPath;
                DEFINE(
                    __NAMESPACE__ . '\\Emailer\\LOGGING',
                    Emailer\getConfig('default')
                );
            }
        }

        /**
         * _isWhitelistEmail
         * 
         * Performs straight comparison as well as regex match to see whether
         * email is in the plugin's whitelist.
         * 
         * @access  protected
         * @param   string|array $email
         * @return  bool
         */
        protected static function _isWhitelistEmail($email)
        {
            // Incase an array of emails are passed in
            if (is_array($email) === true) {
                foreach ($email as $specific) {
                    if (self::_isWhitelistEmail($specific) === false) {
                        return false;
                    }
                }
                return true;
            } else {

                // Standard
                $whitelist = Emailer\getConfig('whitelist');
                if (in_array($email, $whitelist) === true) {
                    return true;
                }

                // Regex (prevent errors)
                set_error_handler(function() {});
                foreach ($whitelist as $possible) {
                    if (@preg_match($possible, $email) === 1) {
                        restore_error_handler();
                        return true;
                    }
                }
                restore_error_handler();

                // Fails
                return false;
            }
        }

        /**
         * send
         * 
         * @access  public
         * @return  string|false Id of the message that was sent (from the
         *          sending service), or false if it couldn't be sent
         */
        public static function send()
        {
            $args = func_get_args();
            if (
                Emailer\getConfig('send') === true
                || isset($args[0]) === false// no args sent = logging email
                || self::_isWhitelistEmail($args[0])
            ) {
                if (Emailer\getConfig('sender') === 'mailgun') {
                    return call_user_func_array(
                        array('self', '_sendThroughMailgun'),
                        $args
                    );
                } elseif (Emailer\getConfig('sender') === 'postmark') {
                    return call_user_func_array(
                        array('self', '_sendThroughPostmark'),
                        $args
                    );
                }
            }
            return false;
        }

        /**
         * setConfigPath
         * 
         * @access  public
         * @param   string $path
         * @return  void
         */
        public static function setConfigPath($path)
        {
            self::$_configPath = $path;
        }
    }

    // Config
    $info = pathinfo(__DIR__);
    $parent = ($info['dirname']) . '/' . ($info['basename']);
    $configPath = ($parent) . '/config.inc.php';
    if (is_file($configPath) === true) {
        Emailer::setConfigPath($configPath);
    }

    // Load functions
    require_once 'local.inc.php';
    require_once 'global.inc.php';
