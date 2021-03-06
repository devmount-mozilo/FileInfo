<?php

/**
 * moziloCMS Plugin: FileInfoAdmin
 *
 * Offers a list of all registered files with an overview of information
 * and administration tools like resetting or deleting file infos.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   DEVMOUNT <mail@devmount.de>
 * @license  GPL v3+
 * @link     https://github.com/devmount-mozilo/FileInfo
 *
 * Plugin created by DEVMOUNT
 * www.devmount.de
 *
 */

// only allow moziloCMS administration environment
if (!defined('IS_ADMIN') or !IS_ADMIN) {
    die();
}

/**
 * FileInfoAdmin Class
 *
 * @category PHP
 * @package  PHP_MoziloPlugins
 * @author   DEVMOUNT <mail@devmount.de>
 * @license  GPL v3+
 * @link     https://github.com/devmount-mozilo/FileInfo
 */
class FileInfoAdmin extends FileInfo
{
    // language
    public $admin_lang;
    // plugin settings
    private $_settings;
    // PLUGIN_SELF_DIR from FileInfo
    private $_self_dir;
    // PLUGIN_SELF_URL from FileInfo
    private $_self_url;

    /**
     * constructor
     *
     * @param object $plugin FileInfo plugin object
     */
    function FileInfoAdmin($plugin)
    {
        $this->admin_lang = $plugin->admin_lang;
        $this->_settings = $plugin->settings;
        $this->_self_dir = $plugin->PLUGIN_SELF_DIR;
        $this->_self_url = $plugin->PLUGIN_SELF_URL;
    }

