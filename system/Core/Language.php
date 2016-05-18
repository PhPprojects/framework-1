<?php
/**
 * Language - simple language handler.
 *
 * @author Bartek Kuśmierczuk - contact@qsma.pl - http://qsma.pl
 * @version 3.0
 */

namespace Core;

use Core\Config;
use Core\Error;
use Helpers\Inflector;
use Helpers\Session;
use Helpers\Cookie;

use MessageFormatter;


/**
 * A Language class to load the requested language file.
 */
class Language
{
    /**
     * Variable holds array with Language.
     *
     * @var array
     */
    private $messages = array();

    /**
     * The Domain instances.
     *
     * @var array
     */
    private static $instances = array();

    private $code   = 'en';
    private $info   = 'English';
    private $name   = 'English';
    private $locale = 'en-US';

    /**
     * This variable holds an array with the legacy messages.
     *
     * @var array
     */
    private $legacyMessages;

    /**
     * Language constructor.
     * @param string $domain
     * @param string $code
     */
    protected function __construct($domain, $code)
    {
        $languages = Config::get('languages');

        if (isset($code, $languages)) {
            $info = $languages[$code];

            $this->code = $code;

            $this->info   = $info['info'];
            $this->name   = $info['name'];
            $this->locale = $info['locale'];
        } else {
            $code = 'en';
        }

        //
        $pathName = Inflector::classify($domain);

        $langPath = '';

        if ($pathName == 'System') {
            $langPath = SYSTEMDIR;
        } else if ($pathName == 'App') {
            $langPath = APPDIR;
        } else if (is_dir(APPDIR .'Modules' .DS .$pathName)) {
            $langPath = APPDIR .'Modules/' .$pathName;
        } else if (is_dir(APPDIR .'Templates' .DS .$pathName)) {
            $langPath = APPDIR .'Templates/' .$pathName;
        }

        if (empty($langPath)) {
            return;
        }

        $filePath = str_replace('/', DS, $langPath .'/Language/' .ucfirst($code) .'/messages.php');

        // Check if the language file is readable.
        if (! is_readable($filePath)) {
            return;
        }

        // Get the domain messages from the language file.
        $messages = include($filePath);

        // Final Consistency check.
        if (is_array($messages) && ! empty($messages)) {
            $this->messages = $messages;
        }
    }

    /**
     * Get instance of language with domain and code (optional).
     * @param string $domain Optional custom domain
     * @param string $code Optional custom language code.
     * @return Language
     */
    public static function &getInstance($domain = 'app', $code = LANGUAGE_CODE)
    {
        $code = self::getCurrentLanguage($code);

        // The ID code is something like: 'en/system', 'en/app', 'en/file_manager' or 'en/template/admin'
        $id = $code.'/'.$domain;

        // Initialize the domain instance, if not already exists.
        if (! isset(self::$instances[$id])) {
            self::$instances[$id] = new self($domain, $code);
        }

        return self::$instances[$id];
    }


    public static function init()
    {
        $languages = Config::get('languages');

        if (Session::exists('language')) {
            // The Language was already set; nothing to do.
            return;
        } else if(Cookie::exists(PREFIX .'language')) {
            $cookie = Cookie::get(PREFIX .'language');

            if (preg_match ('/[a-z]/', $cookie) && in_array($cookie, array_keys($languages))) {
                Session::set('language', ucfirst($cookie));
            }
        }
    }

    /**
     * Translate a message with optional formatting
     * @param string $message Original message.
     * @param array $params Optional params for formatting.
     * @return string
     */
    public function translate($message, $params = array())
    {
        // Update the current message with the domain translation, if we have one.
        if (isset($this->messages[$message]) && ! empty($this->messages[$message])) {
            $message = $this->messages[$message];
        }

        if (empty($params)) {
            return $message;
        }

        // Standard Message formatting, using the standard PHP Intl and its MessageFormatter.
        // The message string should be formatted using the standard ICU commands.
        return MessageFormatter::formatMessage($this->locale, $message, $params);

        // The VSPRINTF alternative for Message formatting, for those die-hard against ICU.
        // The message string should be formatted using the standard PRINTF commands.
        //return vsprintf($message, $arguments);
    }

    protected static function getCurrentLanguage($code)
    {
        if ($code != LANGUAGE_CODE) {
            // User defined Language Code; nothing to do.
        } else if (Session::exists('language')) {
            return Session::get('language');
        }

        return ucfirst($code);
    }

    // Public Getters

    /**
     * Get current code
     * @return string
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * Get current info
     * @return string
     */
    public function info()
    {
        return $this->info;
    }

    /**
     * Get current name
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get current locale
     * @return string
     */
    public function locale()
    {
        return $this->locale;
    }

    /**
     * Get all messages
     * @return array
     */
    public function messages()
    {
        return $this->messages;
    }

    //--------------------------------------------------------------------
    // Legacy API Methods
    //--------------------------------------------------------------------

    /**
     * Load language function.
     *
     * @param string $name
     * @param string $code
     */
    public function load($name, $code = LANGUAGE_CODE)
    {
        $code = self::getCurrentLanguage($code);

        // Language file.
        $file = APPDIR."Language/$code/$name.php";

        // Check if it is readable.
        if (is_readable($file)) {
            // Require the file.
            $this->legacyMessages[$code] = include $file;
        } else {
            // Display an error.
            echo Error::display("Could not load the language file: '$code/$name.php'");
            die;
        }
    }

    /**
     * Retrieve an element from the language array by its key.
     *
     * @param  string $value
     *
     * @return string
     */
    public function get($value, $code = LANGUAGE_CODE)
    {
        $code = self::getCurrentLanguage($code);

        if (!empty($this->legacyMessages[$code][$value])) {
            return $this->legacyMessages[$code][$value];
        } elseif(!empty($this->legacyMessages[LANGUAGE_CODE][$value])) {
            return $this->legacyMessages[LANGUAGE_CODE][$value];
        } else {
            return $value;
        }
    }

    /**
     * Get the language for the views.
     *
     * @param  string $value this is a "word" value from the language file
     * @param  string $name  name of the file with the language
     * @param  string $code  optional, language code
     *
     * @return string
     */
    public static function show($value, $name, $code = LANGUAGE_CODE)
    {
        $code = self::getCurrentLanguage($code);

        // Language file.
        $file = APPDIR."Language/$code/$name.php";

        // Check if it is readable.
        if (is_readable($file)) {
            // Require the file.
            $messages = include($file);
        } else {
            // Display an error.
            echo Error::display("Could not load the language file: '$code/$name.php'");
            die;
        }

        if (!empty($messages[$value])) {
            return $messages[$value];
        } else {
            return $value;
        }
    }
}
