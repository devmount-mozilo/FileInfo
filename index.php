<?php if(!defined('IS_CMS')) die();

/**
 * moziloCMS Plugin: FileInfo
 *
 * Reads special file information like type, size
 * Counts number of downloads for each file
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   DEVMOUNT <mail@devmount.de>
 * @license  GPL v3+
 * @version  GIT: v0.5.2019-10-05
 * @link     https://github.com/mozilo-plugins/FileInfo/wiki/Dokumentation
 * @see      Many are the plans in a person’s heart, but it is the Lord’s purpose
 *           that prevails.
 *            - The Bible
 *
 * Plugin created by DEVMOUNT
 * www.devmount.de
 *
 */

// only allow moziloCMS environment
if (!defined('IS_CMS')) {
    die();
}

// add database class
require_once "database.php";

/**
 * FileInfo Class
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   DEVMOUNT <mail@devmount.de>
 * @license  GPL v3+
 * @link     https://github.com/mozilo-plugins/FileInfo
 */
class FileInfo extends Plugin
{
    // language
    public $admin_lang;
    public $cms_lang;

    // plugin information
    const PLUGIN_AUTHOR  = 'DEVMOUNT';
    const PLUGIN_TITLE   = 'FileInfo';
    const PLUGIN_VERSION = 'v0.5.2019-10-05';
    const MOZILO_VERSION = '2.0';
    const PLUGIN_DOCU
        = 'https://github.com/mozilo-plugins/FileInfo/wiki/Dokumentation';

    private $_plugin_tags = array(
        'tag' => '{FileInfo|<file>|<template>|<linktext>}',
    );

    // set markers
    private $_marker = array('#LINK#','#TYPE#','#SIZE#','#COUNT#','#DATE#');

    const LOGO_URL = 'http://media.devmount.de/logo_pluginconf.png';

    /**
     * set configuration elements, their default values and their configuration
     * parameters
     *
     * @var array $_confdefault
     *      text     => default, type, maxlength, size, regex
     *      textarea => default, type, cols, rows, regex
     *      password => default, type, maxlength, size, regex, saveasmd5
     *      check    => default, type
     *      radio    => default, type, descriptions
     *      select   => default, type, descriptions, multiselect
     */
    private $_confdefault = array(
        'date' => array(
            'd.m.Y',
            'text',
            '100',
            '5',
            '',
        ),
    );

    /**
     * creates plugin content
     *
     * @param string $value Parameter divided by '|'
     *
     * @return string HTML output
     */
    function getContent($value)
    {
        global $CMS_CONF;
        global $syntax;
        global $CatPage;

        if($value == "plugin_first" and getRequestValue('downloadable_file_id', 'post')  and getRequestValue('submit', 'post')) {
            require_once __DIR__ . '/download.php';
            // exit
        }

        $this->cms_lang = new Language(
            $this->PLUGIN_SELF_DIR
            . 'lang/cms_language_'
            . $CMS_CONF->get('cmslanguage')
            . '.txt'
        );

        // get conf and set default
        $conf = array();
        foreach ($this->_confdefault as $elem => $default) {
            $conf[$elem] = ($this->settings->get($elem) == '')
                ? $default[0]
                : $this->settings->get($elem);
        }

        // get params
        list($param_file, $param_template, $param_linktext)
            = array_pad($this->makeUserParaArray($value, false, '|'), 3, '');

        // check if cat:file construct is correct
        if (!strpos($param_file, '%3A')) {
            return $this->throwMessage(
                $this->cms_lang->getLanguageValue(
                    'error_invalid_input',
                    urldecode($param_file)
                ),
                'ERROR'
            );
        }

        // get category and file name
        list($cat, $file) = explode('%3A', $param_file);

        // check if file exists
        if (!$CatPage->exists_File($cat, $file)) {
            return $this->throwMessage(
                $this->cms_lang->getLanguageValue(
                    'error_invalid_file',
                    urldecode($file),
                    urldecode($cat)
                ),
                'ERROR'
            );
        }

        // get file path url
        $url = $CatPage->get_pfadFile($cat, $file);

        // set type contents
        $types = array(
            // #LINK#
            $this->getLink($param_file, $param_linktext),
            // #TYPE#
            $this->getType($file),
            // #SIZE#
            $this->formatFilesize(filesize($url)),
            // #COUNT#
            $this->getCount($this->PLUGIN_SELF_DIR . 'data/' . $param_file),
            // #DATE#
            $this->formatFiledate(filectime($url), $conf['date']),
        );

        // initialize return content, begin plugin content
        $content = '<!-- BEGIN ' . self::PLUGIN_TITLE . ' plugin content --> ';

        // fill template with content
        if ($param_template == '') {
            $param_template = '#LINK#';
        }
        $content .= str_replace($this->_marker, $types, $param_template);

        // end plugin content
        $content .= '<!-- END ' . self::PLUGIN_TITLE . ' plugin content --> ';

        return $content;
    }

