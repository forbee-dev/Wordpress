<?php

class HCMS_Transients 
{
    public static function get_transient($key, $args, $prefix = 'HCMS_') {
        $args = json_encode(array( 'args' =>  $args ));
        return get_transient($prefix . $key . $args) ?: null;
    }

    public static function set_transient($key, $args, $value, $expiration = 60 * 60 * 24, $prefix = 'HCMS_') {
        $args = json_encode(array( 'args' =>  $args ));
        return set_transient($prefix . $key . $args, $value, $expiration);
    }

    public static function delete_transient($key, $prefix = 'HCMS_') {
        return delete_transient($prefix . $key);
    }

    public static function delete_all_transients($prefix = 'HCMS_') {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_$prefix%'");
    }

    public static function get_all_transients($prefix = 'HCMS_') {
        global $wpdb;
        $transients = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '_transient_$prefix%'");

        return $transients;
    }
}