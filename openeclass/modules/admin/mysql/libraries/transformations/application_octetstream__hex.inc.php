<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package phpMyAdmin-Transformation
 * @version $Id: application_octetstream__hex.inc.php 11973 2008-11-24 09:30:37Z nijel $
 */

/**
 *
 */
function PMA_transformation_application_octetstream__hex($buffer, $options = array(), $meta = '') {
    // possibly use a global transform and feed it with special options:
    // include './libraries/transformations/global.inc.php';
    if (!isset($options[0])) {
        $options[0] = 2;
    } else {
        $options[0] = (int)$options[0];
    }

    if ($options[0] < 1) {
        return bin2hex($buffer);
    } else {
        return chunk_split(bin2hex($buffer), $options[0], ' ');
    }

}

?>