    /**
     * sets backend configuration elements and template
     *
     * @return Array configuration
     */
    function getConfig()
    {
        global $CatPage;

        if(IS_ADMIN and $this->settings->get("plugin_first") !== "true") {
            $this->settings->set("plugin_first","true");
        }
        
        $config = array();

        // create button to administration area
        $config['--admin~~'] = array(
            'buttontext' =>
                $this->admin_lang->getLanguageValue('admin_buttontext'),
            'description' =>
                $this->admin_lang->getLanguageValue('admin_buttondescription'),
            'datei_admin' => 'admin.php',
        );

        // read configuration values
        foreach ($this->_confdefault as $key => $value) {
            // handle each form type
            switch ($value[1]) {
            case 'text':
                $config[$key] = $this->confText(
                    $this->admin_lang->getLanguageValue('config_' . $key),
                    $value[2],
                    $value[3],
                    $value[4],
                    $this->admin_lang->getLanguageValue(
                        'config_' . $key . '_error'
                    )
                );
                break;

            case 'textarea':
                $config[$key] = $this->confTextarea(
                    $this->admin_lang->getLanguageValue('config_' . $key),
                    $value[2],
                    $value[3],
                    $value[4],
                    $this->admin_lang->getLanguageValue(
                        'config_' . $key . '_error'
                    )
                );
                break;

            case 'password':
                $config[$key] = $this->confPassword(
                    $this->admin_lang->getLanguageValue('config_' . $key),
                    $value[2],
                    $value[3],
                    $value[4],
                    $this->admin_lang->getLanguageValue(
                        'config_' . $key . '_error'
                    ),
                    $value[5]
                );
                break;

            case 'check':
                $config[$key] = $this->confCheck(
                    $this->admin_lang->getLanguageValue('config_' . $key)
                );
                break;

            case 'radio':
                $descriptions = array();
                foreach ($value[2] as $label) {
                    $descriptions[$label] = $this->admin_lang->getLanguageValue(
                        'config_' . $key . '_' . $label
                    );
                }
                $config[$key] = $this->confRadio(
                    $this->admin_lang->getLanguageValue('config_' . $key),
                    $descriptions
                );
                break;

            case 'select':
                $descriptions = array();
                foreach ($value[2] as $label) {
                    $descriptions[$label] = $this->admin_lang->getLanguageValue(
                        'config_' . $key . '_' . $label
                    );
                }
                $config[$key] = $this->confSelect(
                    $this->admin_lang->getLanguageValue('config_' . $key),
                    $descriptions,
                    $value[3]
                );
                break;

            default:
                break;
            }
        }

        // read admin.css
        $admin_css = '';
        $lines = file('../plugins/FileInfo/admin.css');
        foreach ($lines as $line_num => $line) {
            $admin_css .= trim($line);
        }

        // add template CSS
        $template = '<style>' . $admin_css . '</style>';


        $template .= '
            <div class="index-header">
            <span>'
                . $this->admin_lang->getLanguageValue(
                    'index_header',
                    self::PLUGIN_TITLE
                )
            . '</span>
            <a href="' . self::PLUGIN_DOCU . '" target="_blank">
            <img style="float:right;" src="' . self::LOGO_URL . '" />
            </a>
            </div>
        </li>
        <li class="mo-in-ul-li ui-widget-content admin-li">
            <div class="index-subheader">'
            . $this->admin_lang->getLanguageValue('admin_format')
            . '</div>
            <div style="margin-bottom:5px;">
                {date_text}
                {date_description}
                <span class="admin-default">
                    [' . $this->_confdefault['date'][0] .']
                </span>
        ';

        $config['--template~~'] = $template;

        return $config;
    }

    /**
     * sets default backend configuration elements, if no plugin.conf.php is
     * created yet
     *
     * @return Array configuration
     */
    function getDefaultSettings()
    {
        $config = array('active' => 'true');
        foreach ($this->_confdefault as $elem => $default) {
            $config[$elem] = $default[0];
        }
        return $config;
    }

