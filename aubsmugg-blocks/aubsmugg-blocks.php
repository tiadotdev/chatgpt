<?php
/**
 * Plugin Name:       Custom Blocks
 * Description:       A collection of Gutenberg blocks produced by Aubs & Mugg.
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Version:           1.0
 * Author:            Aubs & Mugg
 * Author URI:        https://aubsandmugg.com/contact
 * Text Domain:       aubsmugg
 *
 * @package           aubsmugg-blocks
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Define plugin constants
define('AUBSMUGG_BLOCKS_VERSION', '0.1.0');
define('AUBSMUGG_BLOCKS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AUBSMUGG_BLOCKS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AUBSMUGG_BLOCKS_PLUGIN_FILE', __FILE__);

// make sure AubsMuggBlocks class is not already defined
if (!class_exists('AubsMuggBlocks')) {

    // Create class to require the functions file for each block
    class AubsMuggBlocks {
        public function __construct() {
            $this->require_folders();
            $this->require_files();
        }

        // get names of each folder in the src directory
        public function get_folders() {

            $folders = array_diff(scandir(AUBSMUGG_BLOCKS_PLUGIN_DIR . 'src/blocks'), array('..', '.'));

            // ignore all hidden files/folders
            foreach ($folders as $hidden => $folder) {
                if (substr($folder, 0, 1) === '.') {
                    unset($folders[$hidden]);
                }
            }
            return $folders;
        }

        // require each PHP file in each folder
        public function require_folders() {
            $folders = $this->get_folders();
            foreach ($folders as $folder) {
                require_once AUBSMUGG_BLOCKS_PLUGIN_DIR . 'src/blocks/' . $folder . '/block-' . $folder . '.php';
            }
        }

        // require all files in the inc folder
        public function require_files() {
            $files = glob(AUBSMUGG_BLOCKS_PLUGIN_DIR . 'inc/*.php');
            foreach ($files as $file) {
                require_once $file;
            }
        }
    }

    // Instantiate class
    new AubsMuggBlocks();
    
}