    /**
     * creates plugin administration area content
     *
     * @param array $postresult result of post action
     *
     * @return string HTML output
     */
    function getContentAdmin($postresult)
    {
        global $CatPage;

        // initialize message content
        $msg = '';

        // handle postresult
        if (isset($postresult['reset'])) {
            if ($postresult['reset']) {
                $msg = $this->throwMessage(
                    $this->admin_lang->getLanguageValue('msg_success_reset'),
                    'SUCCESS'
                );
            } else {
                $msg = $this->throwMessage(
                    $this->admin_lang->getLanguageValue('msg_error_reset'),
                    'ERROR'
                );
            }
        }
        if (isset($postresult['delete'])) {
            if ($postresult['delete']) {
                $msg = $this->throwMessage(
                    $this->admin_lang->getLanguageValue('msg_success_delete'),
                    'SUCCESS'
                );
            } else {
                $msg = $this->throwMessage(
                    $this->admin_lang->getLanguageValue('msg_error_delete'),
                    'ERROR'
                );
            }
        }

        // get all registered files
        $catfiles = array_diff(
            scandir($this->_self_dir . 'data', 1),
            array('..', '.')
        );

        // build (category => file1, file2) structure
        $sortedfiles = array();

        foreach ($catfiles as $catfile) {
            list($cat, $file) = explode('%3A', $catfile);
            $sortedfiles[$cat][] = substr($file, 0, -4);
        }

        // read admin.css
        $admin_css = '';
        $lines = file('../plugins/FileInfo/admin.css');
        foreach ($lines as $line_num => $line) {
            $admin_css .= trim($line);
        }

        // add template CSS
        $content = '<style>' . $admin_css . '</style>';

        // add tablesorter js
        $content .= '
            <script
                type="text/javascript"
                src="../plugins/FileInfo/js/jquery.tablesorter.min.js"
            >
            </script>
        ';
        // build Template
        $content .= '
            <div class="admin-header">
            <span>'
                . $this->admin_lang->getLanguageValue(
                    'admin_header',
                    self::PLUGIN_TITLE
                )
            . '</span>
            <a
                class="img-button icon-refresh"
                title="'
                . $this->admin_lang->getLanguageValue('icon_refresh')
                . '" onclick="window.location
                    = (String(window.location).indexOf(\'?\') != -1)
                    ? window.location
                    : String(window.location)
                    + \'?nojs=true&pluginadmin=FileInfo&action=plugins&multi=true\';"
            ></a>
            <a href="' . self::PLUGIN_DOCU . '" target="_blank">
                <img style="float:right;" src="' . self::LOGO_URL . '" />
            </a>
            </div>
        ';

        // add possible message to output content
        if ($msg != '') {
            $content .= '<div class="admin-msg">' . $msg . '</div>';
        }

        // find all categories
        foreach ($sortedfiles as $cat => $files) {
            $id = rand();
            $content .= '
            <script language="Javascript" type="text/javascript">
                $(document).ready(function()
                    {
                        $("#' . $id . '").tablesorter({
                            headers: {
                                0: { sorter: \'text\' },
                                1: { sorter: \'text\' },
                                2: { sorter: \'isoDate\' },
                                3: { sorter: \'digit\' },
                                4: { sorter: \'digit\' },
                                5: { sorter: false }
                            },
                            sortList: [[4,1]]
                        });
                    }
                );
            </script>
            <ul class="fileinfo-ul">
                <li class="mo-in-ul-li ui-widget-content admin-li">
                    <div class="admin-subheader">'
                    . urldecode($cat)
                    . '</div>
                    <table
                        cellspacing="0"
                        cellpadding="4px"
                        id="' . $id . '"
                        class="tablesorter"
                    >
                        <colgroup>
                            <col style="width:*;">
                            <col style="width:60px;">
                            <col style="width:80px;">
                            <col style="width:80px;">
                            <col style="width:80px;">
                            <col style="width:60px;">
                        </colgroup>
                        <thead>
                        <tr>
                            <th>'
                            . $this->admin_lang->getLanguageValue('admin_filename')
                            . '</th>
                            <th>'
                            . $this->admin_lang->getLanguageValue('admin_filetype')
                            . '</th>
                            <th>'
                            . $this->admin_lang->getLanguageValue('admin_filedate')
                            . '</th>
                            <th>'
                            . $this->admin_lang->getLanguageValue('admin_filesize')
                            . '</th>
                            <th>'
                            . $this->admin_lang->getLanguageValue('admin_filecount')
                            . '</th>
                            <th>'
                            . $this->admin_lang->getLanguageValue('admin_action')
                            . '</th>
                        </tr>
                        </thead>
                        <tbody>
                ';

            // find all files in current category
            foreach ($files as $filename) {
                $formid = rand();

                // get filepaths
                $url = $CatPage->get_pfadFile($cat, $filename);
                $src = $CatPage->get_srcFile($cat, $filename);

                // rebuild catfile form
                $catfile = $cat . '%3A' . $filename;

                // calculate percentage of maximum counts
                $count = $this->getCount($this->_self_dir . 'data/' . $catfile);
                $maxcount = $this->getMaxCount();
                $percentcount = ($maxcount == 0)
                    ? 0
                    : round($count/$maxcount*100, 1);

                // calculate percentage of maximum size
                $size = filesize($url);
                $maxsize = $this->getMaxSize();
                $percentsize = ($maxsize == 0)
                    ? 0
                    : round($size/$maxsize*100, 1);

                $content .= '
                    <tr>
                        <td>
                            <a href="' . $src . '" class="admin-link">'
                            . urldecode($filename)
                            . '</a>
                        </td>
                        <td style="text-align:center;padding-right:10px;">'
                            . $this->getType(urldecode($filename))
                        . '</td>
                        <td style="text-align:center;padding-right:10px;">'
                            . $this->formatFiledate(
                                filectime($url),
                                $this->_settings->get('date')
                            )
                        . '</td>
                        <td style="text-align:right;padding-right:10px;">
                            <div style="
                                padding: 1px 4px;
                                background: linear-gradient(
                                    to left,
                                    #abcdef ' . $percentsize . '%,
                                    transparent ' . $percentsize . '%
                                );
                            ">'
                            . $this->formatFilesize($size)
                            . '</div>
                        </td>
                        <td>
                            <div style="
                                padding: 1px 4px;
                                background: linear-gradient(
                                    to right,
                                    #abcdef ' . $percentcount . '%,
                                    transparent ' . $percentcount . '%
                                );
                            ">'
                            . $count
                            . '</div>
                        </td>
                        <td>
                            <form
                                id="' . $formid . 'r"
                                action="' . URL_BASE . ADMIN_DIR_NAME . '/index.php"
                                method="post"
                            >
                                <input type="hidden" name="pluginadmin"
                                    value="' . PLUGINADMIN . '"
                                />
                                <input type="hidden" name="action"
                                    value="' . ACTION . '"
                                />
                                <input type="hidden" name="r"
                                    value="' . $catfile . '"
                                />
                            </form>
                            <a
                                class="img-button icon-reset"
                                title="'
                                . $this->admin_lang->getLanguageValue('icon_reset')
                                . '"
                                onclick="if(confirm(\''
                                . $this->admin_lang->getLanguageValue(
                                    'confirm_reset',
                                    urldecode($filename)
                                )
                                . '\'))
                                document.getElementById(\'' . $formid . 'r\')
                                    .submit()"
                            ></a>
                            <form
                                id="' . $formid . 'd"
                                action="' . URL_BASE . ADMIN_DIR_NAME . '/index.php"
                                method="post"
                            >
                                <input type="hidden" name="pluginadmin"
                                    value="' . PLUGINADMIN . '"
                                />
                                <input type="hidden" name="action"
                                    value="' . ACTION . '"
                                />
                                <input type="hidden" name="d"
                                    value="' . $catfile . '"
                                />
                            </form>
                            <a
                                class="img-button icon-delete"
                                title="'
                                . $this->admin_lang->getLanguageValue('icon_delete')
                                . '"
                                onclick="if(confirm(\''
                                . $this->admin_lang->getLanguageValue(
                                    'confirm_delete',
                                    urldecode($filename)
                                )
                                . '\'))
                                document.getElementById(\'' . $formid . 'd\')
                                    .submit()"
                            ></a>
                        </td>
                    </tr>';
            }
            $content .= '</tbody></table>';
            $content .= '</li></ul>';
        }

        if (count($sortedfiles) == 0) {
            $content .= '
                <ul class="fileinfo-ul">
                    <li class="mo-in-ul-li ui-widget-content admin-li">'
                        . $this->admin_lang->getLanguageValue('admin_nofiles')
                    . '</li>
                </ul>
            ';
        }

        return $content;
    }

    /**
     * finds maximum download number of all files
     *
     * @return int maximum download number
     */
    protected function getMaxCount()
    {
        // get all registered files
        $catfiles = array_diff(
            scandir($this->_self_dir . 'data', 1),
            array('..', '.')
        );

        // initialize counter
        $max = 0;

        // compare current max with each download number
        foreach ($catfiles as $catfile) {
            $count = intval(
                $this->getCount(
                    $this->_self_dir . 'data/' . substr($catfile, 0, -4)
                )
            );
            if ($count > $max) {
                $max = $count;
            }
        }

        return $max;
    }

    /**
     * finds maximum filezise of all files
     *
     * @return int maximum filesize
     */
    protected function getMaxSize()
    {
        global $CatPage;

        // get all registered files
        $catfiles = array_diff(
            scandir($this->_self_dir . 'data', 1),
            array('..', '.')
        );

        // initialize counter
        $max = 0;

        // compare current max with each download number
        foreach ($catfiles as $catfile) {
            list($cat, $file) = explode('%3A', $catfile);
            $filename = substr($file, 0, -4);
            $url = $CatPage->get_pfadFile($cat, $filename);

            $size = intval(filesize($url));
            if ($size > $max) {
                $max = $size;
            }
        }

        return $max;
    }

    /**
     * checks and handles post variables
     *
     * @return boolean success
     */
    function checkPost()
    {
        // initialize return array
        $success = array();

        // handle actions
        $reset = getRequestValue('r', "post", false);
        $delete = getRequestValue('d', "post", false);
        if ($reset != '') {
            $catfile = $reset;
            $success['reset'] = $this->resetCount($catfile);
        }
        if ($delete != '') {
            $catfile = $delete;
            $success['delete'] = $this->deleteCount($catfile);
        }

        return $success;
    }

    /**
     * resets the download counts of given file to 0
     *
     * @param string $catfile file to reset count
     *
     * @return boolean success
     */
    protected function resetCount($catfile)
    {
        return Database::saveArray(
            $this->_self_dir . 'data/' . $catfile . '.php',
            '0'
        );
    }

    /**
     * deletes the db file of given file
     *
     * @param string $catfile file to delete db file
     *
     * @return boolean success
     */
    protected function deleteCount($catfile)
    {
        return Database::deleteFile($this->_self_dir . 'data/' . $catfile . '.php');
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
            . '<div>' . $this->admin_lang->getLanguageValue(strtolower($type)) . '</div>'
            . '<span>' . $text. '</span>'
            . '</div>';
    }
}

// instantiate FileInfoAdmin class
$FileInfoAdmin = new FileInfoAdmin($plugin);

// handle post input
$postresult = $FileInfoAdmin->checkPost();

// return admin content
return $FileInfoAdmin->getContentAdmin($postresult);


?>