    /**
     * sets backend plugin information
     *
     * @return Array information
     */
    function getInfo()
    {
        global $ADMIN_CONF;

        $this->admin_lang = new Language(
            $this->PLUGIN_SELF_DIR
            . 'lang/admin_language_'
            . $ADMIN_CONF->get('language')
            . '.txt'
        );

        // build plugin tags
        $tags = array();
        foreach ($this->_plugin_tags as $key => $tag) {
            $tags[$tag] = $this->admin_lang->getLanguageValue('tag_' . $key);
        }

        $info = array(
            '<b>' . self::PLUGIN_TITLE . '</b> ' . self::PLUGIN_VERSION,
            self::MOZILO_VERSION,
            $this->admin_lang->getLanguageValue(
                'description',
                htmlspecialchars($this->_plugin_tags['tag'], ENT_COMPAT, 'UTF-8'),
                implode(', ', $this->_marker)
            ),
            self::PLUGIN_AUTHOR,
            array(
                self::PLUGIN_DOCU,
                self::PLUGIN_TITLE . ' '
                . $this->admin_lang->getLanguageValue('on_devmount')
            ),
            $tags
        );

        return $info;
    }

    /**
     * builds formula with download link
     *
     * @param string $downloadable_file_id  id of downloadable file
     * @param string $linktext optional text for download link
     *
     * @return html formula
     */
    protected function getLink($downloadable_file_id, $linktext = '')
    {
        list($cat, $file) = explode('%3A', $downloadable_file_id);
        $text = ($linktext == '') ? urldecode($file) : $linktext;
        return '<form
                    class="FileInfoDownload"
                    action=""
                    method="post"
                >
                    <input name="downloadable_file_id" type="hidden" value="' . $downloadable_file_id . '" />
                    <input name="submit" type="submit" value="'. $text . '"/>
                </form>';
    }

    /**
     * gets current hit count of given file
     *
     * @param string $catfile name of file
     *
     * @return string number of hits
     */
    protected function getCount($catfile)
    {
        $count = Database::loadArray(
            $catfile . '.php'
        );
        return ($count == '') ? '0' : $count;
    }

    /**
     * gets type extension of given file
     *
     * @param string $file name of file
     *
     * @return html uppercase file type
     */
    protected function getType($file)
    {
        global $CatPage;
        $type
            = '<span style="text-transform:uppercase;">'
            . substr($CatPage->get_FileType($file), 1)
            . '</span>';
        return $type;
    }

    /**
     * throws styled message
     *
     * @param string $type Type of message ('ERROR', 'SUCCESS')
     * @param string $text Content of message
     *
     * @return string HTML content
     */
    protected function throwMessage($text, $type)
    {
        return '<div class="' . self::PLUGIN_TITLE . ucfirst(strtolower($type)) . '">'
            . '<div>' . $this->cms_lang->getLanguageValue(strtolower($type)) . '</div>'
            . '<span>' . $text. '</span>'
            . '</div>';
    }

    /**
     * returns filesize with unit, like 5.32 M
     *
     * @param integer $bytes    number of bytes
     * @param integer $decimals number of decimals
     *
     * @return string formatted filesize
     */
    protected function formatFilesize($bytes, $decimals = 2)
    {
        // $sz = 'BKMGTP';
        $sz = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return
            sprintf("%.{$decimals}f ", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    /**
     * returns formatted filedate
     *
     * @param integer $tstamp timestamp to format
     * @param string  $format optional date format
     *
     * @return string formatted filedate
     */
    protected function formatFiledate($tstamp, $format = 'd.m.Y')
    {
        return date($format, $tstamp);
    }

    /**
     * creates configuration for text fields
     *
     * @param string $description Label
     * @param string $maxlength   Maximum number of characters
     * @param string $size        Size
     * @param string $regex       Regular expression for allowed input
     * @param string $regex_error Wrong input error message
     *
     * @return Array  Configuration
     */
    protected function confText(
        $description,
        $maxlength = '',
        $size = '',
        $regex = '',
        $regex_error = ''
    ) {
        // required properties
        $conftext = array(
            'type' => 'text',
            'description' => $description,
        );
        // optional properties
        if ($maxlength != '') {
            $conftext['maxlength'] = $maxlength;
        }
        if ($size != '') {
            $conftext['size'] = $size;
        }
        if ($regex != '') {
            $conftext['regex'] = $regex;
        }
        if ($regex_error != '') {
            $conftext['regex_error'] = $regex_error;
        }
        return $conftext;
    }

}

?>
