<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin-Transformation
 * @version $Id: application_octetstream__download.inc.php 11973 2008-11-24 09:30:37Z nijel $
 */

/**
 *
 */
function PMA_transformation_application_octetstream__download(&$buffer, $options = array(), $meta = '') {
    global $row, $fields_meta;

    if (isset($options[0]) && !empty($options[0])) {
        $cn = $options[0]; // filename
    } else {
        if (isset($options[1]) && !empty($options[1])) {
            foreach($fields_meta as $key => $val) {
                if ($val->name == $options[1]) {
                    $pos = $key;
                    break;
                }
            }
            if (isset($pos)) {
                $cn = $row[$pos];
            }
        }
        if (empty($cn)) {
            $cn = 'binary_file.dat';
        }
    }

    return
      sprintf(
        '<a href="transformation_wrapper.php%s&amp;ct=application/octet-stream&amp;cn=%s" title="%s">%s</a>',

        $options['wrapper_link'],
        urlencode($cn),
        htmlspecialchars($cn),
        htmlspecialchars($cn)
      );
}

?>
